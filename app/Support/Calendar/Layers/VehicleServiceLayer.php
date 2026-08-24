<?php

namespace App\Support\Calendar\Layers;

use App\Models\VehicleRepair;
use App\Support\Calendar\CalendarEvent;
use App\Support\Calendar\CalendarLayer;
use Carbon\CarbonImmutable;

class VehicleServiceLayer extends CalendarLayer
{
    public function key(): string
    {
        return 'vehicle_service';
    }

    public function label(): string
    {
        return 'Auta — serwis';
    }

    public function group(): string
    {
        return 'Pojazdy';
    }

    public function icon(): string
    {
        return 'bi bi-wrench-adjustable';
    }

    public function color(): string
    {
        return '#ef4444';
    }

    public function permission(): ?string
    {
        return 'vehicle-assignments.view';
    }

    public function description(): ?string
    {
        return 'Okresy niedostępności pojazdu';
    }

    public function fetch(CarbonImmutable $from, CarbonImmutable $to): iterable
    {
        $repairs = VehicleRepair::query()
            ->with('vehicle:id,registration_number')
            ->whereDate('start_date', '<=', $to->toDateString())
            ->where(function ($q) use ($from) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $from->toDateString());
            })
            ->orderBy('start_date')
            ->limit($this->limit())
            ->get();

        foreach ($repairs as $repair) {
            yield new CalendarEvent(
                layer: $this->key(),
                id: (string) $repair->id,
                title: $repair->vehicle?->registration_number ?? 'Pojazd',
                start: $repair->start_date,
                end: $repair->end_date ?? $to,
                subtitle: $repair->action_type?->label(),
                url: route('vehicle-repairs.show', $repair->id),
                badge: $repair->status_label,
                openEnded: $repair->end_date === null,
            );
        }
    }
}
