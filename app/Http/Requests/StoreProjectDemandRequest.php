<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectDemandRequest extends FormRequest
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
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
            'demands' => ['required', 'array'],
            'demands.*.role_id' => ['required', 'exists:roles,id'],
            'demands.*.required_count' => ['required', 'integer', 'min:0'],
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
            'start_date.required' => 'Data rozpoczęcia jest wymagana.',
            'start_date.date' => 'Data rozpoczęcia musi być poprawną datą.',
            'end_date.date' => 'Data zakończenia musi być poprawną datą.',
            'end_date.after_or_equal' => 'Data zakończenia musi być późniejsza lub równa dacie rozpoczęcia.',
            'demands.required' => 'Brak danych o rolach.',
            'demands.array' => 'Dane o rolach muszą być tablicą.',
            'demands.*.role_id.required' => 'Rola jest wymagana.',
            'demands.*.role_id.exists' => 'Wybrana rola nie istnieje.',
            'demands.*.required_count.required' => 'Ilość jest wymagana.',
            'demands.*.required_count.integer' => 'Ilość musi być liczbą całkowitą.',
            'demands.*.required_count.min' => 'Ilość nie może być ujemna.',
        ];
    }
}
