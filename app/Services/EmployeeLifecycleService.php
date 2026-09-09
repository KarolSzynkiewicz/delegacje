<?php

namespace App\Services;

use App\Enums\EmployeeLifecycleEventType;
use App\Enums\EmployeeTerminationReason;
use App\Models\Employee;
use App\Models\EmployeeLifecycleEvent;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentProcess;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\DB;

/**
 * Identity employment lifecycle lives on Employee (hired_at / terminated_at
 * plus append-only employee_lifecycle_events). Recruitment stays a hiring
 * pipeline: we may link a candidate identity, but we never append fake
 * hire/terminate processes there.
 */
class EmployeeLifecycleService
{
    /**
     * Record that this person became an employee. Idempotent for the first
     * hire event. Optionally points at the recruitment process that produced
     * the hire (pipeline outcome stays on that process).
     */
    public function recordHire(Employee $employee, ?RecruitmentProcess $process = null): void
    {
        $hiredAt = $employee->hired_at ?? $employee->created_at ?? now();

        if ($employee->hired_at === null) {
            $employee->update(['hired_at' => $hiredAt]);
        }

        $existing = EmployeeLifecycleEvent::query()
            ->where('employee_id', $employee->id)
            ->where('type', EmployeeLifecycleEventType::Hired)
            ->first();

        if ($existing) {
            if ($process && $existing->recruitment_process_id === null) {
                $existing->update(['recruitment_process_id' => $process->id]);
            }

            return;
        }

        $this->appendEvent(
            $employee,
            EmployeeLifecycleEventType::Hired,
            $hiredAt,
            recruitmentProcessId: $process?->id,
        );
    }

    /**
     * After creating an employee via /employees/create: ensure a candidate
     * identity exists and is linked, then record the hire on the employee.
     */
    public function recordHireOutsideProcess(Employee $employee): void
    {
        DB::transaction(function () use ($employee) {
            $candidate = $this->ensureCandidateLinked($employee);

            $roleIds = $employee->roles()->pluck('roles.id');
            if ($roleIds->isNotEmpty()) {
                $candidate->roles()->syncWithoutDetaching($roleIds);
            }

            $this->recordHire($employee);
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

            $this->appendEvent(
                $employee,
                EmployeeLifecycleEventType::Terminated,
                $employee->terminated_at ?? now(),
                reason: $reason,
                note: $note,
            );
        });
    }

    /**
     * Undo a termination. Existing event history stays as-is; we only clear
     * current-state termination fields and append a reinstated event.
     */
    public function reinstate(Employee $employee): void
    {
        DB::transaction(function () use ($employee) {
            $employee->update([
                'terminated_at' => null,
                'termination_reason' => null,
                'termination_note' => null,
            ]);

            $this->appendEvent(
                $employee,
                EmployeeLifecycleEventType::Reinstated,
                now(),
            );
        });
    }

    /**
     * Prefer an existing unlinked candidate matched by phone; otherwise create
     * a new candidate identity. On phone conflict (already linked to another
     * employee), create a new candidate without that phone so we never steal
     * or duplicate a linked identity.
     */
    private function ensureCandidateLinked(Employee $employee): RecruitmentCandidate
    {
        $existing = RecruitmentCandidate::query()->where('employee_id', $employee->id)->first();
        if ($existing) {
            return $existing;
        }

        return $this->resolveOrCreateCandidateForHire($employee);
    }

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

    private function appendEvent(
        Employee $employee,
        EmployeeLifecycleEventType $type,
        mixed $occurredAt,
        ?EmployeeTerminationReason $reason = null,
        ?string $note = null,
        ?int $recruitmentProcessId = null,
    ): void {
        EmployeeLifecycleEvent::create([
            'employee_id' => $employee->id,
            'type' => $type,
            'occurred_at' => $occurredAt,
            'reason' => $reason,
            'note' => $note,
            'recruitment_process_id' => $recruitmentProcessId,
            'created_by' => auth()->id(),
        ]);
    }
}
