<?php

namespace App\Enums;

enum VehicleType: string
{
    case COMPANY_VEHICLE = 'company_vehicle';
    case PUBLIC_TRANSPORT = 'public_transport';
    case RENTAL = 'rental';

    public function label(): string
    {
        return match($this) {
            self::COMPANY_VEHICLE => 'Pojazd firmowy',
            self::PUBLIC_TRANSPORT => 'Transport publiczny',
            self::RENTAL => 'Wynajem',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
