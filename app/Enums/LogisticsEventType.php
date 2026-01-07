<?php

namespace App\Enums;

enum LogisticsEventType: string
{
    case DEPARTURE = 'departure';
    case RETURN = 'return';

    public function label(): string
    {
        return match($this) {
            self::DEPARTURE => 'Wyjazd',
            self::RETURN => 'Zjazd',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
