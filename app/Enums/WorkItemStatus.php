<?php

namespace App\Enums;

enum WorkItemStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Oczekujące',
            self::InProgress => 'W trakcie',
            self::Completed => 'Zakończone',
            self::Cancelled => 'Anulowane',
        };
    }

    public static function fromTaskStatus(TaskStatus $status): self
    {
        return match ($status) {
            TaskStatus::PENDING => self::Pending,
            TaskStatus::IN_PROGRESS => self::InProgress,
            TaskStatus::COMPLETED => self::Completed,
            TaskStatus::CANCELLED => self::Cancelled,
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Pending || $this === self::InProgress;
    }
}
