<?php

namespace App\Events;

use App\Models\CommentMention;
use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskAssigneeChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ProjectTask|TaskSubtask|CommentMention $subject,
        public ?int $previousAssigneeId,
        public ?User $actor,
    ) {}
}
