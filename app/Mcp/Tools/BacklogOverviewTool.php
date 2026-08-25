<?php

namespace App\Mcp\Tools;

use App\Enums\WorkItemStatus;
use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Models\Sprint;
use App\Models\WorkItem;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class BacklogOverviewTool extends Tool
{
    use ActsAsConfiguredUser;

    protected string $name = 'backlog_overview';

    protected string $description = <<<'MARKDOWN'
        Zwraca otwarty backlog, czyli pozycje pracy nieprzypisane do żadnego sprintu,
        razem z listą sprintów (cel, definition of done, daty) i rozkładem pozycji
        po kategoriach i typach.

        Backlog to wspólny indeks: zadania, podzadania, procedury, kompletacje,
        wzmianki i zatwierdzenia. Służy do planowania sprintu – szukania pozycji,
        które łączy wspólny temat albo cel.

        To narzędzie tylko czyta i niczego nie przypisuje do sprintu.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $this->actingUser();

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:300'],
            'category' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:50'],
        ]);

        $limit = $validated['limit'] ?? 100;

        $query = WorkItem::query()
            ->with(['assignee:id,name', 'createdBy:id,name'])
            ->whereNull('sprint_id')
            ->whereIn('status', [WorkItemStatus::Pending->value, WorkItemStatus::InProgress->value]);

        if (! empty($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        if (! empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        $total = (clone $query)->count();

        $items = $query
            ->orderByRaw('priority IS NULL')
            ->orderBy('priority')
            ->orderByRaw('due_at IS NULL')
            ->orderBy('due_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return Response::json([
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'returned' => $items->count(),
                'total_open_backlog' => $total,
            ],
            'sprints' => $this->sprints(),
            'breakdown' => [
                'by_category' => $this->countBy($items, fn (WorkItem $i) => $i->category ?: '(bez kategorii)'),
                'by_type' => $this->countBy($items, fn (WorkItem $i) => $i->type->value),
            ],
            'backlog' => $items->map(fn (WorkItem $item) => [
                'work_item_id' => $item->id,
                'type' => $item->type->value,
                'type_label' => $item->type->label(),
                'source_type' => $item->source_type,
                'source_id' => $item->source_id,
                'task_id' => $item->type->value === 'task' ? $item->source_id : null,
                'title' => $item->title,
                'description' => $item->plainDescription(),
                'category' => $item->category,
                'priority' => $item->priority,
                'status' => $item->status->value,
                'due_date' => $item->due_at?->toDateString(),
                'assignee' => $item->assignee?->name,
                'created_by' => $item->createdBy?->name,
            ])->values()->all(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sprints(): array
    {
        return Sprint::query()
            ->withCount('tasks')
            ->orderByDesc('start_date')
            ->limit(10)
            ->get()
            ->map(fn (Sprint $sprint) => [
                'id' => $sprint->id,
                'name' => $sprint->name,
                'status' => $sprint->statusLabel(),
                'goal' => $sprint->goal,
                'definition_of_done' => $sprint->definition_of_done,
                'start_date' => $sprint->start_date?->toDateString(),
                'end_date' => $sprint->end_date?->toDateString(),
                'tasks_count' => $sprint->tasks_count,
            ])
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, WorkItem>  $items
     * @return array<string, int>
     */
    private function countBy($items, callable $key): array
    {
        return $items
            ->groupBy($key)
            ->map->count()
            ->sortDesc()
            ->all();
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()
                ->description('Maksymalna liczba pozycji backlogu. Domyślnie 100.')
                ->min(1)
                ->max(300),
            'category' => $schema->string()
                ->description('Zawęź do jednej kategorii (dokładne dopasowanie).'),
            'type' => $schema->string()
                ->description('Zawęź do jednego typu pozycji.')
                ->enum(['task', 'subtask', 'procedure_run', 'dispatch', 'follow_up', 'callback', 'approval']),
        ];
    }
}
