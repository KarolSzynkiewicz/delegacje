<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccommodationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'country' => ['nullable', 'string', 'in:' . implode(',', array_column(\App\Enums\EuropeanCountry::cases(), 'value'))],
            'capacity' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:wynajmowany,własny'],
            'lease_start_date' => ['nullable', 'date', 'required_if:type,wynajmowany'],
            'lease_end_date' => ['nullable', 'date', 'required_if:type,wynajmowany', 'after_or_equal:lease_start_date'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
