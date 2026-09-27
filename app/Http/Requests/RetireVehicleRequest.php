<?php

namespace App\Http\Requests;

use App\Enums\VehicleRetirementReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RetireVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::enum(VehicleRetirementReason::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Wybierz powód wycofania.',
            'note.max' => 'Notatka może mieć co najwyżej 1000 znaków.',
        ];
    }
}
