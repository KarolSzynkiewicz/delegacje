<?php

namespace App\Livewire;

use App\Enums\ApprovalDecision;
use App\Enums\ProcedureSubjectType;
use App\Enums\WorkItemTimeBlockKind;
use App\Models\ApprovalRequest;
use App\Models\ProcedureTemplate;
use App\Models\ProjectTask;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemTimeBlock;
use App\Services\WorkItemPlanService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class WorkItemPlan extends Component
{
    #[Url(as: 'w')]
    public string $week = '';

    #[Url(as: 'u')]
    public ?int $userId = null;

    #[Url(as: 'pin')]
    public ?int $pinId = null;

    public bool $composerOpen = false;

    public string $composerType = 'task';

    public string $composerTitle = '';

    public string $composerDate = '';

    public int $composerStart = 0;

    public int $composerEnd = 0;

    public bool $composerAllDay = false;

    public bool $composerUnscheduled = false;

    public string $composerLocation = '';

    /** @var list<int|string> */
    public array $composerParticipantIds = [];

    public string $composerProcedureTemplateId = '';

    public string $composerProcedureSubjectId = '';

    public string $composerProcedureNameSuffix = '';

    /** @var array{token: string, message: string, type: string, payload: array<string, mixed>}|null */
    public ?array $undo = null;

    public ?string $openKind = null;

    public ?int $openId = null;

    public ?int $openMemberId = null;

    public string $approvalComment = '';

    public bool $editingSessionTitle = false;

    public string $sessionTitleDraft = '';

    public function mount(): void
    {
        $service = app(WorkItemPlanService::class);
        $this->week = $service->weekStart($this->week !== '' ? $this->week : now())->toDateString();
        if (! $this->userId) {
            $this->userId = auth()->id();
        }
    }

    public function previousWeek(): void
    {
        $this->week = $this->weekStart()->subWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $this->week = $this->weekStart()->addWeek()->toDateString();
    }

    public function goToToday(): void
    {
        $this->week = app(WorkItemPlanService::class)->weekStart(now())->toDateString();
    }

    public function updatedUserId(): void
    {
        if (! $this->userId) {
            $this->userId = auth()->id();
        }
    }

    public function dropOnCell(string $kind, int $id, string $date, int $minutes, bool $allDay = false, bool $copy = false): void
    {
        $starts = $this->dateAt($date, $allDay ? 0 : $minutes);
        $user = $this->calendarUser();
        $actor = auth()->user();
        $service = app(WorkItemPlanService::class);

        if ($kind === 'queue') {
            $item = $this->workItem($id);
            if (! $item || (int) $item->assignee_id !== (int) $user->id) {
                return;
            }
            if ($allDay && $item->isMeetingItem()) {
                return;
            }
            $service->placeFromQueue($item, $user, $actor, $starts, $allDay);
            if ($this->pinId && (int) $this->pinId === (int) $item->id) {
                $this->pinId = null;
            }
            $this->refreshPlanQueue();

            return;
        }

        if ($kind === 'block') {
            $block = $this->blockForUser($id, $user->id);
            if (! $block) {
                return;
            }
            if ($copy) {
                $service->duplicateBlock($block, $actor, $starts, $allDay);

                return;
            }
            $snapshot = $this->blockSnapshot($block);
            $service->moveBlock($block, $starts, $allDay);
            $block->refresh();
            $this->offerUndo($this->movedMessage($block->starts_at, (bool) $block->all_day), 'restore_block', $snapshot);
            $this->refreshPlanQueue();

            return;
        }

        if ($kind === 'meeting') {
            if ($allDay || $copy) {
                return;
            }
            $item = $this->workItem($id);
            if (! $item || ! $item->isMeetingItem()) {
                return;
            }
            $source = $item->source;
            if (! $source instanceof ProjectTask) {
                return;
            }
            $snapshot = $this->meetingSnapshot($item, $source);
            $service->moveMeeting($item, $starts);
            $source->refresh();
            $this->offerUndo(
                $this->movedMessage($source->starts_at ?? $starts, false),
                'restore_meeting',
                $snapshot,
            );
            $this->refreshPlanQueue();
        }
    }

    public function resizeOnCell(string $kind, int $id, string $date, int $endMinutes): void
    {
        $ends = $this->dateAt($date, $endMinutes);
        $user = $this->calendarUser();
        $service = app(WorkItemPlanService::class);

        if ($kind === 'block') {
            $block = $this->blockForUser($id, $user->id);
            if (! $block) {
                return;
            }
            $snapshot = $this->blockSnapshot($block);
            $service->resizeBlock($block, $ends);
            $block->refresh();
            $this->offerUndo(
                $this->resizedMessage($block->starts_at, $block->ends_at),
                'restore_block',
                $snapshot,
            );

            return;
        }

        if ($kind === 'meeting') {
            $item = $this->workItem($id);
            if (! $item || ! $item->isMeetingItem()) {
                return;
            }
            $source = $item->source;
            if (! $source instanceof ProjectTask || ! $source->starts_at) {
                return;
            }
            $snapshot = $this->meetingSnapshot($item, $source);
            $service->resizeMeeting($item, $ends);
            $source->refresh();
            $this->offerUndo(
                $this->resizedMessage($source->starts_at, $source->ends_at),
                'restore_meeting',
                $snapshot,
            );
        }
    }

    public function unschedule(int $blockId): void
    {
        $block = $this->blockForUser($blockId, $this->calendarUser()->id);
        if (! $block) {
            $this->closeEvent();

            return;
        }
        $snapshot = $this->blockSnapshot($block);
        app(WorkItemPlanService::class)->deleteBlock($block);
        $this->closeEvent();
        $this->offerUndo('Odplanowano', 'recreate_block', $snapshot);
        $this->refreshPlanQueue();
    }

    public function openEvent(string $kind, int $id): void
    {
        if ($this->composerOpen) {
            $this->closeComposer();
        }

        if (! in_array($kind, ['block', 'meeting', 'item'], true) || $id < 1) {
            $this->closeEvent();

            return;
        }

        $this->openKind = $kind;
        $this->openId = $id;
        $this->openMemberId = null;
        $this->approvalComment = '';
        $this->editingSessionTitle = false;
        $this->sessionTitleDraft = '';
    }

    public function openSessionMember(int $itemId): void
    {
        if ($this->openKind !== 'block' || ! $this->openId || $itemId < 1) {
            return;
        }

        $block = $this->blockForUser($this->openId, $this->calendarUser()->id);
        $member = $block?->items()->whereKey($itemId)->first();
        if (! $member instanceof WorkItem || ! $member->status->isOpen()) {
            return;
        }

        $this->openMemberId = $itemId;
        $this->approvalComment = '';
    }

    public function closeSessionMember(): void
    {
        $this->openMemberId = null;
        $this->editingSessionTitle = false;
        $this->sessionTitleDraft = '';
    }

    public function closeEvent(): void
    {
        $this->openKind = null;
        $this->openId = null;
        $this->openMemberId = null;
        $this->approvalComment = '';
        $this->editingSessionTitle = false;
        $this->sessionTitleDraft = '';
    }

    public function openPrevSessionMember(): void
    {
        $id = (int) (($this->resolveOpenCard() ?? [])['prevMemberId'] ?? 0);
        if ($id > 0) {
            $this->openSessionMember($id);
        }
    }

    public function openNextSessionMember(): void
    {
        $id = (int) (($this->resolveOpenCard() ?? [])['nextMemberId'] ?? 0);
        if ($id > 0) {
            $this->openSessionMember($id);
        }
    }

    public function startSessionRename(): void
    {
        $card = $this->resolveOpenCard();
        if (! $card || ! $card['isSession'] || $card['viewingMember'] || ! $card['blockId']) {
            return;
        }

        $this->sessionTitleDraft = (string) ($card['title'] ?? '');
        $this->editingSessionTitle = true;
    }

    public function cancelSessionRename(): void
    {
        $this->editingSessionTitle = false;
        $this->sessionTitleDraft = '';
    }

    public function saveSessionTitle(): void
    {
        $card = $this->resolveOpenCard();
        if (! $card || ! $card['isSession'] || $card['viewingMember'] || ! $card['blockId']) {
            return;
        }

        $this->validate([
            'sessionTitleDraft' => ['nullable', 'string', 'max:255'],
        ]);

        $block = $this->blockForUser((int) $card['blockId'], $this->calendarUser()->id);
        if (! $block) {
            return;
        }

        app(WorkItemPlanService::class)->renameSession($block, $this->sessionTitleDraft);
        $this->editingSessionTitle = false;
        $this->sessionTitleDraft = '';
    }

    public function decideOpenApproval(string $decision): void
    {
        $card = $this->resolveOpenCard();
        $approval = $card['approval'] ?? null;
        if (! $approval instanceof ApprovalRequest) {
            return;
        }

        $this->authorize('decide', $approval);
        $this->validate([
            'approvalComment' => ['nullable', 'string', 'max:5000'],
        ]);
        if (! in_array($decision, ['approved', 'rejected'], true)) {
            return;
        }

        $approval->decide(
            ApprovalDecision::from($decision),
            auth()->user(),
            $this->approvalComment,
        );
        $this->approvalComment = '';
        $this->closeEvent();
        $this->refreshPlanQueue();
    }

    #[On('task-title-updated')]
    public function refreshAfterNestedTaskEdit(): void
    {
        // Occupancy titles refresh on the next parent render.
    }

    #[On('procedure-run-updated')]
    public function refreshAfterProcedureAdvance(): void
    {
        // Nested stepper already mutated the run; occupancy/status refresh on render.
    }

    public function dismissUndo(): void
    {
        $this->undo = null;
    }

    public function undoLastChange(): void
    {
        if (! $this->undo) {
            return;
        }

        $undo = $this->undo;
        $this->undo = null;
        $payload = $undo['payload'];

        if ($undo['type'] === 'restore_block') {
            $block = $this->blockForUser((int) $payload['id'], $this->calendarUser()->id);
            if (! $block) {
                return;
            }
            $block->update([
                'starts_at' => $payload['starts_at'],
                'ends_at' => $payload['ends_at'],
                'all_day' => $payload['all_day'],
            ]);
            $this->refreshPlanQueue();

            return;
        }

        if ($undo['type'] === 'recreate_block') {
            $block = WorkItemTimeBlock::query()->create([
                'work_item_id' => $payload['work_item_id'],
                'kind' => $payload['kind'],
                'title' => $payload['title'],
                'user_id' => $payload['user_id'],
                'starts_at' => $payload['starts_at'],
                'ends_at' => $payload['ends_at'],
                'all_day' => $payload['all_day'],
                'created_by_id' => $payload['created_by_id'],
            ]);
            if ($payload['kind'] === WorkItemTimeBlockKind::Session->value && $payload['item_ids'] !== []) {
                $block->items()->sync($payload['item_ids']);
            }
            $this->refreshPlanQueue();

            return;
        }

        if ($undo['type'] === 'restore_meeting') {
            $item = $this->workItem((int) $payload['work_item_id']);
            $source = $item?->source;
            if (! $source instanceof ProjectTask) {
                return;
            }
            $source->update([
                'starts_at' => $payload['starts_at'],
                'ends_at' => $payload['ends_at'],
                'due_date' => $payload['due_date'],
            ]);
            $this->refreshPlanQueue();
        }
    }

    public function addToSession(int $blockId, int $itemId): void
    {
        if ($this->attachQueueItemToSession($blockId, $itemId)) {
            $this->refreshPlanQueue();
        }
    }

    /**
     * @param  list<int>  $ids
     */
    public function addQueueItemsToSession(array $ids = [], int $sessionId = 0): void
    {
        $ids = $this->normalizeQueueIds($ids);
        $added = [];
        foreach ($ids as $id) {
            if ($this->attachQueueItemToSession($sessionId, $id)) {
                $added[] = $id;
            }
        }
        if ($added !== []) {
            $this->forgetQueueSelection($added);
            $this->refreshPlanQueue();
        }
    }

    /**
     * Zaznaczone karty z kolejki (bez spotkań) spadają na siatkę jako nowa sesja.
     *
     * @param  list<int>  $ids
     */
    public function dropQueueBundle(array $ids, string $date, int $minutes, bool $allDay = false, string $title = ''): void
    {
        $ids = $this->normalizeQueueIds($ids);
        $user = $this->calendarUser();
        $actor = auth()->user();
        $service = app(WorkItemPlanService::class);
        $items = [];
        foreach ($ids as $id) {
            $item = $this->workItem($id);
            if (! $item || $item->isMeetingItem() || (int) $item->assignee_id !== (int) $user->id) {
                continue;
            }
            $items[] = $item;
        }

        if (count($items) < 2) {
            if (count($items) === 1) {
                $this->dropOnCell('queue', $items[0]->id, $date, $minutes, $allDay);
            }

            return;
        }

        $starts = $this->dateAt($date, $allDay ? 0 : $minutes);
        $ends = $allDay ? $starts : $starts->addMinutes(WorkItemPlanService::DEFAULT_MINUTES);
        $session = $service->createSession($title, $user, $actor, $starts, $ends, $allDay);
        $added = [];
        foreach ($items as $item) {
            $service->addToSession($session, $item);
            $added[] = (int) $item->id;
            if ($this->pinId && (int) $this->pinId === (int) $item->id) {
                $this->pinId = null;
            }
        }
        $this->forgetQueueSelection($added);
        $this->refreshPlanQueue();
    }

    /**
     * @param  list<mixed>  $ids
     * @return list<int>
     */
    protected function normalizeQueueIds(array $ids): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', $ids),
            fn (int $id) => $id > 0
        )));
    }

    /**
     * @param  list<int>  $ids
     */
    protected function forgetQueueSelection(array $ids): void
    {
        $this->dispatch('plan-queue-forget-selected', ids: $ids)->to(TasksGrid::class);
    }

    protected function attachQueueItemToSession(int $blockId, int $itemId): bool
    {
        $user = $this->calendarUser();
        $block = $this->blockForUser($blockId, $user->id);
        $item = $this->workItem($itemId);
        if (! $block || ! $item || (int) $item->assignee_id !== (int) $user->id) {
            return false;
        }

        app(WorkItemPlanService::class)->addToSession($block, $item);
        if ($this->pinId && (int) $this->pinId === (int) $item->id) {
            $this->pinId = null;
        }

        return true;
    }

    public function removeFromSession(int $blockId, int $itemId): void
    {
        $block = $this->blockForUser($blockId, $this->calendarUser()->id);
        if (! $block) {
            return;
        }

        app(WorkItemPlanService::class)->removeFromSession($block, $itemId);
        if ($this->openMemberId && (int) $this->openMemberId === $itemId) {
            $this->openMemberId = null;
        }
        $this->refreshPlanQueue();
    }

    public function openComposer(string $date, int $startMinutes, int $endMinutes, bool $allDay = false): void
    {
        $this->closeEvent();
        $this->resetErrorBag();
        $start = min($startMinutes, $endMinutes);
        $end = max($startMinutes, $endMinutes);
        $this->composerDate = $date;
        $this->composerStart = $start;
        $this->composerEnd = max($start + WorkItemPlanService::SNAP_MINUTES, $end);
        $this->composerAllDay = $allDay;
        $this->composerUnscheduled = false;
        $this->composerType = 'task';
        $this->composerTitle = '';
        $this->composerLocation = '';
        $this->composerParticipantIds = [$this->calendarUser()->id];
        $this->composerProcedureTemplateId = '';
        $this->composerProcedureSubjectId = '';
        $this->composerProcedureNameSuffix = '';
        $this->composerOpen = true;
    }

    public function openUnscheduledMeeting(): void
    {
        $this->closeEvent();
        $this->resetErrorBag();
        $this->composerDate = '';
        $this->composerStart = 0;
        $this->composerEnd = 0;
        $this->composerAllDay = false;
        $this->composerUnscheduled = true;
        $this->composerType = 'meeting';
        $this->composerTitle = '';
        $this->composerLocation = '';
        $this->composerParticipantIds = [$this->calendarUser()->id];
        $this->composerProcedureTemplateId = '';
        $this->composerProcedureSubjectId = '';
        $this->composerProcedureNameSuffix = '';
        $this->composerOpen = true;
    }

    public function closeComposer(): void
    {
        $this->composerOpen = false;
        $this->composerUnscheduled = false;
        $this->composerTitle = '';
        $this->composerLocation = '';
        $this->composerParticipantIds = [];
        $this->composerProcedureTemplateId = '';
        $this->composerProcedureSubjectId = '';
        $this->composerProcedureNameSuffix = '';
        $this->resetErrorBag();
    }

    public function updatedComposerType(): void
    {
        $this->resetErrorBag();
        if ($this->composerUnscheduled && $this->composerType !== 'meeting') {
            $this->composerType = 'meeting';
        }
        if ($this->composerType === 'meeting' && $this->composerParticipantIds === []) {
            $this->composerParticipantIds = [$this->calendarUser()->id];
        }
    }

    public function updatedComposerProcedureTemplateId(): void
    {
        $this->composerProcedureSubjectId = '';
        $this->composerProcedureNameSuffix = '';
        $this->resetErrorBag(['composerProcedureSubjectId', 'composerProcedureNameSuffix']);
    }

    public function submitComposer(): void
    {
        $this->validate($this->composerRules(), $this->composerMessages(), $this->composerAttributes());

        if ($this->composerType === 'meeting' && $this->composerAllDay && ! $this->composerUnscheduled) {
            throw ValidationException::withMessages([
                'composerType' => 'Spotkanie musi mieć godzinę — narysuj slot na siatce albo dodaj je bez terminu z kolejki.',
            ]);
        }

        $service = app(WorkItemPlanService::class);

        if ($this->composerType === 'meeting' && $this->composerUnscheduled) {
            $service->createHangingMeeting(
                $this->composerTitle,
                $this->calendarUser(),
                auth()->user(),
                [
                    'location' => $this->composerLocation,
                    'participant_ids' => $this->composerParticipantIds,
                ],
            );
            $this->closeComposer();
            $this->refreshPlanQueue();

            return;
        }

        $starts = $this->dateAt($this->composerDate, $this->composerAllDay ? 0 : $this->composerStart);
        $ends = $this->dateAt($this->composerDate, $this->composerAllDay ? 0 : $this->composerEnd);

        try {
            $service->createOnCalendar(
                $this->composerType,
                $this->composerTitle,
                $this->calendarUser(),
                auth()->user(),
                $starts,
                $ends,
                $this->composerAllDay,
                [
                    'location' => $this->composerLocation,
                    'participant_ids' => $this->composerParticipantIds,
                    'template_id' => $this->composerProcedureTemplateId !== '' ? (int) $this->composerProcedureTemplateId : null,
                    'subject_id' => $this->composerProcedureSubjectId !== '' ? (int) $this->composerProcedureSubjectId : null,
                    'name_suffix' => $this->composerProcedureNameSuffix !== '' ? $this->composerProcedureNameSuffix : null,
                ],
            );
        } catch (ValidationException $e) {
            throw $e;
        }

        $this->closeComposer();
        $this->refreshPlanQueue();
    }

    public function procedureComposerSubjectType(): ?ProcedureSubjectType
    {
        if ($this->composerProcedureTemplateId === '') {
            return null;
        }

        return ProcedureTemplate::query()->find((int) $this->composerProcedureTemplateId)?->subjectType();
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    public function procedureComposerSubjectOptions(): array
    {
        return $this->procedureComposerSubjectType()?->dropdownOptions() ?? [];
    }

    public function render()
    {
        $service = app(WorkItemPlanService::class);
        $weekStart = $this->weekStart();
        $user = $this->calendarUser();
        $now = now();
        $occupancy = $service->occupancy($user, $weekStart, $now);
        $pinnedTitle = $this->pinId
            ? WorkItem::query()->whereKey($this->pinId)->value('title')
            : null;

        return view('livewire.work-item-plan', [
            'calendarUser' => $user,
            'users' => $this->users(),
            'weekStart' => $weekStart,
            'weekLabel' => $weekStart->format('d.m').'–'.$weekStart->addDays(6)->format('d.m.Y'),
            'days' => $service->weekDays($weekStart),
            'hours' => range(WorkItemPlanService::GRID_START_HOUR, WorkItemPlanService::GRID_END_HOUR - 1),
            'pinnedTitle' => $pinnedTitle,
            'eventsByDay' => $occupancy['timed'],
            'allDayByDay' => $occupancy['allDay'],
            'dueFlags' => $service->dueFlags($user, $weekStart),
            'today' => CarbonImmutable::parse($now)->toDateString(),
            'gridStartHour' => WorkItemPlanService::GRID_START_HOUR,
            'viewStartHour' => WorkItemPlanService::VIEW_START_HOUR,
            'snap' => WorkItemPlanService::SNAP_MINUTES,
            'defaultMinutes' => WorkItemPlanService::DEFAULT_MINUTES,
            'composerRangeLabel' => $this->composerRangeLabel(),
            'procedureTemplates' => ProcedureTemplate::query()->orderBy('name')->get(['id', 'name', 'subject_type']),
            'procedureSubjectType' => $this->procedureComposerSubjectType(),
            'procedureSubjectOptions' => $this->procedureComposerSubjectOptions(),
            'openCard' => $this->resolveOpenCard(),
        ]);
    }

    protected function composerRangeLabel(): string
    {
        if ($this->composerOpen && $this->composerUnscheduled) {
            return 'Bez terminu · kolejka Do przypięcia';
        }
        if (! $this->composerOpen || $this->composerDate === '') {
            return '';
        }
        $day = CarbonImmutable::parse($this->composerDate);
        $date = $day->format('d.m.Y');
        if ($this->composerAllDay) {
            return $date.' · cały dzień';
        }
        $start = $day->startOfDay()->addMinutes($this->composerStart);
        $end = $day->startOfDay()->addMinutes($this->composerEnd);

        return $date.' · '.$start->format('H:i').'–'.$end->format('H:i');
    }

    /**
     * @return array<string, mixed>
     */
    protected function composerRules(): array
    {
        $rules = [
            'composerType' => ['required', 'in:task,meeting,approval,procedure,session'],
        ];

        if (! $this->composerUnscheduled) {
            $rules['composerDate'] = ['required', 'date'];
        }

        if ($this->composerType === 'procedure') {
            $rules['composerProcedureTemplateId'] = ['required', 'exists:procedure_templates,id'];
            $rules['composerProcedureNameSuffix'] = ['nullable', 'string', 'max:80'];
            $subjectType = $this->procedureComposerSubjectType();
            $subjectTable = null;
            if ($subjectType) {
                $modelClass = $subjectType->modelClass();
                $subjectTable = (new $modelClass)->getTable();
            }
            $rules['composerProcedureSubjectId'] = array_values(array_filter([
                $subjectType ? 'required' : 'nullable',
                'integer',
                $subjectTable ? Rule::exists($subjectTable, 'id') : null,
            ]));

            return $rules;
        }

        if ($this->composerType === 'session') {
            $rules['composerTitle'] = ['nullable', 'string', 'max:255'];

            return $rules;
        }

        $rules['composerTitle'] = ['required', 'string', 'max:255'];

        if ($this->composerType === 'meeting') {
            $rules['composerLocation'] = ['nullable', 'string', 'max:4000'];
            $rules['composerParticipantIds'] = ['array'];
            $rules['composerParticipantIds.*'] = ['integer', 'exists:users,id'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function composerMessages(): array
    {
        $subjectType = $this->procedureComposerSubjectType();

        return [
            'composerProcedureSubjectId.required' => 'Wybierz '.mb_strtolower($subjectType?->label() ?? 'kogo dotyczy').'.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function composerAttributes(): array
    {
        $subjectType = $this->procedureComposerSubjectType();

        return [
            'composerTitle' => 'nazwa',
            'composerType' => 'typ',
            'composerLocation' => 'miejsce',
            'composerParticipantIds' => 'uczestnicy',
            'composerProcedureTemplateId' => 'szablon procedury',
            'composerProcedureNameSuffix' => 'dopisek',
            'composerProcedureSubjectId' => $subjectType?->label() ?? 'dotyczy',
        ];
    }

    protected function refreshPlanQueue(): void
    {
        $this->dispatch('plan-queue-refresh')->to(TasksGrid::class);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function offerUndo(string $message, string $type, array $payload): void
    {
        $this->undo = [
            'token' => (string) Str::uuid(),
            'message' => $message,
            'type' => $type,
            'payload' => $payload,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function blockSnapshot(WorkItemTimeBlock $block): array
    {
        $kind = $block->kind instanceof WorkItemTimeBlockKind
            ? $block->kind->value
            : (string) $block->kind;

        return [
            'id' => $block->id,
            'work_item_id' => $block->work_item_id,
            'kind' => $kind,
            'title' => $block->title,
            'user_id' => $block->user_id,
            'starts_at' => $block->starts_at->toDateTimeString(),
            'ends_at' => $block->ends_at->toDateTimeString(),
            'all_day' => (bool) $block->all_day,
            'created_by_id' => $block->created_by_id,
            'item_ids' => $kind === WorkItemTimeBlockKind::Session->value
                ? $block->items()->pluck('work_items.id')->all()
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meetingSnapshot(WorkItem $item, ProjectTask $task): array
    {
        return [
            'work_item_id' => $item->id,
            'starts_at' => $task->starts_at?->toDateTimeString(),
            'ends_at' => $task->ends_at?->toDateTimeString(),
            'due_date' => $task->due_date?->toDateString(),
        ];
    }

    protected function movedMessage(CarbonInterface $starts, bool $allDay): string
    {
        $when = CarbonImmutable::parse($starts)->locale('pl');
        if ($allDay) {
            return 'Wydarzenie zostało przełożone na '.$when->translatedFormat('j M').' · cały dzień';
        }

        return 'Wydarzenie zostało przełożone na '.$when->translatedFormat('j M, H:i');
    }

    protected function resizedMessage(CarbonInterface $starts, CarbonInterface $ends): string
    {
        $start = CarbonImmutable::parse($starts)->locale('pl');
        $end = CarbonImmutable::parse($ends)->locale('pl');

        return 'Wydarzenie zostało przełożone na '.$start->translatedFormat('j M, H:i').'–'.$end->format('H:i');
    }

    /**
     * @return array{
     *     key: string,
     *     timeLabel: string,
     *     typeLabel: string,
     *     typeIcon: string,
     *     title: string,
     *     url: string,
     *     canUnschedule: bool,
     *     isSession: bool,
     *     viewingMember: bool,
     *     members: list<array{id: int, title: string, url: string, typeLabel: string, typeIcon: string, dueLabel: ?string, dueLate: bool}>,
     *     task: ?ProjectTask,
     *     showSubtasks: bool,
     *     description: string,
     *     blockId: ?int
     * }|null
     */
    protected function resolveOpenCard(): ?array
    {
        if (! $this->openKind || ! $this->openId) {
            return null;
        }

        if ($this->openKind === 'meeting') {
            return $this->resolveOpenMeetingCard($this->openId);
        }

        if ($this->openKind === 'item') {
            return $this->resolveOpenQueueItemCard($this->openId);
        }

        return $this->resolveOpenBlockCard($this->openId);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function resolveOpenQueueItemCard(int $workItemId): ?array
    {
        $item = $this->workItem($workItemId);
        if (! $item || (int) $item->assignee_id !== (int) $this->calendarUser()->id) {
            return null;
        }

        $item->loadMissing('source');

        return $this->cardFromWorkItem($item, 'Bez godziny', false, false, false, [], null);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function resolveOpenMeetingCard(int $workItemId): ?array
    {
        $item = $this->workItem($workItemId);
        $source = $item?->source;
        if (! $item || ! $item->isMeetingItem() || ! $source instanceof ProjectTask) {
            return null;
        }

        $userId = (int) $this->calendarUser()->id;
        $participants = array_map('intval', $source->participant_ids ?? []);
        if ((int) $item->assignee_id !== $userId && ! in_array($userId, $participants, true)) {
            return null;
        }

        $starts = $source->starts_at ? CarbonImmutable::parse($source->starts_at) : null;
        $ends = $source->ends_at ? CarbonImmutable::parse($source->ends_at) : null;
        $timeLabel = $starts
            ? $this->planSlotLabel($starts, $ends ?? $starts->addMinutes(WorkItemPlanService::DEFAULT_MINUTES), false)
            : 'Bez godziny';

        return $this->cardFromWorkItem($item, $timeLabel, false, false, false, [], null);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function resolveOpenBlockCard(int $blockId): ?array
    {
        $block = $this->blockForUser($blockId, $this->calendarUser()->id);
        if (! $block) {
            return null;
        }

        $block->loadMissing(['workItem.source', 'items']);
        $timeLabel = $this->planSlotLabel(
            CarbonImmutable::parse($block->starts_at),
            CarbonImmutable::parse($block->ends_at),
            (bool) $block->all_day,
        );

        if ($block->isSession()) {
            $members = $this->sessionMemberRows($block);
            $member = $this->openMemberId
                ? $block->items->firstWhere('id', $this->openMemberId)
                : null;
            if ($member instanceof WorkItem && $member->status->isOpen()) {
                return $this->cardFromWorkItem($member, $timeLabel, false, true, true, $members, $block->id);
            }

            return array_merge([
                'key' => 'session:'.$block->id,
                'timeLabel' => $timeLabel,
                'typeLabel' => WorkItemTimeBlockKind::Session->label(),
                'typeIcon' => WorkItemTimeBlockKind::Session->icon(),
                'title' => trim((string) $block->title),
                'url' => '',
                'canUnschedule' => true,
                'isSession' => true,
                'viewingMember' => false,
                'members' => $members,
                'task' => null,
                'approval' => null,
                'procedureRun' => null,
                'showSubtasks' => false,
                'description' => '',
                'blockId' => $block->id,
            ], $this->memberNav($members, null));
        }

        $item = $block->workItem;
        if (! $item) {
            return null;
        }
        $item->loadMissing('source');

        return $this->cardFromWorkItem($item, $timeLabel, true, false, false, [], $block->id);
    }

    /**
     * @param  list<array{id: int, title: string, url: string, typeLabel: string, typeIcon: string, dueLabel: ?string, dueLate: bool}>  $members
     * @return array<string, mixed>
     */
    protected function cardFromWorkItem(
        WorkItem $item,
        string $timeLabel,
        bool $canUnschedule,
        bool $isSession,
        bool $viewingMember,
        array $members,
        ?int $blockId,
    ): array {
        $task = $item->editableProjectTask();
        if ($task) {
            $task->loadMissing(['assignedTo', 'createdBy', 'sprint', 'attachments.uploader']);
            if ($task->isProcedure()) {
                $task->loadMissing([
                    'procedureRun.template',
                    'procedureRun.version',
                    'procedureRun.steps.approvalRequest.approver',
                    'procedureRun.steps.performedBy',
                    'procedureRun.subject',
                    'procedureRun.startedBy',
                    'procedureRun.task',
                ]);
            }
        }

        $approval = $item->source instanceof ApprovalRequest ? $item->source : null;
        if ($approval && auth()->user()?->can('view', $approval)) {
            $approval->loadMissing([
                'approver',
                'createdBy',
                'decidedBy',
                'sprint',
                'attachments.uploader',
                'comment.commentable',
                'procedureRun.template',
                'procedureRun.task',
                'procedureRun.subject',
            ]);
        } else {
            $approval = null;
        }

        return array_merge([
            'key' => ($viewingMember ? 'session-member:' : 'item:').$item->id,
            'timeLabel' => $timeLabel,
            'typeLabel' => $item->type->label(),
            'typeIcon' => $item->type->icon(),
            'title' => $item->title,
            'url' => $item->openUrl(),
            'canUnschedule' => $canUnschedule,
            'isSession' => $isSession,
            'viewingMember' => $viewingMember,
            'members' => $members,
            'task' => $task,
            'approval' => $approval,
            'procedureRun' => $task?->procedureRun,
            'showSubtasks' => $task && ! $task->isProcedure() && ! $task->isCallback() && ! $task->isMeeting(),
            'description' => $item->plainDescription(),
            'blockId' => $blockId,
        ], $this->memberNav($members, $viewingMember ? $item->id : null));
    }

    /**
     * @param  list<array{id: int, title: string, url: string, typeLabel: string, typeIcon: string, dueLabel: ?string, dueLate: bool}>  $members
     * @return array{prevMemberId: ?int, nextMemberId: ?int, memberIndex: int, memberTotal: int}
     */
    protected function memberNav(array $members, ?int $currentId): array
    {
        $ids = array_values(array_map('intval', array_column($members, 'id')));
        $index = $currentId ? array_search((int) $currentId, $ids, true) : false;

        return [
            'prevMemberId' => $index === false ? null : ($ids[$index - 1] ?? null),
            'nextMemberId' => $index === false ? null : ($ids[$index + 1] ?? null),
            'memberIndex' => $index === false ? 0 : $index + 1,
            'memberTotal' => count($ids),
        ];
    }

    /**
     * @return list<array{id: int, title: string, url: string, typeLabel: string, typeIcon: string, dueLabel: ?string, dueLate: bool}>
     */
    protected function sessionMemberRows(WorkItemTimeBlock $block): array
    {
        return $block->openItems()
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
    }

    protected function planSlotLabel(CarbonImmutable $starts, CarbonImmutable $ends, bool $allDay): string
    {
        $day = $starts->locale('pl');
        $head = Str::ucfirst($day->isoFormat('dd')).' '.$day->format('d.m');
        if ($allDay) {
            return $head.' · cały dzień';
        }

        return $head.' · '.$starts->format('H:i').'–'.$ends->format('H:i');
    }

    protected function weekStart(): CarbonImmutable
    {
        return app(WorkItemPlanService::class)->weekStart($this->week !== '' ? $this->week : now());
    }

    protected function calendarUser(): User
    {
        $user = $this->userId ? User::query()->find($this->userId) : null;

        return $user ?? auth()->user();
    }

    /**
     * @return Collection<int, User>
     */
    protected function users(): Collection
    {
        return User::query()->orderBy('name')->get(['id', 'name']);
    }

    protected function workItem(int $id): ?WorkItem
    {
        return WorkItem::query()->with('source')->find($id);
    }

    protected function blockForUser(int $id, int $userId): ?WorkItemTimeBlock
    {
        return WorkItemTimeBlock::query()
            ->where('user_id', $userId)
            ->find($id);
    }

    protected function dateAt(string $date, int $minutes): CarbonImmutable
    {
        $minutes = max(0, min(24 * 60, $minutes));
        if ($minutes === 24 * 60) {
            return CarbonImmutable::parse($date)->startOfDay()->addDay();
        }

        return CarbonImmutable::parse($date)->startOfDay()->addMinutes($minutes);
    }
}
