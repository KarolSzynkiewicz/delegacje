<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Models\ProjectTask;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsDestructive]
#[IsIdempotent]
class SetTaskCategoriesTool extends Tool
{
    use ActsAsConfiguredUser;

    protected string $name = 'set_task_categories';

    protected string $description = <<<'MARKDOWN'
        Zapisuje kategorie na wskazanych zadaniach.

        Zasada obowiązkowa: najpierw pokaż użytkownikowi pełną listę propozycji
        (id zadania, nazwa, proponowana kategoria, uzasadnienie) i poczekaj na
        wyraźną zgodę. Dopiero wtedy wywołaj to narzędzie z `confirmed_by_user`
        ustawionym na true. Nigdy nie ustawiaj tej flagi z własnej inicjatywy.

        Domyślnie uzupełnia wyłącznie puste kategorie. Nadpisanie istniejącej
        wartości wymaga `overwrite: true` i osobnej zgody użytkownika.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = $this->actingUser();

        if (! $user->isAdmin() && ! $user->hasPermission('tasks.update')) {
            return Response::error(
                "Użytkownik {$user->name} nie ma uprawnienia tasks.update – zapis kategorii odrzucony."
            );
        }

        $max = config('ai_tools.max_category_assignments');

        $validated = $request->validate([
            'confirmed_by_user' => ['required', 'boolean'],
            'overwrite' => ['nullable', 'boolean'],
            'assignments' => ['required', 'array', 'min:1', "max:{$max}"],
            'assignments.*.task_id' => ['required', 'integer', 'min:1'],
            'assignments.*.category' => ['required', 'string', 'max:255'],
        ]);

        if ($validated['confirmed_by_user'] !== true) {
            return Response::error(
                'Zapis wstrzymany: brak potwierdzenia użytkownika. Pokaż listę propozycji, '
                .'poproś o akceptację i wywołaj narzędzie ponownie z confirmed_by_user=true.'
            );
        }

        $overwrite = $validated['overwrite'] ?? false;

        $assignments = collect($validated['assignments'])
            ->keyBy(fn (array $row) => (int) $row['task_id']);

        $tasks = ProjectTask::query()
            ->whereIn('id', $assignments->keys()->all())
            ->get()
            ->keyBy('id');

        $updated = [];
        $skipped = [];
        $notFound = [];

        foreach ($assignments as $taskId => $row) {
            $task = $tasks->get($taskId);

            if (! $task) {
                $notFound[] = $taskId;

                continue;
            }

            $newCategory = trim((string) $row['category']);
            $current = trim((string) ($task->category ?? ''));

            if ($current === $newCategory) {
                $skipped[] = [
                    'task_id' => $taskId,
                    'reason' => 'Zadanie ma już tę kategorię.',
                    'category' => $current,
                ];

                continue;
            }

            if ($current !== '' && ! $overwrite) {
                $skipped[] = [
                    'task_id' => $taskId,
                    'reason' => 'Zadanie ma już kategorię – nadpisanie wymaga overwrite=true.',
                    'current_category' => $current,
                    'proposed_category' => $newCategory,
                ];

                continue;
            }

            $task->update(['category' => $newCategory]);

            $updated[] = [
                'task_id' => $taskId,
                'name' => $task->name,
                'from' => $current !== '' ? $current : null,
                'to' => $newCategory,
            ];
        }

        return Response::json([
            'meta' => [
                'applied_at' => now()->toIso8601String(),
                'applied_by' => $user->name,
                'overwrite' => $overwrite,
            ],
            'counts' => [
                'updated' => count($updated),
                'skipped' => count($skipped),
                'not_found' => count($notFound),
            ],
            'updated' => $updated,
            'skipped' => $skipped,
            'not_found' => $notFound,
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'assignments' => $schema->array()
                ->description('Lista zmian do zapisania – każda pozycja to konkretne zadanie i jego kategoria.')
                ->items($schema->object([
                    'task_id' => $schema->integer()
                        ->description('ID zadania (project_tasks.id).')
                        ->required(),
                    'category' => $schema->string()
                        ->description('Kategoria do zapisania, najlepiej ze słownika known_categories.')
                        ->required(),
                ]))
                ->min(1)
                ->required(),
            'confirmed_by_user' => $schema->boolean()
                ->description('True tylko wtedy, gdy użytkownik wyraźnie zatwierdził pokazane mu propozycje.')
                ->required(),
            'overwrite' => $schema->boolean()
                ->description('Pozwól nadpisać istniejące kategorie. Domyślnie false.'),
        ];
    }
}
