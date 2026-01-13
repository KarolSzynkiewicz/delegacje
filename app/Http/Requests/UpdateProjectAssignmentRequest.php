<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectAssignmentRequest extends FormRequest
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
            'project_id' => ['required', 'exists:projects,id'],
            'employee_id' => ['required', 'exists:employees,id'],
            'role_id' => ['required', 'exists:roles,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
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
            'project_id.required' => 'Projekt jest wymagany.',
            'project_id.exists' => 'Wybrany projekt nie istnieje.',
            'employee_id.required' => 'Pracownik jest wymagany.',
            'employee_id.exists' => 'Wybrany pracownik nie istnieje.',
            'role_id.required' => 'Rola jest wymagana.',
            'role_id.exists' => 'Wybrana rola nie istnieje.',
            'start_date.required' => 'Data rozpoczęcia jest wymagana.',
            'start_date.date' => 'Data rozpoczęcia musi być poprawną datą.',
            'end_date.date' => 'Data zakończenia musi być poprawną datą.',
            'end_date.after_or_equal' => 'Data zakończenia musi być późniejsza lub równa dacie rozpoczęcia.',
        ];
    }
}
