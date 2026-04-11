<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'commentable_type' => ['required', 'string', 'in:project,project_task,vehicle,accommodation,logistics_event,location'],
            'commentable_id' => ['required', 'integer'],
            'body' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:15'],
            'attachments.*' => ['file', 'max:15360', 'mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,txt,zip'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $body = trim((string) $this->input('body', ''));
            $files = $this->file('attachments', []);
            if (! is_array($files)) {
                $files = $files ? [$files] : [];
            }
            $hasFile = collect($files)->filter(fn ($f) => $f && $f->isValid())->isNotEmpty();
            if ($body === '' && ! $hasFile) {
                $v->errors()->add('body', 'Dodaj treść wpisu albo załącznik.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'commentable_type.required' => 'Typ komentarza jest wymagany.',
            'commentable_type.in' => 'Nieprawidłowy typ komentarza.',
            'commentable_id.required' => 'ID zasobu jest wymagane.',
            'body.max' => 'Komentarz nie może być dłuższy niż 5000 znaków.',
        ];
    }
}
