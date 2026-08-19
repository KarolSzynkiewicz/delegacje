<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CommentLiked extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Comment $comment,
        public readonly User $likedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'comment_liked',
            'message' => $this->likedBy->name.' polubił(a) Twój komentarz',
            'comment_id' => $this->comment->id,
            'liked_by_id' => $this->likedBy->id,
            'liked_by_name' => $this->likedBy->name,
            'resource_url' => $this->comment->urlWithCommentAnchor(),
            'context_name' => $this->comment->notificationContextLabel(),
            'excerpt' => $this->comment->bodyExcerpt(),
        ];
    }
}
