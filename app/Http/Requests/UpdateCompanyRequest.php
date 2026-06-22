<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->route('company')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'nip' => ['required', 'string', 'digits:10', Rule::unique('companies', 'nip')->ignore($companyId)],
            'regon' => ['nullable', 'string', 'regex:/^(\d{9}|\d{14})$/'],
            'krs' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'country' => [
                'nullable',
                'string',
                Rule::in(array_column(\App\Enums\EuropeanCountry::cases(), 'value')),
            ],
            'founded_at' => ['nullable', 'date'],
            'president_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nazwa spółki jest wymagana.',
            'nip.required' => 'NIP jest wymagany.',
            'nip.digits' => 'NIP musi składać się z 10 cyfr.',
            'nip.unique' => 'Spółka z tym NIP-em już istnieje.',
            'regon.regex' => 'REGON musi składać się z 9 lub 14 cyfr.',
            'email.email' => 'Podaj poprawny adres e-mail.',
        ];
    }
}
