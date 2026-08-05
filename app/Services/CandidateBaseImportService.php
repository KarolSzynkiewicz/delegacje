<?php

namespace App\Services;

use App\Enums\RecruitmentCandidateFlag;
use App\Enums\RecruitmentContactOutcome;
use App\Enums\RecruitmentReferralSource;
use App\Enums\RecruitmentRejectionReason;
use App\Enums\RecruitmentStatus;
use App\Models\Employee;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentLead;
use App\Models\RecruitmentProcess;
use App\Models\Role;
use App\Support\PhoneNormalizer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Imports a full candidate profile (Candidate + Lead + Process + contact history)
 * from a fixed-schema CSV — unlike MbsLeadImportService, which only ever creates a
 * bare Lead+Process. Built for the one-time "Baza Kandydatów Zespolona" historical
 * migration but kept generic/reusable for future bulk profile imports (the CSV is
 * meant to be produced by a throwaway merge script per-import, not hand-written).
 *
 * Expected CSV columns (exact header names):
 *   first_name, last_name, phone, email, city, has_driving_license_b, roles_raw,
 *   expected_rate_eur, available_from_raw, legacy_status, referral_source,
 *   referral_source_detail, lead_created_at, contact_date, notes
 *
 * `legacy_status` is expected to already be the single, resolved status label from
 * the source system (e.g. spreadsheet row color merged with any text status column)
 * — one of the labels in STATUS_LEGEND, or empty. This service does not need to
 * know about spreadsheet colors; that resolution happens upstream in the merge step.
 *
 * Import rules
 * ─────────────
 * • Existing candidate (matched by normalised phone) → ENRICH only null/false
 *   fields, never overwrite. Roles are merged additively.
 * • A Lead+Process is only created if the candidate has no existing process whose
 *   lead was created on the same calendar day as `lead_created_at` — otherwise the
 *   existing process is reused and enriched. This avoids duplicating leads already
 *   brought in by the daily MBS import, and keeps re-running this import idempotent.
 * • `legacy_status` drives RecruitmentStatus / RecruitmentCandidateFlag / rejection
 *   reason via STATUS_LEGEND. "Aktualny pracownik" additionally tries to link the
 *   candidate to an existing Employee by phone (mirrors EmployeeCandidateHireSyncService);
 *   without a phone match it is never guessed — flagged as a warning instead.
 */
class CandidateBaseImportService
{
    /** @var array<int, string> */
    public const EXPECTED_HEADERS = [
        'first_name', 'last_name', 'phone', 'email', 'city', 'has_driving_license_b',
        'roles_raw', 'expected_rate_eur', 'available_from_raw', 'legacy_status',
        'referral_source', 'referral_source_detail', 'lead_created_at', 'contact_date', 'notes',
    ];

    /** Free-text specialty tokens (lowercased, trimmed) → exact `roles.name` value. */
    private const ROLE_SYNONYMS = [
        'piaskarz' => 'Piaskarz',
        'piaksarz' => 'Piaskarz',
        'szlifierz' => 'Szlifierz',
        'szlifowanie' => 'Szlifierz',
        'malarz natryskowy' => 'Malarz natryskowy',
        'malarz' => 'Malarz natryskowy',
        'asystent' => 'Asystent jachtowy',
        'asystent jachtowy' => 'Asystent jachtowy',
        'pomocnik malarza' => 'Pomocnik malarza',
        'pomoc malarza' => 'Pomocnik malarza',
        'pomocnik' => 'Pomocnik malarza',
        'lakiernik jachtowy' => 'Lakiernik jachtowy',
        'lakiernik' => 'Lakiernik jachtowy',
        'lifts operator - bez certyfikacji' => 'Lifts operator - Bez certyfikacji',
        'lifts operator' => 'Lifts operator',
        'uhp 2000-3000 bar' => 'Uhp 2000-3000 BAR',
        'uhp' => 'Uhp 2000-3000 BAR',
    ];

