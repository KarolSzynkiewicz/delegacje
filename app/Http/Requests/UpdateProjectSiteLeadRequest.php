<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectSiteLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Wybierz pracownika.',
            'employee_id.exists' => 'Wybrany pracownik nie istnieje.',
            'start_date.required' => 'Data od jest wymagana.',
            'end_date.after_or_equal' => 'Data do nie może być wcześniejsza niż data od.',
        ];
    }
}
