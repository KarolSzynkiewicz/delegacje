<?php

namespace App\Enums;

enum OfficePayMode: string
{
    case Hourly = 'hourly';
    case Salary = 'salary';

    public function label(): string
    {
        return match ($this) {
            self::Hourly => 'Stawka godzinowa',
            self::Salary => 'Etat / pensja',
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
