<?php

namespace App\Enums;

enum EmployeeLifecycleEventType: string
{
    case Hired = 'hired';
    case Terminated = 'terminated';
    case Reinstated = 'reinstated';

    public function label(): string
    {
        return match ($this) {
            self::Hired => 'Zatrudnienie',
            self::Terminated => 'Zwolnienie',
            self::Reinstated => 'Przywrócenie',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Hired => 'success',
            self::Terminated => 'danger',
            self::Reinstated => 'info',
        };
    }
}
