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
 * Backfill recruitment pipeline so every employee with a phone has a matching
 * candidate marked as hired (RecruitmentProcess status Zatrudniony + employee_id).
 *
 * Preview / apply mirror the MBS import UX: inspect first, then commit.
 */
class EmployeeCandidateHireSyncService
{
    public const STATUS_NO_PHONE = 'no_phone';

    public const STATUS_HIRED = 'hired';

    public const STATUS_UNHIRED = 'unhired';

    public const STATUS_MISSING = 'missing';

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
        $candidates = RecruitmentCandidate::query()
            ->whereNotNull('phone')
            ->with(['processes:id,candidate_id,status,employee_id'])
            ->get()
            ->keyBy('phone');

        return Employee::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (Employee $employee) => $this->buildRow($employee, $candidates));
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
    private function buildRow(Employee $employee, Collection $candidatesByPhone): array
    {
        $phone = PhoneNormalizer::normalize($employee->phone);

        if ($phone === null) {
            return $this->rowPayload($employee, null, null, self::STATUS_NO_PHONE, false);
        }

        /** @var RecruitmentCandidate|null $candidate */
        $candidate = $candidatesByPhone->get($phone);

        if (! $candidate) {
            return $this->rowPayload($employee, $phone, null, self::STATUS_MISSING, true);
        }

        $status = $this->resolveMatchStatus($employee, $candidate);

        return $this->rowPayload(
            $employee,
            $phone,
            $candidate,
            $status,
            $status === self::STATUS_UNHIRED || $status === self::STATUS_MISSING,
        );
    }

    private function resolveMatchStatus(Employee $employee, RecruitmentCandidate $candidate): string
    {
        $processes = $candidate->relationLoaded('processes')
            ? $candidate->processes
            : $candidate->processes()->get(['id', 'candidate_id', 'status', 'employee_id']);

        if ($processes->contains(fn (RecruitmentProcess $p) => (int) $p->employee_id === (int) $employee->id)) {
            return self::STATUS_HIRED;
        }

        if ($processes->contains(fn (RecruitmentProcess $p) => $p->status === RecruitmentStatus::Zatrudniony)) {
            return self::STATUS_HIRED;
        }

        return self::STATUS_UNHIRED;
    }

    /**
     * @return 'created'|'marked'|'skipped'
     */
    private function applyForEmployee(Employee $employee): string
    {
        $phone = PhoneNormalizer::normalize($employee->phone);
        if ($phone === null) {
            return 'skipped';
        }

        $candidate = RecruitmentCandidate::where('phone', $phone)
            ->with(['processes:id,candidate_id,status,employee_id'])
            ->first();

        if ($candidate) {
            $status = $this->resolveMatchStatus($employee, $candidate);
            if ($status === self::STATUS_HIRED) {
                return 'skipped';
            }

            $this->createHiredProcess($candidate, $employee);

            return 'marked';
        }

        $candidate = RecruitmentCandidate::create([
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email ?: null,
            'phone' => $employee->phone,
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
