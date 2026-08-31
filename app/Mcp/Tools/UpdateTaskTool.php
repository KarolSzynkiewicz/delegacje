<?php

namespace App\Mcp\Tools;

use App\Enums\TaskStatus;
use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Concerns\ParsesTaskId;
use App\Mcp\Support\TaskPayload;
use App\Models\ProjectTask;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsDestructive]
#[IsIdempotent]
class UpdateTaskTool extends Tool
{
    use ActsAsConfiguredUser;
    use ParsesTaskId;

    protected string $name = 'update_task';

    protected string $description = <<<'MARKDOWN'
        Aktualizuje jedno zadanie: status, przypisanie, termin albo priorytet.

        Zasada obowiązkowa: pokaż użytkownikowi co się zmieni (ID, nazwa, pola)
        i poczekaj na zgodę. Dopiero wtedy wywołaj z `confirmed_by_user: true`.

        Kategorie: `set_task_categories`. Sprint: `assign_tasks_to_sprint`.
        Zdjęcie przypisania: `unassign: true`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = $this->actingUser();

        if (! $user->isAdmin() && ! $user->hasPermission('tasks.update')) {
            return Response::error(
                "Użytkownik {$user->name} nie ma uprawnienia tasks.update – zmiana zadania odrzucona."
            );
        }

        $validated = $request->validate([
            'confirmed_by_user' => ['required', 'boolean'],
            'task_id' => ['required'],
            'status' => ['nullable', 'string', 'in:pending,in_progress,completed,cancelled'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'unassign' => ['nullable', 'boolean'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
            'clear_due_date' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:5'],
            'clear_priority' => ['nullable', 'boolean'],
        ]);

        if ($validated['confirmed_by_user'] !== true) {
            return Response::error(
                'Zapis wstrzymany: brak potwierdzenia użytkownika. Pokaż planowaną zmianę, '
                .'poproś o akceptację i wywołaj ponownie z confirmed_by_user=true.'
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

        $hasChange = isset($validated['status'])
            || array_key_exists('assigned_to', $validated)
            || ($validated['unassign'] ?? false)
            || array_key_exists('due_date', $validated)
            || ($validated['clear_due_date'] ?? false)
            || array_key_exists('priority', $validated)
            || ($validated['clear_priority'] ?? false);

        if (! $hasChange) {
            return Response::error(
                'Nic do zapisania: podaj status, assigned_to/unassign, due_date/clear_due_date albo priority/clear_priority.'
            );
        }

        $before = TaskPayload::listItem($task);
        $changed = [];

        if (isset($validated['status'])) {
            $this->applyStatus($task, TaskStatus::from($validated['status']));
            $changed[] = 'status';
        }

        if ($validated['unassign'] ?? false) {
            $task->reassign(null);
            $changed[] = 'assigned_to';
        } elseif (array_key_exists('assigned_to', $validated) && $validated['assigned_to'] !== null) {
            $previous = $task->assigned_to;
            $assignee = User::query()->find((int) $validated['assigned_to']);
            $task->reassign($assignee);
            if ($assignee && $assignee->id !== $previous && $assignee->id !== $user->id) {
                $assignee->notify(new TaskAssigned($task->fresh(), $user));
            }
            $changed[] = 'assigned_to';
        }

        if ($validated['clear_due_date'] ?? false) {
            $task->update(['due_date' => null]);
            $changed[] = 'due_date';
        } elseif (! empty($validated['due_date'])) {
            $task->update(['due_date' => $validated['due_date']]);
            $changed[] = 'due_date';
        }

        if ($validated['clear_priority'] ?? false) {
            $task->update(['priority' => null]);
            $changed[] = 'priority';
        } elseif (array_key_exists('priority', $validated) && $validated['priority'] !== null) {
            $task->update(['priority' => (int) $validated['priority']]);
            $changed[] = 'priority';
        }

        $task->refresh();

        return Response::json([
            'meta' => [
                'applied_at' => now()->toIso8601String(),
                'applied_by' => $user->name,
                'changed' => array_values(array_unique($changed)),
            ],
            'before' => $before,
            'task' => TaskPayload::listItem($task),
        ]);
    }

    private function applyStatus(ProjectTask $task, TaskStatus $status): void
    {
        match ($status) {
            TaskStatus::PENDING => $task->reopen(),
            TaskStatus::IN_PROGRESS => $task->markInProgress(),
            TaskStatus::COMPLETED => $task->markCompleted(),
            TaskStatus::CANCELLED => $task->cancel(),
        };
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
            'confirmed_by_user' => $schema->boolean()
                ->description('True tylko po wyraźnej zgodzie użytkownika.')
                ->required(),
            'status' => $schema->string()
                ->description('Nowy status.')
                ->enum(['pending', 'in_progress', 'completed', 'cancelled']),
            'assigned_to' => $schema->integer()
                ->description('ID użytkownika do przypisania (users.id).'),
            'unassign' => $schema->boolean()
                ->description('Zdejmij przypisanie (assigned_to = null).'),
            'due_date' => $schema->string()
                ->description('Termin YYYY-MM-DD.'),
            'clear_due_date' => $schema->boolean()
                ->description('Usuń termin.'),
            'priority' => $schema->integer()
                ->description('Priorytet 1–5 (1 = najwyższy).')
                ->min(1)
                ->max(5),
            'clear_priority' => $schema->boolean()
                ->description('Usuń priorytet.'),
        ];
    }
}
