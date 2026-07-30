<?php

namespace App\Enums;

enum RecruitmentContactOutcome: string
{
    case Odebrano = 'odebrano';
    case BrakOdpowiedzi = 'brak_odpowiedzi';
    case NumerNieaktywny = 'numer_nieaktywny';
    case ProsiOOddzwonienie = 'prosi_o_oddzwonienie';

    public function label(): string
    {
        return match ($this) {
            self::Odebrano => 'Odebrał(a)',
            self::BrakOdpowiedzi => 'Brak odpowiedzi',
            self::NumerNieaktywny => 'Numer nieaktywny',
            self::ProsiOOddzwonienie => 'Prosi o oddzwonienie',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Odebrano => 'success',
            self::BrakOdpowiedzi => 'danger',
            self::NumerNieaktywny => 'danger',
            self::ProsiOOddzwonienie => 'warning',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
