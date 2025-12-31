<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:employees'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role_id' => ['required', 'exists:roles,id'],
            'a1_valid_from' => ['nullable', 'date'],
            'a1_valid_to' => ['nullable', 'date', 'after_or_equal:a1_valid_from'],
            'document_1' => ['nullable', 'string'],
            'document_2' => ['nullable', 'string'],
            'document_3' => ['nullable', 'string'],
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
            'first_name.required' => 'Imię jest wymagane.',
            'last_name.required' => 'Nazwisko jest wymagane.',
            'email.required' => 'Email jest wymagany.',
            'email.email' => 'Email musi być poprawnym adresem email.',
            'email.unique' => 'Ten email jest już używany.',
            'role_id.required' => 'Rola jest wymagana.',
            'role_id.exists' => 'Wybrana rola nie istnieje.',
            'a1_valid_to.after_or_equal' => 'Data ważności do nie może być wcześniejsza niż data ważności od.',
            'image.image' => 'Plik musi być obrazem (JPEG, PNG, JPG, GIF lub WEBP).',
            'image.mimes' => 'Obraz musi być w formacie: JPEG, PNG, JPG, GIF lub WEBP.',
            'image.max' => 'Rozmiar obrazu nie może przekraczać 2MB.',
        ];
    }
}

