<?php

namespace App\Support\Calendar\Layers;

use App\Models\Sprint;
use App\Support\Calendar\CalendarEvent;
use App\Support\Calendar\CalendarLayer;
use Carbon\CarbonImmutable;

class SprintLayer extends CalendarLayer
{
    public function key(): string
    {
        return 'sprints';
    }

    public function label(): string
    {
        return 'Sprinty';
    }

    public function group(): string
    {
        return 'Zadania';
    }

    public function icon(): string
    {
        return 'bi bi-flag';
    }

    public function color(): string
    {
        return '#6366f1';
    }

    public function permission(): ?string
    {
        return 'tasks.view';
    }

    public function description(): ?string
    {
        return 'Okresy sprintów';
    }

    public function fetch(CarbonImmutable $from, CarbonImmutable $to): iterable
    {
        $sprints = Sprint::query()
            ->whereNotNull('start_date')
            ->whereDate('start_date', '<=', $to->toDateString())
            ->where(function ($q) use ($from) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $from->toDateString());
            })
            ->orderBy('start_date')
            ->limit($this->limit())
            ->get();

        foreach ($sprints as $sprint) {
            yield new CalendarEvent(
                layer: $this->key(),
                id: (string) $sprint->id,
                title: $sprint->name ?? 'Sprint',
                start: $sprint->start_date,
                end: $sprint->end_date,
                url: route('sprints.show', $sprint),
                openEnded: $sprint->end_date === null,
            );
        }
    }
}
