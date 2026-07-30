<?php

namespace App\Enums;

enum RecruitmentCandidateFlag: string
{
    case Wartosciowy = 'wartosciowy';
    case CzarnaLista = 'czarna_lista';

    public function label(): string
    {
        return match ($this) {
            self::Wartosciowy => 'Wartościowy kandydat',
            self::CzarnaLista => 'Czarna lista',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Wartosciowy => 'success',
            self::CzarnaLista => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
