<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ProjectTask $task,
        public readonly User $assignedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
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
