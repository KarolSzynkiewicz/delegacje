<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleAssignmentRequest extends FormRequest
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
            'vehicle_id' => ['required', 'exists:vehicles,id'],
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
            'vehicle_id.required' => 'Pojazd jest wymagany.',
            'vehicle_id.exists' => 'Wybrany pojazd nie istnieje.',
            'start_date.required' => 'Data rozpoczęcia jest wymagana.',
            'start_date.date' => 'Data rozpoczęcia musi być poprawną datą.',
            'end_date.date' => 'Data zakończenia musi być poprawną datą.',
            'end_date.after_or_equal' => 'Data zakończenia musi być późniejsza lub równa dacie rozpoczęcia.',
        ];
    }
}
