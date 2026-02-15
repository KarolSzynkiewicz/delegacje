<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccommodationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'location_id' => ['required', 'exists:locations,id'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'capacity' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:wynajmowany,własny'],
            'lease_start_date' => ['nullable', 'date', 'required_if:type,wynajmowany'],
            'lease_end_date' => ['nullable', 'date', 'required_if:type,wynajmowany', 'after_or_equal:lease_start_date'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'location_id.required' => 'Lokalizacja jest wymagana.',
            'location_id.exists' => 'Wybrana lokalizacja nie istnieje.',
        ];
    }
}
