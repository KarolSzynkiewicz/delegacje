<?php

namespace App\WorkItems;

use App\Models\ProjectTask;
use App\Models\WorkItem;

class TaskLikeHandler implements HandlesWorkItem
{
    public function __construct(private ProjectTaskFields $fields) {}

    public function supports(GridField $field): bool
    {
        return $field !== GridField::Type;
    }

    public function writable(GridField $field): bool
    {
        return $this->supports($field) && $field !== GridField::Subtasks && $field !== GridField::Comments;
    }

    public function statusWidget(): StatusWidget
    {
        return StatusWidget::TaskSelect;
    }

    public function statusLabel(WorkItem $item): string
    {
        return $item->status->label();
    }

    public function write(WorkItem $item, GridField $field, mixed $value): void
    {
        $task = $item->source instanceof ProjectTask ? $item->source : null;
        if (! $task || ! $this->writable($field)) {
            return;
        }

        $this->fields->write($task, $field, $value);
    }

    public function expandable(WorkItem $item): bool
    {
        return true;
    }

    public function relocatable(GridField $field): bool
    {
        return $this->writable($field) && $field->isGroupable();
    }
}
