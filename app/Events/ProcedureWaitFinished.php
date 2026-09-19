<?php

namespace App\Events;

use App\Models\ProcedureRun;
use App\Models\ProcedureRunStep;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProcedureWaitFinished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ProcedureRun $run,
        public ProcedureRunStep $step,
    ) {}
}
