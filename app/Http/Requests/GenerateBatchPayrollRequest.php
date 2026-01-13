<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateBatchPayrollRequest extends FormRequest
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
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
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
            'period_start.required' => 'Data rozpoczęcia okresu jest wymagana.',
            'period_start.date' => 'Data rozpoczęcia okresu musi być poprawną datą.',
            'period_end.required' => 'Data zakończenia okresu jest wymagana.',
            'period_end.date' => 'Data zakończenia okresu musi być poprawną datą.',
            'period_end.after_or_equal' => 'Data zakończenia okresu nie może być wcześniejsza niż data rozpoczęcia.',
        ];
    }
}
