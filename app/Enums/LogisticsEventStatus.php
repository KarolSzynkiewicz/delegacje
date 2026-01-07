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
            self::PLANNED => 'Zaplanowane',
            self::IN_PROGRESS => 'W trakcie',
            self::COMPLETED => 'Zakończone',
            self::CANCELLED => 'Anulowane',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
