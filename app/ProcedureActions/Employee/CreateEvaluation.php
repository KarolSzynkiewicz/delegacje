<?php

namespace App\ProcedureActions\Employee;

use App\Models\EmployeeEvaluation;
use App\Models\ProcedureRun;
use App\Models\User;
use App\ProcedureActions\AbstractAction;
use RuntimeException;

class CreateEvaluation extends AbstractAction
{
    public function key(): string
    {
        return 'employee.evaluation';
    }

    public function label(): string
    {
        return 'Wystaw ocenę';
    }

    public function subjectTypes(): array
    {
        return ['employee'];
    }

    public function fields(ProcedureRun $run): array
    {
        $score = fn (string $name, string $label) => [
            'name' => $name,
            'label' => $label,
            'type' => 'number',
            'required' => true,
            'min' => 1,
            'max' => 10,
        ];

        return [
            $score('engagement', 'Zaangażowanie (1–10)'),
            $score('skills', 'Umiejętności (1–10)'),
            $score('orderliness', 'Porządek (1–10)'),
            $score('behavior', 'Zachowanie (1–10)'),
            ['name' => 'notes', 'label' => 'Uwagi', 'type' => 'textarea'],
        ];
    }

    public function execute(ProcedureRun $run, array $payload, User $actor): array
    {
        $score = function (string $key) use ($payload): int {
            $value = (int) ($payload[$key] ?? 0);
            if ($value < 1 || $value > 10) {
                throw new RuntimeException('Oceny muszą być w zakresie 1–10.');
            }

            return $value;
        };

        $evaluation = EmployeeEvaluation::query()->create([
            'employee_id' => $this->employee($run)->id,
            'created_by' => $actor->id,
            'engagement' => $score('engagement'),
            'skills' => $score('skills'),
            'orderliness' => $score('orderliness'),
            'behavior' => $score('behavior'),
            'notes' => $this->string($payload, 'notes'),
        ]);

        return ['evaluation_id' => $evaluation->id, 'average' => $evaluation->average_score];
    }
}
