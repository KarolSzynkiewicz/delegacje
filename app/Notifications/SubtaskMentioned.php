<?php

namespace App\Notifications;

use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubtaskMentioned extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ProjectTask $task,
        public readonly TaskSubtask $subtask,
        public readonly User $mentionedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'subtask_mentioned',
            'message' => $this->mentionedBy->name.' wspomniał(-a) o Tobie w podzadaniu',
            'task_id' => $this->task->id,
            'task_name' => $this->task->name,
            'task_url' => route('tasks.show', $this->task),
            'context_name' => $this->task->name,
            'subtask_id' => $this->subtask->id,
            'subtask_name' => $this->subtask->name,
            'excerpt' => $this->subtask->name,
            'mentioned_by_id' => $this->mentionedBy->id,
            'mentioned_by_name' => $this->mentionedBy->name,
        ];
    }
}
