<?php

namespace App\Http\Requests;

use App\Enums\ServiceActionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action_type'         => ['required', Rule::enum(ServiceActionType::class)],
            'start_date'          => ['required', 'date'],
            'end_date'            => ['nullable', 'date', 'after_or_equal:start_date'],
            'price'               => ['nullable', 'numeric', 'min:0'],
            'currency'            => ['nullable', 'required_with:price', 'string', 'size:3'],
            'notes'               => ['nullable', 'string'],

            'location_id'         => ['nullable', 'exists:locations,id'],

            'workshop_name'         => ['nullable', 'string', 'max:255'],
            'workshop_address'      => ['nullable', 'string', 'max:255'],
            'workshop_city'         => ['nullable', 'string', 'max:255'],
            'workshop_postal_code'  => ['nullable', 'string', 'max:20'],
            'workshop_country'      => ['nullable', 'string', 'size:2'],
            'workshop_lat'          => ['nullable', 'numeric'],
            'workshop_lng'          => ['nullable', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'action_type.required'    => 'Typ akcji serwisowej jest wymagany.',
            'start_date.required'     => 'Data oddania do warsztatu jest wymagana.',
            'end_date.after_or_equal' => 'Data odbioru nie może być wcześniejsza niż data oddania.',
            'price.min'               => 'Cena nie może być ujemna.',
            'currency.required_with'  => 'Waluta jest wymagana gdy podano cenę.',
            'currency.size'           => 'Waluta musi być 3-literowym kodem (np. PLN, EUR).',
        ];
    }
}
