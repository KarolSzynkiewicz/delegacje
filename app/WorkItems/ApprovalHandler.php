<?php

namespace App\WorkItems;

use App\Enums\ApprovalDecision;
use App\Models\ApprovalRequest;
use App\Models\Sprint;
use App\Models\User;
use App\Models\WorkItem;

class ApprovalHandler implements HandlesWorkItem
{
    public function supports(GridField $field): bool
    {
        return in_array($field, [
            GridField::Name,
            GridField::Status,
            GridField::Sprint,
            GridField::Category,
            GridField::AssignedTo,
            GridField::Priority,
            GridField::DueDate,
        ], true);
    }

    public function writable(GridField $field): bool
    {
        return in_array($field, [
            GridField::Name,
            GridField::Sprint,
            GridField::Category,
            GridField::AssignedTo,
            GridField::Priority,
            GridField::DueDate,
        ], true);
    }

    public function statusWidget(): StatusWidget
    {
        return StatusWidget::Badge;
    }

    public function statusLabel(WorkItem $item): string
    {
        return match ($item->approvalDecision()) {
            ApprovalDecision::Approved => 'Zatwierdzone',
            ApprovalDecision::Rejected => 'Odrzucone',
            default => 'Oczekuje',
        };
    }

    public function write(WorkItem $item, GridField $field, mixed $value): void
    {
        $approval = $item->source instanceof ApprovalRequest ? $item->source : null;
        if (! $approval || ! $this->writable($field)) {
            return;
        }

        $string = $value === null ? '' : (string) $value;

        match ($field) {
            GridField::Name => $approval->update(['name' => trim($string) !== '' ? trim($string) : $approval->name]),
            GridField::AssignedTo => $this->writeApprover($approval, $string),
            GridField::Sprint => $this->writeSprint($approval, $string),
            GridField::Category => $approval->update(['category' => $string === '' ? null : mb_substr(trim($string), 0, 255)]),
            GridField::Priority => $this->writePriority($approval, $string),
            GridField::DueDate => $approval->update(['due_at' => $string === '' ? null : $string]),
            default => null,
        };
    }

    private function writeApprover(ApprovalRequest $approval, string $value): void
    {
        $newApprover = $value === '' ? null : (int) $value;
        if (! $newApprover || ! User::query()->where('id', $newApprover)->exists()) {
            return;
        }

        $approval->update(['approver_id' => $newApprover]);
    }

    private function writeSprint(ApprovalRequest $approval, string $value): void
    {
        if ($value === '') {
            $approval->update(['sprint_id' => null]);

            return;
        }

        $sprintId = (int) $value;
        if ($sprintId < 1 || ! Sprint::query()->where('id', $sprintId)->exists()) {
            return;
        }

        $approval->update(['sprint_id' => $sprintId]);
    }

    private function writePriority(ApprovalRequest $approval, string $value): void
    {
        if ($value === '') {
            $approval->update(['priority' => null]);

            return;
        }

        $priority = (int) $value;
        if (! in_array($priority, [1, 2, 3, 4, 5], true)) {
            return;
        }

        $approval->update(['priority' => $priority]);
    }

    public function expandable(WorkItem $item): bool
    {
        return false;
    }

    public function relocatable(GridField $field): bool
    {
        return in_array($field, [
            GridField::Sprint,
            GridField::Category,
            GridField::AssignedTo,
            GridField::Priority,
        ], true);
    }
}
