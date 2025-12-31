<?php

namespace App\Enums;

enum ReportFormat: string
{
    case PDF = 'pdf';
    case EXCEL = 'excel';
    case CSV = 'csv';

    public function label(): string
    {
        return match($this) {
            self::PDF => 'PDF',
            self::EXCEL => 'Excel',
            self::CSV => 'CSV',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

