<?php

namespace App\WorkItems;

use App\Enums\TaskStatus;
use App\Models\ProjectTask;
use App\Models\Sprint;
use App\Models\User;
use App\Notifications\TaskAssigned;

class ProjectTaskFields
{
    public function write(ProjectTask $task, GridField $field, mixed $value): void
    {
        $string = $value === null ? '' : (string) $value;

        match ($field) {
            GridField::Name => $task->update(['name' => trim($string) !== '' ? trim($string) : $task->name]),
            GridField::Status => $this->writeStatus($task, $string),
            GridField::Sprint => $this->writeSprint($task, $string),
            GridField::Category => $task->update(['category' => $string === '' ? null : mb_substr(trim($string), 0, 255)]),
            GridField::AssignedTo => $this->writeAssignee($task, $string),
            GridField::Priority => $this->writePriority($task, $string),
            GridField::DueDate => $task->update(['due_date' => $string === '' ? null : $string]),
            GridField::Description => $task->update(['description' => trim($string) !== '' ? trim($string) : null]),
            default => null,
        };
    }

    public function writeStatus(ProjectTask $task, string $status): void
    {
        match ($status) {
            'in_progress' => $task->markInProgress(),
            'completed' => $task->markCompleted(),
            'cancelled' => $task->cancel(),
            'pending' => $task->update(['status' => TaskStatus::PENDING, 'completed_at' => null]),
            default => null,
        };
    }

    public function writeSprint(ProjectTask $task, string $value): void
    {
        if ($value === '') {
            $task->update(['sprint_id' => null, 'sprint_position' => null]);

            return;
        }

        $sprintId = (int) $value;
        if ($sprintId < 1 || ! Sprint::query()->where('id', $sprintId)->exists()) {
            return;
        }

        $position = (int) ProjectTask::query()->where('sprint_id', $sprintId)->max('sprint_position') + 1;
        $task->update([
            'sprint_id' => $sprintId,
            'sprint_position' => $position,
        ]);
    }

    public function writeAssignee(ProjectTask $task, string $value): void
    {
        $newAssignee = $value === '' ? null : (int) $value;
        if ($newAssignee && ! User::query()->where('id', $newAssignee)->exists()) {
            return;
        }

        $previous = $task->assigned_to;
        $task->update(['assigned_to' => $newAssignee]);

        if ($newAssignee && $newAssignee !== $previous && $newAssignee !== auth()->id()) {
            User::query()->find($newAssignee)?->notify(new TaskAssigned($task->fresh(), auth()->user()));
        }
    }

    public function writePriority(ProjectTask $task, string $value): void
    {
        if ($value === '') {
            $task->update(['priority' => null]);

            return;
        }

        $priority = (int) $value;
        if (! in_array($priority, [1, 2, 3, 4, 5], true)) {
            return;
        }

        $task->update(['priority' => $priority]);
    }
}
