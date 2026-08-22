<?php

namespace App\WorkItems;

use App\Enums\WorkItemStatus;
use App\Models\ProjectTask;
use App\Models\WorkItem;

class CheckboxTaskHandler implements HandlesWorkItem
{
    public function __construct(private ProjectTaskFields $fields) {}

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
        $task = $item->source instanceof ProjectTask ? $item->source : null;
        if (! $task || ! $this->writable($field)) {
            return;
        }

        if ($field === GridField::Status) {
            $this->writeStatus($task, $value === null ? '' : (string) $value);

            return;
        }

        $this->fields->write($task, $field, $value);
    }

    private function writeStatus(ProjectTask $task, string $status): void
    {
        if ($status === 'completed') {
            $task->markCompleted();

            return;
        }

        if (in_array($status, ['pending', 'in_progress'], true) && $task->status->value === 'completed') {
            $task->reopen();
        }
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
