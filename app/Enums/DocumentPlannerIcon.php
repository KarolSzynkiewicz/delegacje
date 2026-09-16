<?php

namespace App\Enums;

enum DocumentPlannerIcon: string
{
    case DrivingLicence = 'bi-car-front';
    case Truck = 'bi-truck';
    case Forklift = 'bi-box-seam';
    case Lift = 'bi-arrow-up-square';
    case Height = 'bi-layers';
    case Weld = 'bi-fire';
    case Electrical = 'bi-lightning-charge';
    case Medical = 'bi-heart-pulse';
    case IdCard = 'bi-person-vcard';
    case Passport = 'bi-passport';
    case Safety = 'bi-shield-check';
    case Adr = 'bi-droplet';

    public function label(): string
    {
        return match ($this) {
            self::DrivingLicence => 'Prawo jazdy',
            self::Truck => 'Ciężarówka',
            self::Forklift => 'Wózek / operator',
            self::Lift => 'Podnośnik',
            self::Height => 'Prace na wysokości',
            self::Weld => 'Spawanie',
            self::Electrical => 'Uprawnienia elektryczne',
            self::Medical => 'Badania',
            self::IdCard => 'Dowód / karta',
            self::Passport => 'Paszport',
            self::Safety => 'BHP',
            self::Adr => 'ADR',
        };
    }

    public static function tryFromStored(?string $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::tryFrom($value);
    }
}
