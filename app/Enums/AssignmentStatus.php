<?php

namespace App\Enums;

enum AssignmentStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Aktywny',
            self::COMPLETED => 'Zakończony',
            self::CANCELLED => 'Anulowany',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

