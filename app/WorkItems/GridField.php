<?php

namespace App\WorkItems;

enum GridField: string
{
    case Name = 'name';
    case Type = 'type';
    case Status = 'status';
    case Sprint = 'sprint';
    case Category = 'category';
    case AssignedTo = 'assigned_to';
    case Priority = 'priority';
    case DueDate = 'due_date';
    case Subtasks = 'subtasks';
    case Comments = 'comments';
    case Description = 'description';

    public function isGroupable(): bool
    {
        return in_array($this, [
            self::Status,
            self::Sprint,
            self::Category,
            self::AssignedTo,
            self::Priority,
        ], true);
    }
}
