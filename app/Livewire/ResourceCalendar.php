<?php

namespace App\Livewire;

use App\Support\Calendar\CalendarEvent;
use App\Support\Calendar\CalendarLayer;
use App\Support\Calendar\CalendarRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Kalendarz zasobów: zadania, logistyka, auta, domy i pracownicy na jednej osi czasu.
 *
 * Źródła zdarzeń są wtyczkami (`App\Support\Calendar\CalendarLayer` + `config/calendar.php`),
 * więc komponent nie wie nic o konkretnych modelach — tylko renderuje warstwy z rejestru.
 */
class ResourceCalendar extends Component
{
    public const VIEWS = [
        'day' => 'Dzień',
        'week' => 'Tydzień',
        'month' => 'Miesiąc',
    ];

    #[Url(as: 'v')]
    public string $view = 'month';

    /** Data kotwicząca widok (Y-m-d). */
    #[Url(as: 'd')]
    public string $anchor = '';

    /** Klucze wyłączonych warstw po przecinku — pusto oznacza „pokaż wszystko”. */
    #[Url(as: 'off')]
    public string $hidden = '';

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        if (! array_key_exists($this->view, self::VIEWS)) {
            $this->view = 'month';
        }

        $this->anchor = $this->parseDate($this->anchor)->toDateString();
    }

    // ---------------------------------------------------------------- nawigacja

    public function setView(string $view): void
    {
        if (array_key_exists($view, self::VIEWS)) {
            $this->view = $view;
        }
    }

    public function goToToday(): void
    {
        $this->anchor = CarbonImmutable::today()->toDateString();
    }

    public function previous(): void
    {
        $this->anchor = $this->shift(-1)->toDateString();
    }

    public function next(): void
    {
        $this->anchor = $this->shift(1)->toDateString();
    }

    public function goToDay(string $date): void
    {
        $this->anchor = $this->parseDate($date)->toDateString();
        $this->view = 'day';
    }

    protected function shift(int $direction): CarbonImmutable
    {
        $anchor = $this->anchorDate();

        return match ($this->view) {
            'day' => $anchor->addDays($direction),
            'week' => $anchor->addWeeks($direction),
            default => $anchor->addMonthsNoOverflow($direction),
        };
    }

    // ------------------------------------------------------------------ filtry

    /** @return array<int, string> */
    public function hiddenKeys(): array
    {
        return array_values(array_filter(explode(',', $this->hidden)));
    }

    public function isLayerEnabled(string $key): bool
    {
        return ! in_array($key, $this->hiddenKeys(), true);
    }

    public function toggleLayer(string $key): void
    {
        $keys = $this->hiddenKeys();

        $this->hidden = implode(',', in_array($key, $keys, true)
            ? array_values(array_diff($keys, [$key]))
            : [...$keys, $key]);
    }

    public function showAllLayers(): void
    {
        $this->hidden = '';
    }

    public function hideAllLayers(): void
    {
        $this->hidden = $this->availableLayers()->keys()->implode(',');
    }

    public function toggleGroup(string $group): void
    {
        $groupKeys = $this->availableLayers()
            ->filter(fn (CalendarLayer $layer) => $layer->group() === $group)
            ->keys()
            ->all();

        $hidden = $this->hiddenKeys();
        $anyVisible = collect($groupKeys)->contains(fn (string $key) => ! in_array($key, $hidden, true));

        $this->hidden = implode(',', $anyVisible
            ? array_values(array_unique([...$hidden, ...$groupKeys]))
            : array_values(array_diff($hidden, $groupKeys)));
    }

    /** „Wyczyść” zawsze wraca do stanu maksymalnie pokazującego, nie do domyślnego zawężenia. */
    public function clearFilters(): void
    {
        $this->hidden = '';
        $this->search = '';
    }

    public function hasFilters(): bool
    {
        return $this->hiddenKeys() !== [] || trim($this->search) !== '';
    }

    // ------------------------------------------------------------------ zakresy

    public function anchorDate(): CarbonImmutable
    {
        return $this->parseDate($this->anchor);
    }

    /** Początek okresu, który opisuje nagłówek (bez dopełnienia do pełnych tygodni). */
    public function periodStart(): CarbonImmutable
    {
        $anchor = $this->anchorDate();

        return match ($this->view) {
            'day' => $anchor,
            'week' => $anchor->startOfWeek(CarbonImmutable::MONDAY),
            default => $anchor->startOfMonth(),
        };
    }

    public function periodEnd(): CarbonImmutable
    {
        $anchor = $this->anchorDate();

        return match ($this->view) {
            'day' => $anchor,
            'week' => $anchor->endOfWeek(CarbonImmutable::SUNDAY)->startOfDay(),
            default => $anchor->endOfMonth()->startOfDay(),
        };
    }

    /** Zakres faktycznie rysowany — miesiąc dopełniamy do pełnych tygodni. */
    public function gridStart(): CarbonImmutable
    {
        return $this->view === 'month'
            ? $this->periodStart()->startOfWeek(CarbonImmutable::MONDAY)
            : $this->periodStart();
    }

    public function gridEnd(): CarbonImmutable
    {
        return $this->view === 'month'
            ? $this->periodEnd()->endOfWeek(CarbonImmutable::SUNDAY)->startOfDay()
            : $this->periodEnd();
    }

    public function periodTitle(): string
    {
        $start = $this->periodStart();
        $end = $this->periodEnd();

        return match ($this->view) {
            'day' => ucfirst($start->locale('pl')->translatedFormat('l, j F Y')),
            'week' => $start->locale('pl')->translatedFormat('j M').' – '
                .$end->locale('pl')->translatedFormat('j M Y')
                .' · tydz. '.$start->isoWeek(),
            default => ucfirst($start->locale('pl')->translatedFormat('F Y')),
        };
    }

    // ------------------------------------------------------------------- render

    /** @return Collection<string, CalendarLayer> */
    protected function availableLayers(): Collection
    {
        return app(CalendarRegistry::class)->visibleFor(auth()->user());
    }

    public function render()
    {
        $layers = $this->availableLayers();
        $from = $this->gridStart();
        $to = $this->gridEnd();

        $counts = [];
        /** @var array<int, CalendarEvent> $events */
        $events = [];

        foreach ($layers as $key => $layer) {
            $layerEvents = collect($layer->fetch($from, $to))
                ->filter(fn (CalendarEvent $event) => $event->matches($this->search))
                ->values();

            $counts[$key] = $layerEvents->count();

            if ($this->isLayerEnabled($key)) {
                $events = [...$events, ...$layerEvents->all()];
            }
        }

        // Paski wielodniowe najpierw — dzięki temu trzymają się góry komórki i nie „skaczą” między dniami.
        usort($events, function (CalendarEvent $a, CalendarEvent $b) {
            return [! $a->isMultiDay(), $a->start->getTimestamp(), $a->title]
                <=> [! $b->isMultiDay(), $b->start->getTimestamp(), $b->title];
        });

        $days = $this->buildDays($from, $to, $events);

        return view('livewire.resource-calendar', [
            'layers' => $layers,
            // preserveKeys: groupBy domyślnie przenumerowuje pozycje, a widok potrzebuje kluczy warstw.
            'layerGroups' => $layers->groupBy(fn (CalendarLayer $layer) => $layer->group(), preserveKeys: true),
            'counts' => $counts,
            'days' => $days,
            'weeks' => $this->view === 'day'
                ? []
                : $this->buildWeeks($days, $events, $this->view === 'month' ? $this->maxLanes() : null),
            'totalEvents' => count($events),
            'periodTitle' => $this->periodTitle(),
        ]);
    }

    protected function maxLanes(): int
    {
        return max(1, (int) config('calendar.max_lanes', 4));
    }

    /**
     * Dni siatki z przypisanymi zdarzeniami (zdarzenie wielodniowe trafia do każdego swojego dnia).
     *
     * @param  array<int, CalendarEvent>  $events
     * @return array<int, array<string, mixed>>
     */
    protected function buildDays(CarbonImmutable $from, CarbonImmutable $to, array $events): array
    {
        $today = CarbonImmutable::today();
        $currentMonth = $this->anchorDate()->month;

        $days = [];
        for ($day = $from; $day->lte($to); $day = $day->addDay()) {
            $days[$day->toDateString()] = [
                'date' => $day,
                'is_today' => $day->isSameDay($today),
                'in_period' => $this->view !== 'month' || $day->month === $currentMonth,
                'is_weekend' => $day->isWeekend(),
                'events' => [],
            ];
        }

        foreach ($events as $event) {
            $cursor = $event->start->lt($from) ? $from : $event->start;
            $last = $event->end->gt($to) ? $to : $event->end;

            for (; $cursor->lte($last); $cursor = $cursor->addDay()) {
                $key = $cursor->toDateString();

                if (isset($days[$key])) {
                    $days[$key]['events'][] = $event;
                }
            }
        }

        return array_values($days);
    }

    /**
     * Układa zdarzenia w ciągłe paski w obrębie tygodnia (jak w Google Calendar).
     *
     * Zdarzenie trwające kilka dni to jeden pasek rozciągnięty na kolumny siatki, a nie kilka
     * osobnych kafelków. Paski pakujemy zachłannie w kolejne „pasy” (lanes) — pierwszy wolny pas,
     * w którym nic jeszcze nie zajmuje danej kolumny. Zdarzenie przechodzące przez granicę tygodnia
     * jest przycinane do niego i oznaczane strzałką kontynuacji.
     *
     * @param  array<int, array<string, mixed>>  $days
     * @param  array<int, CalendarEvent>  $events
     * @param  int|null  $maxLanes  Limit pasów (widok miesiąca); nadmiar trafia do licznika „+N”.
     * @return array<int, array<string, mixed>>
     */
    protected function buildWeeks(array $days, array $events, ?int $maxLanes): array
    {
        $weeks = [];

        foreach (array_chunk($days, 7) as $chunk) {
            $columns = count($chunk);
            $weekStart = $chunk[0]['date'];
            $weekEnd = $chunk[$columns - 1]['date'];

            $weekEvents = array_values(array_filter(
                $events,
                fn (CalendarEvent $event) => $event->start->lte($weekEnd) && $event->end->gte($weekStart)
            ));

            // Kolejność pakowania: od najwcześniejszego, a przy remisie najdłuższe na górze.
            usort($weekEvents, function (CalendarEvent $a, CalendarEvent $b) {
                return [$a->start->getTimestamp(), -$a->start->diffInDays($a->end), $a->title]
                    <=> [$b->start->getTimestamp(), -$b->start->diffInDays($b->end), $b->title];
            });

            $bars = [];
            $overflow = [];
            $laneEnd = [];

            foreach ($weekEvents as $event) {
                $startCol = $event->start->lt($weekStart) ? 1 : $weekStart->diffInDays($event->start) + 1;
                $endCol = $event->end->gt($weekEnd) ? $columns : $weekStart->diffInDays($event->end) + 1;

                $lane = 0;
                while (isset($laneEnd[$lane]) && $laneEnd[$lane] >= $startCol) {
                    $lane++;
                }

                if ($maxLanes !== null && $lane >= $maxLanes) {
                    for ($col = $startCol; $col <= $endCol; $col++) {
                        $dayKey = $weekStart->addDays($col - 1)->toDateString();
                        $overflow[$dayKey] = ($overflow[$dayKey] ?? 0) + 1;
                    }

                    continue;
                }

                $laneEnd[$lane] = $endCol;

                $bars[] = [
                    'event' => $event,
                    'col' => $startCol,
                    'span' => $endCol - $startCol + 1,
                    'lane' => $lane,
                    'continues_before' => $event->start->lt($weekStart),
                    'continues_after' => $event->end->gt($weekEnd),
                ];
            }

            $weeks[] = [
                'days' => $chunk,
                'lanes' => $laneEnd === [] ? 0 : max(array_keys($laneEnd)) + 1,
                'bars' => $bars,
                'overflow' => $overflow,
            ];
        }

        return $weeks;
    }

    protected function parseDate(?string $value): CarbonImmutable
    {
        try {
            return $value ? CarbonImmutable::parse($value)->startOfDay() : CarbonImmutable::today();
        } catch (\Throwable) {
            return CarbonImmutable::today();
        }
    }
}
