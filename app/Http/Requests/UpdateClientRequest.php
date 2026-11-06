<?php

namespace App\Http\Requests;

use App\Enums\ClientType;
use App\Enums\EuropeanCountry;
use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clientId = $this->route('client')?->id ?? $this->route('client');

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(ClientType::class)],
            'short_name' => ['nullable', 'string', 'max:100'],
            'nip' => [
                'nullable',
                'string',
                'digits:10',
                Rule::unique('clients', 'nip')->ignore($clientId),
            ],
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Client|null $client */
            $client = $this->route('client');
            if (! $client instanceof Client) {
                return;
            }

            if ($this->input('type') === ClientType::ServiceProvider->value
                && $client->projects()->exists()) {
                $validator->errors()->add(
                    'type',
                    'Nie można zmienić na usługodawcę — kontrahent ma przypisane projekty.'
                );
            }
        });
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
            'is_active' => $this->boolean('is_active'),
            'nip' => $this->filled('nip') ? $this->input('nip') : null,
        ]);
    }
}
