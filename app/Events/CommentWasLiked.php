<?php

namespace App\Events;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentWasLiked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Comment $comment,
        public User $likedBy,
    ) {}
}
