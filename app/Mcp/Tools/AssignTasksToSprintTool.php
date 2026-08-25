<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Services\SprintAssignmentService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsDestructive]
#[IsIdempotent]
class AssignTasksToSprintTool extends Tool
{
    use ActsAsConfiguredUser;

    protected string $name = 'assign_tasks_to_sprint';

    protected string $description = <<<'MARKDOWN'
        Przypisuje istniejące zadania do sprintu (ustawia sprint_id i pozycję na tablicy).

        Zasada obowiązkowa: pokaż użytkownikowi listę zadań do przeniesienia
        (ID, nazwa, docelowy sprint) i poczekaj na zgodę. Dopiero wtedy wywołaj
        z `confirmed_by_user: true`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = $this->actingUser();

        if (! $user->isAdmin() && ! $user->hasPermission('tasks.update')) {
            return Response::error(
                "Użytkownik {$user->name} nie ma uprawnienia tasks.update – przypisanie odrzucone."
            );
        }

        $max = config('ai_tools.max_sprint_assignments');

        $validated = $request->validate([
            'confirmed_by_user' => ['required', 'boolean'],
            'sprint_id' => ['required', 'integer', 'exists:sprints,id'],
            'task_ids' => ['required', 'array', 'min:1', "max:{$max}"],
            'task_ids.*' => ['required', 'integer', 'min:1'],
        ]);

        if ($validated['confirmed_by_user'] !== true) {
            return Response::error(
                'Przypisanie wstrzymane: brak potwierdzenia użytkownika. Pokaż listę zadań '
                .'i sprint docelowy, poproś o akceptację i wywołaj ponownie z confirmed_by_user=true.'
            );
        }

        try {
            $result = app(SprintAssignmentService::class)->assign(
                (int) $validated['sprint_id'],
                $validated['task_ids'],
            );
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage());
        }

        return Response::json([
            'meta' => [
                'applied_at' => now()->toIso8601String(),
                'applied_by' => $user->name,
                'sprint_id' => (int) $validated['sprint_id'],
            ],
            'counts' => [
                'assigned' => count($result['assigned']),
                'skipped' => count($result['skipped']),
                'not_found' => count($result['notFound']),
            ],
            'assigned' => $result['assigned'],
            'skipped' => $result['skipped'],
            'not_found' => $result['notFound'],
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'sprint_id' => $schema->integer()
                ->description('ID sprintu docelowego (sprints.id).')
                ->required(),
            'task_ids' => $schema->array()
                ->description('Lista ID zadań (project_tasks.id) do przypisania.')
                ->items($schema->integer())
                ->min(1)
                ->required(),
            'confirmed_by_user' => $schema->boolean()
                ->description('True tylko po wyraźnej zgodzie użytkownika.')
                ->required(),
        ];
    }
}
