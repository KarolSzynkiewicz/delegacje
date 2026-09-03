<?php

namespace App\ProcedureActions\Employee;

use App\Enums\Currency;
use App\Models\Advance;
use App\Models\Payroll;
use App\Models\ProcedureRun;
use App\Models\User;
use App\ProcedureActions\AbstractAction;
use App\Services\GeneratePayrollForEmployee;
use RuntimeException;

class AddAdvance extends AbstractAction
{
    public function key(): string
    {
        return 'employee.advance';
    }

    public function label(): string
    {
        return 'Dodaj zaliczkę';
    }

    public function subjectTypes(): array
    {
        return ['employee'];
    }

    public function fields(ProcedureRun $run): array
    {
        $employee = $run->subject;
        $payrolls = $employee instanceof \App\Models\Employee
            ? $employee->payrolls()->with('employee')->orderByDesc('period_start')->get()
            : collect();

        return [
            [
                'name' => 'payroll_id',
                'label' => 'Lista płac',
                'type' => 'select',
                'required' => true,
                'options' => $payrolls->map(fn (Payroll $p) => [
                    'value' => (string) $p->id,
                    'label' => $p->display_name,
                ])->all(),
            ],
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
        $employee = $this->employee($run);
        $payroll = Payroll::query()->find((int) ($payload['payroll_id'] ?? 0));
        if (! $payroll || (int) $payroll->employee_id !== (int) $employee->id) {
            throw new RuntimeException('Wybierz listę płac tego pracownika.');
        }

        $amount = $payload['amount'] ?? null;
        if ($amount === null || $amount === '' || (float) $amount < 0) {
            throw new RuntimeException('Podaj kwotę zaliczki.');
        }

        $advance = Advance::query()->create([
            'employee_id' => $employee->id,
            'payroll_id' => $payroll->id,
            'amount' => $amount,
            'currency' => strtoupper((string) ($payload['currency'] ?? 'PLN')),
            'date' => $this->string($payload, 'date', true),
            'notes' => $this->string($payload, 'notes'),
        ]);

        if ($payroll->canBeRecalculated()) {
            $payroll->adjustments_amount = app(GeneratePayrollForEmployee::class)
                ->calculateAdjustmentsAmountForPayroll($payroll);
            $payroll->recalculateTotal();
            $payroll->save();
        }

        return ['advance_id' => $advance->id];
    }
}
