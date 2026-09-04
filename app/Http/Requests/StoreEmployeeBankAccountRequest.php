<?php

namespace App\Http\Requests;

use App\Models\EmployeeBankAccount;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('account_number')) {
            $this->merge([
                'account_number' => EmployeeBankAccount::normalizeAccountNumber((string) $this->input('account_number')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'account_number' => [
                'required',
                'string',
                'max:34',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || preg_match('/^(?:\d{26}|[A-Z]{2}\d{2}[A-Z0-9]{11,30})$/', $value) !== 1) {
                        $fail('Podaj numer NRB (26 cyfr) lub IBAN.');
                    }
                },
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Wybierz pracownika.',
            'account_number.required' => 'Numer konta jest wymagany.',
            'start_date.required' => 'Data rozpoczęcia jest wymagana.',
            'end_date.after_or_equal' => 'Data zakończenia musi być nie wcześniejsza niż data rozpoczęcia.',
        ];
    }
}
