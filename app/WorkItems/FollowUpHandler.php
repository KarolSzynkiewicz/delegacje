<?php

namespace App\WorkItems;

use App\Enums\WorkItemStatus;
use App\Models\CommentMention;
use App\Models\Sprint;
use App\Models\User;
use App\Models\WorkItem;
use App\Notifications\TaskAssigned;

class FollowUpHandler implements HandlesWorkItem
{
    public function supports(GridField $field): bool
    {
        return in_array($field, [
            GridField::Name,
            GridField::Status,
            GridField::Sprint,
            GridField::AssignedTo,
            GridField::Priority,
            GridField::DueDate,
        ], true);
    }

    public function writable(GridField $field): bool
    {
        return $this->supports($field);
    }

    public function statusWidget(): StatusWidget
    {
        return StatusWidget::BinarySelect;
    }

    public function statusLabel(WorkItem $item): string
    {
        return $item->status === WorkItemStatus::Completed ? 'Ukończone' : 'Oczekujące';
    }

    public function write(WorkItem $item, GridField $field, mixed $value): void
    {
        $mention = $item->source instanceof CommentMention ? $item->source : null;
        if (! $mention || ! $this->writable($field)) {
            return;
        }

        $string = $value === null ? '' : (string) $value;

        match ($field) {
            GridField::Name => $mention->update(['title' => trim($string) !== '' ? trim($string) : $mention->title]),
            GridField::Status => $this->writeStatus($mention, $string),
            GridField::AssignedTo => $this->writeAssignee($mention, $string),
            GridField::Sprint => $this->writeSprint($item, $string),
            GridField::Priority => $this->writePriority($item, $string),
            GridField::DueDate => $item->update(['due_at' => $string === '' ? null : $string]),
            default => null,
        };
    }

    private function writeStatus(CommentMention $mention, string $status): void
    {
        if ($status === 'completed') {
            $mention->markCompleted();

            return;
        }

        if (in_array($status, ['pending', 'in_progress'], true) && $mention->isCompleted()) {
            $mention->reopen();
        }
    }

    private function writeAssignee(CommentMention $mention, string $value): void
    {
        $newAssignee = $value === '' ? null : (int) $value;
        if (! $newAssignee || ! User::query()->where('id', $newAssignee)->exists()) {
            return;
        }

        if ($newAssignee === (int) $mention->assigned_to) {
            return;
        }

        $taken = CommentMention::query()
            ->where('comment_id', $mention->comment_id)
            ->where('assigned_to', $newAssignee)
            ->whereKeyNot($mention->id)
            ->exists();
        if ($taken) {
            return;
        }

        $mention->update(['assigned_to' => $newAssignee]);

        if ($newAssignee !== auth()->id()) {
            User::query()->find($newAssignee)?->notify(new TaskAssigned($mention->fresh() ?? $mention, auth()->user()));
        }
    }

    private function writeSprint(WorkItem $item, string $value): void
    {
        if ($value === '') {
            $item->update(['sprint_id' => null]);

            return;
        }

        $sprintId = (int) $value;
        if ($sprintId < 1 || ! Sprint::query()->where('id', $sprintId)->exists()) {
            return;
        }

        $item->update(['sprint_id' => $sprintId]);
    }

    private function writePriority(WorkItem $item, string $value): void
    {
        if ($value === '') {
            $item->update(['priority' => null]);

            return;
        }

        $priority = (int) $value;
        if (! in_array($priority, [1, 2, 3, 4, 5], true)) {
            return;
        }

        $item->update(['priority' => $priority]);
    }

    public function expandable(WorkItem $item): bool
    {
        return false;
    }

    public function relocatable(GridField $field): bool
    {
        return in_array($field, [GridField::Sprint, GridField::AssignedTo, GridField::Priority], true);
    }
}
