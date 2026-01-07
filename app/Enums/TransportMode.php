<?php

namespace App\Enums;

enum TransportMode: string
{
    case FLIGHT = 'flight';
    case TRAIN = 'train';
    case BUS = 'bus';
    case TAXI = 'taxi';
    case TRANSFER = 'transfer';

    public function label(): string
    {
        return match($this) {
            self::FLIGHT => 'Lot',
            self::TRAIN => 'Pociąg',
            self::BUS => 'Autobus',
            self::TAXI => 'Taksówka',
            self::TRANSFER => 'Transfer',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
