<?php

namespace App\Enums;

enum LocationPurposeType: string
{
    case WORKSHOP = 'workshop';
    case PROJECT  = 'project';
    case QUARTER  = 'quarter';
    case AIRPORT  = 'airport';
    case BASE     = 'base';
    case OTHER    = 'other';

    public function label(): string
    {
        return match ($this) {
            self::WORKSHOP => 'Warsztat',
            self::PROJECT  => 'Projekt',
            self::QUARTER  => 'Kwatera',
            self::AIRPORT  => 'Lotnisko',
            self::BASE     => 'Baza (siedziba główna)',
            self::OTHER    => 'Inne',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::WORKSHOP => 'secondary',
            self::PROJECT  => 'primary',
            self::QUARTER  => 'info',
            self::AIRPORT  => 'accent',
            self::BASE     => 'success',
            self::OTHER    => 'warning',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
