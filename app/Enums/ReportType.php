<?php

namespace App\Enums;

enum ReportType: string
{
    case PROJECT = 'project';
    case EMPLOYEE = 'employee';
    case FINANCIAL = 'financial';
    case TIME = 'time';

    public function label(): string
    {
        return match($this) {
            self::PROJECT => 'Projekt',
            self::EMPLOYEE => 'Pracownik',
            self::FINANCIAL => 'Finansowy',
            self::TIME => 'Czasowy',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

