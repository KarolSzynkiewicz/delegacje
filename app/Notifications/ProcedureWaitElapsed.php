<?php

namespace App\Notifications;

use App\Enums\NotificationEvent;
use App\Models\ProcedureRun;
use App\Models\ProcedureRunStep;
use App\Models\User;

class ProcedureWaitElapsed extends ChronoNotification
{
    public function __construct(
        public readonly ProcedureRun $run,
        public readonly ProcedureRunStep $step,
        public readonly ?User $actor = null,
    ) {}

    public function event(): NotificationEvent
    {
        return NotificationEvent::ProcedureWaitElapsed;
    }

    public function toDatabase(object $notifiable): array
    {
        $this->run->loadMissing(['template', 'task']);
        $task = $this->run->task;
        $name = $this->run->template?->name ?: 'Procedura';

        return [
            'type' => 'procedure_wait_elapsed',
            'message' => 'Oczekiwanie minęło — procedura „'.$name.'” idzie dalej',
            'task_id' => $task?->id,
            'task_name' => $this->step->node_name ?: $name,
            'task_url' => $task ? route('tasks.show', $task) : url('/tasks2'),
            'context_name' => $name,
            'excerpt' => $this->step->node_name,
        ];
    }
}
