<?php

namespace App\Notifications;

use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ApprovalRequested extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ApprovalRequest $approval,
        public readonly User $requestedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
