<?php

namespace App\ProcedureActions\Employee;

use App\Enums\Currency;
use App\Models\EmployeeRate;
use App\Models\ProcedureRun;
use App\Models\User;
use App\ProcedureActions\AbstractAction;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SetRate extends AbstractAction
{
    public function key(): string
    {
        return 'employee.rate';
    }

    public function label(): string
    {
        return 'Ustaw stawkę';
    }

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
            ['name' => 'start_date', 'label' => 'Od', 'type' => 'date', 'required' => true],
            ['name' => 'end_date', 'label' => 'Do', 'type' => 'date'],
            ['name' => 'notes', 'label' => 'Notatka', 'type' => 'textarea'],
        ];
    }

    public function execute(ProcedureRun $run, array $payload, User $actor): array
    {
        $employee = $this->employee($run);
        $amount = $payload['amount'] ?? null;
        if ($amount === null || $amount === '' || (float) $amount < 0) {
            throw new RuntimeException('Podaj kwotę stawki.');
        }

        $currency = strtoupper((string) ($payload['currency'] ?? 'PLN'));
        if (! in_array($currency, Currency::values(), true)) {
            throw new RuntimeException('Nieznana waluta.');
        }

        $start = $this->string($payload, 'start_date', true);
        $end = $this->string($payload, 'end_date');

        $overlap = EmployeeRate::query()
            ->where('employee_id', $employee->id)
            ->where('currency', $currency)
            ->where('status', 'active')
            ->where('start_date', '<=', $end ?? '9999-12-31')
            ->where(function ($q) use ($start) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $start);
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'start_date' => 'Pracownik ma już aktywną stawkę w tej walucie w tym okresie.',
            ]);
        }

        $rate = EmployeeRate::query()->create([
            'employee_id' => $employee->id,
            'start_date' => $start,
            'end_date' => $end,
            'amount' => $amount,
            'currency' => $currency,
            'notes' => $this->string($payload, 'notes'),
        ]);

        return ['rate_id' => $rate->id];
    }
}
