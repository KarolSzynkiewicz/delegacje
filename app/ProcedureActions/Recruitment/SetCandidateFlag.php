<?php

namespace App\ProcedureActions\Recruitment;

use App\Enums\RecruitmentCandidateFlag;
use App\Models\ProcedureRun;
use App\Models\User;
use App\ProcedureActions\AbstractAction;
use RuntimeException;

class SetCandidateFlag extends AbstractAction
{
    public function key(): string
    {
        return 'recruitment.flag';
    }

    public function label(): string
    {
        return 'Oceń kandydata';
    }

    public function subjectTypes(): array
    {
        return ['recruitment_candidate', 'recruitment_process'];
    }

    public function fields(ProcedureRun $run): array
    {
        return [
            [
                'name' => 'flag',
                'label' => 'Ocena',
                'type' => 'select',
                'required' => true,
                'options' => array_map(
                    fn (RecruitmentCandidateFlag $flag) => ['value' => $flag->value, 'label' => $flag->label()],
                    RecruitmentCandidateFlag::cases()
                ),
            ],
            ['name' => 'note', 'label' => 'Uzasadnienie', 'type' => 'textarea'],
        ];
    }

    public function execute(ProcedureRun $run, array $payload, User $actor): array
    {
        $flag = RecruitmentCandidateFlag::tryFrom((string) ($payload['flag'] ?? ''));
        if ($flag === null) {
            throw new RuntimeException('Wybierz ocenę kandydata.');
        }

        $candidate = $this->candidate($run);
        $candidate->update([
            'rating' => $flag->value,
            'rating_note' => $this->string($payload, 'note'),
        ]);

        return ['flag' => $flag->value];
    }
}
