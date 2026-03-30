<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmVehicleRepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'accept_consequences' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'accept_consequences.required' => 'Musisz zaakceptować konsekwencje serwisu.',
            'accept_consequences.accepted' => 'Musisz zaakceptować konsekwencje serwisu.',
        ];
    }
}