    /**
     * legacy_status → status/rating/rejection mapping. `special` values need a DB
     * lookup and are resolved in resolveStatusMapping() instead of read directly.
     */
    private const STATUS_LEGEND = [
        'Wartościowy kandydat' => [
            'status' => RecruitmentStatus::WTrakcieKontaktu,
            'rating' => RecruitmentCandidateFlag::Wartosciowy,
        ],
        'Kandydat' => [
            'status' => RecruitmentStatus::WTrakcieKontaktu,
        ],
        'Nie udało się skontaktować' => [
            'status' => RecruitmentStatus::Odrzucony,
            'rejection_reason' => RecruitmentRejectionReason::Inne,
            'rejection_note' => 'Nie udało się skontaktować (import historyczny)',
            'outcome' => RecruitmentContactOutcome::BrakOdpowiedzi,
        ],
        'Nie zainteresowany' => [
            'status' => RecruitmentStatus::Odrzucony,
            'rejection_reason' => RecruitmentRejectionReason::Inne,
        ],
        'Czarna lista' => [
            'status' => RecruitmentStatus::Odrzucony,
            'rating' => RecruitmentCandidateFlag::CzarnaLista,
            'rejection_reason' => RecruitmentRejectionReason::Inne,
        ],
        'Rezerwa' => [
            'status' => RecruitmentStatus::Zaakceptowany,
            'extra_note' => 'Rezerwa (import historyczny)',
        ],
        'Aktualny pracownik' => [
            'special' => 'aktualny_pracownik',
        ],
    ];

    /**
     * Rows processed per HTTP request / DB transaction.
     * Large enough to keep round-trips low; still chunked so a full CSV cannot
     * hold one multi-minute transaction / proxy request.
     */
    public const IMPORT_CHUNK_SIZE = 500;

    /** Memoized map of normalised employee phone → Employee, built once per request. */
    private ?Collection $employeesByPhone = null;

    /** @var Collection<string, RecruitmentCandidate>|null phone → candidate */
    private ?Collection $candidatesByPhone = null;

    /** @var Collection<int, Collection<int, RecruitmentProcess>>|null candidate_id → processes (with lead) */
    private ?Collection $processesByCandidateId = null;

    /** @var array<string, int>|null role name → id */
    private ?array $roleIdsByName = null;

    /**
     * Parse CSV into structured rows. No DB access — safe to call for validation.
     *
     * @return array{rows: Collection<int, array<string, mixed>>, errors: array<int, string>}
     */
    public function parseOnly(string $csvContent): array
    {
        // Streamed via fgetcsv (RFC4180-aware) rather than naive explode("\n") — the
        // `notes` column legitimately contains embedded newlines inside a quoted
        // field (multi-line contact history), which a plain line-split would corrupt.
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $this->toUtf8($csvContent));
        rewind($stream);

        $header = fgetcsv($stream);
        if ($header === false) {
            fclose($stream);

            throw new RuntimeException('Plik CSV jest pusty lub nie zawiera wierszy danych.');
        }
        $header = array_map(fn ($h) => trim((string) $h, " \t\n\r\0\x0B\xEF\xBB\xBF\""), $header);

        $missing = array_diff(self::EXPECTED_HEADERS, $header);
        if (! empty($missing)) {
            fclose($stream);

            throw new RuntimeException('Plik CSV nie ma oczekiwanych kolumn: '.implode(', ', $missing));
        }

        $rows = collect();
        $lineNumber = 1;

        while (($cells = fgetcsv($stream)) !== false) {
            $lineNumber++;

            // fgetcsv() returns [null] for a genuinely blank line.
            if (count($cells) === 1 && trim((string) ($cells[0] ?? '')) === '') {
                continue;
            }

            $raw = [];
            foreach ($header as $i => $key) {
                $raw[$key] = trim((string) ($cells[$i] ?? ''));
            }

            $rows->push($this->buildRow($raw, $lineNumber));
        }
        fclose($stream);

        if ($rows->isEmpty()) {
            throw new RuntimeException('Plik CSV jest pusty lub nie zawiera wierszy danych.');
        }

