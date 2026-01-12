<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_assignment_id' => 'required|exists:project_assignments,id',
            'work_date' => 'required|date',
            'hours_worked' => 'required|numeric|min:0|max:24',
            'notes' => 'nullable|string',
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
            'project_assignment_id.required' => 'Przypisanie do projektu jest wymagane.',
            'project_assignment_id.exists' => 'Wybrane przypisanie nie istnieje.',
            'work_date.required' => 'Data pracy jest wymagana.',
            'work_date.date' => 'Data pracy musi być prawidłową datą.',
            'hours_worked.required' => 'Liczba godzin jest wymagana.',
            'hours_worked.numeric' => 'Liczba godzin musi być liczbą.',
            'hours_worked.min' => 'Liczba godzin nie może być mniejsza niż 0.',
            'hours_worked.max' => 'Liczba godzin nie może przekraczać 24.',
        ];
    }
}
