<?php

namespace App\Services;

use App\Enums\EmployeeTerminationReason;
use App\Enums\RecruitmentReferralSource;
use App\Enums\RecruitmentStatus;
use App\Models\Employee;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentLead;
use App\Models\RecruitmentProcess;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\DB;

/**
 * Employee lifecycle events that touch the recruitment domain:
 * hire outside the recruitment pipeline, terminate, reinstate.
 * History is append-only — existing leads/processes are never rewritten.
 */
class EmployeeLifecycleService
{
    /**
     * After creating an employee via /employees/create: ensure a candidate
     * identity exists, link employee_id, and append an audit lead + process
     * (status Zatrudniony) with referral_source EmployeeLifecycle.
     */
    public function recordHireOutsideProcess(Employee $employee): void
    {
        DB::transaction(function () use ($employee) {
            if (RecruitmentCandidate::query()->where('employee_id', $employee->id)->exists()) {
                return;
            }

            $candidate = $this->resolveOrCreateCandidateForHire($employee);

            $roleIds = $employee->roles()->pluck('roles.id');
            if ($roleIds->isNotEmpty()) {
                $candidate->roles()->syncWithoutDetaching($roleIds);
            }

            $hiredAt = $employee->created_at ?? now();

            $lead = RecruitmentLead::create([
                'candidate_id' => $candidate->id,
                'referral_source' => RecruitmentReferralSource::EmployeeLifecycle,
                'referral_source_detail' => 'Zatrudnienie poza procesem – '.$hiredAt->format('d.m.Y'),
            ]);

            RecruitmentProcess::create([
                'lead_id' => $lead->id,
                'candidate_id' => $candidate->id,
                'status' => RecruitmentStatus::Zatrudniony,
                'employee_id' => $employee->id,
            ]);
        });
    }

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

            $terminatedAt = $employee->terminated_at ?? now();

            $lead = RecruitmentLead::create([
                'candidate_id' => $candidate->id,
                'referral_source' => RecruitmentReferralSource::EmployeeLifecycle,
                'referral_source_detail' => 'Zwolnienie pracownika – '.$terminatedAt->format('d.m.Y'),
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
     * Undo a termination. Existing audit history (including the BylyPracownik
     * process from terminate()) stays as-is; we only clear employee termination
     * fields and append a new Zatrudniony audit lead+process.
     */
    public function reinstate(Employee $employee): void
    {
        DB::transaction(function () use ($employee) {
            $employee->update([
                'terminated_at' => null,
                'termination_reason' => null,
                'termination_note' => null,
            ]);

            $candidate = RecruitmentCandidate::query()->where('employee_id', $employee->id)->first();
            if (! $candidate) {
                return;
            }

            $reinstatedAt = now();

            $lead = RecruitmentLead::create([
                'candidate_id' => $candidate->id,
                'referral_source' => RecruitmentReferralSource::EmployeeLifecycle,
                'referral_source_detail' => 'Przywrócenie pracownika – '.$reinstatedAt->format('d.m.Y'),
            ]);

            RecruitmentProcess::create([
                'lead_id' => $lead->id,
                'candidate_id' => $candidate->id,
                'status' => RecruitmentStatus::Zatrudniony,
                'employee_id' => $employee->id,
            ]);
        });
    }

    /**
     * Prefer an existing unlinked candidate matched by phone; otherwise create
     * a new candidate identity. On phone conflict (already linked to another
     * employee), create a new candidate without that phone so we never steal
     * or duplicate a linked identity.
     */
    private function resolveOrCreateCandidateForHire(Employee $employee): RecruitmentCandidate
    {
        $phone = PhoneNormalizer::normalize($employee->phone);

        if ($phone !== null) {
            $byPhone = RecruitmentCandidate::query()->where('phone', $phone)->first();

            if ($byPhone && $byPhone->employee_id === null) {
                $byPhone->update(['employee_id' => $employee->id]);

                return $byPhone->fresh();
            }

            if ($byPhone && $byPhone->employee_id !== null) {
                // Conflict — create a separate identity without reusing the phone.
                return RecruitmentCandidate::create([
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'email' => $employee->email ?: null,
                    'phone' => null,
                    'employee_id' => $employee->id,
                ]);
            }
        }

        return RecruitmentCandidate::create([
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email ?: null,
            'phone' => $employee->phone,
            'employee_id' => $employee->id,
        ]);
    }
}
