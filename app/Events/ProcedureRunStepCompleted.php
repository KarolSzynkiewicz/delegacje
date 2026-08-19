<?php

namespace App\Events;

use App\Models\ProcedureRun;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProcedureRunStepCompleted
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, mixed>|null  $leavingNode
     * @param  array<string, mixed>|null  $nextNode
     */
    public function __construct(
        public ProcedureRun $run,
        public ?array $leavingNode,
        public ?array $nextNode = null,
    ) {}
}
