<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\Currency;

class UpdateAdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'payroll_id' => [
                'nullable',
                'exists:payrolls,id',
                function ($attribute, $value, $fail) {
                    if (!$value) {
                        return;
                    }
                    $employeeId = $this->input('employee_id');
                    if (!$employeeId) {
                        return;
                    }
                    $payroll = \App\Models\Payroll::find($value);
                    if ($payroll && (int) $payroll->employee_id !== (int) $employeeId) {
                        $fail('Wybrany payroll nie należy do tego pracownika.');
                    }
                },
            ],
            'amount' => ['required', 'numeric'],
            'currency' => ['required', 'string', Rule::in(Currency::values())],
            'type' => ['required', 'string', Rule::in(['penalty', 'bonus'])],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
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
            'type.required' => 'Typ jest wymagany.',
            'type.in' => 'Typ musi być "penalty" lub "bonus".',
            'date.required' => 'Data jest wymagana.',
            'date.date' => 'Data musi być poprawną datą.',
        ];
    }
}
