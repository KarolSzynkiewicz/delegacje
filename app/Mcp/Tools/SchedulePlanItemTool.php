<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Concerns\ParsesTaskId;
use App\Mcp\Concerns\ResolvesCalendarUser;
use App\Mcp\Support\PlanPayload;
use App\Models\WorkItem;
use App\Services\WorkItemPlanService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class SchedulePlanItemTool extends Tool
{
    use ActsAsConfiguredUser;
    use ParsesTaskId;
    use ResolvesCalendarUser;

    protected string $name = 'schedule_plan_item';

    protected string $description = <<<'MARKDOWN'
        Przypina work item z kolejki Planu na slot kalendarza
        (tworzy blok albo zapisuje godzinę spotkania).

        To NIE przesuwa istniejącego klocka — do długu / zmiany godziny
        użyj `move_plan_block`. Drugi drop z kolejki dodałby drugi blok.

        `work_item_id`, `starts_at` (ISO albo YYYY-MM-DD HH:MM).
        Opcjonalnie `ends_at` albo `all_day` (spotkania nie mogą być całodniowe).
        Osoba kalendarza musi być assignee WI.

        HITL: pokaż tytuł, osobę i slot, zgoda, dopiero
        `confirmed_by_user: true`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $actor = $this->actingUser();

        if (! $actor->isAdmin() && ! $actor->hasPermission('tasks.update')) {
            return Response::error(
                "Użytkownik {$actor->name} nie ma uprawnienia tasks.update – planowanie odrzucone."
            );
        }

        $validated = $request->validate([
            'confirmed_by_user' => ['required', 'boolean'],
            'work_item_id' => ['required'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'all_day' => ['nullable', 'boolean'],
            'assigned_to' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'assignee_name' => ['nullable', 'string', 'max:255'],
            'assigned_to_me' => ['nullable', 'boolean'],
        ]);

        if ($validated['confirmed_by_user'] !== true) {
            return Response::error(
                'Zapis wstrzymany: brak potwierdzenia. Pokaż WI, osobę i slot, '
                .'poproś o akceptację i wywołaj ponownie z confirmed_by_user=true.'
            );
        }

        $calendar = $this->calendarUserFrom($validated, $actor);
        if (is_string($calendar)) {
            return Response::error($calendar);
        }

        $itemId = $this->parseTaskId($validated['work_item_id']);
        if (! $itemId) {
            return Response::error('Podaj prawidłowe `work_item_id`.');
        }

        $item = WorkItem::query()->with('source')->find($itemId);
        if (! $item) {
            return Response::error("Nie znaleziono work itemu #{$itemId}.");
        }
        if ((int) $item->assignee_id !== (int) $calendar->id) {
            return Response::error(
                "WI #{$itemId} jest przypisane do innej osoby niż kalendarz {$calendar->name}."
            );
        }

        $allDay = (bool) ($validated['all_day'] ?? false);
        if ($allDay && $item->isMeetingItem()) {
            return Response::error('Spotkanie musi mieć godzinę — nie da się przypiąć jako cały dzień.');
        }

        $starts = CarbonImmutable::parse($validated['starts_at']);
        $ends = isset($validated['ends_at']) ? CarbonImmutable::parse($validated['ends_at']) : null;

        try {
            app(WorkItemPlanService::class)->placeFromQueue(
                $item,
                $calendar,
                $actor,
                $starts,
                $allDay,
                $ends,
            );
        } catch (ValidationException $e) {
            return Response::error(collect($e->errors())->flatten()->first() ?: $e->getMessage());
        }

        $weekStart = app(WorkItemPlanService::class)->weekStart($starts);

        return Response::json([
            'meta' => [
                'scheduled_by' => $actor->name,
                'calendar_user' => PlanPayload::user($calendar),
                'plan_url' => PlanPayload::planUrl($calendar, $weekStart),
            ],
            'work_item_id' => $item->id,
            'title' => $item->title,
            'starts_at' => $starts->toIso8601String(),
            'all_day' => $allDay,
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'work_item_id' => $schema->string()
                ->description('ID work itemu z plan_queue (liczba albo #123).')
                ->required(),
            'starts_at' => $schema->string()
                ->description('Start slotu, np. 2026-09-21T10:00:00 albo 2026-09-21 10:00.')
                ->required(),
            'ends_at' => $schema->string()
                ->description('Koniec. Bez tego – 30 min od startu (albo cały dzień).'),
            'all_day' => $schema->boolean()
                ->description('Cały dzień. Spotkania: false.'),
            'assigned_to' => $schema->integer()
                ->description('users.id kalendarza. Domyślnie konto MCP.'),
            'assignee_name' => $schema->string()
                ->description('Nazwa osoby kalendarza.'),
            'assigned_to_me' => $schema->boolean()
                ->description('Kalendarz konta MCP.'),
            'confirmed_by_user' => $schema->boolean()
                ->description('True tylko po wyraźnej zgodzie.')
                ->required(),
        ];
    }
}
