<?php

namespace App\Support\Calendar\Layers;

use App\Models\ProjectAssignment;
use App\Support\Calendar\CalendarEvent;
use App\Support\Calendar\CalendarLayer;
use Carbon\CarbonImmutable;

class ProjectAssignmentLayer extends CalendarLayer
{
    public function key(): string
    {
        return 'project_assignments';
    }

    public function label(): string
    {
        return 'Przypisania do projektów';
    }

    public function group(): string
    {
        return 'Pracownicy';
    }

    public function icon(): string
    {
        return 'bi bi-person-workspace';
    }

    public function color(): string
    {
        return '#84cc16';
    }

    public function permission(): ?string
    {
        return 'project-assignments.view';
    }

    public function description(): ?string
    {
        return 'Kto pracuje na jakim projekcie';
    }

    public function fetch(CarbonImmutable $from, CarbonImmutable $to): iterable
    {
        $assignments = ProjectAssignment::query()
            ->with(['project:id,name', 'employee:id,first_name,last_name', 'role:id,name'])
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
                title: $assignment->employee?->full_name ?? 'Pracownik',
                start: $assignment->start_date,
                end: $assignment->end_date ?? $to,
                subtitle: $assignment->project?->name,
                url: route('project-assignments.show', $assignment->id),
                badge: $assignment->role?->name,
                openEnded: $assignment->end_date === null,
            );
        }
    }
}
