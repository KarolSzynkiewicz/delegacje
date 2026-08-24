<?php

namespace App\Support\Calendar\Layers;

use App\Enums\WorkItemStatus;
use App\Models\ProjectTask;
use App\Models\WorkItem;
use App\Support\Calendar\CalendarEvent;
use App\Support\Calendar\CalendarLayer;
use Carbon\CarbonImmutable;

class TaskLayer extends CalendarLayer
{
    public function key(): string
    {
        return 'tasks';
    }

    public function label(): string
    {
        return 'Zadania';
    }

    public function group(): string
    {
        return 'Zadania';
    }

    public function icon(): string
    {
        return 'bi bi-check2-square';
    }

    public function color(): string
    {
        return '#3b82f6';
    }

    public function permission(): ?string
    {
        return 'tasks.view';
    }

    public function description(): ?string
    {
        return 'Terminy zadań z backlogu';
    }

    public function fetch(CarbonImmutable $from, CarbonImmutable $to): iterable
    {
        $items = WorkItem::query()
            ->with('assignee:id,name')
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [$from->toDateString(), $to->toDateString()])
            ->where('status', '!=', WorkItemStatus::Cancelled)
            ->orderBy('due_at')
            ->limit($this->limit())
            ->get();

        foreach ($items as $item) {
            yield new CalendarEvent(
                layer: $this->key(),
                id: (string) $item->id,
                title: $item->title ?? 'Zadanie',
                start: $item->due_at,
                subtitle: $item->assignee?->name,
                url: $this->urlFor($item),
                badge: $item->status?->label(),
                meta: ['type' => $item->type?->label()],
            );
        }
    }

    protected function urlFor(WorkItem $item): string
    {
        if ($item->source_type === ProjectTask::class && $item->source_id) {
            return route('tasks.show', $item->source_id);
        }

        return route('tasks.grid');
    }
}
