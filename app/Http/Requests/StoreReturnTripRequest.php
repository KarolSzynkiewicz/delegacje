<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReturnTripRequest extends FormRequest
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
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:' . implode(',', \App\Enums\LogisticsEventStatus::values())],
            'accept_consequences' => ['required', 'accepted'],
            'return_trip_id' => ['nullable', 'exists:logistics_events,id'],
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
            'accept_consequences.required' => 'Musisz zaakceptować konsekwencje zjazdu.',
            'accept_consequences.accepted' => 'Musisz zaakceptować konsekwencje zjazdu.',
            'notes.max' => 'Notatki nie mogą przekraczać 1000 znaków.',
            'status.in' => 'Nieprawidłowy status.',
            'return_trip_id.exists' => 'Wybrany zjazd nie istnieje.',
        ];
    }
}
