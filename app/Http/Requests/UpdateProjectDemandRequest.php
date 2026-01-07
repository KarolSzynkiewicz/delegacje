<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectDemandRequest extends FormRequest
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
            'role_id' => ['required', 'exists:roles,id'],
            'required_count' => ['required', 'integer', 'min:0'],
            'date_from' => ['required', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
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
            'role_id.required' => 'Rola jest wymagana.',
            'role_id.exists' => 'Wybrana rola nie istnieje.',
            'required_count.required' => 'Ilość jest wymagana.',
            'required_count.integer' => 'Ilość musi być liczbą całkowitą.',
            'required_count.min' => 'Ilość nie może być ujemna. Ustaw 0 aby usunąć zapotrzebowanie.',
            'date_from.required' => 'Data rozpoczęcia jest wymagana.',
            'date_from.date' => 'Data rozpoczęcia musi być poprawną datą.',
            'date_to.date' => 'Data zakończenia musi być poprawną datą.',
            'date_to.after_or_equal' => 'Data zakończenia musi być późniejsza lub równa dacie rozpoczęcia.',
        ];
    }
}
