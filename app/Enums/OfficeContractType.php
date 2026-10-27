<?php

namespace App\Enums;

enum OfficeContractType: string
{
    case Uop = 'uop';
    case Zlecenie = 'zlecenie';
    case B2b = 'b2b';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Uop => 'UoP',
            self::Zlecenie => 'Zlecenie',
            self::B2b => 'B2B',
            self::Other => 'Inne',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
