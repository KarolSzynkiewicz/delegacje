<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
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
            'registration_number' => ['required', 'string', 'unique:vehicles'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'technical_condition' => ['required', 'in:excellent,good,fair,poor,workshop'],
            'inspection_valid_to' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
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
            'registration_number.required' => 'Numer rejestracyjny jest wymagany.',
            'registration_number.unique' => 'Ten numer rejestracyjny jest już używany.',
            'capacity.integer' => 'Pojemność musi być liczbą całkowitą.',
            'capacity.min' => 'Pojemność musi być większa od 0.',
            'technical_condition.required' => 'Stan techniczny jest wymagany.',
            'technical_condition.in' => 'Stan techniczny musi być jednym z: excellent, good, fair, poor, workshop.',
            'inspection_valid_to.date' => 'Data ważności przeglądu musi być poprawną datą.',
            'image.image' => 'Plik musi być obrazem.',
            'image.mimes' => 'Obraz musi być w formacie: JPEG, PNG, JPG, GIF lub WEBP.',
            'image.max' => 'Rozmiar obrazu nie może przekraczać 2MB.',
        ];
    }
}
