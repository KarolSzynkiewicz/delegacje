<?php

namespace App\Observers;

use App\Models\ApprovalRequest;
use App\Notifications\ApprovalDecided;
use App\Notifications\ApprovalRequested;

class NotifiesApprovalAssignee
{
    public function created(ApprovalRequest $approval): void
    {
        $this->notifyApprover($approval);
    }

    public function updated(ApprovalRequest $approval): void
    {
        if ($approval->wasChanged('approver_id')) {
            $this->notifyApprover($approval);
        }

        if ($approval->wasChanged('decision') && $approval->decision !== null) {
            $this->notifyRequester($approval);
        }
    }

    private function notifyApprover(ApprovalRequest $approval): void
    {
        $approval->loadMissing(['approver', 'createdBy']);
        $approver = $approval->approver;
        $actor = auth()->user() ?? $approval->createdBy;
        if (! $approver || ! $actor || (int) $approver->id === (int) $actor->id) {
            return;
        }

        $approver->notify(new ApprovalRequested($approval, $actor));
    }

    private function notifyRequester(ApprovalRequest $approval): void
    {
        $approval->loadMissing(['createdBy', 'decidedBy']);
        $requester = $approval->createdBy;
        $actor = $approval->decidedBy ?? auth()->user();
        if (! $requester || ! $actor || (int) $requester->id === (int) $actor->id) {
            return;
        }

        $requester->notify(new ApprovalDecided($approval, $actor));
    }
}
