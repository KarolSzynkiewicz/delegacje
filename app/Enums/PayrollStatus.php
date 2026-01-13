<?php

namespace App\Enums;

enum PayrollStatus: string
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case PAID = 'paid';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Szkic',
            self::APPROVED => 'Zatwierdzony',
            self::PAID => 'Wypłacony',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
