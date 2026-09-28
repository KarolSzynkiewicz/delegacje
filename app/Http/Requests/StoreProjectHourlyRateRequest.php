<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectHourlyRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Podaj stawkę za godzinę.',
            'amount.numeric' => 'Stawka za godzinę musi być liczbą.',
            'amount.min' => 'Stawka za godzinę nie może być ujemna.',
            'start_date.required' => 'Podaj dzień, od którego obowiązuje nowa stawka.',
            'start_date.date' => 'Dzień rozpoczęcia stawki jest niepoprawny.',
        ];
    }
}
