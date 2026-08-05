<?php

namespace App\Enums;

enum RecruitmentRejectionReason: string
{
    case Stawka = 'stawka';
    case PracujeGdzieIndziej = 'pracuje_gdzie_indziej';
    case Duplikat = 'duplikat';
    case BrakDoswiadczenia = 'brak_doswiadczenia';
    case BledneDaneKontaktowe = 'bledne_dane_kontaktowe';
    case Inne = 'inne';

    public function label(): string
    {
        return match ($this) {
            self::Stawka => 'Stawka (zbyt niska/wysoka)',
            self::PracujeGdzieIndziej => 'Pracuje gdzie indziej',
            self::Duplikat => 'Duplikat',
            self::BrakDoswiadczenia => 'Brak doświadczenia',
            self::BledneDaneKontaktowe => 'Błędne dane kontaktowe',
            self::Inne => 'Inne',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
