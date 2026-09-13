<?php

namespace App\Mcp\Support;

use App\Enums\WorkItemType;
use App\Models\WorkItem;
use App\Support\EntityLinks;

final class WorkItemPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function listItem(WorkItem $item): array
    {
        $item->loadMissing(['assignee:id,name', 'createdBy:id,name', 'sprint:id,name']);

        $type = $item->type instanceof WorkItemType ? $item->type : WorkItemType::tryFrom((string) $item->type);

        return [
            'work_item_id' => $item->id,
            'type' => $type?->value,
            'type_label' => $type?->label(),
            'source_type' => $item->source_type,
            'source_id' => $item->source_id,
            'task_id' => self::taskId($item, $type),
            'subtask_id' => $type === WorkItemType::Subtask ? $item->source_id : null,
            'procedure_run_id' => $type === WorkItemType::ProcedureRun ? $item->source_id : null,
            'approval_id' => $type === WorkItemType::Approval ? $item->source_id : null,
            'title' => $item->title,
            'status' => $item->status?->value,
            'status_label' => $item->statusLabel(),
            'category' => $item->category,
            'priority' => $item->priority,
            'due_date' => $item->due_at?->toDateString(),
            'assignee' => $item->assignee?->name,
            'created_by' => $item->createdBy?->name,
            'sprint' => $item->sprint ? [
                'id' => $item->sprint->id,
                'name' => $item->sprint->name,
                'url' => EntityLinks::sprint($item->sprint),
            ] : null,
            'url' => EntityLinks::workItem($item),
            'next' => self::next($item, $type),
        ];
    }

    private static function taskId(WorkItem $item, ?WorkItemType $type): ?int
    {
        return match ($type) {
            WorkItemType::Task, WorkItemType::Meeting, WorkItemType::Callback => $item->source_id,
            default => null,
        };
    }

    /**
     * @return array{tool: string|null, hint: string}
     */
    private static function next(WorkItem $item, ?WorkItemType $type): array
    {
        return match ($type) {
            WorkItemType::Task, WorkItemType::Meeting, WorkItemType::Callback => [
                'tool' => 'get_task',
                'hint' => 'Szczegóły karty: get_task z task_id='.$item->source_id,
            ],
            WorkItemType::ProcedureRun => [
                'tool' => 'get_procedure_run',
                'hint' => 'Aktualny krok: get_procedure_run z run_id='.$item->source_id,
            ],
            WorkItemType::Subtask => [
                'tool' => 'update_subtask',
                'hint' => 'Odhacz / zmień krok: update_subtask z subtask_id='.$item->source_id,
            ],
            WorkItemType::Approval => [
                'tool' => null,
                'hint' => 'Wniosek o zatwierdzenie – otwórz url (asystent nie klika decyzji).',
            ],
            default => [
                'tool' => null,
                'hint' => 'Otwórz url pozycji.',
            ],
        };
    }
}
