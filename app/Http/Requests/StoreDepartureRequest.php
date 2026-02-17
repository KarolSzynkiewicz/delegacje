<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartureRequest extends FormRequest
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
        $rules = [
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['exists:employees,id'],
            'departure_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:departure_date'],
            'to_location_id' => ['required', 'exists:locations,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        // If departure_date is in the past, require confirmation
        $departureDate = $this->input('departure_date');
        if ($departureDate && \Carbon\Carbon::parse($departureDate)->startOfDay()->isPast()) {
            $rules['confirm_past_date'] = ['accepted'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_ids.required' => 'Musisz wybrać przynajmniej jednego pracownika.',
            'employee_ids.array' => 'Lista pracowników musi być tablicą.',
            'employee_ids.min' => 'Musisz wybrać przynajmniej jednego pracownika.',
            'employee_ids.*.exists' => 'Wybrany pracownik nie istnieje.',
            'departure_date.required' => 'Data wyjazdu jest wymagana.',
            'departure_date.date' => 'Data wyjazdu musi być poprawną datą.',
            'confirm_past_date.accepted' => 'Musisz potwierdzić, że chcesz dodać wyjazd z datą w przeszłości.',
            'end_date.required' => 'Data przybycia jest wymagana.',
            'end_date.date' => 'Data przybycia musi być poprawną datą.',
            'end_date.after' => 'Data przybycia musi być późniejsza niż data wyjazdu.',
            'to_location_id.required' => 'Lokalizacja docelowa jest wymagana.',
            'to_location_id.exists' => 'Wybrana lokalizacja nie istnieje.',
            'vehicle_id.exists' => 'Wybrany pojazd nie istnieje.',
            'notes.max' => 'Notatki nie mogą przekraczać 1000 znaków.',
        ];
    }
}
