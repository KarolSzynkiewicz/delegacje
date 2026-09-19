<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Enums\WorkItemStatus;
use App\Enums\WorkItemTimeBlockKind;
use App\Enums\WorkItemType;
use App\Models\ApprovalRequest;
use App\Models\ProcedureRun;
use App\Models\ProcedureTemplate;
use App\Models\ProjectTask;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemTimeBlock;
use App\Support\Plan\PlanEvent;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class WorkItemPlanService
{
    public const GRID_START_HOUR = 0;

    public const GRID_END_HOUR = 24;

    public const VIEW_START_HOUR = 8;

    public const VIEW_END_HOUR = 18;

    public const DEFAULT_MINUTES = 30;

    public const SNAP_MINUTES = 15;

    public function weekStart(CarbonInterface|string $anchor): CarbonImmutable
    {
        return CarbonImmutable::parse($anchor)->startOfWeek(CarbonImmutable::MONDAY)->startOfDay();
    }

    /**
     * @return list<CarbonImmutable>
     */
    public function weekDays(CarbonInterface|string $weekStart): array
    {
        $start = $this->weekStart($weekStart);
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = $start->addDays($i);
        }

        return $days;
    }

    /**
     * Otwarte WI osoby, które nie mają slotu z datą ≥ dziś (blok, sesja albo spotkanie).
     *
     * @return Collection<int, WorkItem>
     */
    public function queue(User $calendarUser, CarbonInterface $now, ?int $pinId = null): Collection
    {
        return $this->applyQueueConstraints(WorkItem::query()->with('source'), $calendarUser, $now, $pinId)
            ->orderByRaw('due_at IS NULL')
            ->orderBy('due_at')
            ->orderByDesc('priority')
            ->orderBy('id')
            ->limit(80)
            ->get();
    }

    /**
     * Overlay kolejki Planu: osoba kalendarza, status aktywny,
     * bez bloku / sesji / slotu na źródle (spotkanie) ≥ dziś.
     * `pinId` zawęża do jednego WI (silniejszy lock z `?pin=`).
     */
    public function applyQueueConstraints(Builder $query, User $calendarUser, CarbonInterface $now, ?int $pinId = null): Builder
    {
        $today = CarbonImmutable::parse($now)->startOfDay();
        $meetingMorph = (new ProjectTask)->getMorphClass();

        $blockedIds = WorkItemTimeBlock::query()
            ->where('user_id', $calendarUser->id)
            ->where('starts_at', '>=', $today)
            ->whereNotNull('work_item_id')
            ->pluck('work_item_id');

        $sessionItemIds = WorkItem::query()
            ->whereHas('sessionBlocks', function ($sessionQuery) use ($calendarUser, $today) {
                $sessionQuery->where('user_id', $calendarUser->id)
                    ->where('kind', WorkItemTimeBlockKind::Session->value)
                    ->where('starts_at', '>=', $today);
            })
            ->pluck('id');

        $hideIds = $blockedIds->merge($sessionItemIds)->unique()->filter()->values();

        $query
            ->where('work_items.assignee_id', $calendarUser->id)
            ->whereIn('work_items.status', [WorkItemStatus::Pending->value, WorkItemStatus::InProgress->value])
            ->when($hideIds->isNotEmpty(), fn (Builder $q) => $q->whereNotIn('work_items.id', $hideIds))
            ->whereNotExists(function ($sub) use ($today, $meetingMorph) {
                $sub->selectRaw('1')
                    ->from('project_tasks')
                    ->whereColumn('project_tasks.id', 'work_items.source_id')
                    ->where('work_items.source_type', $meetingMorph)
                    ->whereNotNull('project_tasks.starts_at')
                    ->where('project_tasks.starts_at', '>=', $today);
            });

        if ($pinId && $pinId > 0) {
            $query->where('work_items.id', $pinId);
        }

        return $query;
    }

    /**
     * @return array{timed: array<string, list<PlanEvent>>, allDay: array<string, list<PlanEvent>>}
     */
    public function occupancy(User $calendarUser, CarbonInterface|string $weekStart, CarbonInterface $now): array
    {
        $start = $this->weekStart($weekStart);
        $end = $start->addDays(7);
        $today = CarbonImmutable::parse($now)->startOfDay();
        $timed = [];
        $allDay = [];

        $blocks = WorkItemTimeBlock::query()
            ->with(['workItem', 'items'])
            ->where('user_id', $calendarUser->id)
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->where(function (Builder $query) {
                $query->where('kind', WorkItemTimeBlockKind::Session->value)
                    ->orWhereHas('workItem', function (Builder $items) {
                        $items->whereIn('status', [
                            WorkItemStatus::Pending->value,
                            WorkItemStatus::InProgress->value,
                        ]);
                    });
            })
            ->orderBy('starts_at')
            ->get();

        foreach ($blocks as $block) {
            $event = $block->isSession()
                ? $this->eventFromSession($block, $today)
                : ($block->workItem ? $this->eventFromBlock($block, $block->workItem, $today) : null);
            if (! $event) {
                continue;
            }
            if ($event->allDay) {
                $allDay[] = $event;
            } else {
                $timed[] = $event;
            }
        }

        $meetings = ProjectTask::query()
            ->whereNotNull('starts_at')
            ->whereNotIn('status', [TaskStatus::COMPLETED->value, TaskStatus::CANCELLED->value])
            ->where('starts_at', '<', $end)
            ->where(function ($query) use ($start) {
                $query->where('ends_at', '>', $start)
                    ->orWhere(function ($inner) use ($start) {
                        $inner->whereNull('ends_at')->where('starts_at', '>=', $start);
                    });
            })
            ->get();

        $meetingItemBySource = WorkItem::query()
            ->where('type', WorkItemType::Meeting)
            ->whereIn('status', [WorkItemStatus::Pending->value, WorkItemStatus::InProgress->value])
            ->where('source_type', (new ProjectTask)->getMorphClass())
            ->whereIn('source_id', $meetings->pluck('id'))
            ->get()
            ->keyBy('source_id');

        foreach ($meetings as $task) {
            if (! $this->meetingInvolves($task, $calendarUser->id)) {
                continue;
            }
            $item = $meetingItemBySource->get($task->id);
            if (! $item) {
                continue;
            }
            $event = $this->eventFromMeeting($task, $item, $today);
            if ($event) {
                $timed[] = $event;
            }
        }

        return [
            'timed' => $this->layoutTimed($timed, $start),
            'allDay' => $this->bucketByDay($allDay, $start),
        ];
    }

    /**
     * Terminy otwartych WI na pasie całodniowym — nie occupancy godzinowa.
     *
     * @return array<string, list<array{title: string, url: string}>>
     */
    public function dueFlags(User $calendarUser, CarbonInterface|string $weekStart): array
    {
        $start = $this->weekStart($weekStart);
        $end = $start->addDays(6);
        $flags = [];

        $items = WorkItem::query()
            ->where('assignee_id', $calendarUser->id)
            ->whereIn('status', [WorkItemStatus::Pending->value, WorkItemStatus::InProgress->value])
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [$start->toDateString(), $end->toDateString()])
            ->where('type', '!=', WorkItemType::Meeting)
            ->orderBy('due_at')
            ->limit(50)
            ->get();

        foreach ($items as $item) {
            $day = $item->due_at?->toDateString();
            if (! $day) {
                continue;
            }
            $flags[$day][] = [
                'title' => $item->title,
                'url' => $item->openUrl(),
            ];
        }

        return $flags;
    }

    public function placeFromQueue(
        WorkItem $item,
        User $calendarUser,
        User $actor,
        CarbonInterface $startsAt,
        bool $allDay = false,
        ?CarbonInterface $endsAt = null,
    ): void {
        if ($item->isMeetingItem()) {
            if ($allDay) {
                return;
            }
            $start = $startsAt;
            $window = $endsAt
                ? ['starts_at' => $this->snap($start), 'ends_at' => $this->snap($endsAt)]
                : $this->windowFromStart($start);
            if ($window['ends_at']->lessThanOrEqualTo($window['starts_at'])) {
                $window = $this->windowFromStart($window['starts_at']);
            }
            $this->writeMeetingSlot($item, $window['starts_at'], $window['ends_at']);

            return;
        }

        if ($allDay) {
            $day = CarbonImmutable::parse($startsAt)->startOfDay();
            WorkItemTimeBlock::query()->create([
                'work_item_id' => $item->id,
                'kind' => WorkItemTimeBlockKind::Item,
                'user_id' => $calendarUser->id,
                'starts_at' => $day,
                'ends_at' => $day->endOfDay(),
                'all_day' => true,
                'created_by_id' => $actor->id,
            ]);

            return;
        }

        $window = $endsAt
            ? ['starts_at' => $this->snap($startsAt), 'ends_at' => $this->snap($endsAt)]
            : $this->windowFromStart($startsAt);
        if ($window['ends_at']->lessThanOrEqualTo($window['starts_at'])) {
            $window = $this->windowFromStart($window['starts_at']);
        }
        WorkItemTimeBlock::query()->create([
            'work_item_id' => $item->id,
            'kind' => WorkItemTimeBlockKind::Item,
            'user_id' => $calendarUser->id,
            'starts_at' => $window['starts_at'],
            'ends_at' => $window['ends_at'],
            'all_day' => false,
            'created_by_id' => $actor->id,
        ]);
    }

    public function moveBlock(WorkItemTimeBlock $block, CarbonInterface $startsAt, bool $allDay = false): void
    {
        if ($allDay) {
            $day = CarbonImmutable::parse($startsAt)->startOfDay();
            $block->update([
                'starts_at' => $day,
                'ends_at' => $day->endOfDay(),
                'all_day' => true,
            ]);

            return;
        }

        $duration = $block->all_day
            ? self::DEFAULT_MINUTES
            : max(self::SNAP_MINUTES, $this->minutesBetween($block->starts_at, $block->ends_at));
        $window = $this->windowFromStart($startsAt, $duration);
        $block->update([
            'starts_at' => $window['starts_at'],
            'ends_at' => $window['ends_at'],
            'all_day' => false,
        ]);
    }

    public function duplicateBlock(WorkItemTimeBlock $block, User $actor, CarbonInterface $startsAt, bool $allDay = false): WorkItemTimeBlock
    {
        if ($allDay) {
            $day = CarbonImmutable::parse($startsAt)->startOfDay();
            $copy = WorkItemTimeBlock::query()->create([
                'work_item_id' => $block->isSession() ? null : $block->work_item_id,
                'kind' => $block->kind,
                'title' => $block->title,
                'user_id' => $block->user_id,
                'starts_at' => $day,
                'ends_at' => $day->endOfDay(),
                'all_day' => true,
                'created_by_id' => $actor->id,
            ]);
            $this->copySessionMembers($block, $copy);

            return $copy;
        }

        $duration = $block->all_day
            ? self::DEFAULT_MINUTES
            : max(self::SNAP_MINUTES, $this->minutesBetween($block->starts_at, $block->ends_at));
        $window = $this->windowFromStart($startsAt, $duration);
        $copy = WorkItemTimeBlock::query()->create([
            'work_item_id' => $block->isSession() ? null : $block->work_item_id,
            'kind' => $block->kind,
            'title' => $block->title,
            'user_id' => $block->user_id,
            'starts_at' => $window['starts_at'],
            'ends_at' => $window['ends_at'],
            'all_day' => false,
            'created_by_id' => $actor->id,
        ]);
        $this->copySessionMembers($block, $copy);

        return $copy;
    }

    public function addToSession(WorkItemTimeBlock $block, WorkItem $item): void
    {
        if (! $block->isSession() || $item->isMeetingItem()) {
            return;
        }
        if ((int) $item->assignee_id !== (int) $block->user_id) {
            return;
        }

        $block->items()->syncWithoutDetaching([$item->id]);
    }

    public function removeFromSession(WorkItemTimeBlock $block, int $itemId): void
    {
        if (! $block->isSession()) {
            return;
        }

        $block->items()->detach($itemId);
    }

    public function resizeBlock(WorkItemTimeBlock $block, CarbonInterface $endsAt): void
    {
        if ($block->all_day) {
            return;
        }
        $start = CarbonImmutable::parse($block->starts_at);
        $end = $this->snap($endsAt)->setDateFrom($start);
        if ($end->lessThanOrEqualTo($start->addMinutes(self::SNAP_MINUTES))) {
            $end = $start->addMinutes(self::SNAP_MINUTES);
        }
        $dayEnd = $this->gridDayEnd($start);
        if ($end->greaterThan($dayEnd)) {
            $end = $dayEnd;
        }
        if ($end->lessThanOrEqualTo($start)) {
            throw ValidationException::withMessages([
                'ends_at' => 'Blok musi trwać przynajmniej '.self::SNAP_MINUTES.' minut.',
            ]);
        }
        $block->update(['ends_at' => $end]);
    }

    public function deleteBlock(WorkItemTimeBlock $block): void
    {
        $block->delete();
    }

    public function moveMeeting(WorkItem $item, CarbonInterface $startsAt): void
    {
        $source = $item->source;
        if (! $source instanceof ProjectTask) {
            return;
        }
        $duration = $source->starts_at && $source->ends_at
            ? $this->minutesBetween($source->starts_at, $source->ends_at)
            : self::DEFAULT_MINUTES;
        $window = $this->windowFromStart($startsAt, $duration);
        $this->writeMeetingSlot($item, $window['starts_at'], $window['ends_at']);
    }

    public function resizeMeeting(WorkItem $item, CarbonInterface $endsAt): void
    {
        $source = $item->source;
        if (! $source instanceof ProjectTask || ! $source->starts_at) {
            return;
        }
        $start = CarbonImmutable::parse($source->starts_at);
        $end = $this->snap($endsAt)->setDateFrom($start);
        if ($end->lessThanOrEqualTo($start->addMinutes(self::SNAP_MINUTES))) {
            $end = $start->addMinutes(self::SNAP_MINUTES);
        }
        $this->writeMeetingSlot($item, $start, $end);
    }

    /**
     * @return array{starts_at: CarbonImmutable, ends_at: CarbonImmutable}
     */
    public function windowFromStart(CarbonInterface $startsAt, int $durationMinutes = self::DEFAULT_MINUTES): array
    {
        $start = $this->snap($startsAt);
        $durationMinutes = max(self::SNAP_MINUTES, $this->snapMinutes($durationMinutes));
        $end = $start->addMinutes($durationMinutes);
        $dayEnd = $this->gridDayEnd($start);
        if ($end->greaterThan($dayEnd)) {
            $end = $dayEnd;
        }
        if ($end->lessThanOrEqualTo($start)) {
            throw ValidationException::withMessages([
                'starts_at' => 'Nie da się wstawić bloku w tym slocie.',
            ]);
        }

        return ['starts_at' => $start, 'ends_at' => $end];
    }

    public function minutesBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) round((CarbonImmutable::parse($to)->getTimestamp() - CarbonImmutable::parse($from)->getTimestamp()) / 60);
    }

    public function snap(CarbonInterface $time): CarbonImmutable
    {
        $value = CarbonImmutable::parse($time)->second(0)->microsecond(0);
        $snapped = (int) (round($value->minute / self::SNAP_MINUTES) * self::SNAP_MINUTES);
        if ($snapped === 60) {
            return $value->addHour()->minute(0);
        }

        return $value->minute($snapped);
    }

    /**
     * Spotkanie bez slotu — backlog i kolejka planu, aż ktoś je przypnie z godziną.
     *
     * @param  array{location?: string|null, participant_ids?: list<int>}  $extra
     */
    public function createHangingMeeting(string $title, User $calendarUser, User $actor, array $extra = []): ProjectTask
    {
        $title = ProjectTask::meetingTitle($title);
        $participants = $this->meetingParticipantIds($calendarUser, $extra['participant_ids'] ?? []);
        $location = trim((string) ($extra['location'] ?? ''));

        return app(TaskCreationService::class)->create([
            'name' => $title,
            'assigned_to' => $calendarUser->id,
            'starts_at' => null,
            'ends_at' => null,
            'participant_ids' => $participants,
            'location' => $location !== '' ? $location : null,
            'due_date' => null,
        ], $actor);
    }

    /**
     * @param  array{
     *     location?: string|null,
     *     participant_ids?: list<int>,
     *     template_id?: int|null,
     *     subject_id?: int|null,
     *     name_suffix?: string|null
     * }  $extra
     * @return array{task: ProjectTask}|array{approval: ApprovalRequest}|array{meeting: ProjectTask}|array{procedure: ProcedureRun}|array{session: WorkItemTimeBlock}
     */
    public function createOnCalendar(
        string $type,
        string $title,
        User $calendarUser,
        User $actor,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        bool $allDay,
        array $extra = [],
    ): array {
        if ($type === 'procedure') {
            return $this->createProcedureOnCalendar($calendarUser, $actor, $startsAt, $endsAt, $allDay, $extra);
        }

        if ($type === 'session') {
            return ['session' => $this->createSession($title, $calendarUser, $actor, $startsAt, $endsAt, $allDay)];
        }

        $title = trim($title);
        if ($title === '') {
            throw ValidationException::withMessages([
                'title' => 'Podaj nazwę.',
            ]);
        }

        if ($type === 'approval') {
            $approval = ApprovalRequest::query()->create([
                'name' => $title,
                'approver_id' => $calendarUser->id,
                'created_by' => $actor->id,
                'due_at' => null,
            ]);
            $item = WorkItem::query()
                ->where('source_type', $approval->getMorphClass())
                ->where('source_id', $approval->id)
                ->first();
            if ($item) {
                $this->placeFromQueue($item, $calendarUser, $actor, $startsAt, $allDay, $allDay ? null : $endsAt);
            }

            return ['approval' => $approval];
        }

        if ($type === 'meeting') {
            if ($allDay) {
                throw ValidationException::withMessages([
                    'composerType' => 'Spotkanie musi mieć godzinę.',
                ]);
            }
            $start = $this->snap($startsAt);
            $end = $this->snap($endsAt);
            if ($end->lessThanOrEqualTo($start)) {
                $end = $start->addMinutes(self::DEFAULT_MINUTES);
            }
            $participants = $this->meetingParticipantIds($calendarUser, $extra['participant_ids'] ?? []);
            $location = trim((string) ($extra['location'] ?? ''));
            $task = app(TaskCreationService::class)->create([
                'name' => $title,
                'assigned_to' => $calendarUser->id,
                'starts_at' => $start->toDateTimeString(),
                'ends_at' => $end->toDateTimeString(),
                'participant_ids' => $participants,
                'location' => $location !== '' ? $location : null,
                'due_date' => $start->toDateString(),
            ], $actor);

            return ['meeting' => $task];
        }

        $task = app(TaskCreationService::class)->create([
            'name' => $title,
            'assigned_to' => $calendarUser->id,
        ], $actor);
        $item = WorkItem::query()
            ->where('source_type', 'project_task')
            ->where('source_id', $task->id)
            ->first();
        if ($item) {
            $this->placeFromQueue($item, $calendarUser, $actor, $startsAt, $allDay, $allDay ? null : $endsAt);
        }

        return ['task' => $task];
    }

    /**
     * @param  array{
     *     template_id?: int|null,
     *     subject_id?: int|null,
     *     name_suffix?: string|null
     * }  $extra
     * @return array{procedure: ProcedureRun}
     */
    private function createProcedureOnCalendar(
        User $calendarUser,
        User $actor,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        bool $allDay,
        array $extra,
    ): array {
        $templateId = (int) ($extra['template_id'] ?? 0);
        $template = ProcedureTemplate::query()->find($templateId);
        if (! $template) {
            throw ValidationException::withMessages([
                'template_id' => 'Wybierz procedurę.',
            ]);
        }

        $subjectType = $template->subjectType();
        $subjectId = isset($extra['subject_id']) ? (int) $extra['subject_id'] : 0;

        try {
            $run = app(ProcedureRunService::class)->startRun($template, [
                'name_suffix' => $subjectType ? null : ($extra['name_suffix'] ?? null),
                'assigned_to' => $calendarUser->id,
                'subject_type' => $subjectType?->value,
                'subject_id' => $subjectId > 0 ? $subjectId : null,
            ]);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'template_id' => $e->getMessage(),
            ]);
        }

        $item = WorkItem::query()
            ->where('source_type', $run->getMorphClass())
            ->where('source_id', $run->id)
            ->first();
        if ($item) {
            $this->placeFromQueue($item, $calendarUser, $actor, $startsAt, $allDay, $allDay ? null : $endsAt);
        }

        return ['procedure' => $run];
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<int>
     */
    private function meetingParticipantIds(User $calendarUser, array $ids): array
    {
        $participants = collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->push($calendarUser->id)
            ->unique()
            ->values()
            ->all();

        return $participants === [] ? [$calendarUser->id] : $participants;
    }

    public function gridDayStart(CarbonImmutable $day): CarbonImmutable
    {
        return $day->startOfDay()->addHours(self::GRID_START_HOUR);
    }

    public function gridDayEnd(CarbonImmutable $day): CarbonImmutable
    {
        return $day->startOfDay()->addHours(self::GRID_END_HOUR);
    }

    private function snapMinutes(int $minutes): int
    {
        $snapped = (int) (round($minutes / self::SNAP_MINUTES) * self::SNAP_MINUTES);

        return max(self::SNAP_MINUTES, $snapped);
    }

    private function meetingInvolves(ProjectTask $task, int $userId): bool
    {
        if ((int) $task->assigned_to === $userId) {
            return true;
        }

        $participants = array_map('intval', $task->participant_ids ?? []);

        return in_array($userId, $participants, true);
    }

    private function writeMeetingSlot(WorkItem $item, CarbonImmutable $startsAt, CarbonImmutable $endsAt): void
    {
        $source = $item->source;
        if (! $source instanceof ProjectTask) {
            return;
        }

        $source->update([
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'due_date' => $startsAt->toDateString(),
        ]);
    }

    public function createSession(
        string $title,
        User $calendarUser,
        User $actor,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        bool $allDay = false,
    ): WorkItemTimeBlock {
        if ($allDay) {
            $day = CarbonImmutable::parse($startsAt)->startOfDay();

            return WorkItemTimeBlock::query()->create([
                'work_item_id' => null,
                'kind' => WorkItemTimeBlockKind::Session,
                'title' => $this->sessionTitle($title),
                'user_id' => $calendarUser->id,
                'starts_at' => $day,
                'ends_at' => $day->endOfDay(),
                'all_day' => true,
                'created_by_id' => $actor->id,
            ]);
        }

        $start = $this->snap($startsAt);
        $end = $this->snap($endsAt);
        if ($end->lessThanOrEqualTo($start)) {
            $end = $start->addMinutes(self::DEFAULT_MINUTES);
        }

        return WorkItemTimeBlock::query()->create([
            'work_item_id' => null,
            'kind' => WorkItemTimeBlockKind::Session,
            'title' => $this->sessionTitle($title),
            'user_id' => $calendarUser->id,
            'starts_at' => $start,
            'ends_at' => $end,
            'all_day' => false,
            'created_by_id' => $actor->id,
        ]);
    }

    public function renameSession(WorkItemTimeBlock $block, string $title): void
    {
        if (! $block->isSession()) {
            return;
        }

        $block->update(['title' => $this->sessionTitle($title)]);
    }

    private function sessionTitle(string $title): ?string
    {
        $title = trim($title);

        return $title !== '' ? $title : null;
    }

    private function copySessionMembers(WorkItemTimeBlock $from, WorkItemTimeBlock $to): void
    {
        if (! $from->isSession()) {
            return;
        }

        $ids = $from->items()->pluck('work_items.id')->all();
        if ($ids !== []) {
            $to->items()->sync($ids);
        }
    }

    private function eventFromSession(WorkItemTimeBlock $block, CarbonImmutable $today): PlanEvent
    {
        $start = CarbonImmutable::parse($block->starts_at);
        $end = CarbonImmutable::parse($block->ends_at);
        $open = $block->openItems();
        $members = $open
            ->map(fn (WorkItem $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'url' => $item->openUrl(),
                'typeLabel' => $item->type->label(),
                'typeIcon' => $item->type->icon(),
                'dueLabel' => $item->due_at?->format('d.m'),
                'dueLate' => (bool) $item->due_at?->isPast(),
            ])
            ->all();

        return new PlanEvent(
            key: 'block:'.$block->id,
            kind: 'block',
            workItemId: null,
            blockId: $block->id,
            title: $block->displayTitle(),
            url: '',
            startsAt: $start,
            endsAt: $end,
            day: $start->toDateString(),
            type: WorkItemTimeBlockKind::Session->value,
            typeLabel: $block->itemCountLabel($open->count()),
            typeIcon: WorkItemTimeBlockKind::Session->icon(),
            ghost: $start->toDateString() < $today->toDateString(),
            movable: true,
            allDay: (bool) $block->all_day,
            isSession: true,
            members: $members,
        );
    }

    private function eventFromBlock(WorkItemTimeBlock $block, WorkItem $item, CarbonImmutable $today): ?PlanEvent
    {
        if (! $item->status->isOpen()) {
            return null;
        }

        $start = CarbonImmutable::parse($block->starts_at);
        $end = CarbonImmutable::parse($block->ends_at);
        $past = $start->toDateString() < $today->toDateString();
        $type = $item->type;

        return new PlanEvent(
            key: 'block:'.$block->id,
            kind: 'block',
            workItemId: $item->id,
            blockId: $block->id,
            title: $item->title,
            url: $item->openUrl(),
            startsAt: $start,
            endsAt: $end,
            day: $start->toDateString(),
            type: $type->value,
            typeLabel: $type->label(),
            typeIcon: $type->icon(),
            ghost: $past && $item->status->isOpen(),
            movable: true,
            allDay: (bool) $block->all_day,
        );
    }

    private function eventFromMeeting(ProjectTask $task, WorkItem $item, CarbonImmutable $today): ?PlanEvent
    {
        if (! $item->status->isOpen() || ! $task->isOpenMeeting()) {
            return null;
        }

        $start = CarbonImmutable::parse($task->starts_at);
        $end = $task->ends_at
            ? CarbonImmutable::parse($task->ends_at)
            : $start->addMinutes(self::DEFAULT_MINUTES);
        $past = $start->toDateString() < $today->toDateString();

        return new PlanEvent(
            key: 'meeting:'.$item->id,
            kind: 'meeting',
            workItemId: $item->id,
            blockId: null,
            title: $item->title,
            url: $item->openUrl(),
            startsAt: $start,
            endsAt: $end,
            day: $start->toDateString(),
            type: WorkItemType::Meeting->value,
            typeLabel: WorkItemType::Meeting->label(),
            typeIcon: WorkItemType::Meeting->icon(),
            ghost: $past && $item->status->isOpen(),
            movable: true,
        );
    }

    /**
     * @param  list<PlanEvent>  $events
     * @return array<string, list<PlanEvent>>
     */
    private function layoutTimed(array $events, CarbonImmutable $weekStart): array
    {
        $byDay = $this->bucketByDay($events, $weekStart);

        foreach ($byDay as $day => $dayEvents) {
            $timed = array_values(array_filter($dayEvents, fn (PlanEvent $e) => ! $e->allDay));
            foreach ($timed as $event) {
                $this->positionOnGrid($event, CarbonImmutable::parse($day));
            }
            $byDay[$day] = $this->assignLanes($timed);
        }

        return $byDay;
    }

    /**
     * @param  list<PlanEvent>  $events
     * @return array<string, list<PlanEvent>>
     */
    private function bucketByDay(array $events, CarbonImmutable $weekStart): array
    {
        $byDay = [];
        foreach ($this->weekDays($weekStart) as $day) {
            $byDay[$day->toDateString()] = [];
        }

        foreach ($events as $event) {
            if (! array_key_exists($event->day, $byDay)) {
                continue;
            }
            $byDay[$event->day][] = $event;
        }

        return $byDay;
    }

    private function positionOnGrid(PlanEvent $event, CarbonImmutable $day): void
    {
        $gridStart = $this->gridDayStart($day);
        $gridEnd = $this->gridDayEnd($day);
        $total = (self::GRID_END_HOUR - self::GRID_START_HOUR) * 60;

        $visualStart = $event->startsAt->greaterThan($gridStart) ? $event->startsAt : $gridStart;
        $visualEnd = $event->endsAt->lessThan($gridEnd) ? $event->endsAt : $gridEnd;
        if ($visualEnd->lessThanOrEqualTo($visualStart)) {
            $event->topPercent = 0;
            $event->heightPercent = 3;

            return;
        }

        $top = $this->minutesBetween($gridStart, $visualStart);
        $height = $this->minutesBetween($visualStart, $visualEnd);
        $event->topPercent = max(0, ($top / $total) * 100);
        $event->heightPercent = max(0.4, ($height / $total) * 100);
    }

    /**
     * @param  list<PlanEvent>  $events
     * @return list<PlanEvent>
     */
    private function assignLanes(array $events): array
    {
        usort($events, fn (PlanEvent $a, PlanEvent $b) => $a->startsAt <=> $b->startsAt);

        foreach ($events as $index => $event) {
            $used = [];
            foreach ($events as $otherIndex => $other) {
                if ($otherIndex >= $index) {
                    break;
                }
                if ($event->startsAt->lt($other->endsAt) && $event->endsAt->gt($other->startsAt)) {
                    $used[$other->lane] = true;
                }
            }
            $lane = 0;
            while (isset($used[$lane])) {
                $lane++;
            }
            $event->lane = $lane;
        }

        if ($events === []) {
            return [];
        }

        $n = count($events);
        $parent = range(0, max(0, $n - 1));
        $find = function (int $i) use (&$parent, &$find): int {
            return $parent[$i] === $i ? $i : $parent[$i] = $find($parent[$i]);
        };
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                if ($events[$i]->startsAt->lt($events[$j]->endsAt) && $events[$i]->endsAt->gt($events[$j]->startsAt)) {
                    $a = $find($i);
                    $b = $find($j);
                    if ($a !== $b) {
                        $parent[$b] = $a;
                    }
                }
            }
        }

        $clusterMax = [];
        foreach ($events as $i => $event) {
            $root = $n === 0 ? 0 : $find($i);
            $clusterMax[$root] = max($clusterMax[$root] ?? 0, $event->lane);
        }
        foreach ($events as $i => $event) {
            $event->laneCount = ($clusterMax[$find($i)] ?? 0) + 1;
        }

        return $events;
    }
}
