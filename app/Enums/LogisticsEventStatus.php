<?php

namespace App\Enums;

enum LogisticsEventStatus: string
{
    case PLANNED = 'planned';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PLANNED => 'Oczekuje na przypisanie',
            self::IN_PROGRESS => 'W trakcie', // Deprecated - do not use
            self::COMPLETED => 'Przypisany',
            self::CANCELLED => 'Anulowany',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
