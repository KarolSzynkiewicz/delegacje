<?php

namespace App\Traits;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasComments
{
    /**
     * Get all comments for this model.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Add a comment to this model.
     */
    public function addComment(?string $body, \App\Models\User $user, ?Comment $parent = null): Comment
    {
        return $this->comments()->create([
            'user_id' => $user->id,
            'body' => $body,
            'parent_id' => $parent?->id,
        ]);
    }

    /**
     * Get the count of comments.
     */
    public function commentsCount(): int
    {
        return $this->comments()->count();
    }
}
