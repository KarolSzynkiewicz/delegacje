<?php

namespace App\WorkItems;

use App\Models\WarehouseDispatch;
use App\Models\WorkItem;

class DispatchHandler implements HandlesWorkItem
{
    public function __construct(private ProjectTaskFields $fields) {}

    public function supports(GridField $field): bool
    {
        return in_array($field, [
            GridField::Name,
            GridField::Status,
            GridField::Sprint,
            GridField::AssignedTo,
            GridField::DueDate,
        ], true);
    }

    public function writable(GridField $field): bool
    {
        return in_array($field, [GridField::Sprint, GridField::AssignedTo], true);
    }

    public function statusWidget(): StatusWidget
    {
        return StatusWidget::Badge;
    }

    public function statusLabel(WorkItem $item): string
    {
        $source = $item->source;
        if ($source instanceof WarehouseDispatch) {
            return $source->statusLabel();
        }

        return $item->status->label();
    }

    public function write(WorkItem $item, GridField $field, mixed $value): void
    {
        $task = $item->editableProjectTask();
        if (! $task || ! $this->writable($field)) {
            return;
        }

        $this->fields->write($task, $field, $value);
    }

    public function expandable(WorkItem $item): bool
    {
        return false;
    }

    public function relocatable(GridField $field): bool
    {
        return $this->writable($field) && $field->isGroupable();
    }
}
