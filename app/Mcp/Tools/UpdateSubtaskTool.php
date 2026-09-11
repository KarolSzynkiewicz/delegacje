<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Concerns\ParsesTaskId;
use App\Mcp\Support\TaskPayload;
use App\Models\TaskSubtask;
use App\Models\TaskSubtaskEvent;
use App\Models\User;
use App\Notifications\TaskAssigned;
use App\Services\UserMentionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsDestructive]
#[IsIdempotent]
class UpdateSubtaskTool extends Tool
{
    use ActsAsConfiguredUser;
    use ParsesTaskId;

    protected string $name = 'update_subtask';

    protected string $description = <<<'MARKDOWN'
        Aktualizuje jedno podzadanie: odhaczenie, nazwę albo przypisanie.
        Nie zamyka zadania-rodzica – to osobna decyzja przez `update_task`.

        Zasada obowiązkowa: pokaż ID podzadania, rodzica i planowaną zmianę,
        poczekaj na zgodę, dopiero wtedy `confirmed_by_user: true`.

        Nowe kroki: `add_subtasks`. ID kroków: `get_task`.
        Zdjęcie osoby: `unassign: true`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = $this->actingUser();

        if (! $user->isAdmin() && ! $user->hasPermission('tasks.update')) {
            return Response::error(
                "Użytkownik {$user->name} nie ma uprawnienia tasks.update – zmiana podzadania odrzucona."
            );
        }

        $validated = $request->validate([
            'confirmed_by_user' => ['required', 'boolean'],
            'subtask_id' => ['required'],
            'is_completed' => ['nullable', 'boolean'],
            'name' => ['nullable', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'unassign' => ['nullable', 'boolean'],
        ]);

        if ($validated['confirmed_by_user'] !== true) {
            return Response::error(
                'Zapis wstrzymany: brak potwierdzenia użytkownika. Pokaż planowaną zmianę, '
                .'poproś o akceptację i wywołaj ponownie z confirmed_by_user=true.'
            );
        }

        $id = $this->parseTaskId($validated['subtask_id']);
        if (! $id) {
            return Response::error('Podaj prawidłowe `subtask_id` (liczba albo #12).');
        }

        $subtask = TaskSubtask::query()->find($id);
        if (! $subtask) {
            return Response::error("Nie znaleziono podzadania #{$id}.");
        }

        $newName = null;
        if (array_key_exists('name', $validated) && $validated['name'] !== null) {
            $newName = trim($validated['name']);
            if ($newName === '') {
                return Response::error('Nazwa podzadania nie może być pusta.');
            }
        }

        $hasChange = (array_key_exists('is_completed', $validated) && $validated['is_completed'] !== null)
            || $newName !== null
            || array_key_exists('assigned_to', $validated)
            || ($validated['unassign'] ?? false);

        if (! $hasChange) {
            return Response::error(
                'Nic do zapisania: podaj is_completed, name albo assigned_to/unassign.'
            );
        }

        $subtask->loadMissing('task');
        $before = TaskPayload::subtask($subtask);
        $changed = [];

        if (array_key_exists('is_completed', $validated) && $validated['is_completed'] !== null) {
            $complete = (bool) $validated['is_completed'];
            if ($complete && ! $subtask->is_completed) {
                $subtask->markCompleted();
                TaskSubtaskEvent::log($subtask, 'completed', $user->id);
            } elseif (! $complete && $subtask->is_completed) {
                $subtask->markIncomplete();
                TaskSubtaskEvent::log($subtask, 'reopened', $user->id);
            }
            $changed[] = 'is_completed';
        }

        if ($newName !== null) {
            if ($newName !== $subtask->name) {
                TaskSubtaskEvent::log($subtask, 'renamed', $user->id);
                $subtask->update(['name' => $newName]);
                if ($subtask->task) {
                    app(UserMentionService::class)->notifySubtaskMentions(
                        $subtask->task,
                        $subtask->fresh(),
                        $newName,
                        $user,
                    );
                }
            }
            $changed[] = 'name';
        }

        if ($validated['unassign'] ?? false) {
            $subtask->update(['assigned_to' => null]);
            $changed[] = 'assigned_to';
        } elseif (array_key_exists('assigned_to', $validated) && $validated['assigned_to'] !== null) {
            $previous = $subtask->assigned_to;
            $assigneeId = (int) $validated['assigned_to'];
            $subtask->update(['assigned_to' => $assigneeId]);
            if ($assigneeId !== $previous && $assigneeId !== $user->id) {
                User::query()->find($assigneeId)?->notify(
                    new TaskAssigned($subtask->fresh() ?? $subtask, $user)
                );
            }
            $changed[] = 'assigned_to';
        }

        $subtask->refresh();

        return Response::json([
            'meta' => [
                'applied_at' => now()->toIso8601String(),
                'applied_by' => $user->name,
                'changed' => array_values(array_unique($changed)),
            ],
            'before' => $before,
            'subtask' => TaskPayload::subtask($subtask),
            'task' => [
                'id' => $subtask->task_id,
                'name' => $subtask->task?->name,
                'url' => $subtask->task ? route('tasks.show', $subtask->task) : null,
            ],
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'subtask_id' => $schema->string()
                ->description('ID podzadania albo "#12".')
                ->required(),
            'confirmed_by_user' => $schema->boolean()
                ->description('True tylko po wyraźnej zgodzie użytkownika.')
                ->required(),
            'is_completed' => $schema->boolean()
                ->description('True = odhacz, false = odhacz wstecz (otwórz ponownie).'),
            'name' => $schema->string()
                ->description('Nowa nazwa kroku.'),
            'assigned_to' => $schema->integer()
                ->description('ID użytkownika (users.id) do przypisania podzadania.'),
            'unassign' => $schema->boolean()
                ->description('Zdejmij przypisanie (assigned_to = null).'),
        ];
    }
}
