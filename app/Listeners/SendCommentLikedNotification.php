<?php

namespace App\Listeners;

use App\Events\CommentWasLiked;
use App\Models\User;
use App\Notifications\CommentLiked;
use App\Notifications\Notifier;

class SendCommentLikedNotification
{
    public function handle(CommentWasLiked $event): void
    {
        $author = User::query()->find($event->comment->user_id);
        Notifier::send($author, new CommentLiked($event->comment, $event->likedBy), $event->likedBy);
    }
}
