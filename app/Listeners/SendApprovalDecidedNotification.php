<?php

namespace App\Listeners;

use App\Events\ApprovalDecisionRecorded;
use App\Notifications\ApprovalDecided;
use App\Notifications\Notifier;

class SendApprovalDecidedNotification
{
    public function handle(ApprovalDecisionRecorded $event): void
    {
        $event->approval->loadMissing(['createdBy', 'decidedBy']);
        $actor = $event->actor ?? $event->approval->decidedBy;
        $requester = $event->approval->createdBy;
        if (! $actor || ! $requester) {
            return;
        }

        Notifier::send(
            $requester,
            new ApprovalDecided($event->approval, $actor),
            $actor,
        );
    }
}