        return ['rows' => $rows, 'errors' => []];
    }

    /**
     * Enrich parsed rows with DB-derived preview info (candidate/process reuse
     * decisions, role resolution warnings, employee match for "Aktualny pracownik").
     * No writes happen here.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    public function preview(Collection $rows): Collection
    {
        $this->warmSharedCaches();
        $this->warmRowCaches($rows);

        return $rows->map(function (array $row) {
            if (! $row['phone']) {
                return $row + ['candidate_action' => 'skip', 'warnings' => array_merge($row['warnings'], ['Brak numeru telefonu'])];
            }

            $candidate = $this->candidatesByPhone->get($row['phone']);
            $row['candidate_action'] = $candidate ? 'enrich' : 'create';
            $row['candidate_id'] = $candidate?->id;

            $statusInfo = $this->resolveStatusMapping($row, $candidate);
            $row['resolved_status'] = $statusInfo['status']->value;
            $row['resolved_status_label'] = $statusInfo['status']->label();
            $row['warnings'] = array_merge($row['warnings'], $statusInfo['warnings']);

            $row['process_action'] = 'create';
            if ($candidate) {
                $existing = $this->findReusableProcess($candidate, $this->parseDate($row['lead_created_at']));
                if ($existing) {
                    $row['process_action'] = 'reuse';
                    $row['process_id'] = $existing->id;
                }
            }

            return $row;
        });
    }

    /**
     * Persist all rows, committing in chunks so a multi-thousand-row run does not
     * hold one giant transaction open for minutes.
     *
     * For HTTP (Livewire) prefer {@see importChunk()} driven across requests —
     * this full-file path is mainly for tests / artisan.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{created: int, enriched: int, skipped: int, warnings: array<int, string>}
     */
    public function import(Collection $rows): array
    {
        $totals = ['created' => 0, 'enriched' => 0, 'skipped' => 0, 'warnings' => []];

        $this->warmSharedCaches();

        foreach ($rows->chunk(self::IMPORT_CHUNK_SIZE) as $chunk) {
            $stats = $this->importChunk($chunk, warmShared: false);
            $totals['created'] += $stats['created'];
            $totals['enriched'] += $stats['enriched'];
            $totals['skipped'] += $stats['skipped'];
            $totals['warnings'] = array_merge($totals['warnings'], $stats['warnings']);
        }

        return $totals;
    }

    /**
     * Persist a single chunk inside one transaction, with prefetch for that chunk.
     * Intended to be called repeatedly from Livewire (one HTTP request ≈ one chunk).
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{created: int, enriched: int, skipped: int, warnings: array<int, string>}
     */
    public function importChunk(Collection $rows, bool $warmShared = true): array
    {
        if ($warmShared) {
            $this->warmSharedCaches();
        }

        $created = 0;
        $enriched = 0;
        $skipped = 0;
        $warnings = [];

        DB::transaction(function () use ($rows, &$created, &$enriched, &$skipped, &$warnings) {
            $this->warmRowCaches($rows);

            foreach ($rows as $row) {
                if (! $row['phone']) {
                    $skipped++;

                    continue;
                }

                ['action' => $action, 'warnings' => $rowWarnings] = $this->importRow($row);

                match ($action) {
                    'created' => $created++,
                    'enriched' => $enriched++,
                    default => $skipped++,
                };

                foreach ($rowWarnings as $w) {
                    $warnings[] = "[{$row['first_name']} {$row['last_name']} / {$row['phone']}] {$w}";
                }
            }
        });

        return compact('created', 'enriched', 'skipped', 'warnings');
    }

    /** Load roles + employees once — reused across preview / many import chunks. */
    private function warmSharedCaches(): void
    {
        if ($this->roleIdsByName === null) {
            $this->roleIdsByName = Role::query()->pluck('id', 'name')->all();
        }

        if ($this->employeesByPhone === null) {
            $this->employeesByPhone = Employee::query()
                ->whereNotNull('phone')
                ->get()
                ->keyBy(fn (Employee $e) => PhoneNormalizer::normalize($e->phone));
        }
    }

    /**
     * Prefetch candidates + processes (+ roles / contact attempts / comments) for
     * the phones in $rows — keeps per-row work to writes, not N×SELECT.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function warmRowCaches(Collection $rows): void
    {
        $phones = $rows->pluck('phone')->filter()->unique()->values();

        if ($phones->isEmpty()) {
            $this->candidatesByPhone = collect();
            $this->processesByCandidateId = collect();

            return;
        }

        $this->candidatesByPhone = RecruitmentCandidate::query()
            ->whereIn('phone', $phones)
            ->with('roles')
            ->get()
            ->keyBy('phone');

        $candidateIds = $this->candidatesByPhone->pluck('id');

        $this->processesByCandidateId = $candidateIds->isEmpty()
            ? collect()
            : RecruitmentProcess::query()
                ->whereIn('candidate_id', $candidateIds)
                ->with(['lead', 'contactAttempts', 'comments'])
                ->latest('id')
                ->get()
                ->groupBy('candidate_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Row parsing
    // ─────────────────────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function buildRow(array $raw, int $lineNumber): array
    {
        $phone = PhoneNormalizer::normalize($raw['phone'] ?? null);
        $roles = $this->matchRoles($raw['roles_raw'] ?? null);
        [$expectedRateEur, $rateWarning] = $this->parseExpectedRate($raw['expected_rate_eur'] ?? null);

        $warnings = ! empty($roles['unmatched'])
            ? ['Nierozpoznane specjalności: '.implode(', ', $roles['unmatched'])]
            : [];
        if ($rateWarning !== null) {
            $warnings[] = $rateWarning;
        }

        return [
            'line' => $lineNumber,
            'first_name' => $raw['first_name'] ?? '',
            'last_name' => $raw['last_name'] ?? '',
            'phone_raw' => $raw['phone'] ?? '',
            'phone' => $phone,
            'email' => ($raw['email'] ?? '') !== '' ? mb_strtolower($raw['email']) : null,
            'city' => ($raw['city'] ?? '') !== '' ? $raw['city'] : null,
            'has_driving_license_b' => $this->parseBool($raw['has_driving_license_b'] ?? null),
            'matched_role_names' => $roles['matched'],
            'unmatched_specialties' => $roles['unmatched'],
            'expected_rate_eur' => $expectedRateEur,
            // Kept as raw strings (not Carbon/enum instances) throughout parseOnly/preview —
            // parsed lazily only where a DB write/comparison needs it. Matches
            // MbsLeadImportService's convention and keeps rows safe to store in a
            // plain Livewire public property (Carbon/enum objects would need extra
            // synth handling there).
            'available_from_raw' => ($raw['available_from_raw'] ?? '') !== '' ? $raw['available_from_raw'] : null,
            'legacy_status' => trim($raw['legacy_status'] ?? ''),
            'referral_source' => ($raw['referral_source'] ?? '') !== '' ? $raw['referral_source'] : null,
            'referral_source_detail' => ($raw['referral_source_detail'] ?? '') !== '' ? $raw['referral_source_detail'] : null,
            'lead_created_at' => ($raw['lead_created_at'] ?? '') !== '' ? $raw['lead_created_at'] : null,
            'contact_date' => ($raw['contact_date'] ?? '') !== '' ? $raw['contact_date'] : null,
            'notes' => ($raw['notes'] ?? '') !== '' ? $raw['notes'] : null,
            'warnings' => $warnings,
        ];
    }

    /** @return array{matched: array<int, string>, unmatched: array<int, string>} */
    private function matchRoles(?string $rolesRaw): array
    {
        if (! $rolesRaw) {
            return ['matched' => [], 'unmatched' => []];
        }

        $normalized = mb_strtolower(trim($rolesRaw));
        $segments = preg_split('/[\/,;]+/u', $normalized) ?: [];

        $matched = [];
        $unmatched = [];

        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            if (isset(self::ROLE_SYNONYMS[$segment])) {
                $matched[] = self::ROLE_SYNONYMS[$segment];

                continue;
            }

            // Space-separated combo of otherwise-unmarked single roles, e.g. "malarz piaskarz".
            $subtokens = preg_split('/\s+/u', $segment) ?: [];
            $localMatches = [];
            $allMatched = ! empty($subtokens);
            foreach ($subtokens as $token) {
                if (isset(self::ROLE_SYNONYMS[$token])) {
                    $localMatches[] = self::ROLE_SYNONYMS[$token];
                } else {
                    $allMatched = false;
                }
            }

            if ($allMatched && ! empty($localMatches)) {
                $matched = array_merge($matched, $localMatches);
            } else {
                $unmatched[] = $segment;
            }
        }

        return ['matched' => array_values(array_unique($matched)), 'unmatched' => array_values(array_unique($unmatched))];
    }

    private function parseBool(?string $value): bool
    {
        return in_array(mb_strtolower(trim((string) $value)), ['tak', '1', 'true', 'yes'], true);
    }

    private function parseFloat(?string $value): ?float
    {
        $value = trim((string) $value);

        return $value !== '' && is_numeric($value) ? (float) $value : null;
    }

    /**
     * expected_rate_eur is a decimal(6,2) column (max 9999.99). Source data is a
     * free-form import — e.g. a handful of rows had an Excel date accidentally
     * typed into the rate column — so an out-of-range value is dropped (with a
     * warning surfaced in the preview) instead of throwing and aborting the
     * whole import transaction.
     *
     * @return array{0: ?float, 1: ?string}
     */
    private function parseExpectedRate(?string $value): array
    {
        $rate = $this->parseFloat($value);

        if ($rate === null) {
            return [null, null];
        }

        if ($rate < 0 || $rate > 9999.99) {
            return [null, "Odczytana stawka ({$rate}) jest poza sensownym zakresem — zignorowano, zweryfikuj ręcznie."];
        }

        return [$rate, null];
    }

    private function parseDate(?string $value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception) {
            return null;
        }
    }

    private function resolveAvailableFrom(?string $raw): ?Carbon
    {
        $value = mb_strtolower(trim((string) $raw));
        if ($value === '') {
            return null;
        }

        if (str_contains($value, 'zaraz')) {
            return now()->startOfDay();
        }

        return $this->parseDate($raw);
    }

    private function resolveReferralSource(?string $raw): ?RecruitmentReferralSource
    {
        return $raw ? RecruitmentReferralSource::tryFrom($raw) : null;
    }

    private function toUtf8(string $content): string
    {
        if (! mb_check_encoding($content, 'UTF-8')) {
            $converted = mb_convert_encoding($content, 'UTF-8', 'CP1250');

            return $converted ?: $content;
        }

        return $content;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Status / rating / rejection resolution
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @return array{
     *   status: RecruitmentStatus,
     *   rating: ?RecruitmentCandidateFlag,
     *   rejection_reason: ?RecruitmentRejectionReason,
     *   rejection_note: ?string,
     *   outcome: RecruitmentContactOutcome,
     *   extra_note: ?string,
     *   employee: ?Employee,
     *   warnings: array<int, string>,
     * }
     */
    private function resolveStatusMapping(array $row, ?RecruitmentCandidate $candidate): array
    {
        $legend = self::STATUS_LEGEND[$row['legacy_status']] ?? null;
        $warnings = [];
        $employee = null;

        if (($legend['special'] ?? null) === 'aktualny_pracownik') {
            $employee = $this->findEmployeeByPhone($row['phone']);
            $processComment = null;

            if ($employee) {
                if ($candidate && $candidate->employee_id && $candidate->employee_id !== $employee->id) {
                    $warnings[] = 'Oznaczony jako Aktualny pracownik, ale kandydat jest już połączony z innym pracownikiem — nie nadpisano.';
                    $employee = null;
                    $status = RecruitmentStatus::WTrakcieKontaktu;
                } else {
                    $status = RecruitmentStatus::Zatrudniony;
                }
            } else {
                // No phone match against current Employees — can't safely guess who this
                // is, so it's routed to Weryfikacja (manual check) instead of Zatrudniony,
                // with the reason left as a visible comment rather than only a fleeting
                // import warning.
                $status = RecruitmentStatus::Zaakceptowany;
                $processComment = 'Oznaczony jako Aktualny pracownik w bazie historycznej — brak dopasowania po telefonie, zweryfikuj ręcznie.';
                $warnings[] = $processComment;
            }

            return [
                'status' => $status,
                'rating' => null,
                'rejection_reason' => null,
                'rejection_note' => null,
                'outcome' => RecruitmentContactOutcome::Odebrano,
                'extra_note' => null,
                'process_comment' => $processComment,
                'employee' => $employee,
                'warnings' => $warnings,
            ];
        }

        $status = $legend['status'] ?? RecruitmentStatus::Nowy;

        return [
            'status' => $status,
            'rating' => $legend['rating'] ?? null,
            'rejection_reason' => $legend['rejection_reason'] ?? null,
            // Falls back to the row's own notes when the legend doesn't dictate a
            // fixed rejection note (e.g. plain "Nie zainteresowany") — used by both
            // the create-new-process and reuse-existing-process paths.
            'rejection_note' => $status === RecruitmentStatus::Odrzucony
                ? ($legend['rejection_note'] ?? $row['notes'])
                : null,
            'outcome' => $legend['outcome'] ?? RecruitmentContactOutcome::Odebrano,
            'extra_note' => $legend['extra_note'] ?? null,
            'process_comment' => null,
            'employee' => null,
            'warnings' => $warnings,
        ];
    }

    private function findEmployeeByPhone(string $normalizedPhone): ?Employee
    {
        if ($this->employeesByPhone === null) {
            $this->employeesByPhone = Employee::query()
                ->whereNotNull('phone')
                ->get()
                ->keyBy(fn (Employee $e) => PhoneNormalizer::normalize($e->phone));
        }

        return $this->employeesByPhone->get($normalizedPhone);
    }

    /**
     * A candidate's existing process is reused (enriched in place, no new Lead)
     * when its lead was created on the same calendar day as this row's lead date
     * — this is what makes re-enriching bare, already-imported MBS leads work,
     * and keeps re-running this import idempotent instead of duplicating leads.
     *
     * When the row carries no usable date (rare — neither MBS nor a sane Excel
     * contact date survived the merge step), fall back to reusing the candidate's
     * only process, but only if it still looks completely untouched (status Nowy,
     * no admin notes yet) — otherwise we can't safely tell two distinct historical
     * leads apart and a new process is created instead.
     */
    private function findReusableProcess(RecruitmentCandidate $candidate, ?Carbon $leadCreatedAt): ?RecruitmentProcess
    {
        $processes = $this->processesForCandidate($candidate);

        if ($leadCreatedAt) {
            $date = $leadCreatedAt->toDateString();

            return $processes->first(function (RecruitmentProcess $process) use ($date) {
                $lead = $process->relationLoaded('lead') ? $process->lead : $process->lead()->first();

                return $lead && $lead->created_at?->toDateString() === $date;
            });
        }

        if ($processes->count() === 1) {
            $only = $processes->first();
            if ($only->status === RecruitmentStatus::Nowy && ! $only->admin_notes) {
                return $only;
            }
        }

        return null;
    }

    /** @return Collection<int, RecruitmentProcess> */
    private function processesForCandidate(RecruitmentCandidate $candidate): Collection
    {
        if ($this->processesByCandidateId !== null) {
            return $this->processesByCandidateId->get($candidate->id, collect());
        }

        return RecruitmentProcess::where('candidate_id', $candidate->id)
            ->with('lead')
            ->latest('id')
            ->get();
    }

    private function rememberCandidate(RecruitmentCandidate $candidate): void
    {
        if ($this->candidatesByPhone === null) {
            $this->candidatesByPhone = collect();
        }

        $this->candidatesByPhone->put($candidate->phone, $candidate);
    }

    private function rememberProcess(RecruitmentProcess $process): void
    {
        if ($this->processesByCandidateId === null) {
            $this->processesByCandidateId = collect();
        }

        $bucket = $this->processesByCandidateId->get($process->candidate_id, collect());
        $this->processesByCandidateId->put(
            $process->candidate_id,
            $bucket->prepend($process)->unique('id')->values()
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Persistence
    // ─────────────────────────────────────────────────────────────────────────

    /** @return array{action: 'created'|'enriched'|'skipped', warnings: array<int, string>} */
    private function importRow(array $row): array
    {
        $candidate = $this->candidatesByPhone?->get($row['phone']);
        $isNew = $candidate === null;

        if (! $candidate) {
            $candidate = RecruitmentCandidate::create([
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'],
                'phone' => $row['phone_raw'],
                'city' => $row['city'],
                'has_driving_license_b' => $row['has_driving_license_b'],
                'expected_rate_eur' => $row['expected_rate_eur'],
                'available_from' => $this->resolveAvailableFrom($row['available_from_raw']),
            ]);
            $candidate->setRelation('roles', collect());
            $this->rememberCandidate($candidate);
        } else {
            $this->enrichCandidate($candidate, $row);
        }

        $this->syncMatchedRoles($candidate, $row['matched_role_names'] ?? []);

        $statusInfo = $this->resolveStatusMapping($row, $candidate);

        $postCreateUpdates = [];
        if ($statusInfo['rating'] && ! $candidate->rating) {
            $postCreateUpdates['rating'] = $statusInfo['rating']->value;
        }
        if ($statusInfo['employee'] && ! $candidate->employee_id) {
            $postCreateUpdates['employee_id'] = $statusInfo['employee']->id;
        }
        if (! empty($postCreateUpdates)) {
            $candidate->update($postCreateUpdates);
        }

        $process = $this->resolveOrCreateProcess($candidate, $row, $statusInfo);

        $this->recordContactAttempt($process, $row, $statusInfo);

        if ($statusInfo['process_comment']) {
            $alreadyCommented = $process->relationLoaded('comments')
                ? $process->comments->contains(fn ($c) => $c->body === $statusInfo['process_comment'])
                : $process->comments()->where('body', $statusInfo['process_comment'])->exists();

            if (! $alreadyCommented) {
                $comment = $process->addComment($statusInfo['process_comment'], auth()->user());
                if ($process->relationLoaded('comments')) {
                    $process->setRelation('comments', $process->comments->prepend($comment));
                }
            }
        }

        return ['action' => $isNew ? 'created' : 'enriched', 'warnings' => $statusInfo['warnings']];
    }

    /** @param  array<int, string>  $matchedRoleNames */
    private function syncMatchedRoles(RecruitmentCandidate $candidate, array $matchedRoleNames): void
    {
        if ($matchedRoleNames === []) {
            return;
        }

        $roleIds = collect($matchedRoleNames)
            ->map(fn (string $name) => $this->roleIdsByName[$name] ?? null)
            ->filter()
            ->values();

        if ($roleIds->isEmpty()) {
            return;
        }

        $existingIds = $candidate->relationLoaded('roles')
            ? $candidate->roles->pluck('id')
            : $candidate->roles()->pluck('roles.id');

        $toAttach = $roleIds->diff($existingIds)->values();
        if ($toAttach->isEmpty()) {
            return;
        }

        $now = now();
        $candidate->roles()->attach(
            $toAttach->mapWithKeys(fn ($id) => [$id => ['created_at' => $now, 'updated_at' => $now]])->all()
        );

        if ($candidate->relationLoaded('roles')) {
            $stubs = $toAttach->map(function ($id) {
                $role = new Role(['id' => $id]);
                $role->exists = true;

                return $role;
            });
            $candidate->setRelation('roles', $candidate->roles->concat($stubs)->unique('id')->values());
        }
    }

    private function enrichCandidate(RecruitmentCandidate $candidate, array $row): void
    {
        $updates = [];

        foreach (['email', 'city', 'expected_rate_eur'] as $field) {
            if ($candidate->{$field} === null && $row[$field] !== null) {
                $updates[$field] = $row[$field];
            }
        }

        if ($candidate->available_from === null) {
            $availableFrom = $this->resolveAvailableFrom($row['available_from_raw']);
            if ($availableFrom !== null) {
                $updates['available_from'] = $availableFrom;
            }
        }

        // Booleans default to false (not nullable) — only ever upgrade false → true.
        if ($row['has_driving_license_b'] && ! $candidate->has_driving_license_b) {
            $updates['has_driving_license_b'] = true;
        }

        if (! empty($updates)) {
            $candidate->update($updates);
        }
    }

    private function resolveOrCreateProcess(RecruitmentCandidate $candidate, array $row, array $statusInfo): RecruitmentProcess
    {
        $leadCreatedAt = $this->parseDate($row['lead_created_at']);

        $existing = $this->findReusableProcess($candidate, $leadCreatedAt);
        if ($existing) {
            $this->applyStatus($existing, $statusInfo);

            return $existing;
        }

        $lead = new RecruitmentLead([
            'candidate_id' => $candidate->id,
            'referral_source' => $this->resolveReferralSource($row['referral_source']) ?? RecruitmentReferralSource::HistoricalImport,
            'referral_source_detail' => $row['referral_source_detail'],
        ]);
        if ($leadCreatedAt) {
            $lead->created_at = $leadCreatedAt;
        }
        $lead->save();

        $attributes = [
            'lead_id' => $lead->id,
            'candidate_id' => $candidate->id,
            'status' => $statusInfo['status'],
            'employee_id' => $statusInfo['employee']?->id,
        ];

        if ($statusInfo['status'] === RecruitmentStatus::Odrzucony) {
            $attributes['rejection_reason'] = $statusInfo['rejection_reason'];
            $attributes['rejection_reason_note'] = $statusInfo['rejection_note'];
        }

        $process = RecruitmentProcess::create($attributes);
        $process->setRelation('lead', $lead);
        $process->setRelation('contactAttempts', collect());
        $process->setRelation('comments', collect());

        $this->rememberProcess($process);

        return $process;
    }

    private function applyStatus(RecruitmentProcess $process, array $statusInfo): void
    {
        if ($statusInfo['employee'] && ! $process->employee_id) {
            $process->update(['employee_id' => $statusInfo['employee']->id]);
        }

        // Never move a process backwards or clobber a status a recruiter has since
        // progressed manually — only apply when it's still at the default "Nowy".
        // Goes through transitionTo() so the change lands in recruitment_status_history
        // like every other status change in the app.
        if ($process->status === RecruitmentStatus::Nowy && $statusInfo['status'] !== RecruitmentStatus::Nowy) {
            $process->transitionTo(
                $statusInfo['status'],
                auth()->id(),
                $statusInfo['rejection_reason'],
                $statusInfo['rejection_note'],
            );
        }
    }

    private function recordContactAttempt(RecruitmentProcess $process, array $row, array $statusInfo): void
    {
        $comment = trim(implode("\n\n", array_filter([$row['notes'], $statusInfo['extra_note']])));
        $comment = $comment !== '' ? $comment : null;
        $attemptDate = $this->parseDate($row['contact_date']) ?? $this->parseDate($row['lead_created_at']);
        $attemptDateString = $attemptDate?->toDateString();

        // Prefer in-memory attempts (prefetched / previously created in this chunk)
        // so re-imports and same-chunk duplicates do not hit exists() per row.
        if ($process->relationLoaded('contactAttempts')) {
            $duplicate = $process->contactAttempts->contains(function ($attempt) use ($comment, $attemptDateString) {
                if ($attempt->comment !== $comment) {
                    return false;
                }

                return $attemptDateString === null
                    || $attempt->created_at?->toDateString() === $attemptDateString;
            });

            if ($duplicate) {
                return;
            }
        } else {
            $duplicateQuery = $process->contactAttempts()->where('comment', $comment);
            if ($attemptDate) {
                $duplicateQuery->whereDate('created_at', $attemptDate->toDateString());
            }
            if ($duplicateQuery->exists()) {
                return;
            }
        }

        $attempt = $process->contactAttempts()->make([
            'user_id' => auth()->id(),
            'outcome' => $statusInfo['outcome'],
            'comment' => $comment,
        ]);
        if ($attemptDate) {
            $attempt->created_at = $attemptDate;
        }
        $attempt->save();

        if ($process->relationLoaded('contactAttempts')) {
            $process->setRelation('contactAttempts', $process->contactAttempts->prepend($attempt));
        }

        if ($comment !== null && ! $process->admin_notes) {
            $process->update(['admin_notes' => $comment]);
        }
    }
}
