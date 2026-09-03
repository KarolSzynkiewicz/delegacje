<?php

namespace App\ProcedureActions;

use App\Models\Employee;
use App\Models\ProcedureRun;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentProcess;
use App\ProcedureActions\Contracts\ProcedureAction;
use RuntimeException;

abstract class AbstractAction implements ProcedureAction
{
    public function fields(ProcedureRun $run): array
    {
        return [];
    }

    protected function employee(ProcedureRun $run): Employee
    {
        $subject = $run->subject;

        if ($subject instanceof Employee) {
            return $subject;
        }

        if ($subject instanceof RecruitmentProcess && $subject->employee_id) {
            $employee = Employee::query()->find($subject->employee_id);
            if ($employee) {
                return $employee;
            }
        }

        if ($subject instanceof RecruitmentCandidate && $subject->employee_id) {
            $employee = Employee::query()->find($subject->employee_id);
            if ($employee) {
                return $employee;
            }
        }

        throw new RuntimeException('Ta akcja wymaga pracownika jako przedmiotu procedury.');
    }

    protected function process(ProcedureRun $run): RecruitmentProcess
    {
        $subject = $run->subject;

        if ($subject instanceof RecruitmentProcess) {
            return $subject;
        }

        if ($subject instanceof RecruitmentCandidate) {
            $process = $subject->latestProcess
                ?? $subject->processes()->latest('id')->first();
            if ($process) {
                return $process;
            }
        }

        throw new RuntimeException('Ta akcja wymaga procesu rekrutacji jako przedmiotu procedury.');
    }

    protected function candidate(ProcedureRun $run): RecruitmentCandidate
    {
        $subject = $run->subject;

        if ($subject instanceof RecruitmentCandidate) {
            return $subject;
        }

        if ($subject instanceof RecruitmentProcess) {
            $subject->loadMissing('candidate');
            if ($subject->candidate) {
                return $subject->candidate;
            }
        }

        throw new RuntimeException('Ta akcja wymaga kandydata jako przedmiotu procedury.');
    }

    /** @param  array<string, mixed>  $payload */
    protected function string(array $payload, string $key, bool $required = false): ?string
    {
        $value = trim((string) ($payload[$key] ?? ''));

        if ($required && $value === '') {
            throw new RuntimeException('Uzupełnij pole przed kontynuacją.');
        }

        return $value === '' ? null : $value;
    }
}
