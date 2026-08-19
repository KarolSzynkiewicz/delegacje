<?php

namespace App\Events;

use App\Models\ProcedureRun;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProcedureRunStepEntered
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>|null  $previousNode
     */
    public function __construct(
        public ProcedureRun $run,
        public array $node,
        public ?array $previousNode = null,
    ) {}
}
