<?php

namespace App\Enums;

enum LogisticsEventStatus: string
{
    case PLANNED = 'planned';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PLANNED => 'Oczekuje na przypisanie',
            self::IN_PROGRESS => 'W trakcie', // Deprecated - do not use
            self::COMPLETED => 'Przypisany',
            self::CANCELLED => 'Anulowany',
        };
    }

    /**
     * Krótki opis do tooltipów / pomocy w UI (co oznacza status zdarzenia logistycznego).
     */
    public function helpText(): string
    {
        return match ($this) {
            self::PLANNED => 'Zdarzenie jest zaplanowane, ale nadal brakuje wymaganych przypisań (np. ludzie, pojazd, baza) albo nie zostało jeszcze domknięte w planerze.',
            self::IN_PROGRESS => 'Status historyczny — nie stosuj go przy nowych zdarzeniach.',
            self::COMPLETED => 'Wymagane przypisania są uzupełnione; zdarzenie jest traktowane jako gotowe do realizacji w ustalonym terminie.',
            self::CANCELLED => 'Zdarzenie zostało unieważnione; powiązane przypisania mogły zostać cofnięte lub usunięte w zależności od typu zdarzenia.',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
