<?php

namespace App\ProcedureActions\Employee;

use App\Enums\Currency;
use App\Models\Adjustment;
use App\Models\ProcedureRun;
use App\Models\User;
use App\ProcedureActions\AbstractAction;
use RuntimeException;

abstract class AddAdjustment extends AbstractAction
{
    abstract protected function adjustmentType(): string;

    public function subjectTypes(): array
    {
        return ['employee'];
    }

    public function fields(ProcedureRun $run): array
    {
        return [
            ['name' => 'amount', 'label' => 'Kwota', 'type' => 'number', 'required' => true, 'step' => '0.01'],
            [
                'name' => 'currency',
                'label' => 'Waluta',
                'type' => 'select',
                'required' => true,
                'options' => array_map(fn (Currency $c) => ['value' => $c->value, 'label' => $c->label()], Currency::cases()),
            ],
            ['name' => 'date', 'label' => 'Data', 'type' => 'date', 'required' => true],
            ['name' => 'notes', 'label' => 'Notatka', 'type' => 'textarea'],
        ];
    }

    public function execute(ProcedureRun $run, array $payload, User $actor): array
    {
        $amount = $payload['amount'] ?? null;
        if ($amount === null || $amount === '' || ! is_numeric($amount)) {
            throw new RuntimeException('Podaj kwotę.');
        }

        $currency = strtoupper((string) ($payload['currency'] ?? 'PLN'));
        if (! in_array($currency, Currency::values(), true)) {
            throw new RuntimeException('Nieznana waluta.');
        }

        $adjustment = Adjustment::query()->create([
            'employee_id' => $this->employee($run)->id,
            'payroll_id' => null,
            'amount' => abs((float) $amount),
            'currency' => $currency,
            'type' => $this->adjustmentType(),
            'date' => $this->string($payload, 'date', true),
            'notes' => $this->string($payload, 'notes'),
        ]);

        return ['adjustment_id' => $adjustment->id, 'type' => $adjustment->type];
    }
}
