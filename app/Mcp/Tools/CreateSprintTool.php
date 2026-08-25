<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Services\SprintCreationService;
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
        Tworzy nowy sprint (nazwa, cel, definition of done, daty).

        Zasada obowiązkowa: najpierw pokaż użytkownikowi pełną propozycję sprintu
        i poczekaj na wyraźną zgodę. Dopiero wtedy wywołaj z `confirmed_by_user: true`.

        Po utworzeniu sprintu zadania dodawaj narzędziem `create_task` (pole sprint_id)
        albo przypisuj istniejące przez `assign_tasks_to_sprint`.
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
            'definition_of_done' => ['nullable', 'string', 'max:10000'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        if ($validated['confirmed_by_user'] !== true) {
            return Response::error(
                'Tworzenie wstrzymane: brak potwierdzenia użytkownika. Pokaż propozycję sprintu, '
                .'poproś o akceptację i wywołaj ponownie z confirmed_by_user=true.'
            );
        }

        $sprint = app(SprintCreationService::class)->create([
            'name' => trim($validated['name']),
            'goal' => isset($validated['goal']) ? trim($validated['goal']) : null,
            'definition_of_done' => isset($validated['definition_of_done']) ? trim($validated['definition_of_done']) : null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ], $user);

        return Response::json([
            'meta' => [
                'created_at' => now()->toIso8601String(),
                'created_by' => $user->name,
            ],
            'sprint' => [
                'id' => $sprint->id,
                'name' => $sprint->name,
                'goal' => $sprint->goal,
                'definition_of_done' => $sprint->definition_of_done,
                'start_date' => $sprint->start_date?->toDateString(),
                'end_date' => $sprint->end_date?->toDateString(),
                'url' => route('sprints.show', $sprint),
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
            'definition_of_done' => $schema->string()
                ->description('Definition of done – kiedy sprint uznajemy za zamknięty.'),
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
