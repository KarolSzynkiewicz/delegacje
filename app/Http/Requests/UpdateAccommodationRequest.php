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
            'location_id'   => ['nullable', 'integer', 'exists:locations,id'],
            'location_name' => ['nullable', 'required_without:location_id', 'string', 'max:255'],
            'address'       => ['nullable', 'string', 'max:255'],
            'city'          => ['nullable', 'string', 'max:255'],
            'postal_code'   => ['nullable', 'string', 'max:10'],
            'country'       => [
                'nullable',
                'string',
                'in:' . implode(',', array_column(\App\Enums\EuropeanCountry::cases(), 'value')),
            ],
            'latitude'      => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'     => ['nullable', 'numeric', 'between:-180,180'],

            'name'             => ['required', 'string', 'max:255'],
            'capacity'         => ['required', 'integer', 'min:1'],
            'description'      => ['nullable', 'string'],
            'type'             => ['required', 'in:wynajmowany,własny'],
            'lease_start_date' => ['nullable', 'date', 'required_if:type,wynajmowany'],
            'lease_end_date'   => ['nullable', 'date', 'required_if:type,wynajmowany', 'after_or_equal:lease_start_date'],
            'image'            => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'location_name.required_without' => 'Podaj nazwę nowej lokalizacji lub wybierz istniejącą.',
            'lease_start_date.required_if'   => 'Data początku najmu jest wymagana dla wynajmowanej akomodacji.',
            'lease_end_date.required_if'     => 'Data końca najmu jest wymagana dla wynajmowanej akomodacji.',
        ];
    }
}
