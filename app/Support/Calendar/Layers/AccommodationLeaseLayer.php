<?php

namespace App\Support\Calendar\Layers;

use App\Models\AccommodationLease;
use App\Support\Calendar\CalendarEvent;
use App\Support\Calendar\CalendarLayer;
use Carbon\CarbonImmutable;

class AccommodationLeaseLayer extends CalendarLayer
{
    public function key(): string
    {
        return 'accommodation_leases';
    }

    public function label(): string
    {
        return 'Domy — umowy najmu';
    }

    public function group(): string
    {
        return 'Zakwaterowanie';
    }

    public function icon(): string
    {
        return 'bi bi-file-earmark-text';
    }

    public function color(): string
    {
        return '#fb923c';
    }

    public function permission(): ?string
    {
        return 'accommodations.view';
    }

    public function description(): ?string
    {
        return 'Od kiedy do kiedy dom jest dostępny';
    }

    public function fetch(CarbonImmutable $from, CarbonImmutable $to): iterable
    {
        $leases = AccommodationLease::query()
            ->with('accommodation:id,name,city')
            ->whereDate('start_date', '<=', $to->toDateString())
            ->where(function ($q) use ($from) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $from->toDateString());
            })
            ->orderBy('start_date')
            ->limit($this->limit())
            ->get();

        foreach ($leases as $lease) {
            yield new CalendarEvent(
                layer: $this->key(),
                id: (string) $lease->id,
                title: $lease->accommodation?->name ?? 'Mieszkanie',
                start: $lease->start_date,
                end: $lease->end_date ?? $to,
                subtitle: $lease->type ? ucfirst((string) $lease->type) : null,
                url: $lease->accommodation ? route('accommodations.show', $lease->accommodation_id) : null,
                openEnded: $lease->end_date === null,
            );
        }
    }
}
