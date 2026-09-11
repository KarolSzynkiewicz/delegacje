<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Concerns\ParsesTaskId;
use App\Mcp\Support\TaskPayload;
use App\Models\ProjectTask;
use App\Services\TaskCreationService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class AddSubtasksTool extends Tool
{
    use ActsAsConfiguredUser;
    use ParsesTaskId;

    protected string $name = 'add_subtasks';

    protected string $description = <<<'MARKDOWN'
        Dokłada podzadania (kroki checklisty) do istniejącego zadania.
        Nie nadpisuje ani nie odhacza starych kroków.

        Zasada obowiązkowa: pokaż użytkownikowi ID zadania, jego nazwę
        i listę nowych kroków w kolejności, potem poczekaj na zgodę.
        Dopiero wtedy wywołaj z `confirmed_by_user: true`.

        `@Osoba` w nazwie kroku przypisuje podzadanie – tak jak w UI.
        Zmiana istniejącego kroku: `update_subtask`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = $this->actingUser();

        if (! $user->isAdmin() && ! $user->hasPermission('tasks.update')) {
            return Response::error(
                "Użytkownik {$user->name} nie ma uprawnienia tasks.update – dodawanie podzadań odrzucone."
            );
        }

        $maxSubtasks = config('ai_tools.max_subtasks_per_task');

        $validated = $request->validate([
            'confirmed_by_user' => ['required', 'boolean'],
            'task_id' => ['required'],
            'subtasks' => ['required', 'array', 'min:1', "max:{$maxSubtasks}"],
            'subtasks.*' => ['required', 'string', 'max:255'],
        ]);

        if ($validated['confirmed_by_user'] !== true) {
            return Response::error(
                'Zapis wstrzymany: brak potwierdzenia użytkownika. Pokaż zadanie i listę '
                .'nowych podzadań, poproś o akceptację i wywołaj ponownie z confirmed_by_user=true.'
            );
        }

        $id = $this->parseTaskId($validated['task_id']);
        if (! $id) {
            return Response::error('Podaj prawidłowe `task_id` (liczba albo #12).');
        }

        $task = ProjectTask::query()->find($id);
        if (! $task) {
            return Response::error("Nie znaleziono zadania #{$id}.");
        }

        $names = collect($validated['subtasks'])
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values()
            ->all();

        if ($names === []) {
            return Response::error('Lista podzadań jest pusta po usunięciu pustych nazw.');
        }

        $existing = $task->subtasks()->count();
        if ($existing + count($names) > $maxSubtasks) {
            return Response::error(
                "Zadanie #{$task->id} ma już {$existing} podzadań. Limit to {$maxSubtasks} łącznie."
            );
        }

        $created = app(TaskCreationService::class)->addSubtasks($task, $names, $user);

        $task->refresh();
        $task->load(['subtasks.assignedTo:id,name']);

        return Response::json([
            'meta' => [
                'applied_at' => now()->toIso8601String(),
                'applied_by' => $user->name,
                'added' => count($created),
            ],
            'task' => [
                'id' => $task->id,
                'name' => $task->name,
                'url' => route('tasks.show', $task),
            ],
            'added' => collect($created)
                ->map(fn ($subtask) => TaskPayload::subtask($subtask->fresh(['assignedTo'])))
                ->values()
                ->all(),
            'subtasks' => $task->subtasks
                ->sortBy([
                    ['sort_order', 'asc'],
                    ['id', 'asc'],
                ])
                ->values()
                ->map(fn ($subtask) => TaskPayload::subtask($subtask))
                ->all(),
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->string()
                ->description('ID zadania albo "#12".')
                ->required(),
            'subtasks' => $schema->array()
                ->description('Nowe kroki checklisty w kolejności – każdy element to nazwa.')
                ->items($schema->string())
                ->min(1)
                ->required(),
            'confirmed_by_user' => $schema->boolean()
                ->description('True tylko po wyraźnej zgodzie użytkownika.')
                ->required(),
        ];
    }
}
