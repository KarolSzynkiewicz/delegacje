<?php

namespace App\Enums;

enum LogisticsEventType: string
{
    case DEPARTURE = 'departure';
    case RETURN = 'return';
    case TRANSFER = 'transfer';

    public function label(): string
    {
        return match($this) {
            self::DEPARTURE => 'Wyjazd',
            self::RETURN => 'Zjazd',
            self::TRANSFER => 'Transfer',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
