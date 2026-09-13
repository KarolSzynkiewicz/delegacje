<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Services\SprintCreationService;
use App\Support\EntityLinks;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class CreateSprintTool extends Tool
{
    use ActsAsConfiguredUser;

    protected string $name = 'create_sprint';

    protected string $description = <<<'MARKDOWN'
        Tworzy nowy sprint (nazwa, cel, warunki startu, warunki ukończenia, daty).

        Checklisty na tablicy:
        - `definition_of_ready` – co musi być, by w ogóle zacząć pracę
        - `definition_of_done` – kiedy uznamy, że zrobione (tekst albo lista stringów)
        Kamienie milowe (`milestones`) to osobna lista przełomów.

        Po starcie odhaczaj je przez `update_sprint_checklist_item`.

        Zasada obowiązkowa: najpierw pokaż użytkownikowi pełną propozycję sprintu
        i poczekaj na wyraźną zgodę. Dopiero wtedy wywołaj z `confirmed_by_user: true`.

        Po utworzeniu sprintu zadania dodawaj narzędziem `create_task` (pole sprint_id)
        albo przypisuj istniejące przez `assign_tasks_to_sprint`. Ponowne wywołanie
        z tą samą nazwą i datami w krótkim oknie zwraca istniejący sprint
        (`meta.reused: true`) zamiast tworzyć drugi.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = $this->actingUser();

        if (! $user->isAdmin() && ! $user->hasPermission('tasks.view')) {
            return Response::error(
                "Użytkownik {$user->name} nie ma dostępu do modułu zadań – tworzenie sprintu odrzucone."
            );
        }

        $validated = $request->validate([
            'confirmed_by_user' => ['required', 'boolean'],
            'name' => ['required', 'string', 'max:255'],
            'goal' => ['nullable', 'string', 'max:10000'],
            'definition_of_ready' => ['nullable', 'array', 'max:50'],
            'definition_of_ready.*' => ['string', 'max:255'],
            'definition_of_done' => ['nullable'],
            'milestones' => ['nullable', 'array', 'max:20'],
            'milestones.*.name' => ['required_with:milestones', 'string', 'max:255'],
            'milestones.*.due_date' => ['nullable', 'date_format:Y-m-d'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        if ($validated['confirmed_by_user'] !== true) {
            return Response::error(
                'Tworzenie wstrzymane: brak potwierdzenia użytkownika. Pokaż propozycję sprintu, '
                .'poproś o akceptację i wywołaj ponownie z confirmed_by_user=true.'
            );
        }

        $done = $validated['definition_of_done'] ?? null;
        if (! (is_string($done) || is_array($done) || $done === null)) {
            return Response::error('`definition_of_done` musi być tekstem albo listą kryteriów.');
        }

        $sprint = app(SprintCreationService::class)->create([
            'name' => trim($validated['name']),
            'goal' => isset($validated['goal']) ? trim($validated['goal']) : null,
            'definition_of_ready' => $validated['definition_of_ready'] ?? null,
            'definition_of_done' => $done,
            'milestones' => $validated['milestones'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ], $user);

        $lists = $sprint->checklists();

        return Response::json([
            'meta' => [
                'created_at' => now()->toIso8601String(),
                'created_by' => $user->name,
                'reused' => ! $sprint->wasRecentlyCreated,
            ],
            'sprint' => [
                'id' => $sprint->id,
                'name' => $sprint->name,
                'goal' => $sprint->goal,
                'definition_of_ready' => $lists['readiness'],
                'definition_of_done' => $lists['done'],
                'milestones' => $lists['milestones'],
                'start_date' => $sprint->start_date?->toDateString(),
                'end_date' => $sprint->end_date?->toDateString(),
                'url' => EntityLinks::sprint($sprint),
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
                ->description('Nazwa sprintu.')
                ->required(),
            'goal' => $schema->string()
                ->description('Cel sprintu – po co go robimy.'),
            'definition_of_ready' => $schema->array()
                ->description('Co musi być, by w ogóle zacząć pracę – lista zdań.')
                ->items($schema->string()),
            'definition_of_done' => $schema->string()
                ->description('Kiedy uznamy, że zrobione – zdanie albo lista (w JSON-ie tablica stringów też przechodzi).'),
            'start_date' => $schema->string()
                ->description('Data rozpoczęcia YYYY-MM-DD.')
                ->required(),
            'end_date' => $schema->string()
                ->description('Data zakończenia YYYY-MM-DD (włącznie).')
                ->required(),
            'confirmed_by_user' => $schema->boolean()
                ->description('True tylko po wyraźnej zgodzie użytkownika na pokazaną propozycję.')
                ->required(),
        ];
    }
}
