<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Concerns\ParsesTaskId;
use App\Mcp\Support\SprintChecklist;
use App\Models\Sprint;
use App\Models\SprintMilestone;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsDestructive]
#[IsIdempotent]
class UpdateSprintChecklistItemTool extends Tool
{
    use ActsAsConfiguredUser;
    use ParsesTaskId;

    protected string $name = 'update_sprint_checklist_item';

    protected string $description = <<<'MARKDOWN'
        Odhacza, odznacza, zmienia nazwę albo usuwa pozycję checklisty sprintu.

        `list`: `ready` (start pracy), `done` (kiedy zrobione), `milestone` (przełom).
        ID pozycji biorę z `sprint_insights` (`ready` / `done` / `milestones`).

        `done: true` odhacza, `false` otwiera ponownie. `delete: true` usuwa.
        Kamienie mogą dostać nowy `due_date`.

        Zasada obowiązkowa: pokaż sprint, pozycję i zmianę, poczekaj na zgodę,
        dopiero `confirmed_by_user: true`. Nowe pozycje: `add_sprint_checklist_item`.
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
            'item_id' => ['required'],
            'done' => ['nullable', 'boolean'],
            'name' => ['nullable', 'string', 'max:255'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
            'delete' => ['nullable', 'boolean'],
        ]);

        if ($validated['confirmed_by_user'] !== true) {
            return Response::error(
                'Zapis wstrzymany: brak potwierdzenia użytkownika. Pokaż pozycję i zmianę, '
                .'poproś o akceptację i wywołaj ponownie z confirmed_by_user=true.'
            );
        }

        $list = SprintChecklist::normalize($validated['list']);
        if ($list === null) {
            return Response::error('`list` musi być: ready, done albo milestone.');
        }

        $sprintId = $this->parseTaskId($validated['sprint_id']);
        $itemId = $this->parseTaskId($validated['item_id']);
        if (! $sprintId || ! $itemId) {
            return Response::error('Podaj prawidłowe `sprint_id` i `item_id` (liczba albo #12).');
        }

        $sprint = Sprint::query()->find($sprintId);
        if (! $sprint) {
            return Response::error("Nie znaleziono sprintu #{$sprintId}.");
        }

        $item = SprintChecklist::findItem($sprint, $list, $itemId);
        if (! $item) {
            return Response::error(
                'Nie znaleziono pozycji #'.$itemId.' na liście „'.SprintChecklist::label($list).'”.'
            );
        }

        $before = SprintChecklist::itemPayload($item, $list);
        $changed = [];

        if ($validated['delete'] ?? false) {
            $item->delete();
            $sprint->refresh();

            return Response::json([
                'meta' => [
                    'applied_at' => now()->toIso8601String(),
                    'applied_by' => $user->name,
                    'changed' => ['deleted'],
                    'list' => $list,
                ],
                'before' => $before,
                'item' => null,
                'sprint' => SprintChecklist::payload($sprint),
            ]);
        }

        $newName = null;
        if (array_key_exists('name', $validated) && $validated['name'] !== null) {
            $newName = trim($validated['name']);
            if ($newName === '') {
                return Response::error('Nazwa pozycji nie może być pusta.');
            }
        }

        $hasChange = (array_key_exists('done', $validated) && $validated['done'] !== null)
            || $newName !== null
            || array_key_exists('due_date', $validated);

        if (! $hasChange) {
            return Response::error('Nic do zapisania: podaj done, name, due_date albo delete.');
        }

        if (array_key_exists('done', $validated) && $validated['done'] !== null) {
            $complete = (bool) $validated['done'];
            $isDone = $item->isCompleted();
            if ($complete !== $isDone) {
                $item->update([
                    'completed_at' => $complete ? now() : null,
                ]);
            }
            $changed[] = 'done';
        }

        if ($newName !== null) {
            $item->update(['name' => $newName]);
            $changed[] = 'name';
        }

        if (array_key_exists('due_date', $validated) && $validated['due_date'] !== null) {
            if (! $item instanceof SprintMilestone) {
                return Response::error('due_date dotyczy tylko kamieni milowych.');
            }
            $item->update(['due_date' => $validated['due_date']]);
            $changed[] = 'due_date';
        }

        $item->refresh();
        $sprint->refresh();

        return Response::json([
            'meta' => [
                'applied_at' => now()->toIso8601String(),
                'applied_by' => $user->name,
                'changed' => array_values(array_unique($changed)),
                'list' => $list,
                'list_label' => SprintChecklist::label($list),
            ],
            'before' => $before,
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
            'item_id' => $schema->string()
                ->description('ID pozycji z sprint_insights.')
                ->required(),
            'done' => $schema->boolean()
                ->description('True = odhacz, false = odhacz wstecz.'),
            'name' => $schema->string()
                ->description('Nowa treść / nazwa.'),
            'due_date' => $schema->string()
                ->description('Nowy termin kamienia YYYY-MM-DD.'),
            'delete' => $schema->boolean()
                ->description('Usuń pozycję.'),
            'confirmed_by_user' => $schema->boolean()
                ->description('True tylko po wyraźnej zgodzie użytkownika.')
                ->required(),
        ];
    }
}
