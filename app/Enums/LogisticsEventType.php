<?php

namespace App\Enums;

enum LogisticsEventType: string
{
    case DEPARTURE = 'departure';
    case RETURN = 'return';
    case TRANSFER = 'transfer';
    case PLACEMENT_CORRECTION = 'placement_correction';

    public function label(): string
    {
        return match ($this) {
            self::DEPARTURE => 'Wyjazd',
            self::RETURN => 'Zjazd',
            self::TRANSFER => 'Transfer',
            self::PLACEMENT_CORRECTION => 'Korekta położenia',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
