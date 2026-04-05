<?php

namespace App\Notifications;

use App\Models\ProjectTask;
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
        return [
            'type' => 'task_assigned',
            'message' => $this->assignedBy->name . ' przypisał(-a) Ci zadanie',
            'task_id' => $this->task->id,
            'task_name' => $this->task->name,
            'task_url' => route('tasks.show', $this->task),
            'assigned_by_id' => $this->assignedBy->id,
            'assigned_by_name' => $this->assignedBy->name,
        ];
    }
}
