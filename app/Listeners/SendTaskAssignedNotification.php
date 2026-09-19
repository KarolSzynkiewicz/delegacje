<?php

namespace App\Listeners;

use App\Events\TaskAssigneeChanged;
use App\Models\User;
use App\Notifications\Notifier;
use App\Notifications\TaskAssigned;

class SendTaskAssignedNotification
{
    public function handle(TaskAssigneeChanged $event): void
    {
        $actor = $event->actor;
        if (! $actor) {
            return;
        }

        $assigneeId = (int) $event->subject->assigned_to;
        if ($assigneeId < 1 || $assigneeId === (int) $event->previousAssigneeId) {
            return;
        }

        $assignee = User::query()->find($assigneeId);
        Notifier::send($assignee, new TaskAssigned($event->subject, $actor), $actor);
    }
}
