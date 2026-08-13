<?php

namespace App\Enums;

enum LocationPurposeType: string
{
    case WORKSHOP = 'workshop';
    case PROJECT = 'project';
    case QUARTER = 'quarter';
    case AIRPORT = 'airport';
    /** Dworzec (np. autobus, pociąg) — jak lotnisko w planerze transportu zbiorowego. */
    case STATION = 'station';
    case BASE = 'base';
    case WAREHOUSE = 'warehouse';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::WORKSHOP => 'Warsztat',
            self::PROJECT => 'Projekt',
            self::QUARTER => 'Kwatera',
            self::AIRPORT => 'Lotnisko',
            self::STATION => 'Dworzec',
            self::BASE => 'Baza (siedziba główna)',
            self::WAREHOUSE => 'Magazyn',
            self::OTHER => 'Inne',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::WORKSHOP => 'secondary',
            self::PROJECT => 'primary',
            self::QUARTER => 'info',
            self::AIRPORT => 'accent',
            self::STATION => 'info',
            self::BASE => 'success',
            self::WAREHOUSE => 'accent',
            self::OTHER => 'warning',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
