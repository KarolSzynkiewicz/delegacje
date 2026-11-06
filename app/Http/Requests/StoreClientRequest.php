<?php

namespace App\Http\Requests;

use App\Enums\ClientType;
use App\Enums\EuropeanCountry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(ClientType::class)],
            'short_name' => ['nullable', 'string', 'max:100'],
            'nip' => ['nullable', 'string', 'digits:10', 'unique:clients,nip'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'country' => [
                'nullable',
                'string',
                Rule::in(array_column(EuropeanCountry::cases(), 'value')),
            ],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nazwa kontrahenta jest wymagana.',
            'type.required' => 'Wybierz typ kontrahenta.',
            'nip.digits' => 'NIP musi składać się z 10 cyfr.',
            'nip.unique' => 'Kontrahent z tym NIP-em już istnieje.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'type' => $this->input('type', ClientType::Client->value),
            'nip' => $this->filled('nip') ? $this->input('nip') : null,
        ]);
    }
}
