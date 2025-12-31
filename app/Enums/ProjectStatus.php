<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case ON_HOLD = 'on_hold';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Aktywny',
            self::COMPLETED => 'Zakończony',
            self::ON_HOLD => 'Wstrzymany',
            self::CANCELLED => 'Anulowany',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

