<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdvanceRequest extends FormRequest
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
            'payroll_id' => ['required', 'exists:payrolls,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'date' => ['required', 'date'],
            'is_interest_bearing' => ['nullable', 'boolean'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_interest_bearing' => $this->has('is_interest_bearing') ? (bool) $this->is_interest_bearing : false,
        ]);
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payroll_id.required' => 'Payroll jest wymagany.',
            'payroll_id.exists' => 'Wybrany payroll nie istnieje.',
            'amount.required' => 'Kwota jest wymagana.',
            'amount.numeric' => 'Kwota musi być liczbą.',
            'amount.min' => 'Kwota nie może być ujemna.',
            'date.required' => 'Data jest wymagana.',
            'date.date' => 'Data musi być poprawną datą.',
            'interest_rate.numeric' => 'Stawka oprocentowania musi być liczbą.',
            'interest_rate.min' => 'Stawka oprocentowania nie może być ujemna.',
            'interest_rate.max' => 'Stawka oprocentowania nie może przekraczać 100%.',
        ];
    }
}
