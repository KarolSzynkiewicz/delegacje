<?php

namespace App\Observers;

use App\Events\CommentWasLiked;
use App\Models\CommentLike;

class DispatchesCommentLikeEvents
{
    public function created(CommentLike $like): void
    {
        $like->loadMissing(['comment', 'user']);
        if (! $like->comment || ! $like->user) {
            return;
        }

        if ((int) $like->comment->user_id === (int) $like->user_id) {
            return;
        }

        CommentWasLiked::dispatch($like->comment, $like->user);
    }
}
