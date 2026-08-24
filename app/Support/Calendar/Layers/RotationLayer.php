<?php

namespace App\Support\Calendar\Layers;

use App\Models\Rotation;
use App\Support\Calendar\CalendarEvent;
use App\Support\Calendar\CalendarLayer;
use Carbon\CarbonImmutable;

class RotationLayer extends CalendarLayer
{
    public function key(): string
    {
        return 'rotations';
    }

    public function label(): string
    {
        return 'Rotacje pracowników';
    }

    public function group(): string
    {
        return 'Pracownicy';
    }

    public function icon(): string
    {
        return 'bi bi-arrow-repeat';
    }

    public function color(): string
    {
        return '#ec4899';
    }

    public function permission(): ?string
    {
        return 'rotations.view';
    }

    public function description(): ?string
    {
        return 'Okna dostępności pracownika';
    }

    public function fetch(CarbonImmutable $from, CarbonImmutable $to): iterable
    {
        $rotations = Rotation::query()
            ->with('employee:id,first_name,last_name')
            ->whereDate('start_date', '<=', $to->toDateString())
            ->where(function ($q) use ($from) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $from->toDateString());
            })
            ->orderBy('start_date')
            ->limit($this->limit())
            ->get();

        foreach ($rotations as $rotation) {
            yield new CalendarEvent(
                layer: $this->key(),
                id: (string) $rotation->id,
                title: $rotation->employee?->full_name ?? 'Pracownik',
                start: $rotation->start_date,
                end: $rotation->end_date ?? $to,
                subtitle: 'Rotacja',
                url: $rotation->employee_id
                    ? route('employees.rotations.show', [$rotation->employee_id, $rotation->id])
                    : null,
                openEnded: $rotation->end_date === null,
            );
        }
    }
}
