<?php

namespace App\Http\Requests;

class UpdateForumPostRequest extends StoreForumPostRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'remove_image' => ['sometimes', 'boolean'],
        ]);
    }
}
