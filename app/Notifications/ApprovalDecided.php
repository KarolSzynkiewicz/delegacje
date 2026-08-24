<?php

namespace App\Notifications;

use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ApprovalDecided extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ApprovalRequest $approval,
        public readonly User $decidedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
