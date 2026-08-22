<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\CommentMention;
use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ProjectTask|TaskSubtask|CommentMention $task,
        public readonly User $assignedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        if ($this->task instanceof CommentMention) {
            $this->task->loadMissing(['comment.commentable']);
            $comment = $this->task->comment;
            $card = $comment?->commentableCard();

            return [
                'type' => 'task_assigned',
                'message' => $this->assignedBy->name.' przypisał(-a) Ci wzmiankę',
                'task_id' => null,
                'task_name' => $this->task->title,
                'task_url' => $comment?->urlWithCommentAnchor() ?? url('/tasks2'),
                'context_name' => $card['label'] ?? null,
                'excerpt' => $comment?->bodyExcerpt(),
                'assigned_by_id' => $this->assignedBy->id,
                'assigned_by_name' => $this->assignedBy->name,
            ];
        }

        if ($this->task instanceof TaskSubtask) {
            $this->task->loadMissing('task');
            $parent = $this->task->task;

            return [
                'type' => 'task_assigned',
                'message' => $this->assignedBy->name.' przypisał(-a) Ci podzadanie',
                'task_id' => $parent?->id,
                'task_name' => $this->task->name,
                'task_url' => $parent ? route('tasks.show', $parent) : url('/tasks2'),
                'context_name' => $parent?->name,
                'excerpt' => $this->task->name,
                'assigned_by_id' => $this->assignedBy->id,
                'assigned_by_name' => $this->assignedBy->name,
            ];
        }

        $this->task->loadMissing('subject');
        $card = $this->task->sourceCard();
        $subject = $this->task->subject;
        $excerpt = match (true) {
            $subject instanceof Comment => $subject->bodyExcerpt(),
            $subject instanceof TaskSubtask => filled($subject->name) ? (string) $subject->name : null,
            default => null,
        };

        return [
            'type' => 'task_assigned',
            'message' => $this->assignedBy->name.' przypisał(-a) Ci zadanie',
            'task_id' => $this->task->id,
            'task_name' => $this->task->name,
            'task_url' => $card['url'] ?? route('tasks.show', $this->task),
            'context_name' => $card['label'] ?? null,
            'excerpt' => $excerpt,
            'assigned_by_id' => $this->assignedBy->id,
            'assigned_by_name' => $this->assignedBy->name,
        ];
    }
}
