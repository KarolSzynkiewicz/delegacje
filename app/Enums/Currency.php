<?php

namespace App\Enums;

enum Currency: string
{
    case PLN = 'PLN';
    case EUR = 'EUR';
    case USD = 'USD';
    case GBP = 'GBP';
    case CZK = 'CZK';
    case NOK = 'NOK';
    case SEK = 'SEK';
    case DKK = 'DKK';
    case CHF = 'CHF';

    public function label(): string
    {
        return match($this) {
            self::PLN => 'PLN – Złoty',
            self::EUR => 'EUR – Euro',
            self::USD => 'USD – Dolar',
            self::GBP => 'GBP – Funt',
            self::CZK => 'CZK – Korona czeska',
            self::NOK => 'NOK – Korona norweska',
            self::SEK => 'SEK – Korona szwedzka',
            self::DKK => 'DKK – Korona duńska',
            self::CHF => 'CHF – Frank szwajcarski',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
