<?php

namespace App\Mcp\Tools;

use App\Enums\WorkItemStatus;
use App\Enums\WorkItemType;
use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Support\WorkItemPayload;
use App\Models\User;
use App\Models\WorkItem;
use App\Services\UserMentionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class SearchWorkItemsTool extends Tool
{
    use ActsAsConfiguredUser;

    protected string $name = 'search_work_items';

    protected string $description = <<<'MARKDOWN'
        Szuka pozycji z indeksu work items – tej samej siatki co `/tasks`:
        zadania, spotkania, procedury, podzadania, zatwierdzenia, wzmianki,
        kompletacje, callbacki. Karty bez opisów.

        To właściwe „co u mnie / co w sprincie” dla mieszanych typów.
        `search_tasks` widzi tylko tabelę `project_tasks`.
        `backlog_overview` to work items, ale tylko otwarte i poza sprintem.

        Filtry: `assignee_name` / `assigned_to` / `assigned_to_me`, `type` albo
        `types` (meeting, approval, procedure_run, …), `sprint_id` / `no_sprint`,
        `category`, `q`, `overdue`, `unassigned`.

        Potem: pole `next.tool` – zwykle `get_task` albo `get_procedure_run`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = $this->actingUser();

        $max = config('ai_tools.max_search_results');

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:80'],
            'types' => ['nullable', 'array', 'max:10'],
            'types.*' => ['string'],
            'status' => ['nullable', 'string', 'in:pending,in_progress,completed,cancelled'],
            'category' => ['nullable', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'assignee_name' => ['nullable', 'string', 'max:255'],
            'assigned_to_me' => ['nullable', 'boolean'],
            'created_by' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'created_by_name' => ['nullable', 'string', 'max:255'],
            'unassigned' => ['nullable', 'boolean'],
            'missing_category' => ['nullable', 'boolean'],
            'include_closed' => ['nullable', 'boolean'],
            'sprint_id' => ['nullable', 'integer', 'exists:sprints,id'],
            'no_sprint' => ['nullable', 'boolean'],
            'overdue' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', "max:{$max}"],
        ]);

        $types = $this->parseTypes($validated);
        if (array_key_exists('type', $validated) && filled($validated['type']) && $types === []) {
            return Response::error(
                'Nieznany `type`. Dozwolone: '.implode(', ', array_column(WorkItemType::cases(), 'value')).'.'
            );
        }

        $assigneeId = $validated['assigned_to'] ?? null;
        if ($validated['assigned_to_me'] ?? false) {
            $assigneeId = $user->id;
        } elseif (! $assigneeId && ! empty($validated['assignee_name'])) {
            $resolved = $this->resolveUserByName($validated['assignee_name']);
            if (! $resolved) {
                return Response::error(
                    'Nie znaleziono użytkownika „'.$validated['assignee_name'].'”. Sprawdź dokładną nazwę przez `list_users`.'
                );
            }
            $assigneeId = $resolved->id;
        }

        $creatorId = $validated['created_by'] ?? null;
        if (! $creatorId && ! empty($validated['created_by_name'])) {
            $resolved = $this->resolveUserByName($validated['created_by_name']);
            if (! $resolved) {
                return Response::error(
                    'Nie znaleziono użytkownika „'.$validated['created_by_name'].'”. Sprawdź dokładną nazwę przez `list_users`.'
                );
            }
            $creatorId = $resolved->id;
        }

        $limit = (int) ($validated['limit'] ?? 50);
        $unassigned = (bool) ($validated['unassigned'] ?? false);
        $missingCategory = (bool) ($validated['missing_category'] ?? false);
        $includeClosed = (bool) ($validated['include_closed'] ?? false);
        $noSprint = (bool) ($validated['no_sprint'] ?? false);
        $overdue = (bool) ($validated['overdue'] ?? false);

        $query = WorkItem::query()
            ->with(['assignee:id,name', 'createdBy:id,name', 'sprint:id,name', 'source']);

        if (! empty($validated['q'])) {
            $query->where('title', 'like', '%'.$validated['q'].'%');
        }

        if ($types !== []) {
            $query->whereIn('type', $types);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        } elseif (! $includeClosed) {
            $query->whereIn('status', [WorkItemStatus::Pending->value, WorkItemStatus::InProgress->value]);
        }

        if (! empty($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        if ($unassigned) {
            $query->whereNull('assignee_id');
        } elseif ($assigneeId) {
            $query->where('assignee_id', $assigneeId);
        }

        if ($creatorId) {
            $query->where('created_by_id', $creatorId);
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

        if ($overdue) {
            $query->whereNotNull('due_at')
                ->whereDate('due_at', '<', now()->toDateString())
                ->whereIn('status', [WorkItemStatus::Pending->value, WorkItemStatus::InProgress->value]);
        }

        $total = (clone $query)->count();

        $items = $query
            ->orderByRaw('priority IS NULL')
            ->orderBy('priority')
            ->orderByRaw('due_at IS NULL')
            ->orderBy('due_at')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();

        return Response::json([
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'returned' => $items->count(),
                'total_matching' => $total,
                'filters' => [
                    'q' => $validated['q'] ?? null,
                    'types' => $types !== [] ? $types : null,
                    'status' => $validated['status'] ?? ($includeClosed ? null : 'open'),
                    'category' => $validated['category'] ?? null,
                    'assigned_to' => $assigneeId,
                    'created_by' => $creatorId,
                    'unassigned' => $unassigned,
                    'sprint_id' => $validated['sprint_id'] ?? null,
                    'no_sprint' => $noSprint,
                    'overdue' => $overdue,
                ],
            ],
            'items' => $items->map(fn (WorkItem $item) => WorkItemPayload::listItem($item))->values()->all(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return list<string>
     */
    private function parseTypes(array $validated): array
    {
        $raw = [];
        if (filled($validated['type'] ?? null)) {
            $raw = array_merge($raw, preg_split('/[,\s]+/', (string) $validated['type']) ?: []);
        }
        foreach ($validated['types'] ?? [] as $value) {
            $raw[] = (string) $value;
        }

        $allowed = array_column(WorkItemType::cases(), 'value');

        return collect($raw)
            ->map(fn (string $type) => strtolower(trim($type)))
            ->filter(fn (string $type) => $type !== '' && in_array($type, $allowed, true))
            ->unique()
            ->values()
            ->all();
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
        $types = array_column(WorkItemType::cases(), 'value');

        return [
            'q' => $schema->string()
                ->description('Fragment tytułu.'),
            'type' => $schema->string()
                ->description('Jeden typ albo lista po przecinku: task, meeting, approval, procedure_run, subtask, follow_up, dispatch, callback.'),
            'types' => $schema->array()
                ->description('Kilka typów naraz.')
                ->items($schema->string()->enum($types)),
            'status' => $schema->string()
                ->description('Bez tego – tylko otwarte (pending + in_progress).')
                ->enum(['pending', 'in_progress', 'completed', 'cancelled']),
            'category' => $schema->string()
                ->description('Dokładna nazwa kategorii.'),
            'assigned_to' => $schema->integer()
                ->description('users.id wykonawcy.'),
            'assignee_name' => $schema->string()
                ->description('Nazwa wykonawcy (jak w @wzmiankach).'),
            'assigned_to_me' => $schema->boolean()
                ->description('Tylko pozycje przypisane do Ciebie (konto MCP).'),
            'created_by' => $schema->integer()
                ->description('users.id twórcy.'),
            'created_by_name' => $schema->string()
                ->description('Nazwa twórcy.'),
            'unassigned' => $schema->boolean()
                ->description('Bez osoby.'),
            'missing_category' => $schema->boolean()
                ->description('Bez kategorii.'),
            'include_closed' => $schema->boolean()
                ->description('Dołącz zakończone i anulowane.'),
            'sprint_id' => $schema->integer()
                ->description('Tylko w tym sprincie.'),
            'no_sprint' => $schema->boolean()
                ->description('Tylko poza sprintem (jak backlog).'),
            'overdue' => $schema->boolean()
                ->description('Otwarte po terminie.'),
            'limit' => $schema->integer()
                ->description('Maksymalna liczba kart. Domyślnie 50.')
                ->min(1)
                ->max(200),
        ];
    }
}
