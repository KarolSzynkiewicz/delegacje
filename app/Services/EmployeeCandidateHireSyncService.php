<?php

namespace App\Services;

use App\Enums\RecruitmentReferralSource;
use App\Enums\RecruitmentStatus;
use App\Models\Employee;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentLead;
use App\Models\RecruitmentProcess;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Backfill `recruitment_candidates.employee_id` — the FK that is the source of
 * truth for "which candidate identity is this employee" — for every employee
 * with a phone number. Also creates an audit Lead + RecruitmentProcess (status
 * Zatrudniony) for traceability, mirroring the MBS import UX: inspect first,
 * then commit. RecruitmentProcess history itself is never mutated.
 */
class EmployeeCandidateHireSyncService
{
    public const STATUS_NO_PHONE = 'no_phone';

    /** Already linked via employee_id — nothing to do. */
    public const STATUS_HIRED = 'hired';

    /** Candidate found by phone but not linked to any employee — actionable. */
    public const STATUS_UNHIRED = 'unhired';

    /** No candidate found by phone or link — actionable (creates one). */
    public const STATUS_MISSING = 'missing';

    /** Candidate found by phone but already linked to a DIFFERENT employee — needs manual review. */
    public const STATUS_CONFLICT = 'conflict';

    /**
     * Build a preview row per employee. No DB writes.
     *
     * @return Collection<int, array{
     *   employee_id: int,
     *   first_name: string,
     *   last_name: string,
     *   phone_raw: string|null,
     *   phone: string|null,
     *   email: string|null,
     *   candidate_id: int|null,
     *   candidate_name: string|null,
     *   status: string,
     *   actionable: bool,
     * }>
     */
    public function preview(): Collection
    {
        $candidatesByPhone = RecruitmentCandidate::query()
            ->whereNotNull('phone')
            ->get()
            ->keyBy('phone');

        $candidatesByEmployeeId = RecruitmentCandidate::query()
            ->whereNotNull('employee_id')
            ->get()
            ->keyBy('employee_id');

        return Employee::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (Employee $employee) => $this->buildRow($employee, $candidatesByPhone, $candidatesByEmployeeId));
    }

    /**
     * Apply actionable preview rows. Re-checks DB state for idempotency.
     *
     * @param  Collection<int, array{employee_id: int, status: string, actionable?: bool}>  $rows
     * @return array{created: int, marked: int, skipped: int}
     */
    public function apply(Collection $rows): array
    {
        $created = 0;
        $marked = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, &$created, &$marked, &$skipped) {
            foreach ($rows as $row) {
                if (! ($row['actionable'] ?? false)) {
                    $skipped++;

                    continue;
                }

                $employee = Employee::find($row['employee_id'] ?? null);
                if (! $employee) {
                    $skipped++;

                    continue;
                }

                $result = $this->applyForEmployee($employee);

                match ($result) {
                    'created' => $created++,
                    'marked' => $marked++,
                    default => $skipped++,
                };
            }
        });

        return compact('created', 'marked', 'skipped');
    }

    /**
     * @param  Collection<string, RecruitmentCandidate>  $candidatesByPhone
     * @param  Collection<int, RecruitmentCandidate>  $candidatesByEmployeeId
     * @return array{
     *   employee_id: int,
     *   first_name: string,
     *   last_name: string,
     *   phone_raw: string|null,
     *   phone: string|null,
     *   email: string|null,
     *   candidate_id: int|null,
     *   candidate_name: string|null,
     *   status: string,
     *   actionable: bool,
     * }
     */
    private function buildRow(Employee $employee, Collection $candidatesByPhone, Collection $candidatesByEmployeeId): array
    {
        $phone = PhoneNormalizer::normalize($employee->phone);

        /** @var RecruitmentCandidate|null $linked */
        $linked = $candidatesByEmployeeId->get($employee->id);
        if ($linked) {
            return $this->rowPayload($employee, $phone, $linked, self::STATUS_HIRED, false);
        }

        if ($phone === null) {
            return $this->rowPayload($employee, null, null, self::STATUS_NO_PHONE, false);
        }

        /** @var RecruitmentCandidate|null $candidate */
        $candidate = $candidatesByPhone->get($phone);

        if (! $candidate) {
            return $this->rowPayload($employee, $phone, null, self::STATUS_MISSING, true);
        }

        if ($candidate->employee_id !== null) {
            // Phone matches this employee, but the candidate row is already linked
            // to a different employee — do not silently repoint the FK.
            return $this->rowPayload($employee, $phone, $candidate, self::STATUS_CONFLICT, false);
        }

        return $this->rowPayload($employee, $phone, $candidate, self::STATUS_UNHIRED, true);
    }

    /**
     * @return 'created'|'marked'|'skipped'
     */
    private function applyForEmployee(Employee $employee): string
    {
        if (RecruitmentCandidate::where('employee_id', $employee->id)->exists()) {
            return 'skipped';
        }

        $phone = PhoneNormalizer::normalize($employee->phone);
        if ($phone === null) {
            return 'skipped';
        }

        $candidate = RecruitmentCandidate::where('phone', $phone)->first();

        if ($candidate) {
            if ($candidate->employee_id !== null) {
                return 'skipped'; // conflict — needs manual resolution
            }

            $candidate->update(['employee_id' => $employee->id]);
            $this->createHiredProcess($candidate, $employee);

            return 'marked';
        }

        $candidate = RecruitmentCandidate::create([
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email ?: null,
            'phone' => $employee->phone,
            'employee_id' => $employee->id,
        ]);

        $roleIds = $employee->roles()->pluck('roles.id');
        if ($roleIds->isNotEmpty()) {
            $candidate->roles()->sync($roleIds);
        }

        $this->createHiredProcess($candidate, $employee);

        return 'created';
    }

    private function createHiredProcess(RecruitmentCandidate $candidate, Employee $employee): void
    {
        $lead = RecruitmentLead::create([
            'candidate_id' => $candidate->id,
            'referral_source' => RecruitmentReferralSource::SystemBackfill,
            'referral_source_detail' => 'Synchronizacja pracownik → kandydat (akcje systemowe)',
        ]);

        RecruitmentProcess::create([
            'lead_id' => $lead->id,
            'candidate_id' => $candidate->id,
            'status' => RecruitmentStatus::Zatrudniony,
            'employee_id' => $employee->id,
        ]);
    }

    /**
     * @return array{
     *   employee_id: int,
     *   first_name: string,
     *   last_name: string,
     *   phone_raw: string|null,
     *   phone: string|null,
     *   email: string|null,
     *   candidate_id: int|null,
     *   candidate_name: string|null,
     *   status: string,
     *   actionable: bool,
     * }
     */
    private function rowPayload(
        Employee $employee,
        ?string $phone,
        ?RecruitmentCandidate $candidate,
        string $status,
        bool $actionable,
    ): array {
        return [
            'employee_id' => $employee->id,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'phone_raw' => $employee->phone,
            'phone' => $phone,
            'email' => $employee->email,
            'candidate_id' => $candidate?->id,
            'candidate_name' => $candidate?->full_name,
            'status' => $status,
            'actionable' => $actionable,
        ];
    }
}
