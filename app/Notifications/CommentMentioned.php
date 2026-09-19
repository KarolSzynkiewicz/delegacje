<?php

namespace App\Notifications;

use App\Enums\NotificationEvent;
use App\Models\Comment;
use App\Models\User;

class CommentMentioned extends ChronoNotification
{
    public function __construct(
        public readonly Comment $comment,
        public readonly User $mentionedBy,
    ) {}

    public function event(): NotificationEvent
    {
        return NotificationEvent::CommentMentioned;
    }

    public function toDatabase(object $notifiable): array
    {
        $context = $this->comment->notificationContextLabel();

        return [
            'type' => 'comment_mentioned',
            'message' => $this->mentionedBy->name.' wspomniał(-a) o Tobie w komentarzu',
            'comment_id' => $this->comment->id,
            'commentable_type' => $this->comment->commentable_type instanceof \BackedEnum
                ? $this->comment->commentable_type->value
                : $this->comment->commentable_type,
            'commentable_id' => $this->comment->commentable_id,
            'context_name' => $context,
            'url' => $this->comment->urlWithCommentAnchor(),
            'excerpt' => $this->comment->bodyExcerpt(),
            'mentioned_by_id' => $this->mentionedBy->id,
            'mentioned_by_name' => $this->mentionedBy->name,
        ];
    }
}
