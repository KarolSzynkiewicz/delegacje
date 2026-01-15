<?php

namespace App\Enums;

enum VehicleCondition: string
{
    case EXCELLENT = 'excellent';
    case GOOD = 'good';
    case FAIR = 'fair';
    case POOR = 'poor';
    case WORKSHOP = 'workshop';

    public function label(): string
    {
        return match($this) {
            self::EXCELLENT => 'Doskonały',
            self::GOOD => 'Dobry',
            self::FAIR => 'Zadowalający',
            self::POOR => 'Słaby',
            self::WORKSHOP => "Warsztat"
            
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

