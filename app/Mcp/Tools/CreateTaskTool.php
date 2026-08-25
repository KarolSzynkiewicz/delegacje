<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Services\TaskCreationService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class CreateTaskTool extends Tool
{
    use ActsAsConfiguredUser;

    protected string $name = 'create_task';

    protected string $description = <<<'MARKDOWN'
        Tworzy nowe zadanie z opcjonalnymi podzadaniami.

        Zasada obowiązkowa: najpierw pokaż użytkownikowi pełną propozycję
        (nazwa, opis, kategoria, priorytet, termin, przypisanie, lista podzadań
        w kolejności) i poczekaj na wyraźną zgodę. Dopiero wtedy wywołaj to
        narzędzie z `confirmed_by_user: true`. Nigdy nie ustawiaj tej flagi
        z własnej inicjatywy.

        Podzadania to tablica stringów – każdy element to jeden krok checklisty,
        w podanej kolejności.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = $this->actingUser();

        if (! $user->isAdmin() && ! $user->hasPermission('tasks.view')) {
            return Response::error(
                "Użytkownik {$user->name} nie ma dostępu do modułu zadań – tworzenie odrzucone."
            );
        }

        $maxSubtasks = config('ai_tools.max_subtasks_per_task');

        $validated = $request->validate([
            'confirmed_by_user' => ['required', 'boolean'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'category' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:5'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'sprint_id' => ['nullable', 'integer', 'exists:sprints,id'],
            'subtasks' => ['nullable', 'array', "max:{$maxSubtasks}"],
            'subtasks.*' => ['required', 'string', 'max:255'],
        ]);

        if ($validated['confirmed_by_user'] !== true) {
            return Response::error(
                'Tworzenie wstrzymane: brak potwierdzenia użytkownika. Pokaż propozycję zadania '
                .'(nazwa, opis, podzadania), poproś o akceptację i wywołaj ponownie z confirmed_by_user=true.'
            );
        }

        $subtasks = collect($validated['subtasks'] ?? [])
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values()
            ->all();

        $task = app(TaskCreationService::class)->create([
            'name' => trim($validated['name']),
            'description' => isset($validated['description']) ? trim($validated['description']) : null,
            'category' => isset($validated['category']) ? trim($validated['category']) : null,
            'priority' => $validated['priority'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'sprint_id' => $validated['sprint_id'] ?? null,
            'subtasks' => $subtasks,
        ], $user);

        $task->load(['subtasks', 'assignedTo:id,name', 'sprint:id,name']);

        return Response::json([
            'meta' => [
                'created_at' => now()->toIso8601String(),
                'created_by' => $user->name,
            ],
            'task' => [
                'id' => $task->id,
                'name' => $task->name,
                'description' => $task->plainDescription(),
                'status' => $task->status?->value,
                'category' => $task->category,
                'priority' => $task->priority,
                'due_date' => $task->due_date?->toDateString(),
                'assigned_to' => $task->assignedTo?->name,
                'sprint' => $task->sprint?->name,
                'url' => route('tasks.show', $task),
                'subtasks' => $task->subtasks
                    ->sortBy('sort_order')
                    ->values()
                    ->map(fn ($st) => [
                        'id' => $st->id,
                        'sort_order' => $st->sort_order,
                        'name' => $st->name,
                    ])
                    ->all(),
            ],
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('Nazwa zadania.')
                ->required(),
            'description' => $schema->string()
                ->description('Opis zadania – kontekst, cel, uwagi.'),
            'category' => $schema->string()
                ->description('Kategoria (np. dom, Rekrutacja).'),
            'priority' => $schema->integer()
                ->description('Priorytet 1–5 (1 = najwyższy).')
                ->min(1)
                ->max(5),
            'due_date' => $schema->string()
                ->description('Termin w formacie YYYY-MM-DD.'),
            'assigned_to' => $schema->integer()
                ->description('ID użytkownika (users.id), któremu przypisać zadanie.'),
            'sprint_id' => $schema->integer()
                ->description('ID sprintu (sprints.id), jeśli zadanie ma trafić na tablicę.'),
            'subtasks' => $schema->array()
                ->description('Lista podzadań w kolejności wykonania – każdy element to nazwa kroku.')
                ->items($schema->string()),
            'confirmed_by_user' => $schema->boolean()
                ->description('True tylko wtedy, gdy użytkownik wyraźnie zatwierdził pokazaną propozycję zadania.')
                ->required(),
        ];
    }
}
