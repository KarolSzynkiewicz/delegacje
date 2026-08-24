<?php

namespace App\Support\Calendar;

use Carbon\CarbonImmutable;

/**
 * Warstwa kalendarza = jedno źródło zdarzeń, które da się włączyć/wyłączyć w filtrach.
 *
 * Dodanie nowego typu zdarzeń (np. spotkań) sprowadza się do napisania klasy
 * dziedziczącej po tej i dopisania jej do `config/calendar.php`.
 */
abstract class CalendarLayer
{
    /** Stabilny klucz warstwy — trafia do URL-a, więc nie zmieniaj go po wdrożeniu. */
    abstract public function key(): string;

    abstract public function label(): string;

    /**
     * Zdarzenia przecinające zakres [$from, $to] (obie daty włącznie, granulacja dzienna).
     *
     * @return iterable<CalendarEvent>
     */
    abstract public function fetch(CarbonImmutable $from, CarbonImmutable $to): iterable;

    /** Nagłówek sekcji w liście filtrów; kolejność grup ustala `config('calendar.groups')`. */
    public function group(): string
    {
        return 'Inne';
    }

    public function icon(): string
    {
        return 'bi bi-circle-fill';
    }

    /** Kolor warstwy w formacie #rrggbb — legenda, kropki i paski zdarzeń. */
    public function color(): string
    {
        return '#3b82f6';
    }

    /** Uprawnienie wymagane, by warstwa w ogóle pojawiła się w filtrach (null = dla każdego). */
    public function permission(): ?string
    {
        return null;
    }

    public function description(): ?string
    {
        return null;
    }

    /** Bezpiecznik na liczbę rekordów pobieranych przez jedną warstwę. */
    protected function limit(): int
    {
        return (int) config('calendar.max_events_per_layer', 400);
    }

    /** Składowe RGB koloru — do budowania rgba() w stylach inline. */
    public function rgb(): string
    {
        $hex = ltrim($this->color(), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return '59, 130, 246';
        }

        return implode(', ', [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ]);
    }
}
