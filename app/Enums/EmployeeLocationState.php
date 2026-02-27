<?php

namespace App\Enums;

enum EmployeeLocationState: string
{
    case IN_BASE = 'in_base';
    case OUTSIDE_BASE = 'outside_base';
    case IN_TRANSIT = 'in_transit';

    public function label(): string
    {
        return match($this) {
            self::IN_BASE => 'W bazie',
            self::OUTSIDE_BASE => 'Poza bazą',
            self::IN_TRANSIT => 'W podróży',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
