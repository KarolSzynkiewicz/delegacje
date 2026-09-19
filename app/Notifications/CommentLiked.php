<?php

namespace App\Notifications;

use App\Enums\NotificationEvent;
use App\Models\Comment;
use App\Models\User;

class CommentLiked extends ChronoNotification
{
    public function __construct(
        public readonly Comment $comment,
        public readonly User $likedBy,
    ) {}

    public function event(): NotificationEvent
    {
        return NotificationEvent::CommentLiked;
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
