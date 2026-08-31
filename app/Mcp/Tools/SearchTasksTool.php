<?php

namespace App\Mcp\Tools;

use App\Enums\TaskStatus;
use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Support\TaskPayload;
use App\Models\ProjectTask;
use App\Models\User;
use App\Services\UserMentionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class SearchTasksTool extends Tool
{
    use ActsAsConfiguredUser;

    protected string $name = 'search_tasks';

    protected string $description = <<<'MARKDOWN'
        Szuka zadań po filtrach i zwraca karty (bez opisów i wątków komentarzy).

        Typowe wywołania:
        - taski osoby: `assigned_to` (users.id) albo `assignee_name`
        - taski stworzone przez osobę: `created_by` / `created_by_name`
        - kategoria: `category` (dokładna nazwa)
        - hygiene: `missing_category`, `unassigned`, `stale_days`, `overdue`
        - sprint: `sprint_id` albo `no_sprint`
        - tekst: `q` (fragment nazwy)

        Do treści zadania użyj `get_task` / `get_task_comments` na zwróconych ID.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $this->actingUser();

        $max = config('ai_tools.max_search_results');

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'ids' => ['nullable', 'array', 'max:100'],
            'ids.*' => ['integer', 'min:1'],
            'status' => ['nullable', 'string', 'in:pending,in_progress,completed,cancelled'],
            'category' => ['nullable', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'assignee_name' => ['nullable', 'string', 'max:255'],
            'created_by' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'created_by_name' => ['nullable', 'string', 'max:255'],
            'unassigned' => ['nullable', 'boolean'],
            'missing_category' => ['nullable', 'boolean'],
            'include_closed' => ['nullable', 'boolean'],
            'sprint_id' => ['nullable', 'integer', 'exists:sprints,id'],
            'no_sprint' => ['nullable', 'boolean'],
            'stale_days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'overdue' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', "max:{$max}"],
        ]);

        $assigneeId = $validated['assigned_to'] ?? null;
        if (! $assigneeId && ! empty($validated['assignee_name'])) {
            $user = $this->resolveUserByName($validated['assignee_name']);
            if (! $user) {
                return Response::error(
                    'Nie znaleziono użytkownika „'.$validated['assignee_name'].'”. Sprawdź dokładną nazwę przez `list_users`.'
                );
            }
            $assigneeId = $user->id;
        }

        $creatorId = $validated['created_by'] ?? null;
        if (! $creatorId && ! empty($validated['created_by_name'])) {
            $user = $this->resolveUserByName($validated['created_by_name']);
            if (! $user) {
                return Response::error(
                    'Nie znaleziono użytkownika „'.$validated['created_by_name'].'”. Sprawdź dokładną nazwę przez `list_users`.'
                );
            }
            $creatorId = $user->id;
        }

        $limit = (int) ($validated['limit'] ?? 50);
        $unassigned = (bool) ($validated['unassigned'] ?? false);
        $missingCategory = (bool) ($validated['missing_category'] ?? false);
        $includeClosed = (bool) ($validated['include_closed'] ?? false);
        $noSprint = (bool) ($validated['no_sprint'] ?? false);
        $overdue = (bool) ($validated['overdue'] ?? false);

        $query = ProjectTask::query()
            ->with(['assignedTo:id,name', 'createdBy:id,name', 'sprint:id,name'])
            ->withCount([
                'comments',
                'subtasks',
                'subtasks as completed_subtasks_count' => fn ($q) => $q->where('is_completed', true),
            ]);

        if (! empty($validated['ids'])) {
            $query->whereIn('id', $validated['ids']);
        }

        if (! empty($validated['q'])) {
            $query->where('name', 'like', '%'.$validated['q'].'%');
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        } elseif (! $includeClosed && ! $this->hasExplicitScope($validated)) {
            $query->whereIn('status', [TaskStatus::PENDING->value, TaskStatus::IN_PROGRESS->value]);
        }

        if (! empty($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        if ($unassigned) {
            $query->whereNull('assigned_to');
        } elseif ($assigneeId) {
            $query->where('assigned_to', $assigneeId);
        }

        if ($creatorId) {
            $query->where('created_by', $creatorId);
        }

        if ($missingCategory) {
            $query->where(function (Builder $q) {
                $q->whereNull('category')->orWhere('category', '');
            });
        }

        if ($noSprint) {
            $query->whereNull('sprint_id');
        } elseif (! empty($validated['sprint_id'])) {
            $query->where('sprint_id', $validated['sprint_id']);
        }

        if (! empty($validated['stale_days'])) {
            $query->where('updated_at', '<=', now()->subDays((int) $validated['stale_days']))
                ->whereNotIn('status', [TaskStatus::COMPLETED->value, TaskStatus::CANCELLED->value]);
        }

        if ($overdue) {
            $query->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->whereNotIn('status', [TaskStatus::COMPLETED->value, TaskStatus::CANCELLED->value]);
        }

        $total = (clone $query)->count();

        $tasks = $query
            ->orderByRaw('priority IS NULL')
            ->orderBy('priority')
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();

        return Response::json([
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'returned' => $tasks->count(),
                'total_matching' => $total,
                'filters' => [
                    'q' => $validated['q'] ?? null,
                    'status' => $validated['status'] ?? (($this->hasExplicitScope($validated) || $includeClosed) ? null : 'open'),
                    'category' => $validated['category'] ?? null,
                    'assigned_to' => $assigneeId,
                    'created_by' => $creatorId,
                    'unassigned' => $unassigned,
                    'missing_category' => $missingCategory,
                    'include_closed' => $includeClosed,
                    'sprint_id' => $validated['sprint_id'] ?? null,
                    'no_sprint' => $noSprint,
                    'stale_days' => $validated['stale_days'] ?? null,
                    'overdue' => $overdue,
                ],
            ],
            'tasks' => $tasks->map(fn (ProjectTask $task) => TaskPayload::listItem($task))->values()->all(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function hasExplicitScope(array $validated): bool
    {
        return ! empty($validated['status'])
            || ! empty($validated['ids'])
            || ! empty($validated['stale_days'])
            || ! empty($validated['overdue']);
    }

    private function resolveUserByName(string $name): ?User
    {
        return UserMentionService::resolveUserByMentionHandle($name)
            ?? User::query()->where('name', 'like', $name)->first();
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'q' => $schema->string()
                ->description('Fragment nazwy zadania.'),
            'ids' => $schema->array()
                ->description('Lista konkretnych ID zadań.')
                ->items($schema->integer()),
            'status' => $schema->string()
                ->description('Filtr statusu. Bez tego – tylko otwarte (pending + in_progress), chyba że ids/stale/overdue.')
                ->enum(['pending', 'in_progress', 'completed', 'cancelled']),
            'category' => $schema->string()
                ->description('Dokładna nazwa kategorii.'),
            'assigned_to' => $schema->integer()
                ->description('ID użytkownika (users.id), do którego zadanie jest przypisane.'),
            'assignee_name' => $schema->string()
                ->description('Nazwa wykonawcy (jak w @wzmiankach). Użyj, gdy nie znasz ID – albo wywołaj list_users.'),
            'created_by' => $schema->integer()
                ->description('ID twórcy zadania (users.id).'),
            'created_by_name' => $schema->string()
                ->description('Nazwa twórcy zadania.'),
            'unassigned' => $schema->boolean()
                ->description('Tylko zadania bez przypisanej osoby.'),
            'missing_category' => $schema->boolean()
                ->description('Tylko zadania bez kategorii.'),
            'include_closed' => $schema->boolean()
                ->description('Dołącz zakończone i anulowane. Domyślnie tylko otwarte.'),
            'sprint_id' => $schema->integer()
                ->description('ID sprintu.'),
            'no_sprint' => $schema->boolean()
                ->description('Tylko zadania poza sprintem.'),
            'stale_days' => $schema->integer()
                ->description('Otwarte zadania bez aktualizacji od co najmniej N dni.')
                ->min(1)
                ->max(90),
            'overdue' => $schema->boolean()
                ->description('Tylko otwarte zadania po terminie.'),
            'limit' => $schema->integer()
                ->description('Maksymalna liczba kart. Domyślnie 50.')
                ->min(1)
                ->max(200),
        ];
    }
}
