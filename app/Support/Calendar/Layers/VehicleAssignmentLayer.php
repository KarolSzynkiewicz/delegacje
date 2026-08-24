<?php

namespace App\Support\Calendar\Layers;

use App\Models\VehicleAssignment;
use App\Support\Calendar\CalendarEvent;
use App\Support\Calendar\CalendarLayer;
use Carbon\CarbonImmutable;

class VehicleAssignmentLayer extends CalendarLayer
{
    public function key(): string
    {
        return 'vehicle_assignments';
    }

    public function label(): string
    {
        return 'Auta — przypisania';
    }

    public function group(): string
    {
        return 'Pojazdy';
    }

    public function icon(): string
    {
        return 'bi bi-truck';
    }

    public function color(): string
    {
        return '#10b981';
    }

    public function permission(): ?string
    {
        return 'vehicle-assignments.view';
    }

    public function description(): ?string
    {
        return 'Kto ma auto w danym okresie';
    }

    public function fetch(CarbonImmutable $from, CarbonImmutable $to): iterable
    {
        $assignments = VehicleAssignment::query()
            ->with(['vehicle:id,registration_number,brand,model', 'employee:id,first_name,last_name'])
            ->excludingCancelledLogistics()
            ->whereDate('start_date', '<=', $to->toDateString())
            ->where(function ($q) use ($from) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $from->toDateString());
            })
            ->orderBy('start_date')
            ->limit($this->limit())
            ->get();

        foreach ($assignments as $assignment) {
            yield new CalendarEvent(
                layer: $this->key(),
                id: (string) $assignment->id,
                title: $assignment->vehicle?->registration_number ?? 'Pojazd',
                start: $assignment->start_date,
                end: $assignment->end_date ?? $to,
                subtitle: $assignment->employee?->full_name,
                url: route('vehicle-assignments.show', $assignment->id),
                badge: $assignment->position?->label(),
                openEnded: $assignment->end_date === null,
            );
        }
    }
}
