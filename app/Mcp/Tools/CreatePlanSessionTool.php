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

#[IsDestructive]
class CreatePlanSessionTool extends Tool
{
    use ActsAsConfiguredUser;
    use ParsesTaskId;
    use ResolvesCalendarUser;

    protected string $name = 'create_plan_session';

    protected string $description = <<<'MARKDOWN'
        Tworzy sesję (worek WI) na slocie Planu i/lub dokłada istniejące
        work itemy do sesji. Spotkania są pomijane.

        Nowa sesja: `title`, `starts_at`, opcjonalnie `ends_at` / `all_day`,
        `work_item_ids` (otwarte WI assignee = kalendarz).
        Istniejąca: `block_id` sesji + `work_item_ids` (i ewentualnie
        `title` do rename).

        HITL: pokaż nazwę, slot, listę WI (co wpadnie / co pominięte),
        zgoda, `confirmed_by_user: true`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $actor = $this->actingUser();

        if (! $actor->isAdmin() && ! $actor->hasPermission('tasks.update')) {
            return Response::error(
                "Użytkownik {$actor->name} nie ma uprawnienia tasks.update – sesja odrzucona."
            );
        }

        $validated = $request->validate([
            'confirmed_by_user' => ['required', 'boolean'],
            'title' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'all_day' => ['nullable', 'boolean'],
            'block_id' => ['nullable'],
            'work_item_ids' => ['nullable', 'array', 'max:30'],
            'work_item_ids.*' => ['nullable'],
            'assigned_to' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'assignee_name' => ['nullable', 'string', 'max:255'],
            'assigned_to_me' => ['nullable', 'boolean'],
        ]);

        if ($validated['confirmed_by_user'] !== true) {
            return Response::error(
                'Zapis wstrzymany: brak potwierdzenia. Pokaż nazwę sesji, slot i listę WI, '
                .'poproś o akceptację i wywołaj ponownie z confirmed_by_user=true.'
            );
        }

        $calendar = $this->calendarUserFrom($validated, $actor);
        if (is_string($calendar)) {
            return Response::error($calendar);
        }

        $service = app(WorkItemPlanService::class);
        $blockId = isset($validated['block_id']) ? $this->parseTaskId($validated['block_id']) : null;
        $ids = collect($validated['work_item_ids'] ?? [])
            ->map(fn ($id) => $this->parseTaskId($id))
            ->filter()
            ->unique()
            ->values()
            ->all();

        try {
            if ($blockId) {
                $block = WorkItemTimeBlock::query()
                    ->whereKey($blockId)
                    ->where('user_id', $calendar->id)
                    ->first();
                if (! $block || ! $block->isSession()) {
                    return Response::error("Blok #{$blockId} nie jest sesją w kalendarzu {$calendar->name}.");
                }
                if (! empty($validated['title'])) {
                    $service->renameSession($block, (string) $validated['title']);
                }
            } else {
                if (empty($validated['starts_at'])) {
                    return Response::error('Nowa sesja wymaga `starts_at` (albo `block_id` istniejącej).');
                }
                $starts = CarbonImmutable::parse($validated['starts_at']);
                $ends = isset($validated['ends_at'])
                    ? CarbonImmutable::parse($validated['ends_at'])
                    : $starts->addMinutes(WorkItemPlanService::DEFAULT_MINUTES);
                $block = $service->createSession(
                    (string) ($validated['title'] ?? ''),
                    $calendar,
                    $actor,
                    $starts,
                    $ends,
                    (bool) ($validated['all_day'] ?? false),
                );
            }
        } catch (ValidationException $e) {
            return Response::error(collect($e->errors())->flatten()->first() ?: $e->getMessage());
        }

        $added = [];
        $skipped = [];
        foreach ($ids as $itemId) {
            $item = WorkItem::query()->find($itemId);
            if (! $item) {
                $skipped[] = ['work_item_id' => $itemId, 'reason' => 'nie istnieje'];

                continue;
            }
            if ($item->isMeetingItem()) {
                $skipped[] = ['work_item_id' => $itemId, 'title' => $item->title, 'reason' => 'spotkanie nie wchodzi do sesji'];

                continue;
            }
            if ((int) $item->assignee_id !== (int) $calendar->id) {
                $skipped[] = ['work_item_id' => $itemId, 'title' => $item->title, 'reason' => 'inny assignee niż kalendarz'];

                continue;
            }
            if (! $item->status->isOpen()) {
                $skipped[] = ['work_item_id' => $itemId, 'title' => $item->title, 'reason' => 'zamknięte'];

                continue;
            }
            $service->addToSession($block, $item);
            $added[] = ['work_item_id' => $item->id, 'title' => $item->title];
        }

        $block->refresh()->load('items');
        $weekStart = $service->weekStart($block->starts_at);

        return Response::json([
            'meta' => [
                'saved_by' => $actor->name,
                'calendar_user' => PlanPayload::user($calendar),
                'plan_url' => PlanPayload::planUrl($calendar, $weekStart),
            ],
            'session' => [
                'block_id' => $block->id,
                'title' => $block->displayTitle(),
                'starts_at' => $block->starts_at?->toIso8601String(),
                'ends_at' => $block->ends_at?->toIso8601String(),
                'all_day' => (bool) $block->all_day,
                'open_count' => $block->openItems()->count(),
            ],
            'added' => $added,
            'skipped' => $skipped,
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()
                ->description('Nazwa sesji. Pusta = etykieta z liczby WI.'),
            'starts_at' => $schema->string()
                ->description('Start nowej sesji. Wymagane, gdy nie ma block_id.'),
            'ends_at' => $schema->string()
                ->description('Koniec. Domyślnie +30 min.'),
            'all_day' => $schema->boolean()
                ->description('Sesja całodniowa.'),
            'block_id' => $schema->string()
                ->description('Istniejąca sesja — tylko dokładamy WI / zmieniamy nazwę.'),
            'work_item_ids' => $schema->array()
                ->description('ID work itemów do worka (max 30). Spotkania pomijane.')
                ->items($schema->string()),
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
