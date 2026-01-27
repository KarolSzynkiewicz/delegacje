<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReturnTripRequest extends FormRequest
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
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['exists:employees,id'],
            'return_date' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'accept_consequences' => ['required', 'accepted'],
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
            'employee_ids.required' => 'Musisz wybrać przynajmniej jednego pracownika.',
            'employee_ids.array' => 'Lista pracowników musi być tablicą.',
            'employee_ids.min' => 'Musisz wybrać przynajmniej jednego pracownika.',
            'employee_ids.*.exists' => 'Wybrany pracownik nie istnieje.',
            'return_date.required' => 'Data zjazdu jest wymagana.',
            'return_date.date' => 'Data zjazdu musi być poprawną datą.',
            'return_date.after_or_equal' => 'Data zjazdu nie może być wcześniejsza niż dzisiaj.',
            'vehicle_id.exists' => 'Wybrany pojazd nie istnieje.',
            'notes.max' => 'Notatki nie mogą przekraczać 1000 znaków.',
            'accept_consequences.required' => 'Musisz zaakceptować konsekwencje zjazdu.',
            'accept_consequences.accepted' => 'Musisz zaakceptować konsekwencje zjazdu.',
        ];
    }
}
