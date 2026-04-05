<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskCommentAdded extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ProjectTask $task,
        public readonly Comment $comment,
        public readonly User $commentAuthor,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'task_comment_added',
            'message' => $this->commentAuthor->name.' dodał(-a) komentarz do zadania',
            'task_id' => $this->task->id,
            'task_name' => $this->task->name,
            'task_url' => route('tasks.show', $this->task),
            'comment_id' => $this->comment->id,
            'comment_author_id' => $this->commentAuthor->id,
            'comment_author_name' => $this->commentAuthor->name,
        ];
    }
}
