<?php

namespace App\Events;

use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovalAssigneeChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ApprovalRequest $approval,
        public ?User $actor,
    ) {}
}
