<?php

namespace App\Enums;

enum ProjectType: string
{
    case HOURLY = 'hourly';
    case CONTRACT = 'contract';

    public function label(): string
    {
        return match($this) {
            self::HOURLY => 'Rozliczany godzinowo',
            self::CONTRACT => 'Zakontraktowany',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
