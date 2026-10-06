<?php

namespace App\Http\Requests;

use App\Models\Document;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

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
        return array_merge([
            'employee_id' => ['required', 'exists:employees,id'],
        ], self::attributeRules($this));
    }

    /**
     * Reguły wpisu u pracownika. Spółka i data końca wynikają z typu w wymaganiach formalnych.
     *
     * @return array<string, mixed>
     */
    public static function attributeRules(\Illuminate\Http\Request $request): array
    {
        $document = Document::find($request->input('document_id'));
        $scoped = (bool) $document?->is_company_scoped;
        $periodic = (bool) $document?->is_periodic;

        return [
            'document_id' => ['required', 'exists:documents,id'],
            'company_id' => [
                Rule::excludeIf(! $scoped),
                Rule::requiredIf($scoped),
                'nullable',
                'integer',
                'exists:companies,id',
            ],
            'valid_from' => ['required', 'date'],
            'valid_to' => [
                Rule::excludeIf(! $periodic),
                Rule::requiredIf($periodic),
                'nullable',
                'date',
                'after_or_equal:valid_from',
            ],
            'notes' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,odt,txt', 'max:10240'],
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
            'company_id.required' => 'Wybierz spółkę dla tego dokumentu.',
            'valid_to.required' => 'Data ważności do jest wymagana dla dokumentu okresowego.',
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
        Log::warning('EmployeeDocument validation failed', [
            'errors' => $validator->errors()->messages(),
            'employee_id' => $this->input('employee_id'),
        ]);

        parent::failedValidation($validator);
    }
}
