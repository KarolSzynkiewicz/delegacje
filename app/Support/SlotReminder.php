<?php

namespace App\Support;

use App\Models\ProjectTask;
use App\Models\User;
use App\Models\WorkItemTimeBlock;

final class SlotReminder
{
    public static function toggle(string $key, User $actor): void
    {
        if (! preg_match('/^(block|meeting):(\d+)$/', $key, $matches)) {
            abort(404);
        }

        if ($matches[1] === 'block') {
            $block = WorkItemTimeBlock::query()->with('workItem')->findOrFail((int) $matches[2]);
            abort_unless(self::canToggleBlock($actor, $block), 403);
            $block->update(['remind' => ! $block->remind]);

            return;
        }

        $task = ProjectTask::query()->findOrFail((int) $matches[2]);
        abort_unless($task->isMeeting() && self::canToggleMeeting($actor, $task), 403);
        $task->update(['remind' => ! $task->remind]);
    }

    public static function canToggleBlock(User $actor, WorkItemTimeBlock $block): bool
    {
        return $actor->isAdmin()
            || (int) $block->user_id === (int) $actor->id
            || (int) $block->created_by_id === (int) $actor->id
            || (int) ($block->workItem?->assignee_id) === (int) $actor->id;
    }

    public static function canToggleMeeting(User $actor, ProjectTask $task): bool
    {
        $participants = array_map('intval', $task->participant_ids ?? []);

        return $actor->isAdmin()
            || (int) $task->assigned_to === (int) $actor->id
            || (int) $task->created_by === (int) $actor->id
            || in_array((int) $actor->id, $participants, true);
    }
}
