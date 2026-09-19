<?php

namespace App\Observers;

use App\Events\ApprovalAssigneeChanged;
use App\Events\ApprovalDecisionRecorded;
use App\Models\ApprovalRequest;
use App\Support\DomainActor;

class DispatchesApprovalEvents
{
    public function created(ApprovalRequest $approval): void
    {
        ApprovalAssigneeChanged::dispatch($approval, DomainActor::user() ?? $approval->createdBy);
    }

    public function updated(ApprovalRequest $approval): void
    {
        if ($approval->wasChanged('approver_id')) {
            ApprovalAssigneeChanged::dispatch($approval, DomainActor::user() ?? $approval->createdBy);
        }

        if ($approval->wasChanged('decision') && $approval->decision !== null) {
            ApprovalDecisionRecorded::dispatch(
                $approval,
                $approval->decidedBy ?? DomainActor::user(),
            );
        }
    }
}
