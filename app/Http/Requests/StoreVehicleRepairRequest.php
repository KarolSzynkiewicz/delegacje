<?php

namespace App\Http\Requests;

use App\Enums\ServiceActionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id'          => ['required', 'exists:vehicles,id'],
            'project_id'          => ['nullable', 'exists:projects,id'],
            'action_type'         => ['required', Rule::enum(ServiceActionType::class)],
            'start_date'          => ['required', 'date'],
            'end_date'            => ['nullable', 'date', 'after_or_equal:start_date'],
            'price'               => ['nullable', 'numeric', 'min:0'],
            'currency'            => ['nullable', 'required_with:price', 'string', 'size:3'],
            'notes'               => ['nullable', 'string'],

            // Existing location
            'location_id'         => ['nullable', 'exists:locations,id'],

            // New workshop fields (used when location_id is not provided)
            'workshop_name'         => ['nullable', 'required_without:location_id', 'string', 'max:255'],
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
            'vehicle_id.required'   => 'Pojazd jest wymagany.',
            'vehicle_id.exists'     => 'Wybrany pojazd nie istnieje.',
            'project_id.exists'     => 'Wybrany projekt nie istnieje.',
            'action_type.required'  => 'Typ akcji serwisowej jest wymagany.',
            'start_date.required'   => 'Data oddania do warsztatu jest wymagana.',
            'end_date.after_or_equal' => 'Data odbioru nie może być wcześniejsza niż data oddania.',
            'price.min'             => 'Cena nie może być ujemna.',
            'currency.required_with' => 'Waluta jest wymagana gdy podano cenę.',
            'currency.size'         => 'Waluta musi być 3-literowym kodem (np. PLN, EUR).',
            'workshop_name.required_without' => 'Podaj nazwę warsztatu lub wybierz istniejącą lokalizację.',
        ];
    }
}
