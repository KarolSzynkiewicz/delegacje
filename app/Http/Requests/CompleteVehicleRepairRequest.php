<?php

namespace App\Http\Requests;

use App\Enums\VehicleCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteVehicleRepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'end_date'                  => ['required', 'date', 'after_or_equal:' . $this->route('vehicleRepair')->start_date->toDateString()],
            'price'                     => ['required', 'numeric', 'min:0'],
            'currency'                  => ['required', 'string', 'size:3'],
            'new_technical_condition'   => ['required', Rule::enum(VehicleCondition::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.required'                 => 'Data odbioru jest wymagana.',
            'end_date.after_or_equal'           => 'Data odbioru nie może być wcześniejsza niż data oddania.',
            'price.required'                    => 'Koszt naprawy jest wymagany.',
            'price.min'                         => 'Koszt nie może być ujemny.',
            'currency.required'                 => 'Waluta jest wymagana.',
            'currency.size'                     => 'Waluta musi być 3-literowym kodem (np. PLN, EUR).',
            'new_technical_condition.required'  => 'Nowy stan techniczny pojazdu jest wymagany.',
        ];
    }
}
