<?php

namespace App\Enums;

enum AssignmentStatus: string
{
    case ACTIVE = 'active';
    case IN_TRANSIT = 'in_transit';
    case AT_BASE = 'at_base';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Aktywny',
            self::IN_TRANSIT => 'W transporcie',
            self::AT_BASE => 'W bazie',
            self::COMPLETED => 'Zakończony',
            self::CANCELLED => 'Anulowany',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

