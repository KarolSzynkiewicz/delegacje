<?php

namespace App\Http\Requests;

use App\Enums\Currency;
use App\Models\Payroll;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'payroll_id' => [
                'nullable',
                'exists:payrolls,id',
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }
                    $employeeId = $this->input('employee_id');
                    if (! $employeeId) {
                        return;
                    }
                    $payroll = Payroll::find($value);
                    if ($payroll && (int) $payroll->employee_id !== (int) $employeeId) {
                        $fail('Wybrany payroll nie należy do tego pracownika.');
                    }
                },
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', Rule::in(Currency::values())],
            'date' => ['required', 'date'],
            'is_interest_bearing' => ['nullable', 'boolean'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_interest_bearing' => $this->boolean('is_interest_bearing'),
            'payroll_id' => $this->input('payroll_id') ?: null,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payroll_id.exists' => 'Wybrany payroll nie istnieje.',
            'employee_id.required' => 'Pracownik jest wymagany.',
            'employee_id.exists' => 'Wybrany pracownik nie istnieje.',
            'amount.required' => 'Kwota jest wymagana.',
            'amount.numeric' => 'Kwota musi być liczbą.',
            'amount.min' => 'Kwota nie może być ujemna.',
            'date.required' => 'Data jest wymagana.',
            'date.date' => 'Data musi być poprawną datą.',
            'interest_rate.numeric' => 'Stawka oprocentowania musi być liczbą.',
            'interest_rate.min' => 'Stawka oprocentowania nie może być ujemna.',
            'interest_rate.max' => 'Stawka oprocentowania nie może przekraczać 100%.',
        ];
    }
}
