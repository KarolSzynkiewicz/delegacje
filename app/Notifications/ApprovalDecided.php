<?php

namespace App\Notifications;

use App\Enums\NotificationEvent;
use App\Models\ApprovalRequest;
use App\Models\User;

class ApprovalDecided extends ChronoNotification
{
    public function __construct(
        public readonly ApprovalRequest $approval,
        public readonly User $decidedBy,
    ) {}

    public function event(): NotificationEvent
    {
        return NotificationEvent::ApprovalDecided;
    }

    public function toDatabase(object $notifiable): array
    {
        $decision = $this->approval->decision?->label() ?? 'decyzja';

        return [
            'type' => 'approval_decided',
            'message' => $this->decidedBy->name.' oznaczył(-a) wniosek jako '.$decision,
            'task_name' => $this->approval->name,
            'task_url' => route('approval-requests.show', $this->approval),
            'excerpt' => $this->approval->description,
            'decided_by_id' => $this->decidedBy->id,
            'decided_by_name' => $this->decidedBy->name,
        ];
    }
}
