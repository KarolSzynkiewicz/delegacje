<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectTaskRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            'status' => ['nullable', 'in:pending,in_progress,completed,cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nazwa zadania jest wymagana.',
            'assigned_to.exists' => 'Wybrany użytkownik nie istnieje.',
            'due_date.date' => 'Nieprawidłowa data.',
            'due_date.after_or_equal' => 'Data zakończenia nie może być w przeszłości.',
        ];
    }
}
