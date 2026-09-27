<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CorrectVehiclePlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'placement' => ['required', Rule::in(['base', 'field'])],
            'notes' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'placement.required' => 'Wybierz, czy auto jest w bazie, czy poza bazą.',
            'placement.in' => 'Wybierz, czy auto jest w bazie, czy poza bazą.',
            'notes.required' => 'Napisz, dlaczego poprawiasz położenie.',
            'notes.min' => 'Notatka musi mieć co najmniej 3 znaki.',
            'notes.max' => 'Notatka może mieć co najwyżej 1000 znaków.',
        ];
    }
}
