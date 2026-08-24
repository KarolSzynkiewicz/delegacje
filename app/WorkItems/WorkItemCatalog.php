<?php

namespace App\WorkItems;

use App\Enums\WorkItemType;

class WorkItemCatalog
{
    public static function handler(WorkItemType $type): HandlesWorkItem
    {
        return match ($type) {
            WorkItemType::Task => app(TaskLikeHandler::class),
            WorkItemType::FollowUp => app(FollowUpHandler::class),
            WorkItemType::Callback => app(CheckboxTaskHandler::class),
            WorkItemType::Subtask => app(SubtaskHandler::class),
            WorkItemType::ProcedureRun => app(ProcedureRunHandler::class),
            WorkItemType::Dispatch => app(DispatchHandler::class),
            WorkItemType::Approval => app(ApprovalHandler::class),
        };
    }
}
