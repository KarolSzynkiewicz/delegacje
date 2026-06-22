<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'employee_id' => ['required', 'exists:employees,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'Wybierz spółkę.',
            'employee_id.required' => 'Wybierz pracownika.',
            'start_date.required' => 'Data rozpoczęcia jest wymagana.',
            'end_date.after_or_equal' => 'Data zakończenia musi być nie wcześniejsza niż data rozpoczęcia.',
        ];
    }
}
