<?php

namespace App\Enums;

enum VehicleRetirementReason: string
{
    case Scrapped = 'scrapped';
    case Sold = 'sold';
    case LeaseReturned = 'lease_returned';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Scrapped => 'Zezłomowanie',
            self::Sold => 'Sprzedaż',
            self::LeaseReturned => 'Zwrot leasingu',
            self::Other => 'Inne',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
