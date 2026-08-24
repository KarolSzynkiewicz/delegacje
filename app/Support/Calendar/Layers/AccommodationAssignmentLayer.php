<?php

namespace App\Support\Calendar\Layers;

use App\Models\AccommodationAssignment;
use App\Support\Calendar\CalendarEvent;
use App\Support\Calendar\CalendarLayer;
use Carbon\CarbonImmutable;

class AccommodationAssignmentLayer extends CalendarLayer
{
    public function key(): string
    {
        return 'accommodation_assignments';
    }

    public function label(): string
    {
        return 'Domy — zakwaterowanie';
    }

    public function group(): string
    {
        return 'Zakwaterowanie';
    }

    public function icon(): string
    {
        return 'bi bi-house-door';
    }

    public function color(): string
    {
        return '#f59e0b';
    }

    public function permission(): ?string
    {
        return 'accommodation-assignments.view';
    }

    public function description(): ?string
    {
        return 'Kto gdzie mieszka w danym okresie';
    }

    public function fetch(CarbonImmutable $from, CarbonImmutable $to): iterable
    {
        $assignments = AccommodationAssignment::query()
            ->with(['accommodation:id,name,city', 'employee:id,first_name,last_name'])
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
                title: $assignment->accommodation?->name ?? 'Mieszkanie',
                start: $assignment->start_date,
                end: $assignment->end_date ?? $to,
                subtitle: $assignment->employee?->full_name,
                url: route('accommodation-assignments.show', $assignment->id),
                openEnded: $assignment->end_date === null,
            );
        }
    }
}
