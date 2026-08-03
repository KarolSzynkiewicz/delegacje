<?php

namespace App\Services;

use App\Enums\RecruitmentReferralSource;
use App\Enums\RecruitmentStatus;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentLead;
use App\Models\RecruitmentProcess;
use App\Support\PhoneNormalizer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Imports leads exported from Meta Business Suite (Contact Center) into the
 * recruitment pipeline.
 *
 * CSV column headers are matched case-insensitively and work with both the
 * Polish-language and English-language MBS exports. The only required columns
 * are a name (full or separate first/last) and a phone number.
 *
 * Import rules
 * ─────────────
 * • Existing candidate (matched by normalised phone) → new Lead + new Process.
 *   Candidate master data is NOT updated.
 * • Unknown phone → new Candidate + Lead + Process.
 * • Referral source is always set to MetaBusinessSuite.
 */
class MbsLeadImportService
{
    /**
     * Parse CSV without touching the DB. Returns rows with normalised phone but
     * `exists` always false. Use this when you only need structural parsing.
     *
     * @return array{
     *   rows: Collection<int, array{first_name: string, last_name: string, phone_raw: string, phone: string|null, email: string|null, created_at: string|null, exists: false}>,
     *   detected_headers: array<string, string|null>,
     * }
     */
    public function parseOnly(string $csvContent): array
    {
        ['rows' => $rawRows, 'detected_headers' => $detected] = $this->parseCsv($this->toUtf8($csvContent));

        $rows = collect($rawRows)->map(function (array $row) {
            return array_merge($row, [
                'phone'     => PhoneNormalizer::normalize($row['phone_raw']),
                'exists'    => false,
                'duplicate' => false,
            ]);
        });

        return ['rows' => $rows, 'detected_headers' => $detected];
    }

    /**
     * Parse and check existing candidates in DB.  No DB writes happen here.
     *
     * Row flags:
     *  - exists:    phone already belongs to a known candidate
     *  - duplicate: exists=true AND a lead for that candidate on the same date
     *               was already imported → will be skipped during import
     *
     * @return array{
     *   rows: Collection<int, array{first_name: string, last_name: string, phone_raw: string, phone: string|null, email: string|null, created_at: string|null, exists: bool, duplicate: bool}>,
     *   detected_headers: array<string, string|null>,
     * }
     */
    public function preview(string $csvContent): array
    {
        ['rows' => $rawRows, 'detected_headers' => $detected] = $this->parseCsv($this->toUtf8($csvContent));

        $rows = collect($rawRows)->map(function (array $row) {
            $phone     = PhoneNormalizer::normalize($row['phone_raw']);
            $candidate = $phone ? RecruitmentCandidate::where('phone', $phone)->first() : null;
            $exists    = $candidate !== null;
            $duplicate = false;

            if ($exists && $row['created_at']) {
                $parsedDate = $this->parseDate($row['created_at']);
                if ($parsedDate) {
                    $duplicate = RecruitmentLead::where('candidate_id', $candidate->id)
                        ->whereDate('created_at', $parsedDate->toDateString())
                        ->exists();
                }
            }

            return array_merge($row, [
                'phone'     => $phone,
                'exists'    => $exists,
                'duplicate' => $duplicate,
            ]);
        });

        return ['rows' => $rows, 'detected_headers' => $detected];
    }

