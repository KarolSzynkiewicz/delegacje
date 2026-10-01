<?php

namespace App\Http\Requests;

use App\Models\LogisticsEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var LogisticsEvent|null $transfer */
        $transfer = $this->route('transfer');
        $needsProject = $transfer?->has_reassignment && ! $this->boolean('keep_current');

        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'keep_current' => ['nullable', 'boolean'],
            'project_id' => [
                Rule::requiredIf($needsProject),
                'nullable',
                'exists:projects,id',
            ],
            'role_id' => ['nullable', 'exists:roles,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'accommodation_id' => ['nullable', 'exists:accommodations,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'vehicle_position' => ['nullable', 'in:driver,passenger'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Wybierz pracownika.',
            'project_id.required' => 'Wybierz projekt docelowy albo zaznacz „bez zmiany przypisań”.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'keep_current' => $this->boolean('keep_current'),
        ]);
    }
}
