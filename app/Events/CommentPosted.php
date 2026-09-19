<?php

namespace App\Events;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentPosted
{
    use Dispatchable, SerializesModels;

    /**
     * @param  list<int>  $mentionUserIds
     */
    public function __construct(
        public Comment $comment,
        public User $author,
        public array $mentionUserIds,
    ) {}
}
