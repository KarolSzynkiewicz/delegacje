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

    protected function prepareForValidation(): void
    {
        if ($this->input('sprint_id') === '') {
            $this->merge(['sprint_id' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sprint_id' => ['nullable', 'exists:sprints,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            'status' => ['nullable', 'in:pending,in_progress,completed,cancelled'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:5'],
            'category' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable', 'array', 'max:15'],
            'attachments.*' => ['file', 'max:15360', 'mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,txt,zip'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nazwa zadania jest wymagana.',
            'assigned_to.exists' => 'Wybrany użytkownik nie istnieje.',
            'due_date.date' => 'Nieprawidłowa data.',
            'due_date.after_or_equal' => 'Data zakończenia nie może być w przeszłości.',
            'priority.integer' => 'Priorytet musi być liczbą.',
            'priority.min' => 'Priorytet musi być między 1 a 5.',
            'priority.max' => 'Priorytet musi być między 1 a 5.',
        ];
    }
}
