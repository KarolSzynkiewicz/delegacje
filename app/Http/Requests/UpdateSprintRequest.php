<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSprintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'goal' => ['nullable', 'string'],
            'definition_of_done' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'attachments' => ['nullable', 'array', 'max:15'],
            'attachments.*' => ['file', 'max:15360', 'mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,txt,zip'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nazwa sprintu jest wymagana.',
            'start_date.required' => 'Data rozpoczęcia jest wymagana.',
            'end_date.required' => 'Data zakończenia jest wymagana.',
            'end_date.after_or_equal' => 'Data zakończenia nie może być wcześniejsza niż data rozpoczęcia.',
        ];
    }
}
