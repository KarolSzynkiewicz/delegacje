<?php

namespace App\Enums;

enum EuropeanCountry: string
{
    case POLAND = 'PL';
    case GERMANY = 'DE';
    case FRANCE = 'FR';
    case ITALY = 'IT';
    case SPAIN = 'ES';
    case NETHERLANDS = 'NL';
    case BELGIUM = 'BE';
    case AUSTRIA = 'AT';
    case CZECH_REPUBLIC = 'CZ';
    case SLOVAKIA = 'SK';
    case HUNGARY = 'HU';
    case ROMANIA = 'RO';
    case BULGARIA = 'BG';
    case GREECE = 'GR';
    case PORTUGAL = 'PT';
    case SWEDEN = 'SE';
    case DENMARK = 'DK';
    case FINLAND = 'FI';
    case NORWAY = 'NO';
    case SWITZERLAND = 'CH';
    case IRELAND = 'IE';
    case CROATIA = 'HR';
    case SLOVENIA = 'SI';
    case LITHUANIA = 'LT';
    case LATVIA = 'LV';
    case ESTONIA = 'EE';
    case LUXEMBOURG = 'LU';
    case MALTA = 'MT';
    case CYPRUS = 'CY';
    case UKRAINE = 'UA';

    /**
     * Get the country name in Polish.
     */
    public function label(): string
    {
        return match($this) {
            self::POLAND => 'Polska',
            self::GERMANY => 'Niemcy',
            self::FRANCE => 'Francja',
            self::ITALY => 'Włochy',
            self::SPAIN => 'Hiszpania',
            self::NETHERLANDS => 'Holandia',
            self::BELGIUM => 'Belgia',
            self::AUSTRIA => 'Austria',
            self::CZECH_REPUBLIC => 'Czechy',
            self::SLOVAKIA => 'Słowacja',
            self::HUNGARY => 'Węgry',
            self::ROMANIA => 'Rumunia',
            self::BULGARIA => 'Bułgaria',
            self::GREECE => 'Grecja',
            self::PORTUGAL => 'Portugalia',
            self::SWEDEN => 'Szwecja',
            self::DENMARK => 'Dania',
            self::FINLAND => 'Finlandia',
            self::NORWAY => 'Norwegia',
            self::SWITZERLAND => 'Szwajcaria',
            self::IRELAND => 'Irlandia',
            self::CROATIA => 'Chorwacja',
            self::SLOVENIA => 'Słowenia',
            self::LITHUANIA => 'Litwa',
            self::LATVIA => 'Łotwa',
            self::ESTONIA => 'Estonia',
            self::LUXEMBOURG => 'Luksemburg',
            self::MALTA => 'Malta',
            self::CYPRUS => 'Cypr',
            self::UKRAINE => 'Ukraina',
        };
    }

    /**
     * Get country with flag emoji.
     */
    public function labelWithFlag(): string
    {
        return $this->flag() . ' ' . $this->label();
    }

    /**
     * Get flag emoji for country.
     */
    public function flag(): string
    {
        return match($this) {
            self::POLAND => '🇵🇱',
            self::GERMANY => '🇩🇪',
            self::FRANCE => '🇫🇷',
            self::ITALY => '🇮🇹',
            self::SPAIN => '🇪🇸',
            self::NETHERLANDS => '🇳🇱',
            self::BELGIUM => '🇧🇪',
            self::AUSTRIA => '🇦🇹',
            self::CZECH_REPUBLIC => '🇨🇿',
            self::SLOVAKIA => '🇸🇰',
            self::HUNGARY => '🇭🇺',
            self::ROMANIA => '🇷🇴',
            self::BULGARIA => '🇧🇬',
            self::GREECE => '🇬🇷',
            self::PORTUGAL => '🇵🇹',
            self::SWEDEN => '🇸🇪',
            self::DENMARK => '🇩🇰',
            self::FINLAND => '🇫🇮',
            self::NORWAY => '🇳🇴',
            self::SWITZERLAND => '🇨🇭',
            self::IRELAND => '🇮🇪',
            self::CROATIA => '🇭🇷',
            self::SLOVENIA => '🇸🇮',
            self::LITHUANIA => '🇱🇹',
            self::LATVIA => '🇱🇻',
            self::ESTONIA => '🇪🇪',
            self::LUXEMBOURG => '🇱🇺',
            self::MALTA => '🇲🇹',
            self::CYPRUS => '🇨🇾',
            self::UKRAINE => '🇺🇦',
        };
    }

    /**
     * Get all countries sorted alphabetically by label.
     */
    public static function sorted(): array
    {
        $countries = self::cases();
        usort($countries, fn($a, $b) => strcmp($a->label(), $b->label()));
        return $countries;
    }
}
