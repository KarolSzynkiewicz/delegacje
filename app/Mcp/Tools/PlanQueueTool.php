<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Concerns\ResolvesCalendarUser;
use App\Mcp\Support\PlanPayload;
use App\Mcp\Support\WorkItemPayload;
use App\Models\WorkItem;
use App\Services\WorkItemPlanService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class PlanQueueTool extends Tool
{
    use ActsAsConfiguredUser;
    use ResolvesCalendarUser;

    protected string $name = 'plan_queue';

    protected string $description = <<<'MARKDOWN'
        Lewa kolumna Planu: otwarte work itemy osoby, które **nie mają**
        bloku, sesji ani slotu spotkania z datą ≥ dziś.

        To „Do przypięcia”, nie cały backlog. Wczorajszy ghost nadal
        może tu wrócić, jeśli nie ma slotu od dziś wzwyż.

        Osoba: `assigned_to` / `assignee_name` / `assigned_to_me`
        (domyślnie konto MCP). Opcjonalnie `category` (dokładna nazwa)
        i `q` (fragment tytułu).

        Kategoria X bez bloku: `plan_queue` + `category`.
        Potem HITL: `schedule_plan_item` albo `create_plan_session`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $actor = $this->actingUser();
        $service = app(WorkItemPlanService::class);
        $max = config('ai_tools.max_search_results');

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'assignee_name' => ['nullable', 'string', 'max:255'],
            'assigned_to_me' => ['nullable', 'boolean'],
            'category' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', "max:{$max}"],
        ]);

        $calendar = $this->calendarUserFrom($validated, $actor);
        if (is_string($calendar)) {
            return Response::error($calendar);
        }

        $limit = (int) ($validated['limit'] ?? 80);
        $now = CarbonImmutable::now();
        $query = $service->applyQueueConstraints(WorkItem::query()->with('source'), $calendar, $now);

        if (! empty($validated['category'])) {
            $query->where('work_items.category', $validated['category']);
        }
        if (! empty($validated['q'])) {
            $query->where('work_items.title', 'like', '%'.$validated['q'].'%');
        }

        $total = (clone $query)->count();
        $items = $query
            ->orderByRaw('due_at IS NULL')
            ->orderBy('due_at')
            ->orderByDesc('priority')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $weekStart = $service->weekStart($now);

        return Response::json([
            'meta' => [
                'calendar_user' => PlanPayload::user($calendar),
                'today' => $now->toDateString(),
                'returned' => $items->count(),
                'total_matching' => $total,
                'plan_url' => PlanPayload::planUrl($calendar, $weekStart),
            ],
            'items' => $items->map(fn (WorkItem $item) => WorkItemPayload::listItem($item))->values()->all(),
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'assigned_to' => $schema->integer()
                ->description('users.id osoby kolejki.'),
            'assignee_name' => $schema->string()
                ->description('Nazwa osoby (list_users).'),
            'assigned_to_me' => $schema->boolean()
                ->description('Kolejka konta MCP.'),
            'category' => $schema->string()
                ->description('Dokładna kategoria, np. Bug / UI.'),
            'q' => $schema->string()
                ->description('Fragment tytułu.'),
            'limit' => $schema->integer()
                ->description('Maks. kart. Domyślnie 80.')
                ->min(1)
                ->max(200),
        ];
    }
}
