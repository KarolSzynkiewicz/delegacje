<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Concerns\ParsesTaskId;
use App\Mcp\Concerns\ResolvesCalendarUser;
use App\Mcp\Support\PlanPayload;
use App\Models\WorkItem;
use App\Models\WorkItemTimeBlock;
use App\Services\WorkItemPlanService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class UnschedulePlanBlockTool extends Tool
{
    use ActsAsConfiguredUser;
    use ParsesTaskId;
    use ResolvesCalendarUser;

    protected string $name = 'unschedule_plan_block';

    protected string $description = <<<'MARKDOWN'
        Odpina slot z Planu. Work item **zostaje** (wraca do kolejki,
        jeśli nie ma innego slotu ≥ dziś).

        Klocek / sesja: `block_id` — kasuje rekord `work_item_time_blocks`.
        Sesja znika w całości (worki nie kasują członków WI).
        Spotkanie: `work_item_id` — czyści `starts_at` / `ends_at` na karcie.

        HITL: pokaż co zniknie z siatki, zgoda, `confirmed_by_user: true`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $actor = $this->actingUser();

        if (! $actor->isAdmin() && ! $actor->hasPermission('tasks.update')) {
            return Response::error(
                "Użytkownik {$actor->name} nie ma uprawnienia tasks.update – odpinanie odrzucone."
            );
        }

        $validated = $request->validate([
            'confirmed_by_user' => ['required', 'boolean'],
            'block_id' => ['nullable'],
            'work_item_id' => ['nullable'],
            'assigned_to' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'assignee_name' => ['nullable', 'string', 'max:255'],
            'assigned_to_me' => ['nullable', 'boolean'],
        ]);

        if ($validated['confirmed_by_user'] !== true) {
            return Response::error(
                'Zapis wstrzymany: brak potwierdzenia. Pokaż co zniknie z siatki, '
                .'poproś o akceptację i wywołaj ponownie z confirmed_by_user=true.'
            );
        }

        $calendar = $this->calendarUserFrom($validated, $actor);
        if (is_string($calendar)) {
            return Response::error($calendar);
        }

        $service = app(WorkItemPlanService::class);
        $blockId = isset($validated['block_id']) ? $this->parseTaskId($validated['block_id']) : null;
        $workItemId = isset($validated['work_item_id']) ? $this->parseTaskId($validated['work_item_id']) : null;

        if ($blockId) {
            $block = WorkItemTimeBlock::query()
                ->whereKey($blockId)
                ->where('user_id', $calendar->id)
                ->first();
            if (! $block) {
                return Response::error("Nie znaleziono bloku #{$blockId} w kalendarzu {$calendar->name}.");
            }

            $snapshot = [
                'block_id' => $block->id,
                'is_session' => $block->isSession(),
                'work_item_id' => $block->work_item_id,
                'title' => $block->displayTitle(),
                'starts_at' => $block->starts_at?->toIso8601String(),
            ];
            $service->deleteBlock($block);

            return Response::json([
                'meta' => [
                    'unscheduled_by' => $actor->name,
                    'calendar_user' => PlanPayload::user($calendar),
                    'plan_url' => PlanPayload::planUrl($calendar, $service->weekStart(now())),
                ],
                'removed' => $snapshot,
            ]);
        }

        if ($workItemId) {
            $item = WorkItem::query()->with('source')->find($workItemId);
            if (! $item || ! $item->isMeetingItem()) {
                return Response::error(
                    'Bez `block_id` podaj `work_item_id` spotkania, żeby zdjąć godzinę z karty.'
                );
            }
            $service->clearMeetingSlot($item);

            return Response::json([
                'meta' => [
                    'unscheduled_by' => $actor->name,
                    'calendar_user' => PlanPayload::user($calendar),
                    'plan_url' => PlanPayload::planUrl($calendar, $service->weekStart(now())),
                ],
                'removed' => [
                    'kind' => 'meeting',
                    'work_item_id' => $item->id,
                    'title' => $item->title,
                ],
            ]);
        }

        return Response::error('Podaj `block_id` albo `work_item_id` spotkania.');
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'block_id' => $schema->string()
                ->description('ID bloku / sesji do skasowania z kalendarza.'),
            'work_item_id' => $schema->string()
                ->description('ID spotkania — czyści godzinę na karcie.'),
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
