<?php

namespace App\ProcedureActions\Recruitment;

use App\Enums\RecruitmentStatus;
use App\Models\ProcedureRun;
use App\Models\User;
use App\ProcedureActions\AbstractAction;

class VerifyCandidate extends AbstractAction
{
    public function key(): string
    {
        return 'recruitment.verify';
    }

    public function label(): string
    {
        return 'Przenieś do weryfikacji';
    }

    public function subjectTypes(): array
    {
        return ['recruitment_candidate', 'recruitment_process'];
    }

    public function execute(ProcedureRun $run, array $payload, User $actor): array
    {
        $process = $this->process($run);
        $process->transitionTo(RecruitmentStatus::Zaakceptowany, $actor->id);

        return ['status' => RecruitmentStatus::Zaakceptowany->value];
    }
}
