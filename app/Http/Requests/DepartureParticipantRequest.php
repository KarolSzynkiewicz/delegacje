<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepartureParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'project_id' => ['required', 'exists:projects,id'],
            'role_id' => ['required', 'exists:roles,id'],
            'project_start_date' => ['required', 'date'],
            'project_end_date' => ['required', 'date', 'after_or_equal:project_start_date'],
            'accommodation_id' => ['nullable', 'exists:accommodations,id'],
            'accommodation_start_date' => ['nullable', 'required_with:accommodation_id', 'date'],
            'accommodation_end_date' => ['nullable', 'required_with:accommodation_id', 'date', 'after_or_equal:accommodation_start_date'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'vehicle_position' => ['nullable', 'in:driver,passenger'],
            'vehicle_start_date' => ['nullable', 'required_with:vehicle_id', 'date'],
            'vehicle_end_date' => ['nullable', 'required_with:vehicle_id', 'date', 'after_or_equal:vehicle_start_date'],
            'ticket_amount' => ['nullable', 'numeric', 'min:0.01'],
            'ticket_currency' => ['nullable', 'string', 'size:3'],
        ];

        if ($this->isMethod('post')) {
            $rules['employee_id'] = ['required', 'exists:employees,id'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Wybierz pracownika.',
            'project_id.required' => 'Wybierz projekt.',
            'role_id.required' => 'Wybierz rolę.',
            'project_start_date.required' => 'Podaj datę rozpoczęcia przypisania do projektu.',
            'project_end_date.required' => 'Podaj datę zakończenia przypisania do projektu.',
            'project_end_date.after_or_equal' => 'Data końca projektu nie może być wcześniejsza niż data startu.',
            'accommodation_start_date.required_with' => 'Podaj daty zakwaterowania.',
            'accommodation_end_date.required_with' => 'Podaj daty zakwaterowania.',
            'vehicle_start_date.required_with' => 'Podaj daty przypisania do pojazdu.',
            'vehicle_end_date.required_with' => 'Podaj daty przypisania do pojazdu.',
        ];
    }
}
