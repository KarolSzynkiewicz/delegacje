<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
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
        $type = $this->input('type', ProjectType::CONTRACT->value);
        
        $rules = [
            'location_id' => ['required', 'exists:locations,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'type' => ['required', Rule::enum(ProjectType::class)],
            'client_name' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:0'],
        ];

        // Warunkowa walidacja w zależności od typu projektu
        if ($type === ProjectType::HOURLY->value) {
            $rules['hourly_rate'] = ['nullable', 'numeric', 'min:0'];
            $rules['contract_amount'] = ['nullable'];
            $rules['currency'] = ['nullable', 'string', 'size:3'];
        } else {
            $rules['hourly_rate'] = ['nullable'];
            $rules['contract_amount'] = ['nullable', 'numeric', 'min:0'];
            $rules['currency'] = ['nullable', 'string', 'size:3'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'location_id.required' => 'Lokalizacja jest wymagana.',
            'location_id.exists' => 'Wybrana lokalizacja nie istnieje.',
            'name.required' => 'Nazwa projektu jest wymagana.',
            'status.required' => 'Status projektu jest wymagany.',
            'type.required' => 'Typ projektu jest wymagany.',
            'budget.numeric' => 'Budżet musi być liczbą.',
            'budget.min' => 'Budżet nie może być ujemny.',
            'hourly_rate.numeric' => 'Stawka za godzinę musi być liczbą.',
            'hourly_rate.min' => 'Stawka za godzinę nie może być ujemna.',
            'contract_amount.numeric' => 'Kwota kontraktu musi być liczbą.',
            'contract_amount.min' => 'Kwota kontraktu nie może być ujemna.',
            'currency.size' => 'Kod waluty musi składać się z 3 znaków.',
        ];
    }
}

