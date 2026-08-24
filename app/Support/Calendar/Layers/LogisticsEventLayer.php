<?php

namespace App\Support\Calendar\Layers;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Models\LogisticsEvent;
use App\Support\Calendar\CalendarEvent;
use App\Support\Calendar\CalendarLayer;
use Carbon\CarbonImmutable;

/**
 * Wspólna baza dla wyjazdów, zjazdów i transferów — różnią się tylko typem i adresem widoku.
 */
abstract class LogisticsEventLayer extends CalendarLayer
{
    abstract protected function type(): LogisticsEventType;

    abstract protected function routeName(): string;

    public function group(): string
    {
        return 'Logistyka';
    }

    public function fetch(CarbonImmutable $from, CarbonImmutable $to): iterable
    {
        $events = LogisticsEvent::query()
            ->with(['fromLocation:id,name,city', 'toLocation:id,name,city', 'vehicle:id,registration_number'])
            ->withCount('participants')
            ->where('type', $this->type())
            ->where('status', '!=', LogisticsEventStatus::CANCELLED)
            ->whereDate('event_date', '<=', $to->toDateString())
            ->where(function ($q) use ($from) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $from->toDateString());
            })
            ->orderBy('event_date')
            ->limit($this->limit())
            ->get();

        foreach ($events as $event) {
            yield new CalendarEvent(
                layer: $this->key(),
                id: (string) $event->id,
                title: $this->titleFor($event),
                start: $event->event_date,
                end: $event->getEffectiveEndDate(),
                subtitle: $this->subtitleFor($event),
                url: route($this->routeName(), $event->id),
                badge: $event->getVisualStatus(),
                timeLabel: $event->event_date?->format('H:i'),
            );
        }
    }

    protected function titleFor(LogisticsEvent $event): string
    {
        $route = array_filter([
            $event->fromLocation?->name,
            $event->toLocation?->name,
        ]);

        if ($route === []) {
            return $this->label();
        }

        return implode(' → ', $route);
    }

    protected function subtitleFor(LogisticsEvent $event): ?string
    {
        $parts = [];

        if ($event->participants_count > 0) {
            $parts[] = $event->participants_count.' os.';
        }

        if ($event->vehicle?->registration_number) {
            $parts[] = $event->vehicle->registration_number;
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
