<?php

namespace App\Enums;

enum VehicleLifecycleEventType: string
{
    case Retired = 'retired';
    case Reinstated = 'reinstated';

    public function label(): string
    {
        return match ($this) {
            self::Retired => 'Wycofanie',
            self::Reinstated => 'Przywrócenie',
        };
    }
}
