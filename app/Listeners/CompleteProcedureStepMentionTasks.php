<?php

namespace App\Listeners;

use App\Enums\TaskStatus;
use App\Events\ProcedureRunStepCompleted;
use App\Models\ProjectTask;

class CompleteProcedureStepMentionTasks
{
    public function handle(ProcedureRunStepCompleted $event): void
    {
        $leaving = $event->leavingNode;
        if ($leaving === null) {
            return;
        }

        $leavingType = $leaving['type'] ?? '';
        if (in_array($leavingType, ['start', 'end', 'note'], true)) {
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

        $ids = $event->run->variables['step_mention_tasks'][(string) $leavingAssigneeId] ?? [];
        if ($ids === []) {
            return;
        }

        ProjectTask::query()
            ->whereIn('id', $ids)
            ->whereNotIn('status', [TaskStatus::COMPLETED, TaskStatus::CANCELLED])
            ->get()
            ->each(fn (ProjectTask $task) => $task->markCompleted());
    }
}
