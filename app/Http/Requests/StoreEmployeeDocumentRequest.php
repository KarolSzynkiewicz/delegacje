<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Log;

class StoreEmployeeDocumentRequest extends FormRequest
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
            'employee_id' => ['required', 'exists:employees,id'],
            'document_id' => ['required', 'exists:documents,id'],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_okresowy' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,odt,txt', 'max:10240'], // 10MB max
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
            'employee_id.required' => 'Pracownik jest wymagany.',
            'employee_id.exists' => 'Wybrany pracownik nie istnieje.',
            'document_id.required' => 'Dokument jest wymagany.',
            'document_id.exists' => 'Wybrany dokument nie istnieje.',
            'valid_from.required' => 'Data ważności od jest wymagana.',
            'valid_from.date' => 'Data ważności od musi być poprawną datą.',
            'valid_to.date' => 'Data ważności do musi być poprawną datą.',
            'valid_to.after_or_equal' => 'Data ważności do musi być późniejsza lub równa dacie od.',
            'file.file' => 'Przesłany plik jest nieprawidłowy.',
            'file.mimes' => 'Dozwolone typy plików: PDF, DOC, DOCX, XLS, XLSX, ODT, TXT.',
            'file.max' => 'Plik nie może być większy niż 10MB.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        // Bardzo agresywne logowanie - zapisz do pliku I do log
        $errorData = [
            'errors' => $validator->errors()->all(),
            'messages' => $validator->errors()->messages(),
            'input' => $this->except(['_token', 'file']),
            'employee_id' => $this->input('employee_id'),
            'document_id' => $this->input('document_id'),
            'url' => $this->url(),
            'method' => $this->method(),
        ];
        
        Log::error('StoreEmployeeDocumentRequest: Validation FAILED', $errorData);
        
        // Dodatkowo zapisz do emergency log
        Log::channel('stack')->error('VALIDATION FAILED - EmployeeDocument', $errorData);

        parent::failedValidation($validator);
    }
}
