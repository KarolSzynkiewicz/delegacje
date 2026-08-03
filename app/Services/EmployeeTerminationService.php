<?php

namespace App\Services;

use App\Enums\EmployeeTerminationReason;
use App\Enums\RecruitmentReferralSource;
use App\Enums\RecruitmentStatus;
use App\Models\Employee;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentLead;
use App\Models\RecruitmentProcess;
use Illuminate\Support\Facades\DB;

/**
 * Terminating an employee is a lifecycle change on Employee, not a rewrite of
 * recruitment history. It never touches existing leads/processes; it only adds
 * a new audit process (BylyPracownik), mirroring how the hire-sync backfill adds
 * an audit process instead of mutating history.
 */
class EmployeeTerminationService
{
    public function terminate(Employee $employee, EmployeeTerminationReason $reason, ?string $note = null): void
    {
        DB::transaction(function () use ($employee, $reason, $note) {
            $employee->update([
                'terminated_at' => now(),
                'termination_reason' => $reason,
                'termination_note' => $note,
            ]);

            $candidate = RecruitmentCandidate::query()->where('employee_id', $employee->id)->first();
            if (! $candidate) {
                // No linked candidate identity yet — nothing to annotate. The
                // employee-candidate hire sync (system-actions) backfills this FK.
                return;
            }

            $lead = RecruitmentLead::create([
                'candidate_id' => $candidate->id,
                'referral_source' => RecruitmentReferralSource::SystemBackfill,
                'referral_source_detail' => 'Zwolnienie pracownika (akcje systemowe)',
            ]);

            RecruitmentProcess::create([
                'lead_id' => $lead->id,
                'candidate_id' => $candidate->id,
                'status' => RecruitmentStatus::BylyPracownik,
                'employee_id' => $employee->id,
            ]);
        });
    }

    /**
     * Undo a termination. Does not touch recruitment history — the audit trail
     * created by terminate() stays as-is; this only restores the employee's
     * active status.
     */
    public function reinstate(Employee $employee): void
    {
        $employee->update([
            'terminated_at' => null,
            'termination_reason' => null,
            'termination_note' => null,
        ]);
    }
}
