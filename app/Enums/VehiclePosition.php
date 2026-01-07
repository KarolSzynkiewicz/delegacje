<?php

namespace App\Enums;

enum VehiclePosition: string
{
    case DRIVER = 'driver';
    case PASSENGER = 'passenger';

    public function label(): string
    {
        return match($this) {
            self::DRIVER => 'Kierowca',
            self::PASSENGER => 'Pasażer',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
