<?php

namespace App\Enums;

enum RecruitmentRejectionReason: string
{
    case ZaDrogi = 'za_drogi';
    case StawkaZaNiska = 'stawka_za_niska';
    case OczekiwaniaFinansowe = 'oczekiwania_finansowe';
    case BrakDoswiadczenia = 'brak_doswiadczenia';
    case NieOdpowiadaNaTelefon = 'nie_odpowiada_na_telefon';
    case ZatrudnionyGdzieIndziej = 'zatrudniony_gdzie_indziej';
    case Inne = 'inne';

    public function label(): string
    {
        return match ($this) {
            self::ZaDrogi => 'Za drogi',
            self::StawkaZaNiska => 'Stawka za niska',
            self::OczekiwaniaFinansowe => 'Oczekiwania finansowe',
            self::BrakDoswiadczenia => 'Brak doświadczenia',
            self::NieOdpowiadaNaTelefon => 'Nie odpowiada na telefon',
            self::ZatrudnionyGdzieIndziej => 'Zatrudniony gdzie indziej',
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
