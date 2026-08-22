<?php

namespace App\WorkItems;

use App\Enums\WorkItemStatus;
use App\Models\TaskSubtask;
use App\Models\WorkItem;

class SubtaskHandler implements HandlesWorkItem
{
    public function supports(GridField $field): bool
    {
        return in_array($field, [
            GridField::Name,
            GridField::Status,
            GridField::AssignedTo,
            GridField::DueDate,
            GridField::Sprint,
            GridField::Category,
        ], true);
    }

    public function writable(GridField $field): bool
    {
        return in_array($field, [GridField::Name, GridField::Status, GridField::AssignedTo], true);
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
        $subtask = $item->source instanceof TaskSubtask ? $item->source : null;
        if (! $subtask || ! $this->writable($field)) {
            return;
        }

        $string = $value === null ? '' : (string) $value;

        match ($field) {
            GridField::Name => $subtask->update(['name' => trim($string) !== '' ? trim($string) : $subtask->name]),
            GridField::AssignedTo => $subtask->update(['assigned_to' => $string === '' ? null : (int) $string]),
            GridField::Status => $this->writeStatus($subtask, $string),
            default => null,
        };
    }

    private function writeStatus(TaskSubtask $subtask, string $status): void
    {
        if ($status === 'completed' && ! $subtask->is_completed) {
            $subtask->markCompleted();

            return;
        }

        if (in_array($status, ['pending', 'in_progress'], true) && $subtask->is_completed) {
            $subtask->markIncomplete();
        }
    }

    public function expandable(WorkItem $item): bool
    {
        return false;
    }

    public function relocatable(GridField $field): bool
    {
        return $field === GridField::AssignedTo;
    }
}
