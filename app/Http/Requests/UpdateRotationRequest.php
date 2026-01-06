<?php

namespace App\Http\Requests;

use App\Rules\RotationDoesNotOverlap;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRotationRequest extends FormRequest
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
        $employee = $this->route('employee');
        $rotation = $this->route('rotation');
        
        $employeeId = $employee?->id;
        $rotationId = $rotation?->id;

        return [
            'start_date' => ['required', 'date'],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
                new RotationDoesNotOverlap($employeeId, $rotationId)
            ],
            'status' => ['nullable', 'in:cancelled'],
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
            'start_date.required' => 'Data rozpoczęcia jest wymagana.',
            'start_date.date' => 'Data rozpoczęcia musi być poprawną datą.',
            'end_date.required' => 'Data zakończenia jest wymagana.',
            'end_date.date' => 'Data zakończenia musi być poprawną datą.',
            'end_date.after_or_equal' => 'Data zakończenia musi być późniejsza lub równa dacie rozpoczęcia.',
            'status.in' => 'Status może być tylko "cancelled".',
        ];
    }
}
