<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
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
            'report_type' => ['required', 'string', 'in:assignment_summary,employee_hours,project_status,demand_fulfillment'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'format' => ['required', 'string', 'in:pdf,excel,html'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
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
            'report_type.required' => 'Typ raportu jest wymagany.',
            'report_type.in' => 'Typ raportu musi być jednym z: assignment_summary, employee_hours, project_status, demand_fulfillment.',
            'start_date.required' => 'Data rozpoczęcia jest wymagana.',
            'start_date.date' => 'Data rozpoczęcia musi być poprawną datą.',
            'end_date.required' => 'Data zakończenia jest wymagana.',
            'end_date.date' => 'Data zakończenia musi być poprawną datą.',
            'end_date.after_or_equal' => 'Data zakończenia musi być późniejsza lub równa dacie rozpoczęcia.',
            'format.required' => 'Format raportu jest wymagany.',
            'format.in' => 'Format raportu musi być jednym z: pdf, excel, html.',
            'project_id.exists' => 'Wybrany projekt nie istnieje.',
            'employee_id.exists' => 'Wybrany pracownik nie istnieje.',
        ];
    }
}
