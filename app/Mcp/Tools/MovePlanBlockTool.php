<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Concerns\ParsesTaskId;
use App\Mcp\Concerns\ResolvesCalendarUser;
use App\Mcp\Support\PlanPayload;
use App\Models\WorkItem;
use App\Models\WorkItemTimeBlock;
use App\Services\WorkItemPlanService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsDestructive]
#[IsIdempotent]
class MovePlanBlockTool extends Tool
{
    use ActsAsConfiguredUser;
    use ParsesTaskId;
    use ResolvesCalendarUser;

    protected string $name = 'move_plan_block';

    protected string $description = <<<'MARKDOWN'
        Przesuwa albo zmienia koniec istniejącego slotu Planu.

        Klocek / sesja: `block_id` z `plan_occupancy`.
        Spotkanie: `work_item_id` (kind=meeting, bez block_id).

        `starts_at` – nowy początek (przesunięcie, zachowuje długość
        albo 30 min gdy było całodniowe → timed).
        `all_day: true` – cały dzień (spotkań nie wolno).
        `ends_at` bez `starts_at` – tylko zmiana końca (resize).
        Oba – przesuń, potem ustaw koniec.

        Dług (ghost): ten sam `block_id`, nowy `starts_at` ≥ dziś.
        Nie używaj `schedule_plan_item` na już zaplanowanym klocku.

        HITL: pokaż stary i nowy slot, zgoda, `confirmed_by_user: true`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $actor = $this->actingUser();

        if (! $actor->isAdmin() && ! $actor->hasPermission('tasks.update')) {
            return Response::error(
                "Użytkownik {$actor->name} nie ma uprawnienia tasks.update – przesunięcie odrzucone."
            );
        }

        $validated = $request->validate([
            'confirmed_by_user' => ['required', 'boolean'],
            'block_id' => ['nullable'],
            'work_item_id' => ['nullable'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'all_day' => ['nullable', 'boolean'],
            'assigned_to' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'assignee_name' => ['nullable', 'string', 'max:255'],
            'assigned_to_me' => ['nullable', 'boolean'],
        ]);

        if ($validated['confirmed_by_user'] !== true) {
            return Response::error(
                'Zapis wstrzymany: brak potwierdzenia. Pokaż stary i nowy slot, '
                .'poproś o akceptację i wywołaj ponownie z confirmed_by_user=true.'
            );
        }

        if (empty($validated['starts_at']) && empty($validated['ends_at'])) {
            return Response::error('Podaj `starts_at` (przesunięcie) i/lub `ends_at` (resize).');
        }

        $calendar = $this->calendarUserFrom($validated, $actor);
        if (is_string($calendar)) {
            return Response::error($calendar);
        }

        $service = app(WorkItemPlanService::class);
        $allDay = (bool) ($validated['all_day'] ?? false);
        $starts = isset($validated['starts_at']) ? CarbonImmutable::parse($validated['starts_at']) : null;
        $ends = isset($validated['ends_at']) ? CarbonImmutable::parse($validated['ends_at']) : null;

        $blockId = isset($validated['block_id']) ? $this->parseTaskId($validated['block_id']) : null;
        $workItemId = isset($validated['work_item_id']) ? $this->parseTaskId($validated['work_item_id']) : null;

        try {
            if ($blockId) {
                $block = WorkItemTimeBlock::query()
                    ->whereKey($blockId)
                    ->where('user_id', $calendar->id)
                    ->first();
                if (! $block) {
                    return Response::error("Nie znaleziono bloku #{$blockId} w kalendarzu {$calendar->name}.");
                }

                if ($starts) {
                    $service->moveBlock($block, $starts, $allDay);
                    $block->refresh();
                }
                if ($ends && ! $block->all_day) {
                    $service->resizeBlock($block, $ends);
                    $block->refresh();
                }

                $weekStart = $service->weekStart($block->starts_at);

                return Response::json([
                    'meta' => [
                        'moved_by' => $actor->name,
                        'calendar_user' => PlanPayload::user($calendar),
                        'plan_url' => PlanPayload::planUrl($calendar, $weekStart),
                    ],
                    'block_id' => $block->id,
                    'is_session' => $block->isSession(),
                    'work_item_id' => $block->work_item_id,
                    'starts_at' => $block->starts_at?->toIso8601String(),
                    'ends_at' => $block->ends_at?->toIso8601String(),
                    'all_day' => (bool) $block->all_day,
                ]);
            }

            if ($workItemId) {
                $item = WorkItem::query()->with('source')->find($workItemId);
                if (! $item || ! $item->isMeetingItem()) {
                    return Response::error(
                        'Bez `block_id` podaj `work_item_id` spotkania z plan_occupancy (kind=meeting).'
                    );
                }
                if ($allDay) {
                    return Response::error('Spotkanie musi mieć godzinę.');
                }
                if ($starts) {
                    $service->moveMeeting($item, $starts);
                }
                if ($ends) {
                    $service->resizeMeeting($item->fresh(['source']), $ends);
                }
                $item->refresh()->load('source');
                $task = $item->source;
                $weekStart = $service->weekStart($task && $task->starts_at ? $task->starts_at : ($starts ?? now()));

                return Response::json([
                    'meta' => [
                        'moved_by' => $actor->name,
                        'calendar_user' => PlanPayload::user($calendar),
                        'plan_url' => PlanPayload::planUrl($calendar, $weekStart),
                    ],
                    'work_item_id' => $item->id,
                    'kind' => 'meeting',
                    'starts_at' => $task?->starts_at?->toIso8601String(),
                    'ends_at' => $task?->ends_at?->toIso8601String(),
                ]);
            }
        } catch (ValidationException $e) {
            return Response::error(collect($e->errors())->flatten()->first() ?: $e->getMessage());
        }

        return Response::error('Podaj `block_id` (klocek/sesja) albo `work_item_id` spotkania.');
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'block_id' => $schema->string()
                ->description('ID bloku / sesji z plan_occupancy.'),
            'work_item_id' => $schema->string()
                ->description('ID spotkania (kind=meeting), gdy nie ma block_id.'),
            'starts_at' => $schema->string()
                ->description('Nowy start ISO / YYYY-MM-DD HH:MM.'),
            'ends_at' => $schema->string()
                ->description('Nowy koniec (resize). Samo ends_at bez starts_at = tylko długość.'),
            'all_day' => $schema->boolean()
                ->description('Przesuń na cały dzień. Spotkania: nie.'),
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
