<?php

namespace App\ProcedureActions\Recruitment;

use App\Enums\RecruitmentStatus;
use App\Models\ProcedureRun;
use App\Models\User;
use App\ProcedureActions\AbstractAction;

class StartOnboarding extends AbstractAction
{
    public function key(): string
    {
        return 'recruitment.onboarding';
    }

    public function label(): string
    {
        return 'Przenieś do onboardingu';
    }

    public function subjectTypes(): array
    {
        return ['recruitment_candidate', 'recruitment_process'];
    }

    public function execute(ProcedureRun $run, array $payload, User $actor): array
    {
        $process = $this->process($run);
        $process->transitionTo(RecruitmentStatus::Onboarding, $actor->id);

        return ['status' => RecruitmentStatus::Onboarding->value];
    }
}
