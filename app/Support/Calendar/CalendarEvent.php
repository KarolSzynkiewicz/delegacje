<?php

namespace App\Support\Calendar;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Pojedyncze zdarzenie na osi czasu kalendarza.
 *
 * Zakres jest zawsze znormalizowany do pełnych dni (kalendarz jest dniowy),
 * a dokładna godzina — jeśli ma znaczenie — trafia do {@see $timeLabel}.
 */
final class CalendarEvent
{
    public readonly CarbonImmutable $start;

    public readonly CarbonImmutable $end;

    /**
     * @param  string  $layer  Klucz warstwy, z której pochodzi zdarzenie.
     * @param  string  $id  Identyfikator unikalny w obrębie warstwy.
     * @param  string|null  $badge  Krótka etykieta statusu (np. „W trakcie”).
     * @param  string|null  $timeLabel  Godzina/zakres godzin do pokazania obok tytułu.
     * @param  bool  $openEnded  Zdarzenie bez daty końca (trwa nadal).
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $layer,
        public readonly string $id,
        public readonly string $title,
        DateTimeInterface|string $start,
        DateTimeInterface|string|null $end = null,
        public readonly ?string $subtitle = null,
        public readonly ?string $url = null,
        public readonly ?string $badge = null,
        public readonly ?string $timeLabel = null,
        public readonly bool $openEnded = false,
        public readonly array $meta = [],
    ) {
        $from = CarbonImmutable::parse($start)->startOfDay();
        $to = CarbonImmutable::parse($end ?? $start)->startOfDay();

        $this->start = $to->lt($from) ? $to : $from;
        $this->end = $to->lt($from) ? $from : $to;
    }

    public function key(): string
    {
        return $this->layer.':'.$this->id;
    }

    public function spansDay(CarbonImmutable $day): bool
    {
        $day = $day->startOfDay();

        return $this->start->lte($day) && $this->end->gte($day);
    }

    public function isMultiDay(): bool
    {
        return $this->start->notEqualTo($this->end);
    }

    public function startsOn(CarbonImmutable $day): bool
    {
        return $this->start->isSameDay($day);
    }

    public function endsOn(CarbonImmutable $day): bool
    {
        return $this->end->isSameDay($day);
    }

    public function matches(string $needle): bool
    {
        $needle = mb_strtolower(trim($needle));

        if ($needle === '') {
            return true;
        }

        $haystack = mb_strtolower($this->title.' '.($this->subtitle ?? '').' '.($this->badge ?? ''));

        return str_contains($haystack, $needle);
    }
}