    /**
     * Import rows into the DB. Rows without a phone or flagged as duplicates are skipped.
     *
     * @param  Collection<int, array{first_name: string, last_name: string, phone_raw: string, phone: string|null, email: string|null, created_at: string|null, exists: bool, duplicate: bool}>  $rows
     * @return array{imported: int, skipped: int, duplicates: int}
     */
    public function import(Collection $rows): array
    {
        $imported   = 0;
        $skipped    = 0;
        $duplicates = 0;

        DB::transaction(function () use ($rows, &$imported, &$skipped, &$duplicates) {
            foreach ($rows as $row) {
                if (! $row['phone']) {
                    $skipped++;
                    continue;
                }

                if ($row['duplicate'] ?? false) {
                    $duplicates++;
                    continue;
                }

                $this->importRow($row);
                $imported++;
            }
        });

        return ['imported' => $imported, 'skipped' => $skipped, 'duplicates' => $duplicates];
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function importRow(array $row): void
    {
        $candidate = RecruitmentCandidate::where('phone', $row['phone'])->first();

        if (! $candidate) {
            $candidate = RecruitmentCandidate::create([
                'first_name' => $row['first_name'],
                'last_name'  => $row['last_name'],
                'email'      => $row['email'] ?: null,
                'phone'      => $row['phone_raw'],   // mutator normalises it again
            ]);
        }

        $leadCreatedAt = $row['created_at'] ? $this->parseDate($row['created_at']) : null;

        $lead = RecruitmentLead::create([
            'candidate_id'    => $candidate->id,
            'referral_source' => RecruitmentReferralSource::MetaBusinessSuite,
        ]);

        // Manually set created_at if we have the original timestamp from MBS
        if ($leadCreatedAt) {
            $lead->created_at = $leadCreatedAt;
            $lead->save();
        }

        RecruitmentProcess::create([
            'lead_id'      => $lead->id,
            'candidate_id' => $candidate->id,
            'status'       => RecruitmentStatus::Nowy,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CSV parsing
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Parse CSV content into structured rows plus a map of which column name was
     * matched for each logical field (for diagnostic display in the UI).
     *
     * @return array{
     *   rows: array<int, array{first_name: string, last_name: string, phone_raw: string, email: string|null, created_at: string|null}>,
     *   detected_headers: array<string, string|null>,
     * }
     */
    private function parseCsv(string $content): array
    {
        // Detect delimiter (comma or semicolon)
        $firstLine = strtok($content, "\n");
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $lines = array_filter(explode("\n", str_replace(["\r\n", "\r"], "\n", $content)));
        $lines = array_values($lines);

        if (count($lines) < 2) {
            throw new RuntimeException('Plik CSV jest pusty lub nie zawiera wierszy danych.');
        }

        // Parse header — strip BOM + quotes + whitespace
        $rawHeaderLine = array_shift($lines);
        $headersRaw    = str_getcsv($rawHeaderLine, $delimiter);
        $headers       = array_map(
            fn ($h) => mb_strtolower(trim($h, " \t\n\r\0\x0B\xEF\xBB\xBF\"")),
            $headersRaw,
        );
        // Also keep original (untrimmed, but BOM-stripped) for display
        $headersDisplay = array_map(
            fn ($h) => trim($h, " \t\n\r\0\x0B\xEF\xBB\xBF\""),
            $headersRaw,
        );

        // Full name first — "Imię i nazwisko" must not also match as first+last via partial "imię"/"nazwisko"
        $idxFullName  = $this->findHeaderIndex($headers, [
            'full name', 'imię i nazwisko', 'imie i nazwisko',
            'pełne imię i nazwisko', 'pelne imie i nazwisko', 'full_name',
        ]);
        $idxFirstName = $this->findHeaderIndex($headers, ['first name', 'imię', 'imie', 'firstname', 'first_name', 'name_first']);
        $idxLastName  = $this->findHeaderIndex($headers, ['last name', 'nazwisko', 'lastname', 'last_name', 'name_last']);

        // Prefer explicit first+last over a single full-name column when both are present —
        // but only when they resolve to *different* columns. Same index means partial match
        // hit one combined header (e.g. "Imię i nazwisko") for both needles.
        if ($idxFirstName !== null && $idxLastName !== null && $idxFirstName !== $idxLastName) {
            $idxFullName = null;
        } elseif ($idxFirstName !== null && $idxFirstName === $idxLastName) {
            $idxFullName  = $idxFullName ?? $idxFirstName;
            $idxFirstName = null;
            $idxLastName  = null;
        }
        $idxPhone      = $this->findHeaderIndex($headers, [
            'phone number', 'numer telefonu', 'phone', 'telefon',
            'numer_telefonu', 'phone_number', 'numer tel', 'nr telefonu',
            'nr tel', 'mobile', 'mobile number', 'numer komórkowy',
        ]);
        $idxEmail      = $this->findHeaderIndex($headers, [
            'email', 'adres e-mail', 'adres email', 'e-mail', 'mail', 'e_mail',
        ]);
        $idxCreatedAt  = $this->findHeaderIndex($headers, [
            // English MBS
            'created time', 'created at', 'created_at', 'date created',
            'submission time', 'submitted at', 'timestamp', 'date', 'time',
            // Polish MBS — all known variants
            'utworzono',
            'data i godzina', 'data i czas', 'czas i data', 'data utworzenia',
            'data wypełnienia', 'data przesłania', 'czas wypełnienia',
            'czas przesłania', 'data zgłoszenia', 'data dodania',
            'data', 'godzina',
        ]);

        if ($idxFullName === null && ($idxFirstName === null || $idxLastName === null)) {
            $found = implode(', ', array_map(fn ($h) => '"'.$h.'"', $headersDisplay));
            throw new RuntimeException(
                'Nie znaleziono kolumny z imieniem i nazwiskiem. '.
                'Oczekiwana kolumna: "Full name", "Imię i nazwisko", lub osobno "Imię" + "Nazwisko". '.
                'Znalezione kolumny: '.$found
            );
        }

        if ($idxPhone === null) {
            $found = implode(', ', array_map(fn ($h) => '"'.$h.'"', $headersDisplay));
            throw new RuntimeException(
                'Nie znaleziono kolumny z numerem telefonu. '.
                'Oczekiwana kolumna: "Phone number", "Numer telefonu" lub "Phone". '.
                'Znalezione kolumny: '.$found
            );
        }

        // Build diagnostic map: field → actual header name that was matched (or null)
        $detected = [
            'name'       => $idxFullName !== null ? $headersDisplay[$idxFullName]
                : ($idxFirstName !== null ? $headersDisplay[$idxFirstName].' + '.$headersDisplay[$idxLastName] : null),
            'phone'      => $idxPhone !== null ? $headersDisplay[$idxPhone] : null,
            'email'      => $idxEmail !== null ? $headersDisplay[$idxEmail] : null,
            'created_at' => $idxCreatedAt !== null ? $headersDisplay[$idxCreatedAt] : null,
        ];

        $rows = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $cells = str_getcsv($line, $delimiter);
            $cells = array_map('trim', $cells);

            $phoneRaw = $cells[$idxPhone] ?? '';

            if ($idxFullName !== null) {
                $firstName = '';
                $lastName  = '';
                $fullName  = $this->normalizeSpaces($cells[$idxFullName] ?? '');
                $parts     = preg_split('/\s+/u', trim($fullName), 2);
                $firstName = $parts[0] ?? '';
                $lastName  = $parts[1] ?? '';
            } else {
                $firstName = $this->normalizeSpaces($cells[$idxFirstName] ?? '');
                $lastName  = $this->normalizeSpaces($cells[$idxLastName]  ?? '');
                // If only one name column and it contains a space, try to split it.
                if ($lastName === '' && str_contains(trim($firstName), ' ')) {
                    $parts     = preg_split('/\s+/u', trim($firstName), 2);
                    $firstName = $parts[0] ?? '';
                    $lastName  = $parts[1] ?? '';
                }
            }

            $rows[] = [
                'first_name' => trim($firstName),
                'last_name'  => trim($lastName),
                'phone_raw'  => $phoneRaw,
                'email'      => $idxEmail !== null ? ($cells[$idxEmail] ?? null) : null,
                'created_at' => $idxCreatedAt !== null ? ($cells[$idxCreatedAt] ?? null) : null,
            ];
        }

        return ['rows' => $rows, 'detected_headers' => $detected];
    }

    /** Find the first header that matches one of the given needles (case-insensitive). */
    private function findHeaderIndex(array $headers, array $needles): ?int
    {
        foreach ($needles as $needle) {
            $idx = array_search(mb_strtolower($needle), $headers, true);
            if ($idx !== false) {
                return (int) $idx;
            }
        }

        // Partial match fallback
        foreach ($headers as $i => $header) {
            foreach ($needles as $needle) {
                if (str_contains($header, mb_strtolower($needle))) {
                    return $i;
                }
            }
        }

        return null;
    }

    /** Try to parse an MBS date string into a Carbon instance. */
    private function parseDate(string $raw): ?Carbon
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        // Strip trailing timezone abbreviation (e.g. " UTC", " CET", " CEST")
        $clean = preg_replace('/\s+[A-Z]{2,5}$/', '', $raw);

        $formats = [
            // MBS US format: 07/31/2026 5:54am / 07/31/2026 11:30pm
            'm/d/Y g:ia',
            'm/d/Y g:iA',
            'm/d/Y h:ia',
            'm/d/Y h:iA',
            'm/d/Y H:i:s',
            'm/d/Y H:i',
            // ISO / MBS international
            'Y/m/d H:i:s',
            'Y/m/d H:i',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            // Polish formats
            'd.m.Y H:i:s',
            'd.m.Y H:i',
            'd.m.Y',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $clean);
            } catch (\Exception) {
                // try next
            }
        }

        return null;
    }

    /** Replace non-breaking spaces and other Unicode whitespace variants with a regular space. */
    private function normalizeSpaces(string $value): string
    {
        // U+00A0 non-breaking space (common in MBS exports)
        // U+202F narrow no-break space
        // U+2009 thin space
        return trim(preg_replace('/[\x{00A0}\x{202F}\x{2009}\x{200B}]+/u', ' ', $value) ?? $value);
    }

    /** Convert CP1250-encoded content to UTF-8 if needed. */
    private function toUtf8(string $content): string
    {
        if (! mb_check_encoding($content, 'UTF-8')) {
            $converted = mb_convert_encoding($content, 'UTF-8', 'CP1250');

            return $converted ?: $content;
        }

        return $content;
    }
}
