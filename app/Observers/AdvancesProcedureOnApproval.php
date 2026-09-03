<?php

namespace App\Observers;

use App\Models\ApprovalRequest;
use App\Services\ProcedureRunService;

class AdvancesProcedureOnApproval
{
    public function updated(ApprovalRequest $approval): void
    {
        if (! $approval->wasChanged('decision') || $approval->decision === null) {
            return;
        }

        if ($approval->procedure_run_id === null) {
            return;
        }

        app(ProcedureRunService::class)->resumeFromApproval($approval);
    }
}
