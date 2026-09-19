<?php

namespace App\Notifications;

use App\Enums\NotificationEvent;
use App\Models\Comment;
use App\Models\ProjectTask;
use App\Models\User;

class TaskCommentAdded extends ChronoNotification
{
    public function __construct(
        public readonly ProjectTask $task,
        public readonly Comment $comment,
        public readonly User $commentAuthor,
    ) {}

    public function event(): NotificationEvent
    {
        return NotificationEvent::TaskCommentAdded;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'task_comment_added',
            'message' => $this->commentAuthor->name.' dodał(-a) komentarz do zadania',
            'task_id' => $this->task->id,
            'task_name' => $this->task->name,
            'task_url' => $this->comment->urlWithCommentAnchor(),
            'context_name' => $this->task->name,
            'excerpt' => $this->comment->bodyExcerpt(),
            'comment_id' => $this->comment->id,
            'comment_author_id' => $this->commentAuthor->id,
            'comment_author_name' => $this->commentAuthor->name,
        ];
    }
}
