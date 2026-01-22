<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
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
            'commentable_type' => ['required', 'string', 'in:project,project_task,vehicle,accommodation'],
            'commentable_id' => ['required', 'integer'],
            'body' => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'commentable_type.required' => 'Typ komentarza jest wymagany.',
            'commentable_type.in' => 'Nieprawidłowy typ komentarza.',
            'commentable_id.required' => 'ID zasobu jest wymagane.',
            'body.required' => 'Treść komentarza jest wymagana.',
            'body.max' => 'Komentarz nie może być dłuższy niż 5000 znaków.',
        ];
    }
}
