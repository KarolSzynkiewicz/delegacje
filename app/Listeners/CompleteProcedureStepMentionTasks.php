<?php

namespace App\Listeners;

use App\Enums\WorkItemStatus;
use App\Events\ProcedureRunStepCompleted;
use App\Models\CommentMention;

class CompleteProcedureStepMentionTasks
{
    public function handle(ProcedureRunStepCompleted $event): void
    {
        $leaving = $event->leavingNode;
        if ($leaving === null) {
            return;
        }

        $leavingType = $leaving['type'] ?? '';
        if (in_array($leavingType, ['start', 'end', 'note', 'approval'], true)) {
            return;
        }

        $leavingAssigneeId = (int) ($leaving['assigned_user_id'] ?? 0);
        if ($leavingAssigneeId <= 0) {
            return;
        }

        $nextAssigneeId = (int) ($event->nextNode['assigned_user_id'] ?? 0);
        if ($nextAssigneeId === $leavingAssigneeId) {
            return;
        }

        $key = (string) $leavingAssigneeId;
        $ids = $event->run->variables['step_mentions'][$key]
            ?? $event->run->variables['step_mention_tasks'][$key]
            ?? [];
        if ($ids === []) {
            return;
        }

        CommentMention::query()
            ->whereIn('id', $ids)
            ->where('status', '!=', WorkItemStatus::Completed)
            ->get()
            ->each(fn (CommentMention $mention) => $mention->markCompleted());
    }
}
