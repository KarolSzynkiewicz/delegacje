<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Concerns\ParsesTaskId;
use App\Mcp\Support\SprintChecklist;
use App\Models\Sprint;
use App\Models\SprintDodItem;
use App\Models\SprintMilestone;
use App\Models\SprintReadinessItem;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class AddSprintChecklistItemTool extends Tool
{
    use ActsAsConfiguredUser;
    use ParsesTaskId;

    protected string $name = 'add_sprint_checklist_item';

    protected string $description = <<<'MARKDOWN'
        Dodaje pozycję na checklistę sprintu.

        `list`:
        - `ready` – co musi być, by w ogóle zacząć pracę
        - `done` – kiedy uznamy, że zrobione
        - `milestone` – przełomowe osiągnięcie (wymaga `due_date`)

        Zasada obowiązkowa: pokaż sprint, listę i treść pozycji, poczekaj
        na zgodę, dopiero `confirmed_by_user: true`.

        Stan list: `sprint_insights`. Odhaczanie / edycja: `update_sprint_checklist_item`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = $this->actingUser();

        if (! $user->isAdmin() && ! $user->hasPermission('tasks.update')) {
            return Response::error(
                "Użytkownik {$user->name} nie ma uprawnienia tasks.update – zmiana checklisty odrzucona."
            );
        }

        $validated = $request->validate([
            'confirmed_by_user' => ['required', 'boolean'],
            'sprint_id' => ['required'],
            'list' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        if ($validated['confirmed_by_user'] !== true) {
            return Response::error(
                'Zapis wstrzymany: brak potwierdzenia użytkownika. Pokaż sprint, listę i treść, '
                .'poproś o akceptację i wywołaj ponownie z confirmed_by_user=true.'
            );
        }

        $list = SprintChecklist::normalize($validated['list']);
        if ($list === null) {
            return Response::error('`list` musi być: ready, done albo milestone.');
        }

        $sprintId = $this->parseTaskId($validated['sprint_id']);
        if (! $sprintId) {
            return Response::error('Podaj prawidłowe `sprint_id` (liczba albo #12).');
        }

        $sprint = Sprint::query()->find($sprintId);
        if (! $sprint) {
            return Response::error("Nie znaleziono sprintu #{$sprintId}.");
        }

        $name = trim($validated['name']);
        if ($name === '') {
            return Response::error('Nazwa pozycji nie może być pusta.');
        }

        if ($list === SprintChecklist::MILESTONE && empty($validated['due_date'])) {
            return Response::error('Kamień milowy wymaga `due_date` (YYYY-MM-DD).');
        }

        $item = match ($list) {
            SprintChecklist::READY => SprintReadinessItem::query()->create([
                'sprint_id' => $sprint->id,
                'name' => $name,
                'position' => $sprint->nextReadinessPosition(),
                'created_by' => $user->id,
            ]),
            SprintChecklist::DONE => SprintDodItem::query()->create([
                'sprint_id' => $sprint->id,
                'name' => $name,
                'position' => $sprint->nextDonePosition(),
                'created_by' => $user->id,
            ]),
            SprintChecklist::MILESTONE => SprintMilestone::query()->create([
                'sprint_id' => $sprint->id,
                'name' => $name,
                'due_date' => $validated['due_date'],
                'position' => $sprint->nextMilestonePosition(),
                'created_by' => $user->id,
            ]),
        };

        $sprint->refresh();

        return Response::json([
            'meta' => [
                'applied_at' => now()->toIso8601String(),
                'applied_by' => $user->name,
                'list' => $list,
                'list_label' => SprintChecklist::label($list),
            ],
            'item' => SprintChecklist::itemPayload($item, $list),
            'sprint' => SprintChecklist::payload($sprint),
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'sprint_id' => $schema->string()
                ->description('ID sprintu albo "#12".')
                ->required(),
            'list' => $schema->string()
                ->description('ready (start), done (ukończenie) albo milestone.')
                ->enum(['ready', 'done', 'milestone'])
                ->required(),
            'name' => $schema->string()
                ->description('Treść checkboxa / nazwa kamienia.')
                ->required(),
            'due_date' => $schema->string()
                ->description('Termin kamienia YYYY-MM-DD (wymagany przy list=milestone).'),
            'confirmed_by_user' => $schema->boolean()
                ->description('True tylko po wyraźnej zgodzie użytkownika.')
                ->required(),
        ];
    }
}
