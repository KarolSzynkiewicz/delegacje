<?php

namespace App\Enums;

enum RecruitmentShipyardExperience: string
{
    case Brak      = 'brak';
    case Do3Lat    = '1_3';
    case Do10Lat   = '4_10';
    case Powyzej10 = '10_plus';

    public function label(): string
    {
        return match ($this) {
            self::Brak      => '0 lat',
            self::Do3Lat    => '1–3 lata',
            self::Do10Lat   => '4–10 lat',
            self::Powyzej10 => '10+ lat',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Brak      => 'brak',
            self::Do3Lat    => '1–3 l.',
            self::Do10Lat   => '4–10 l.',
            self::Powyzej10 => '10+ l.',
        };
    }
}
