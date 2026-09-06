<?php

namespace App\Http\Requests;

use App\Models\ForumPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreForumPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->input('blocks');
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $this->merge(['blocks' => is_array($decoded) ? $decoded : []]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:80'],
            'blocks' => ['required', 'array', 'min:1', 'max:30'],
            'blocks.*.type' => ['required', 'in:text,image'],
            'blocks.*.content' => ['nullable', 'string', 'max:20000'],
            'blocks.*.path' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:240'],
            'image' => ['nullable', 'image', 'max:2048', 'mimes:jpeg,jpg,png,gif,webp'],
            'pinned' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $blocks = ForumPost::normalizeBlocks($this->input('blocks'));
            if ($blocks === []) {
                $v->errors()->add('blocks', 'Dodaj choć jedną sekcję: tekst albo zdjęcie.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Podaj tytuł wątku.',
            'title.max' => 'Tytuł może mieć najwyżej 80 znaków.',
            'blocks.required' => 'Dodaj treść wątku.',
            'image.max' => 'Okładka może mieć najwyżej 2 MB.',
            'image.mimes' => 'Dozwolone formaty okładki: JPEG, PNG, GIF, WEBP.',
        ];
    }
}
