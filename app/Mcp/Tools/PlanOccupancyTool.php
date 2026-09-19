<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Concerns\ResolvesCalendarUser;
use App\Mcp\Support\PlanPayload;
use App\Services\WorkItemPlanService;
use App\Support\Plan\PlanEvent;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class PlanOccupancyTool extends Tool
{
    use ActsAsConfiguredUser;
    use ResolvesCalendarUser;

    protected string $name = 'plan_occupancy';

    protected string $description = <<<'MARKDOWN'
        Kalendarz Planu (`/plan`) jednej osoby: bloki czasu, sesje i spotkania
        w tygodniu albo w jednym dniu.

        `ghost: true` = slot sprzed dziś, WI nadal otwarte (strefa długu).
        Sesja ma `is_session: true` i listę `members` (otwarte WI w worku).
        Spotkanie ma `kind: meeting`, `block_id: null` i `work_item_id`.
        Zwykły klocek ma `block_id` i `work_item_id`.

        Osoba kalendarza: `assigned_to` / `assignee_name` / `assigned_to_me`.
        Bez tego – konto MCP (OAuth na `/mcp/tasks` albo MCP_ACTOR_USER_ID).

        Zakres: `date` (jeden dzień), `week` (dowolna data w tygodniu Pn–Nd),
        albo `period` this_week / last_week. Domyślnie bieżący tydzień.

        `debt_only: true` – tylko dni przed dziś, które mają eventy.

        Do kolejki bez slotu ≥ dziś użyj `plan_queue`.
        Mutacje: `schedule_plan_item`, `move_plan_block`, `unschedule_plan_block`,
        `create_plan_session` (HITL, `confirmed_by_user`).
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $actor = $this->actingUser();
        $service = app(WorkItemPlanService::class);

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'assignee_name' => ['nullable', 'string', 'max:255'],
            'assigned_to_me' => ['nullable', 'boolean'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'week' => ['nullable', 'date_format:Y-m-d'],
            'period' => ['nullable', 'string', 'in:this_week,last_week'],
            'debt_only' => ['nullable', 'boolean'],
        ]);

        $calendar = $this->calendarUserFrom($validated, $actor);
        if (is_string($calendar)) {
            return Response::error($calendar);
        }

        $now = CarbonImmutable::now();
        $today = $now->toDateString();
        $anchor = $now;
        if (! empty($validated['date'])) {
            $anchor = CarbonImmutable::parse($validated['date']);
        } elseif (! empty($validated['week'])) {
            $anchor = CarbonImmutable::parse($validated['week']);
        } elseif (($validated['period'] ?? null) === 'last_week') {
            $anchor = $now->subWeek();
        }

        $weekStart = $service->weekStart($anchor);
        $occupancy = $service->occupancy($calendar, $weekStart, $now);
        $onlyDate = $validated['date'] ?? null;
        $debtOnly = (bool) ($validated['debt_only'] ?? false);

        $days = [];
        $debtEvents = 0;

        foreach ($service->weekDays($weekStart) as $day) {
            $date = $day->toDateString();
            if ($onlyDate && $onlyDate !== $date) {
                continue;
            }

            $isDebt = $date < $today;
            $timed = array_values(array_map(
                fn (PlanEvent $event) => PlanPayload::event($event),
                $occupancy['timed'][$date] ?? [],
            ));
            $allDay = array_values(array_map(
                fn (PlanEvent $event) => PlanPayload::event($event),
                $occupancy['allDay'][$date] ?? [],
            ));

            if ($debtOnly && (! $isDebt || ($timed === [] && $allDay === []))) {
                continue;
            }

            if ($isDebt) {
                $debtEvents += count($timed) + count($allDay);
            }

            $days[] = [
                'date' => $date,
                'debt' => $isDebt,
                'timed' => $timed,
                'all_day' => $allDay,
            ];
        }

        return Response::json([
            'meta' => [
                'calendar_user' => PlanPayload::user($calendar),
                'today' => $today,
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekStart->addDays(6)->toDateString(),
                'plan_url' => PlanPayload::planUrl($calendar, $weekStart),
                'debt_event_count' => $debtEvents,
                'day_count' => count($days),
            ],
            'days' => $days,
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'assigned_to' => $schema->integer()
                ->description('users.id osoby, której kalendarz czytamy.'),
            'assignee_name' => $schema->string()
                ->description('Nazwa osoby kalendarza (jak w list_users).'),
            'assigned_to_me' => $schema->boolean()
                ->description('Kalendarz konta MCP.'),
            'date' => $schema->string()
                ->description('Jeden dzień YYYY-MM-DD.'),
            'week' => $schema->string()
                ->description('Dowolna data w tygodniu (Pn–Nd) YYYY-MM-DD.'),
            'period' => $schema->string()
                ->description('this_week albo last_week. Domyślnie this_week.')
                ->enum(['this_week', 'last_week']),
            'debt_only' => $schema->boolean()
                ->description('Tylko dni przed dziś, które mają eventy (strefa długu).'),
        ];
    }
}
