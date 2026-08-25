<?php

namespace App\Mcp\Tools;

use App\Enums\TaskStatus;
use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Models\ProjectTask;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class TasksWithoutCategoryTool extends Tool
{
    use ActsAsConfiguredUser;

    protected string $name = 'tasks_without_category';

    protected string $description = <<<'MARKDOWN'
        Zwraca zadania bez ustawionej kategorii wraz ze słownikiem kategorii już
        używanych w systemie (`known_categories`) i liczbą ich wystąpień.

        Kategoria to zwykły tekst, nie ma osobnego rejestru etykiet – dlatego
        propozycje należy budować z `known_categories`, a nową nazwę tworzyć tylko
        wtedy, gdy żadna istniejąca naprawdę nie pasuje.

        To narzędzie tylko czyta. Zapis kategorii wykonuje `set_task_categories`
        i wolno go użyć dopiero po zatwierdzeniu propozycji przez użytkownika.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $this->actingUser();

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
            'include_closed' => ['nullable', 'boolean'],
        ]);

        $limit = $validated['limit'] ?? 50;
        $includeClosed = $validated['include_closed'] ?? false;

        $query = ProjectTask::query()
            ->with(['assignedTo:id,name', 'createdBy:id,name', 'subtasks:id,task_id,name,is_completed'])
            ->where(function ($q) {
                $q->whereNull('category')->orWhere('category', '');
            });

        if (! $includeClosed) {
            $query->whereIn('status', [TaskStatus::PENDING->value, TaskStatus::IN_PROGRESS->value]);
        }

        $total = (clone $query)->count();

        $tasks = $query->orderByDesc('updated_at')->limit($limit)->get();

        return Response::json([
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'returned' => $tasks->count(),
                'total_without_category' => $total,
                'includes_closed_tasks' => $includeClosed,
            ],
            'known_categories' => $this->knownCategories(),
            'tasks' => $tasks->map(fn (ProjectTask $task) => [
                'id' => $task->id,
                'name' => $task->name,
                'description' => $task->plainDescription(),
                'status' => $task->status?->value,
                'priority' => $task->priority,
                'due_date' => $task->due_date?->toDateString(),
                'created_at' => $task->created_at?->toIso8601String(),
                'updated_at' => $task->updated_at?->toIso8601String(),
                'assigned_to' => $task->assignedTo?->name,
                'created_by' => $task->createdBy?->name,
                'subtask_names' => $task->subtasks->pluck('name')->all(),
            ])->values()->all(),
        ]);
    }

    /**
     * Słownik kategorii używanych w systemie, od najczęstszej.
     *
     * @return array<int, array{category: string, tasks: int}>
     */
    private function knownCategories(): array
    {
        return ProjectTask::query()
            ->select('category', DB::raw('COUNT(*) as tasks'))
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->groupBy('category')
            ->orderByDesc('tasks')
            ->get()
            ->map(fn ($row) => [
                'category' => (string) $row->category,
                'tasks' => (int) $row->tasks,
            ])
            ->all();
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()
                ->description('Maksymalna liczba zwróconych zadań. Domyślnie 50.')
                ->min(1)
                ->max(200),
            'include_closed' => $schema->boolean()
                ->description('Czy dołączyć zadania zakończone i anulowane. Domyślnie false.'),
        ];
    }
}
