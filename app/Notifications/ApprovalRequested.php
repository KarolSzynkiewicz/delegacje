<?php

namespace App\Notifications;

use App\Enums\NotificationEvent;
use App\Models\ApprovalRequest;
use App\Models\User;

class ApprovalRequested extends ChronoNotification
{
    public function __construct(
        public readonly ApprovalRequest $approval,
        public readonly User $requestedBy,
    ) {}

    public function event(): NotificationEvent
    {
        return NotificationEvent::ApprovalRequested;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'approval_requested',
            'message' => $this->requestedBy->name.' prosi Cię o zatwierdzenie',
            'task_name' => $this->approval->name,
            'task_url' => route('approval-requests.show', $this->approval),
            'excerpt' => $this->approval->description,
            'assigned_by_id' => $this->requestedBy->id,
            'assigned_by_name' => $this->requestedBy->name,
        ];
    }
}
