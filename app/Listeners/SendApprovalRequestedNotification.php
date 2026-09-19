<?php

namespace App\Listeners;

use App\Events\ApprovalAssigneeChanged;
use App\Notifications\ApprovalRequested;
use App\Notifications\Notifier;

class SendApprovalRequestedNotification
{
    public function handle(ApprovalAssigneeChanged $event): void
    {
        $event->approval->loadMissing(['approver', 'createdBy']);
        $actor = $event->actor ?? $event->approval->createdBy;
        if (! $actor) {
            return;
        }

        Notifier::send(
            $event->approval->approver,
            new ApprovalRequested($event->approval, $actor),
            $actor,
        );
    }
}
