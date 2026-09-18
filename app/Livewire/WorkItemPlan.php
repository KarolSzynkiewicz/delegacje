<?php

namespace App\Livewire;

use App\Enums\ProcedureSubjectType;
use App\Enums\WorkItemType;
use App\Models\ProcedureTemplate;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemTimeBlock;
use App\Services\WorkItemPlanService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;

class WorkItemPlan extends Component
{
    #[Url(as: 'w')]
    public string $week = '';

    #[Url(as: 'u')]
    public ?int $userId = null;

    public bool $composerOpen = false;

    public string $composerType = 'task';

    public string $composerTitle = '';

    public string $composerDate = '';

    public int $composerStart = 0;

    public int $composerEnd = 0;

    public bool $composerAllDay = false;

    public string $composerLocation = '';

    /** @var list<int|string> */
    public array $composerParticipantIds = [];

    public string $composerProcedureTemplateId = '';

    public string $composerProcedureSubjectId = '';

    public string $composerProcedureNameSuffix = '';

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
            $service->placeFromQueue($item, $user, $actor, $starts, $allDay);

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
            $service->moveBlock($block, $starts, $allDay);

            return;
        }

        if ($kind === 'meeting') {
            if ($allDay || $copy) {
                return;
            }
            $item = $this->workItem($id);
            if (! $item || $item->type !== WorkItemType::Meeting) {
                return;
            }
            $service->moveMeeting($item, $starts);
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
            $service->resizeBlock($block, $ends);

            return;
        }

        if ($kind === 'meeting') {
            $item = $this->workItem($id);
            if (! $item || $item->type !== WorkItemType::Meeting) {
                return;
            }
            $service->resizeMeeting($item, $ends);
        }
    }

    public function unschedule(int $blockId): void
    {
        $block = $this->blockForUser($blockId, $this->calendarUser()->id);
        if (! $block) {
            return;
        }
        app(WorkItemPlanService::class)->deleteBlock($block);
    }

    public function openComposer(string $date, int $startMinutes, int $endMinutes, bool $allDay = false): void
    {
        $this->resetErrorBag();
        $start = min($startMinutes, $endMinutes);
        $end = max($startMinutes, $endMinutes);
        $this->composerDate = $date;
        $this->composerStart = $start;
        $this->composerEnd = max($start + WorkItemPlanService::SNAP_MINUTES, $end);
        $this->composerAllDay = $allDay;
        $this->composerType = 'task';
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

        $starts = $this->dateAt($this->composerDate, $this->composerAllDay ? 0 : $this->composerStart);
        $ends = $this->dateAt($this->composerDate, $this->composerAllDay ? 0 : $this->composerEnd);

        try {
            app(WorkItemPlanService::class)->createOnCalendar(
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

        return view('livewire.work-item-plan', [
            'calendarUser' => $user,
            'users' => $this->users(),
            'weekStart' => $weekStart,
            'weekLabel' => $weekStart->format('d.m').'–'.$weekStart->addDays(6)->format('d.m.Y'),
            'days' => $service->weekDays($weekStart),
            'hours' => range(WorkItemPlanService::GRID_START_HOUR, WorkItemPlanService::GRID_END_HOUR - 1),
            'queue' => $service->queue($user, $now),
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
        ]);
    }

    protected function composerRangeLabel(): string
    {
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
            'composerType' => ['required', 'in:task,meeting,approval,procedure'],
            'composerDate' => ['required', 'date'],
        ];

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
