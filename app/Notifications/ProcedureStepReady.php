<?php

namespace App\Notifications;

use App\Enums\NotificationEvent;
use App\Models\ProcedureRun;
use App\Models\User;

class ProcedureStepReady extends ChronoNotification
{
    /**
     * @param  array<string, mixed>  $node
     */
    public function __construct(
        public readonly ProcedureRun $run,
        public readonly array $node,
        public readonly User $requestedBy,
    ) {}

    public function event(): NotificationEvent
    {
        return NotificationEvent::ProcedureStepReady;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $this->run->loadMissing(['template', 'task']);
        $task = $this->run->task;
        $name = $this->run->template?->name ?: 'Procedura';
        $stepName = trim((string) ($this->node['name'] ?? '')) ?: 'krok procedury';

        return [
            'type' => $this->event()->value,
            'message' => $this->requestedBy->name.' przekazał(-a) Ci krok procedury',
            'task_id' => $task?->id,
            'task_name' => $stepName,
            'task_url' => $task ? route('tasks.show', $task) : url('/tasks2'),
            'context_name' => $name,
            'excerpt' => $stepName,
            'assigned_by_id' => $this->requestedBy->id,
            'assigned_by_name' => $this->requestedBy->name,
        ];
    }
}
