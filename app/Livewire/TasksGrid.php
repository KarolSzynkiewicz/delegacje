<?php

namespace App\Livewire;

use App\Contracts\Llm\LlmClient;
use App\Enums\ProcedureSubjectType;
use App\Enums\TaskStatus;
use App\Enums\WorkItemStatus;
use App\Enums\WorkItemType;
use App\Exceptions\LlmException;
use App\Models\ApprovalRequest;
use App\Models\Comment;
use App\Models\CommentMention;
use App\Models\ProcedureTemplate;
use App\Models\ProjectTask;
use App\Models\Sprint;
use App\Models\TaskGridView;
use App\Models\TaskSubtask;
use App\Models\TaskSubtaskEvent;
use App\Models\User;
use App\Models\WorkItem;
use App\Policies\ProjectTaskPolicy;
use App\Services\Llm\TasksFilterImportService;
use App\Services\Llm\TasksFilterMutateService;
use App\Services\Llm\TasksFilterSummaryService;
use App\Services\ProcedureRunService;
use App\Services\TaskCreationService;
use App\Services\UserMentionService;
use App\Services\WorkItemPlanService;
use App\Support\EdiTaskEdit;
use App\Support\Export\TaskExport;
use App\Support\TasksGridUrlParams;
use App\Support\WorkItemListNavigator;
use App\WorkItems\GridField;
use App\WorkItems\ProjectTaskFields;
use App\WorkItems\StatusWidget;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Livewire\WithPagination;

class TasksGrid extends Component
{
    use WithPagination;

    // Filters
    public string $searchTask = '';

    public string $searchCategory = '';

    public string $searchAssignedTo = '';

    /** Priorytet 1–5 z kliknięcia w komórkę, albo `none` = bez priorytetu. Pusty = bez tego wymiaru. */
    public string $filterPriority = '';

    /** Termin Y-m-d (do tego dnia włącznie) albo `none`. Pusty = bez tego wymiaru. */
    public string $filterDueDate = '';

    /** `none` = poza sprintem. Pusty = bez tego wymiaru. */
    public string $filterSprint = '';

    public string $status = ''; // '' = active (pending+in_progress), 'closed', 'all' — skrót z selectedStatuses

    /**
     * Statusy w filtrze (OR wewnątrz). Domyślnie aktywne.
     *
     * @var list<string>
     */
    public array $selectedStatuses = ['pending', 'in_progress'];

    /** '' = wszyscy, 'me' / ID — skrót gdy wybrana jest jedna osoba. */
    public string $assignedFilter = '';

    /**
     * Przypisani (OR wewnątrz). Pusta tablica = wszyscy.
     *
     * @var list<string>
     */
    public array $assignedFilters = [];

    public string $createdByFilter = '';

    /**
     * Twórcy (OR wewnątrz). Pusta tablica = wszyscy.
     *
     * @var list<string>
     */
    public array $createdByFilters = [];

    /**
     * Zaznaczone typy work itemów (checkboxy „Typ pracy” w panelu filtrów,
     * zastępują dawny pojedynczy przełącznik „pokaż oddzwonienia rekrutacji”).
     * Domyślnie bez Oddzwonień (WorkItemType::Callback) — to automatyczne
     * przypomnienia z rekrutacji, osobny workflow, nie mają zaśmiecać backlogu.
     * Pusta tablica = świadomie odznaczone wszystko = brak wyników.
     *
     * @var list<string>
     */
    public array $selectedTypes = ['task', 'subtask', 'procedure_run', 'dispatch', 'follow_up', 'meeting', 'approval'];

    /**
     * Zostawione pod zapisane widoki / stary query string. Między wymiarami
     * filtrów zawsze AND; OR jest tylko wewnątrz wielowartościowego pola
     * (np. Marek lub Krzyś).
     */
    public string $filterJoin = 'and';

    /**
     * Operator per wymiar: eq (jest / zawiera) albo neq (nie jest / nie zawiera).
     *
     * @var array<string, string>
     */
    public array $filterOps = [
        'status' => 'eq',
        'assignedFilter' => 'eq',
        'createdByFilter' => 'eq',
        'selectedTypes' => 'eq',
        'searchTask' => 'eq',
        'searchCategory' => 'eq',
        'searchAssignedTo' => 'eq',
    ];

    // Sorting
    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    // Grouping
    public string $groupBy = '';

    /** @var list<string> */
    public array $collapsedGroups = [];

    // Column management
    public array $visibleColumns = ['name', 'type', 'status', 'sprint', 'category', 'assigned_to', 'created_by', 'priority', 'due_date', 'blocks', 'subtasks'];

    public array $columnWidths = [];

    // Saved views (slug in URL → ?view=moj-widok)
    public string $view = '';

    public string $saveViewName = '';

    public bool $saveViewAsGlobal = false;

    public ?int $activeViewId = null;

    /** Gdy ustawione, siatka pokazuje tylko zadania tego sprintu (np. na stronie sprintu). */
    public ?int $lockedSprintId = null;

    /** Kolejka Planu: karty backlogu z niewyłączalnym overlay (osoba kalendarza + bez slotu ≥ dziś). */
    public bool $planQueue = false;

    public ?int $planUserId = null;

    public ?int $planPinId = null;

    /**
     * Zaznaczone wiersze / karty (work item albo project task id).
     *
     * @var list<int>
     */
    public array $selectedIds = [];

    public string $bulkField = '';

    public string $bulkValue = '';

    /** @var list<int> */
    public array $listedIds = [];

    // Expanded rows (task IDs)
    public array $expandedTasks = [];

    /**
     * Liczby na pigułkach widoków. Publiczne, żeby przetrwać skip-refresh
     * przy expand/collapse (inaczej każdy chevron robi N zapytań COUNT).
     *
     * @var array<int, int>
     */
    public array $viewCountsCache = [];

    /** table | cards — jedna siatka w HTML, druga tylko po zmianie viewportu. */
    public string $layout = 'table';

    // Chrono: podsumowanie filtra / import zadań w kontekście widoku
    public bool $showChronoModal = false;

    /** menu | summary | import | export | edi-import | edi-export */
    public string $chronoMode = 'menu';

    public bool $chronoLoading = false;

    public ?string $chronoError = null;

    /** @var array{headline: string, summary: string, highlights: list<string>, risks: list<string>}|null */
    public ?array $chronoSummary = null;

    public string $importText = '';

    public string $importMode = 'json';

    /** @var list<array<string, mixed>> */
    public array $importProposals = [];

    /** @var list<int> */
    public array $importSelected = [];

    public string $exportJson = '';

    public int $exportCount = 0;

    public int $exportTotal = 0;

    public ?string $ediIntent = null;

    public bool $ediLoading = false;

    public ?string $ediError = null;

    public int $ediReviewed = 0;

    public int $ediTotal = 0;

    /** @var list<array{row_id: int, field: string, kind: string, from: mixed, to: mixed, from_label: string, to_label: string}> */
    public array $ediChanges = [];

    public ?int $ediEditingRowId = null;

    public string $ediEditingField = '';

    // Inline editing
    public ?int $editingTaskId = null;

    public string $editingField = '';

    public string $editingValue = '';

    // Inline add task / procedure / approval
    public bool $showAddRow = false;

    public string $addKind = 'task';

    public string $newTaskName = '';

    public string $newTaskSprint = '';

    public string $newTaskCategory = '';

    public string $newTaskAssignedTo = '';

    public string $newTaskPriority = '';

    public string $newTaskDueDate = '';

    public string $newProcedureTemplateId = '';

    public string $newProcedureSubjectId = '';

    public string $newProcedureNameSuffix = '';

    public string $newMeetingDate = '';

    public string $newMeetingStart = '';

    public string $newMeetingEnd = '';

    /** @var list<int|string> */
    public array $newMeetingParticipantIds = [];

    public string $newMeetingLocation = '';

    // Inline add subtask
    public ?int $addingSubtaskForTask = null;

    public string $newSubtaskName = '';

    // Flash messages
    public ?string $flash = null;

    private bool $batchingViewPersist = false;

    private ?string $groupByBeforeUpdate = null;

    protected function queryString(): array
    {
        if ($this->isLockedToSprint() || $this->isPlanQueue()) {
            return [];
        }

        return [
            'searchTask' => ['except' => '', 'history' => true],
            'searchCategory' => ['except' => '', 'history' => true],
            'searchAssignedTo' => ['except' => '', 'history' => true],
            'filterPriority' => ['except' => '', 'as' => 'priority', 'history' => true],
            'filterDueDate' => ['except' => '', 'as' => 'due', 'history' => true],
            'filterSprint' => ['except' => '', 'as' => 'sprint', 'history' => true],
            'status' => ['except' => '', 'history' => true],
            'selectedStatuses' => ['except' => $this->defaultStatuses(), 'as' => 'statuses', 'history' => true],
            'assignedFilter' => ['except' => '', 'history' => true],
            'assignedFilters' => ['except' => [], 'as' => 'assigned', 'history' => true],
            'createdByFilter' => ['except' => '', 'history' => true],
            'createdByFilters' => ['except' => [], 'as' => 'createdBy', 'history' => true],
            'selectedTypes' => ['except' => $this->defaultSelectedTypes(), 'as' => 'types', 'history' => true],
            'filterJoin' => ['except' => 'and', 'as' => 'join', 'history' => true],
            'sortField' => ['except' => 'created_at', 'history' => true],
            'sortDirection' => ['except' => 'desc', 'history' => true],
            'groupBy' => ['except' => '', 'history' => true],
            'view' => ['except' => '', 'history' => true],
        ];
    }

    /** Zmiana tych pól odłącza aktywny zapisany widok (zamiast go nadpisywać). */
    /** @var list<string> */
    protected array $viewDetachingProperties = [
        'searchTask',
        'searchCategory',
        'searchAssignedTo',
        'filterPriority',
        'filterDueDate',
        'filterSprint',
        'status',
        'selectedStatuses',
        'assignedFilter',
        'assignedFilters',
        'createdByFilter',
        'createdByFilters',
        'selectedTypes',
        'filterJoin',
        'filterOps',
        'groupBy',
        'sortField',
        'sortDirection',
        'visibleColumns',
    ];

    public function mount(): void
    {
        if ($this->isPlanQueue()) {
            $this->layout = 'cards';
            $this->sortField = 'due_date';
            $this->sortDirection = 'asc';
            $this->view = '';
            $this->activeViewId = null;
            $this->visibleColumns = ['name'];
            $this->groupBy = '';
            $this->enforcePlanLocks();
            $this->resetPlanStatusSlice();
            $this->restoreGridChromeFromCookies();
            $this->hideGroupedColumn();
            $this->persistGridChrome();

            return;
        }

        $cookieLayout = request()->cookie('tg_layout');
        if ($cookieLayout === 'cards' || $cookieLayout === 'table') {
            $this->layout = $cookieLayout;
        } elseif ($this->requestLooksLikeMobile()) {
            $this->layout = 'cards';
        }

        if ($this->isLockedToSprint()) {
            $this->status = 'all';
            $this->selectedStatuses = $this->allStatusValues();
            $this->sortField = 'sprint_position';
            $this->sortDirection = 'asc';
            $this->groupBy = '';
            $this->view = '';
            $this->activeViewId = null;
            $this->newTaskSprint = (string) $this->lockedSprintId;
            $this->visibleColumns = array_values(array_filter(
                $this->visibleColumns,
                fn ($col) => $col !== 'sprint' && $col !== 'type'
            ));
            $this->hideGroupedColumn();

            return;
        }

        if ($this->view !== '' && $this->gridViewsTableExists()) {
            $this->loadViewFromSlug($this->view, flash: false);
        } else {
            $this->restoreGridChromeFromCookies();
        }

        $this->hideGroupedColumn();
        $this->persistGridChrome();
    }

    public function setLayout(string $layout): void
    {
        if ($this->isPlanQueue()) {
            $this->layout = 'cards';
            $this->skipRender();

            return;
        }

        if ($layout !== 'table' && $layout !== 'cards') {
            $this->skipRender();

            return;
        }

        if ($this->layout === $layout) {
            $this->skipRender();

            return;
        }

        $this->layout = $layout;
    }

    protected function requestLooksLikeMobile(): bool
    {
        if (request()->header('Sec-CH-UA-Mobile') === '?1') {
            return true;
        }

        $ua = strtolower((string) request()->userAgent());

        return $ua !== '' && preg_match('/mobile|android|iphone|ipad|ipod/', $ua) === 1;
    }

    public function isLockedToSprint(): bool
    {
        return (int) $this->lockedSprintId > 0;
    }

    public function isPlanQueue(): bool
    {
        return $this->planQueue && (int) $this->planUserId > 0;
    }

    #[On('plan-queue-refresh')]
    public function refreshPlanQueueListing(): void
    {
        if (! $this->isPlanQueue()) {
            return;
        }

        $this->resetPage();
    }

    /**
     * @return list<int>
     */
    public function normalizedSelectedIds(): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', $this->selectedIds),
            fn (int $id) => $id > 0
        )));
    }

    public function isSelected(int $id): bool
    {
        return in_array($id, $this->normalizedSelectedIds(), true);
    }

    public function rowSelectable(object $record): bool
    {
        if (! $this->isPlanQueue()) {
            return true;
        }

        return ! ($record instanceof WorkItem && $record->isMeetingItem());
    }

    public function toggleSelected(int $id): void
    {
        if ($id < 1) {
            return;
        }

        if ($this->isPlanQueue()) {
            $item = WorkItem::query()->find($id);
            if (! $item || $item->isMeetingItem()) {
                return;
            }
        }

        $ids = $this->normalizedSelectedIds();
        if (in_array($id, $ids, true)) {
            $this->selectedIds = array_values(array_filter($ids, fn (int $keep) => $keep !== $id));
            $this->skipSelectionRender();

            return;
        }

        $ids[] = $id;
        $this->selectedIds = $ids;
        $this->skipSelectionRender();
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->bulkField = '';
        $this->bulkValue = '';
        $this->skipSelectionRender();
    }

    #[On('plan-queue-forget-selected')]
    public function forgetSelected(array $ids = []): void
    {
        if (! $this->isPlanQueue()) {
            return;
        }

        $drop = array_map('intval', $ids);
        $this->selectedIds = array_values(array_diff($this->normalizedSelectedIds(), $drop));
    }

    public function pageIsFullySelected(): bool
    {
        $visible = array_values(array_filter(array_map('intval', $this->listedIds), fn (int $id) => $id > 0));
        if ($visible === []) {
            return false;
        }

        $selected = $this->normalizedSelectedIds();

        return collect($visible)->every(fn (int $id) => in_array($id, $selected, true));
    }

    public function toggleSelectVisible(): void
    {
        $visible = array_values(array_filter(array_map('intval', $this->listedIds), fn (int $id) => $id > 0));
        $selected = $this->normalizedSelectedIds();
        if ($visible !== [] && collect($visible)->every(fn (int $id) => in_array($id, $selected, true))) {
            $this->selectedIds = array_values(array_diff($selected, $visible));
            $this->skipSelectionRender();

            return;
        }

        $this->selectedIds = array_values(array_unique(array_merge($selected, $visible)));
        $this->skipSelectionRender();
    }

    protected function skipSelectionRender(): void
    {
        if ($this->isPlanQueue()) {
            return;
        }

        $this->skipRender();
        $this->dispatch('tg-selection-changed',
            ids: $this->normalizedSelectedIds(),
            count: count($this->normalizedSelectedIds()),
            allVisible: $this->pageIsFullySelected(),
        );
    }

    /**
     * @return array<string, string>
     */
    public function bulkWritableFields(): array
    {
        $fields = [
            'assigned_to' => 'Przypisany',
            'category' => 'Kategoria',
            'status' => 'Status',
        ];

        if (! $this->isLockedToSprint()) {
            $fields['sprint'] = 'Sprint';
        }

        $fields['priority'] = 'Priorytet';
        $fields['due_date'] = 'Do kiedy';

        return $fields;
    }

    public function updatedBulkField(): void
    {
        $this->bulkValue = '';
    }

    public function bulkApply(): void
    {
        if ($this->isPlanQueue()) {
            return;
        }

        $fieldKey = $this->bulkField;
        if (! array_key_exists($fieldKey, $this->bulkWritableFields())) {
            return;
        }

        $field = GridField::tryFrom($fieldKey);
        if (! $field) {
            return;
        }

        if ($fieldKey === 'status' && trim($this->bulkValue) === '') {
            return;
        }

        $ids = $this->normalizedSelectedIds();
        if ($ids === []) {
            return;
        }

        $updated = 0;
        foreach ($this->selectedRecords($ids) as $record) {
            if (! $this->rowWritable($record, $fieldKey)) {
                continue;
            }

            $this->applyBulkValueToRecord($record, $field, $this->bulkValue);
            $updated++;
        }

        if ($updated > 0) {
            $label = $this->bulkWritableFields()[$fieldKey];
            $this->flash = $updated === 1
                ? 'Zmieniono: '.$label.' · 1 zadanie.'
                : 'Zmieniono: '.$label.' · '.$updated.' zadań.';
            $this->invalidateViewCounts();
        }
    }

    protected function applyBulkValueToRecord(WorkItem|ProjectTask $record, GridField $field, string $value): void
    {
        if ($record instanceof WorkItem) {
            $record->handler()->write($record, $field, $value);

            return;
        }

        app(ProjectTaskFields::class)->write($record, $field, $value);
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, WorkItem|ProjectTask>
     */
    protected function selectedRecords(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        if ($this->usesWorkItems() || $this->isPlanQueue()) {
            return WorkItem::query()
                ->with('source')
                ->whereIn('id', $ids)
                ->get();
        }

        return ProjectTask::query()->whereIn('id', $ids)->get();
    }

    protected function enforcePlanLocks(): void
    {
        if (! $this->isPlanQueue()) {
            return;
        }

        $this->layout = 'cards';
        $userId = (string) (int) $this->planUserId;
        $this->assignedFilter = $userId;
        $this->assignedFilters = [$userId];
        $this->filterOps['assignedFilter'] = 'eq';
    }

    protected function resetPlanStatusSlice(): void
    {
        $this->status = '';
        $this->selectedStatuses = $this->defaultStatuses();
        $this->filterOps['status'] = 'eq';
    }

    public function usesWorkItems(): bool
    {
        return ! $this->isLockedToSprint() && $this->workItemsTableExists();
    }

    protected function workItemsTableExists(): bool
    {
        static $exists = null;

        return $exists ??= Schema::hasTable('work_items');
    }

    /** @return list<string> */
    public function allWorkItemTypeValues(): array
    {
        return array_map(fn ($case) => $case->value, WorkItemType::cases());
    }

    /** @return list<string> */
    protected function defaultSelectedTypes(): array
    {
        return array_values(array_filter(
            $this->allWorkItemTypeValues(),
            fn ($value) => $value !== WorkItemType::Callback->value
        ));
    }

    /** Checkbox „Typ pracy” w panelu filtrów — dodaje/usuwa typ z zaznaczenia. */
    public function toggleType(string $type): void
    {
        if (! in_array($type, $this->allWorkItemTypeValues(), true)) {
            return;
        }

        if (in_array($type, $this->selectedTypes, true)) {
            $this->selectedTypes = array_values(array_diff($this->selectedTypes, [$type]));
        } else {
            $this->selectedTypes[] = $type;
        }

        $this->resetPage();
        $this->detachActiveView();
    }

    /** @return list<string> */
    public function allStatusValues(): array
    {
        return TaskStatus::values();
    }

    /** @return list<string> */
    public function defaultStatuses(): array
    {
        return [TaskStatus::PENDING->value, TaskStatus::IN_PROGRESS->value];
    }

    /** @return list<string> */
    public function closedStatuses(): array
    {
        return [TaskStatus::COMPLETED->value, TaskStatus::CANCELLED->value];
    }

    public function currentStatusBucket(): string
    {
        return $this->statusBucketFromSelection($this->selectedStatuses);
    }

    public function setStatusBucket(string $bucket): void
    {
        $bucket = $bucket === 'active' ? '' : $bucket;
        if (! in_array($bucket, ['', 'closed', 'all'], true) && TaskStatus::tryFrom($bucket) === null) {
            return;
        }

        $this->status = $bucket;
        $this->selectedStatuses = $this->statusesFromBucket($bucket);
        $this->resetPage();
        $this->detachActiveView();
    }

    public function toggleStatusValue(string $value): void
    {
        if (! in_array($value, $this->allStatusValues(), true)) {
            return;
        }

        if (in_array($value, $this->selectedStatuses, true)) {
            $this->selectedStatuses = array_values(array_diff($this->selectedStatuses, [$value]));
        } else {
            $this->selectedStatuses[] = $value;
        }

        $this->selectedStatuses = $this->normalizeStatusSelection($this->selectedStatuses);
        $bucket = $this->statusBucketFromSelection($this->selectedStatuses);
        if ($bucket !== 'mixed' && $bucket !== 'none') {
            $this->status = $bucket;
        }

        $this->resetPage();
        $this->detachActiveView();
    }

    public function updatedStatus(mixed $value): void
    {
        $value = (string) $value;
        if ($value === 'mixed' || $value === 'none') {
            return;
        }

        $this->selectedStatuses = $this->statusesFromBucket($value);
    }

    public function updatedAssignedFilter(mixed $value): void
    {
        $this->assignedFilters = ($value === '' || $value === null)
            ? []
            : $this->normalizeUserFilterKeys([(string) $value]);
    }

    public function updatedCreatedByFilter(mixed $value): void
    {
        $this->createdByFilters = ($value === '' || $value === null)
            ? []
            : $this->normalizeUserFilterKeys([(string) $value]);
    }

    public function toggleAssignedFilter(string $key): void
    {
        if ($this->isPlanQueue()) {
            $this->enforcePlanLocks();

            return;
        }

        $this->assignedFilters = $this->toggleUserFilterKey($this->assignedFilters, $key);
        if (count($this->assignedFilters) <= 1) {
            $this->assignedFilter = $this->assignedFilters[0] ?? '';
        }
        $this->resetPage();
        $this->detachActiveView();
    }

    public function toggleCreatedByFilter(string $key): void
    {
        $this->createdByFilters = $this->toggleUserFilterKey($this->createdByFilters, $key);
        if (count($this->createdByFilters) <= 1) {
            $this->createdByFilter = $this->createdByFilters[0] ?? '';
        }
        $this->resetPage();
        $this->detachActiveView();
    }

    public function clearAssignedFilters(): void
    {
        if ($this->isPlanQueue()) {
            $this->enforcePlanLocks();

            return;
        }

        $this->assignedFilters = [];
        $this->assignedFilter = '';
        $this->filterOps['assignedFilter'] = 'eq';
        $this->resetPage();
        $this->detachActiveView();
    }

    public function clearCreatedByFilters(): void
    {
        $this->createdByFilters = [];
        $this->createdByFilter = '';
        $this->filterOps['createdByFilter'] = 'eq';
        $this->resetPage();
        $this->detachActiveView();
    }

    public function itemOpenUrl(ProjectTask|WorkItem $task): string
    {
        if ($task instanceof WorkItem) {
            return WorkItemListNavigator::itemUrl($task);
        }

        return route('tasks.show', $task);
    }

    /**
     * @param  Paginator<WorkItem|ProjectTask>|Collection<int, WorkItem|ProjectTask>|null  $tasks
     * @param  Collection<string, Collection<int, WorkItem|ProjectTask>>|null  $groupedTasks
     */
    protected function rememberWorkItemList(mixed $tasks, mixed $groupedTasks): void
    {
        if ($this->isPlanQueue() || ! $this->usesWorkItems()) {
            if ($this->isPlanQueue()) {
                return;
            }
            WorkItemListNavigator::forget();

            return;
        }

        $fingerprint = md5((string) json_encode($this->currentQueryParams()));
        if (WorkItemListNavigator::isCurrent($fingerprint)) {
            return;
        }

        if ($groupedTasks instanceof Collection) {
            WorkItemListNavigator::rememberWithFingerprint(
                $fingerprint,
                $groupedTasks->flatten(1)->pluck('id')->map(fn ($id) => (int) $id)->take(500)->all()
            );

            return;
        }

        if ($tasks instanceof Paginator) {
            $idQuery = $this->filteredTasksQuery();
            $this->applyWorkItemSorting($idQuery);
            WorkItemListNavigator::rememberWithFingerprint(
                $fingerprint,
                $idQuery->limit(500)->pluck('work_items.id')->map(fn ($id) => (int) $id)->all()
            );

            return;
        }

        if ($tasks instanceof Collection) {
            WorkItemListNavigator::rememberWithFingerprint(
                $fingerprint,
                $tasks->pluck('id')->map(fn ($id) => (int) $id)->take(500)->all()
            );

            return;
        }

        WorkItemListNavigator::forget();
    }

    /** @return array<string, string> */
    public function defaultFilterOps(): array
    {
        return [
            'status' => 'eq',
            'assignedFilter' => 'eq',
            'createdByFilter' => 'eq',
            'selectedTypes' => 'eq',
            'searchTask' => 'eq',
            'searchCategory' => 'eq',
            'searchAssignedTo' => 'eq',
            'filterPriority' => 'eq',
            'filterSprint' => 'eq',
            'filterDueDate' => 'eq',
        ];
    }

    public function filterOp(string $key): string
    {
        return ($this->filterOps[$key] ?? 'eq') === 'neq' ? 'neq' : 'eq';
    }

    public function setFilterJoin(string $join): void
    {
        $this->filterJoin = $join === 'or' ? 'or' : 'and';
        $this->resetPage();
        $this->detachActiveView();
    }

    public function setFilterOp(string $key, string $op): void
    {
        if (! array_key_exists($key, $this->defaultFilterOps())) {
            return;
        }

        $this->filterOps[$key] = $op === 'neq' ? 'neq' : 'eq';
        $this->resetPage();
        $this->detachActiveView();
    }

    public function getAvailableColumnsProperty(): array
    {
        return [
            'name' => ['label' => 'Nazwa', 'sortable' => true, 'always' => true],
            'type' => ['label' => 'Typ pracy', 'sortable' => true],
            'status' => ['label' => 'Status', 'sortable' => true],
            'sprint' => ['label' => 'Sprint', 'sortable' => true],
            'category' => ['label' => 'Kategoria', 'sortable' => true],
            'assigned_to' => ['label' => 'Przypisany', 'sortable' => false],
            'created_by' => ['label' => 'Utworzono przez', 'sortable' => false],
            'priority' => ['label' => 'Priorytet', 'sortable' => true],
            'due_date' => ['label' => 'Do kiedy', 'sortable' => true],
            'blocks' => ['label' => 'W kalendarzu', 'sortable' => false],
            'subtasks' => ['label' => 'Podzadania', 'sortable' => false],
            'comments' => ['label' => 'Komentarze', 'sortable' => false],
            'created_at' => ['label' => 'Utworzono', 'sortable' => true],
            'updated_at' => ['label' => 'Zmodyfikowano', 'sortable' => true],
        ];
    }

    public function updating(string $name, mixed $value): void
    {
        if (in_array($name, ['searchTask', 'searchCategory', 'searchAssignedTo', 'filterPriority', 'filterDueDate', 'filterSprint', 'status', 'selectedStatuses', 'assignedFilter', 'assignedFilters', 'createdByFilter', 'createdByFilters', 'selectedTypes', 'filterJoin', 'filterOps'], true)) {
            $this->resetPage();
        }
    }

    public function updatedFilterDueDate(): void
    {
        $this->filterOps['filterDueDate'] = 'eq';
        $this->detachActiveView();
    }

    public function updated(string $property): void
    {
        if ($this->batchingViewPersist || ! in_array($property, $this->viewDetachingProperties, true)) {
            return;
        }

        $this->detachActiveView();
    }

    /**
     * „Wyczyść” w panelu filtrów: w przeciwieństwie do domyślnego stanu po
     * wejściu na widok (status aktywne, bez oddzwonień — sensowny punkt
     * startowy), to ma naprawdę pokazać WSZYSTKO, bez żadnego ukrytego
     * zawężenia — inaczej user klika „Wyczyść” i dalej nie widzi połowy zadań.
     */
    public function clearFilters(): void
    {
        $this->batchingViewPersist = true;
        $this->searchTask = '';
        $this->searchCategory = '';
        $this->searchAssignedTo = '';
        $this->filterPriority = '';
        $this->filterDueDate = '';
        $this->filterSprint = '';
        $this->status = 'all';
        $this->selectedStatuses = $this->allStatusValues();
        $this->assignedFilter = '';
        $this->assignedFilters = [];
        $this->createdByFilter = '';
        $this->createdByFilters = [];
        $this->selectedTypes = $this->allWorkItemTypeValues();
        $this->filterJoin = 'and';
        $this->filterOps = $this->defaultFilterOps();
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
        $previousGroup = $this->groupBy;
        $this->groupBy = '';
        $this->syncColumnsAfterGroupChange($previousGroup);
        if ($this->isLockedToSprint()) {
            $this->sortField = 'sprint_position';
            $this->sortDirection = 'asc';
        }
        if ($this->isPlanQueue()) {
            $this->sortField = 'due_date';
            $this->sortDirection = 'asc';
            $this->enforcePlanLocks();
            $this->resetPlanStatusSlice();
        }
        $this->batchingViewPersist = false;
        $this->resetPage();
        $this->detachActiveView();
        $this->persistGridChrome();
    }

    /**
     * @return list<array{key: string, label: string, locked?: bool}>
     */
    public function activeFilterChips(?string $activeViewName = null): array
    {
        $chips = [];

        if ($this->isPlanQueue()) {
            $chips[] = ['key' => 'planQueue', 'label' => 'Do przypięcia', 'locked' => true];
        }

        if ($this->view !== '') {
            $viewName = $activeViewName
                ?? ($this->activeViewId
                    ? (TaskGridView::query()->visibleTo(auth()->user())->whereKey($this->activeViewId)->value('name') ?? $this->view)
                    : (TaskGridView::findVisibleTo(auth()->user(), $this->view)?->name ?? $this->view));
            $chips[] = ['key' => 'view', 'label' => 'Widok: '.$viewName];
        }

        if ($this->searchTask !== '') {
            $neg = $this->filterOp('searchTask') === 'neq';
            $chips[] = ['key' => 'searchTask', 'label' => ($neg ? 'Szukaj ≠ ' : 'Szukaj: ').$this->searchTask];
        }

        if ($this->searchCategory !== '') {
            $neg = $this->filterOp('searchCategory') === 'neq';
            $terms = $neg ? $this->splitPinnedValues($this->searchCategory) : [$this->searchCategory];
            $labels = array_map(fn (string $term) => $this->categoryChipLabel($term), $terms);
            $chips[] = [
                'key' => 'searchCategory',
                'label' => $neg
                    ? 'Kategoria '.$this->neqChipLabel($labels)
                    : 'Kategoria: '.$labels[0],
            ];
        }

        if ($this->searchAssignedTo !== '') {
            $neg = $this->filterOp('searchAssignedTo') === 'neq';
            $chips[] = ['key' => 'searchAssignedTo', 'label' => ($neg ? 'Osoba ≠ ' : 'Osoba: ').$this->searchAssignedTo];
        }

        if ($this->filterPriority !== '') {
            $neg = $this->filterOp('filterPriority') === 'neq';
            $terms = $neg ? $this->splitPinnedValues($this->filterPriority) : [$this->filterPriority];
            $labels = array_map(fn (string $term) => $this->priorityChipLabel($term), $terms);
            $chips[] = [
                'key' => 'filterPriority',
                'label' => $neg
                    ? 'Priorytet '.$this->neqChipLabel($labels)
                    : 'Priorytet: '.$labels[0],
            ];
        }

        if ($this->filterDueDate !== '') {
            $chips[] = ['key' => 'filterDueDate', 'label' => $this->dueDateFilterChipLabel()];
        }

        if ($this->filterSprint !== '') {
            $neg = $this->filterOp('filterSprint') === 'neq';
            $terms = $neg ? $this->splitPinnedValues($this->filterSprint) : [$this->filterSprint];
            $labels = array_map(fn (string $term) => $this->sprintChipLabel($term), $terms);
            $chips[] = [
                'key' => 'filterSprint',
                'label' => $neg
                    ? 'Sprint '.$this->neqChipLabel($labels)
                    : 'Sprint: '.$labels[0],
            ];
        }

        // "all" to jedyna wartość statusu, która niczego nie odfiltrowuje —
        // chip pokazujemy dla każdej innej wartości, ŁĄCZNIE z domyślnym ""
        // (aktywne), bo to i tak realnie ukrywa zamknięte/anulowane zadania.
        // Wcześniej domyślne "" było traktowane jak "brak filtra" i chip się
        // nie pokazywał — stąd user widział np. 15 z 129 zadań bez żadnej
        // wskazówki, że coś jest odfiltrowane.
        if (! $this->selectsAllStatuses() || $this->filterOp('status') === 'neq') {
            $neq = $this->filterOp('status') === 'neq';
            if ($neq) {
                $statusLabel = match ($this->statusBucketFromSelection($this->selectedStatuses)) {
                    '' => $this->neqChipLabel(['Aktywne']),
                    'closed' => $this->neqChipLabel(['Zamknięte']),
                    'all' => $this->neqChipLabel(['Wszystkie']),
                    'none' => '≠ żaden (0 wyników)',
                    default => $this->neqChipLabel(array_map(
                        fn (string $value) => TaskStatus::from($value)->label(),
                        $this->normalizeStatusSelection($this->selectedStatuses)
                    )),
                };
            } else {
                $statusLabel = $this->statusChipLabel();
            }
            $chips[] = [
                'key' => 'status',
                'label' => 'Status: '.$statusLabel,
            ];
        }

        $assignedKeys = $this->assignedFilterKeys();
        if ($assignedKeys !== []) {
            $labels = $this->userFilterChipLabels($assignedKeys);
            $neq = $this->filterOp('assignedFilter') === 'neq';
            $chips[] = [
                'key' => 'assignedFilter',
                'label' => $neq
                    ? 'Przypisany: '.$this->neqChipLabel($labels)
                    : 'Przypisany: '.$this->userFilterChipLabel($assignedKeys),
                'locked' => $this->isPlanQueue(),
            ];
        }

        $createdKeys = $this->createdByFilterKeys();
        if ($createdKeys !== []) {
            $labels = $this->userFilterChipLabels($createdKeys);
            $neq = $this->filterOp('createdByFilter') === 'neq';
            $chips[] = [
                'key' => 'createdByFilter',
                'label' => $neq
                    ? 'Utworzono przez: '.$this->neqChipLabel($labels)
                    : 'Utworzono przez: '.$this->userFilterChipLabel($createdKeys),
            ];
        }

        if ($this->usesWorkItems()) {
            $allTypes = $this->allWorkItemTypeValues();
            $selected = $this->selectedTypes;
            $missing = array_values(array_diff($allTypes, $selected));
            $neg = $this->filterOp('selectedTypes') === 'neq';

            if ($neg) {
                if ($selected === []) {
                    // exclude nothing
                } elseif ($missing === []) {
                    $chips[] = ['key' => 'selectedTypes', 'label' => 'Typ pracy: ≠ wszystkie (0 wyników)'];
                } else {
                    $labels = array_map(fn ($v) => WorkItemType::from($v)->label(), $selected);
                    $chips[] = ['key' => 'selectedTypes', 'label' => 'Typ pracy: ≠ '.implode(', ', $labels)];
                }
            } elseif ($missing !== []) {
                if ($selected === []) {
                    $chips[] = ['key' => 'selectedTypes', 'label' => 'Typ pracy: żaden (0 wyników)'];
                } elseif (count($missing) <= count($selected)) {
                    $labels = array_map(fn ($v) => WorkItemType::from($v)->label(), $missing);
                    $chips[] = ['key' => 'selectedTypes', 'label' => 'Typ pracy: bez '.implode(', ', $labels)];
                } else {
                    $labels = array_map(fn ($v) => WorkItemType::from($v)->label(), $selected);
                    $chips[] = ['key' => 'selectedTypes', 'label' => 'Typ pracy: '.implode(', ', $labels)];
                }
            }
        }

        if ($this->groupBy !== '') {
            $groupLabel = $this->availableColumns[$this->groupBy]['label'] ?? $this->groupBy;
            $chips[] = ['key' => 'groupBy', 'label' => 'Grupuj: '.$groupLabel];
        }

        return $chips;
    }

    public function clearFilter(string $key): void
    {
        if ($this->isPlanQueue() && in_array($key, ['planQueue', 'assignedFilter'], true)) {
            $this->enforcePlanLocks();

            return;
        }

        if ($this->isPlanQueue() && $key === 'status') {
            $this->resetPlanStatusSlice();
            $this->resetPage();

            return;
        }

        if ($key === 'groupBy') {
            $this->setGroupBy('');

            return;
        }

        if ($key === 'view') {
            $this->clearView();

            return;
        }

        if ($key === 'filterJoin') {
            $this->filterJoin = 'and';
            $this->resetPage();
            $this->detachActiveView();

            return;
        }

        if ($key === 'status') {
            // Usunięcie chipa = "przestań zawężać", czyli pokaż wszystko —
            // nie wracaj do domyślnego "Aktywne", bo to by wyglądało jak nic
            // się nie zmieniło (patrz komentarz przy activeFilterChips()).
            $this->status = 'all';
            $this->selectedStatuses = $this->allStatusValues();
            $this->filterOps['status'] = 'eq';
            $this->resetPage();
            $this->detachActiveView();

            return;
        }

        if ($key === 'selectedTypes') {
            $this->selectedTypes = $this->allWorkItemTypeValues();
            $this->filterOps['selectedTypes'] = 'eq';
            $this->resetPage();
            $this->detachActiveView();

            return;
        }

        if ($key === 'assignedFilter') {
            $this->clearAssignedFilters();

            return;
        }

        if ($key === 'createdByFilter') {
            $this->clearCreatedByFilters();

            return;
        }

        if (in_array($key, ['searchTask', 'searchCategory', 'searchAssignedTo', 'filterPriority', 'filterDueDate', 'filterSprint'], true)) {
            $this->{$key} = '';
            if (isset($this->filterOps[$key])) {
                $this->filterOps[$key] = 'eq';
            }
            $this->resetPage();
            $this->detachActiveView();
        }
    }

    public function pinClick(string $key, string $value, string $op = 'eq'): ?string
    {
        if (! $this->canPinFilter($key)) {
            return null;
        }

        $encodedKey = $this->jsStr($key);
        $encodedValue = $this->jsStr($value);
        if ($op === 'neq') {
            return 'pinFilter('.$encodedKey.', '.$encodedValue.', \'neq\')';
        }

        return 'pinFilter('.$encodedKey.', '.$encodedValue.')';
    }

    public function pinFilter(string $key, string $value, string $op = 'eq'): void
    {
        if (! $this->canPinFilter($key)) {
            if ($this->isPlanQueue() && $key === 'assignedFilter') {
                $this->enforcePlanLocks();
            }

            return;
        }

        $op = $op === 'neq' ? 'neq' : 'eq';
        if (! $this->applyPinValue($key, $value, $op)) {
            return;
        }

        if (array_key_exists($key, $this->defaultFilterOps())) {
            $this->filterOps[$key] = $op;
        }

        $this->resetPage();
        $this->detachActiveView();
        $this->flashFilterChip($key);
    }

    public function filterByCategory(string $category): void
    {
        $this->pinFilter('searchCategory', $category, 'eq');
    }

    public function filterByStatus(string $status): void
    {
        $this->pinFilter('status', $status, 'eq');
    }

    public function filterByAssignee(string $key): void
    {
        $this->pinFilter('assignedFilter', $key, 'eq');
    }

    public function filterByPriority(string $priority): void
    {
        $this->pinFilter('filterPriority', $priority, 'eq');
    }

    public function filterBySprint(string $sprint): void
    {
        $this->pinFilter('filterSprint', $sprint, 'eq');
    }

    public function filterByDueDate(string $date): void
    {
        $this->pinFilter('filterDueDate', $date, 'eq');
    }

    protected function flashFilterChip(string $key): void
    {
        $this->dispatch('tg-filter-flash', key: $key);
    }

    protected function canPinFilter(string $key): bool
    {
        if ($this->isPlanQueue() && $key === 'assignedFilter') {
            return false;
        }

        if ($this->isLockedToSprint() && $key === 'filterSprint') {
            return false;
        }

        return true;
    }

    protected function applyPinValue(string $key, string $value, string $op = 'eq'): bool
    {
        return match ($key) {
            'searchCategory' => $this->applyCategoryPin($value, $op),
            'status' => $this->applyStatusPin($value, $op),
            'assignedFilter' => $this->applyAssigneePin($value, $op),
            'filterPriority' => $this->applyPriorityPin($value, $op),
            'filterSprint' => $this->applySprintPin($value, $op),
            'filterDueDate' => $this->applyDueDatePin($value),
            default => false,
        };
    }

    protected function applyCategoryPin(string $category, string $op = 'eq'): bool
    {
        $category = trim($category);
        if ($category === '') {
            return false;
        }

        $category = $category === '__none__' ? '__none__' : mb_substr($category, 0, 255);
        if ($this->shouldAppendPin('searchCategory', $op)) {
            $this->searchCategory = $this->appendPinnedValue($this->searchCategory, $category);

            return true;
        }

        $this->searchCategory = $category;

        return true;
    }

    protected function applyStatusPin(string $status, string $op = 'eq'): bool
    {
        if (! in_array($status, $this->allStatusValues(), true)) {
            return false;
        }

        if ($this->shouldAppendPin('status', $op)) {
            if (! in_array($status, $this->selectedStatuses, true)) {
                $this->selectedStatuses[] = $status;
                $this->selectedStatuses = $this->normalizeStatusSelection($this->selectedStatuses);
            }
            $this->status = $this->statusBucketFromSelection($this->selectedStatuses);

            return true;
        }

        $this->selectedStatuses = [$status];
        $this->status = $this->statusBucketFromSelection($this->selectedStatuses);

        return true;
    }

    protected function applyAssigneePin(string $key, string $op = 'eq'): bool
    {
        $key = (string) $key;
        if ($key !== 'unassigned' && $key !== 'me' && ! ctype_digit($key)) {
            return false;
        }

        if ($this->shouldAppendPin('assignedFilter', $op)) {
            $keys = $this->assignedFilterKeys();
            if (! in_array($key, $keys, true)) {
                $keys[] = $key;
            }
            $this->assignedFilters = $this->normalizeUserFilterKeys($keys);
            $this->assignedFilter = count($this->assignedFilters) === 1
                ? $this->assignedFilters[0]
                : '';

            return true;
        }

        $this->assignedFilters = [$key];
        $this->assignedFilter = $key;

        return true;
    }

    protected function applyPriorityPin(string $priority, string $op = 'eq'): bool
    {
        if (! in_array($priority, ['1', '2', '3', '4', '5', 'none'], true)) {
            return false;
        }

        if ($this->shouldAppendPin('filterPriority', $op)) {
            $this->filterPriority = $this->appendPinnedValue($this->filterPriority, $priority);

            return true;
        }

        $this->filterPriority = $priority;

        return true;
    }

    protected function applySprintPin(string $sprint, string $op = 'eq'): bool
    {
        if ($sprint !== 'none' && ! ctype_digit($sprint)) {
            return false;
        }

        if ($this->shouldAppendPin('filterSprint', $op)) {
            $this->filterSprint = $this->appendPinnedValue($this->filterSprint, $sprint);

            return true;
        }

        $this->filterSprint = $sprint;

        return true;
    }

    protected function applyDueDatePin(string $date): bool
    {
        if ($date !== 'none' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        $this->filterDueDate = $date;

        return true;
    }

    protected function jsStr(string $value): string
    {
        return "'".str_replace(['\\', "'"], ['\\\\', "\\'"], $value)."'";
    }

    protected function shouldAppendPin(string $key, string $op): bool
    {
        return $op === 'neq' && $this->filterOp($key) === 'neq';
    }

    /**
     * @return list<string>
     */
    protected function splitPinnedValues(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        $parts = [];
        foreach (explode('|', $raw) as $part) {
            $part = trim($part);
            if ($part !== '' && ! in_array($part, $parts, true)) {
                $parts[] = $part;
            }
        }

        return $parts;
    }

    /**
     * @param  list<string>  $values
     */
    protected function joinPinnedValues(array $values): string
    {
        return implode('|', $values);
    }

    protected function appendPinnedValue(string $current, string $value): string
    {
        $parts = $this->splitPinnedValues($current);
        if (! in_array($value, $parts, true)) {
            $parts[] = $value;
        }

        return $this->joinPinnedValues($parts);
    }

    /**
     * @param  list<string>  $labels
     */
    protected function neqChipLabel(array $labels): string
    {
        if ($labels === []) {
            return '≠';
        }

        return '≠ '.implode(', ≠ ', $labels);
    }

    protected function categoryChipLabel(string $category): string
    {
        return $category === '__none__' ? 'Brak' : $category;
    }

    protected function sprintChipLabel(string $sprint): string
    {
        if ($sprint === 'none') {
            return 'Poza sprintem';
        }

        if (! ctype_digit($sprint)) {
            return $sprint;
        }

        return Sprint::query()->whereKey((int) $sprint)->value('name') ?: $sprint;
    }

    protected function priorityChipLabel(string $priority): string
    {
        return match ($priority) {
            '1' => 'Najniższy',
            '2' => 'Niski',
            '3' => 'Średni',
            '4' => 'Wysoki',
            '5' => 'Krytyczny',
            'none' => 'Brak',
            default => $priority,
        };
    }

    protected function dueDateChipLabel(string $date): string
    {
        if ($date === 'none') {
            return 'Brak';
        }

        try {
            return \Carbon\Carbon::createFromFormat('Y-m-d', $date)->format('d.m.Y');
        } catch (\Throwable) {
            return $date;
        }
    }

    protected function dueDateFilterChipLabel(): string
    {
        $label = $this->dueDateChipLabel($this->filterDueDate);
        $neq = $this->filterOp('filterDueDate') === 'neq';

        if ($this->filterDueDate === 'none') {
            return ($neq ? 'Do kiedy ≠ ' : 'Do kiedy: ').$label;
        }

        return $neq ? 'Do kiedy: po '.$label : 'Do kiedy: do '.$label;
    }

    public function sortBy(string $field): void
    {
        $this->sortColumn($field, $this->sortField === $field && $this->sortDirection === 'asc' ? 'desc' : 'asc');
    }

    public function sortColumn(string $field, string $direction): void
    {
        if (! ($this->availableColumns[$field]['sortable'] ?? false)) {
            return;
        }

        $this->sortField = $field;
        $this->sortDirection = $direction === 'desc' ? 'desc' : 'asc';
        $this->resetPage();
        $this->detachActiveView();
    }

    public function visibleColumnCount(): int
    {
        return count($this->visibleColumns);
    }

    /**
     * Kolejność w pickerze = kolejność na liście (przeciąganie / zapisany widok),
     * potem wyłączone kolumny z katalogu.
     *
     * @return list<string>
     */
    public function columnPickerKeys(): array
    {
        $available = array_keys($this->availableColumns);
        $visible = array_values(array_filter(
            $this->visibleColumns,
            fn (string $key) => in_array($key, $available, true)
        ));
        $rest = array_values(array_diff($available, $visible));

        return array_merge($visible, $rest);
    }

    /**
     * Chip keys that belong to a grid column — same Livewire state as the
     * toolbar Filtry panel, so both UIs stay in sync.
     *
     * @return list<string>
     */
    public function columnFilterChipKeys(string $colKey): array
    {
        return match ($colKey) {
            'name' => ['searchTask'],
            'category' => ['searchCategory'],
            'type' => ['selectedTypes'],
            'status' => ['status'],
            'assigned_to' => ['assignedFilter', 'searchAssignedTo'],
            'created_by' => ['createdByFilter'],
            'priority' => ['filterPriority'],
            'due_date' => ['filterDueDate'],
            'sprint' => ['filterSprint'],
            default => [],
        };
    }

    public function columnIsFilterable(string $colKey): bool
    {
        if ($this->columnFilterChipKeys($colKey) === []) {
            return false;
        }

        if ($colKey === 'type' && ! $this->usesWorkItems()) {
            return false;
        }

        return true;
    }

    public function columnHasActiveFilter(string $colKey): bool
    {
        $keys = $this->columnFilterChipKeys($colKey);
        if ($keys === []) {
            return false;
        }

        $active = array_column($this->activeFilterChips(), 'key');

        return array_intersect($keys, $active) !== [];
    }

    public function clearColumnFilter(string $colKey): void
    {
        foreach ($this->columnFilterChipKeys($colKey) as $key) {
            $this->clearFilter($key);
        }
    }

    public function setGroupBy(string $field): void
    {
        if ($this->isLockedToSprint() && $field === 'sprint') {
            return;
        }

        if ($field === 'type' && ! $this->usesWorkItems()) {
            return;
        }

        $previous = $this->groupBy;
        $this->groupBy = ($field === '' || $previous === $field) ? '' : $field;
        $this->syncColumnsAfterGroupChange($previous);
        $this->collapsedGroups = [];
        $this->resetPage();
        $this->detachActiveView();
        $this->persistGridChrome();
    }

    public function updatingGroupBy(mixed $value): void
    {
        $this->groupByBeforeUpdate = $this->groupBy;
    }

    public function updatedGroupBy(mixed $value): void
    {
        if ($this->batchingViewPersist) {
            $this->hideGroupedColumn();

            return;
        }

        $this->syncColumnsAfterGroupChange($this->groupByBeforeUpdate ?? '');
        $this->groupByBeforeUpdate = null;
        $this->collapsedGroups = [];
        $this->resetPage();
        if (! $this->batchingViewPersist) {
            $this->persistGridChrome();
        }
    }

    public function toggleGroupCollapse(string $groupKey): void
    {
        $groupKey = $this->groupCollapseKey($groupKey);

        if (in_array($groupKey, $this->collapsedGroups, true)) {
            $this->collapsedGroups = array_values(array_filter(
                $this->collapsedGroups,
                fn ($key) => $key !== $groupKey
            ));
        } else {
            $this->collapsedGroups[] = $groupKey;
        }
    }

    public function isGroupCollapsed(string $groupName): bool
    {
        return in_array($this->groupCollapseKey($groupName), $this->collapsedGroups, true);
    }

    public function groupCollapseKey(string $groupName): string
    {
        if (preg_match('/^[a-f0-9]{32}$/', $groupName) === 1) {
            return $groupName;
        }

        return md5($groupName);
    }

    public function toggleColumn(string $key): void
    {
        $cols = $this->availableColumns;
        if ($cols[$key]['always'] ?? false) {
            return;
        }

        if ($key !== '' && $key === $this->groupBy) {
            return;
        }

        if ($this->isLockedToSprint() && $key === 'sprint') {
            return;
        }

        if (! $this->usesWorkItems() && $key === 'type') {
            return;
        }

        if (in_array($key, $this->visibleColumns)) {
            $this->visibleColumns = array_values(array_filter($this->visibleColumns, fn ($c) => $c !== $key));
        } else {
            $this->visibleColumns[] = $key;
        }

        $this->detachActiveView();
        $this->persistGridChrome();
    }

    #[Renderless]
    public function toggleExpand(int $taskId, bool $clientManaged = false, bool $needsHtml = false, ?bool $wantOpen = null): mixed
    {
        if (! $this->rowExpandable($taskId)) {
            $this->setTaskExpanded($taskId, false);

            return $this->pushExpandFragment($taskId, false);
        }

        $open = $wantOpen ?? ! $this->taskIsExpanded($taskId);
        $this->setTaskExpanded($taskId, $open);

        if (! $open) {
            return $this->pushExpandFragment($taskId, false);
        }

        $withHtml = $needsHtml || ! $clientManaged;

        return $this->pushExpandFragment($taskId, true, $withHtml);
    }

    protected function taskIsExpanded(int $taskId): bool
    {
        return in_array($taskId, array_map('intval', $this->expandedTasks), true);
    }

    protected function setTaskExpanded(int $taskId, bool $open): void
    {
        if ($open && ! $this->taskIsExpanded($taskId)) {
            $this->expandedTasks[] = $taskId;

            return;
        }

        if (! $open) {
            $this->expandedTasks = array_values(array_filter(
                $this->expandedTasks,
                fn ($id) => (int) $id !== $taskId
            ));
        }
    }

    /**
     * Dokleja / chowa tylko panel rozwinięcia — bez renderu całej siatki.
     */
    protected function pushExpandFragment(int $rowId, bool $open, bool $withHtml = true): ?string
    {
        $html = '';
        $subDone = null;
        $subTotal = null;

        if ($open && $withHtml) {
            [$html, $subDone, $subTotal] = $this->expandPanelPayload($rowId);
        }

        $payload = [
            'id' => $rowId,
            'open' => $open,
            'html' => $html,
        ];
        if ($subDone !== null) {
            $payload['subDone'] = $subDone;
            $payload['subTotal'] = $subTotal;
        }

        $this->js('window.tgApplyExpand && window.tgApplyExpand('.Js::from($payload).')');

        return $html !== '' ? $html : null;
    }

    /**
     * @return array{0: string, 1: int, 2: int}
     */
    protected function expandPanelPayload(int $taskId): array
    {
        $task = $this->usesWorkItems()
            ? $this->resolveWorkItem($taskId)
            : ProjectTask::query()->find($taskId);

        if (! $task) {
            return ['', 0, 0];
        }

        $src = $task instanceof WorkItem
            ? ($task->source instanceof ProjectTask ? $task->source : null)
            : $task;
        if ($src) {
            $src->loadMissing('subtasks');
        }

        [$subtasksAll, $subtaskTotal, $subtaskDone] = $this->rowSubtaskStats($task, true);

        $borderColor = [
            'pending' => '#f59e0b',
            'in_progress' => '#a855f7',
            'completed' => '#10b981',
            'cancelled' => '#ef4444',
        ][$task->status->value] ?? 'rgba(255,255,255,0.1)';

        $viewName = $this->layout === 'cards'
            ? 'livewire.partials.tasks-grid-expand-card'
            : 'livewire.partials.tasks-grid-expand-row';

        $extend = app(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::class);
        $extend->startLivewireRendering($this);
        $revertLivewire = \Livewire\Drawer\Utils::shareWithViews('__livewire', $this);
        $revertInstance = \Livewire\Drawer\Utils::shareWithViews('_instance', $this);

        try {
            $html = view($viewName, [
                'task' => $task,
                'canAddSubtask' => $this->rowSupports($task, 'subtasks'),
                'isEditing' => $this->editingTaskId === $task->id,
                'editingField' => $this->editingField,
                'borderColor' => $borderColor,
                'visibleColumns' => $this->visibleColumns,
                'subtasksAll' => $subtasksAll,
                'subtaskTotal' => $subtaskTotal,
                'subtaskDone' => $subtaskDone,
                'addingSubtaskForTask' => $this->addingSubtaskForTask,
            ])->render();
        } finally {
            $revertLivewire();
            $revertInstance();
            $extend->endLivewireRendering();
        }

        return [$html, $subtaskDone, $subtaskTotal];
    }

    protected function expandRowIdForSubtask(TaskSubtask $subtask): int
    {
        $projectTaskId = (int) $subtask->task_id;
        $expanded = array_map('intval', $this->expandedTasks);

        if (in_array($projectTaskId, $expanded, true)) {
            return $projectTaskId;
        }

        if ($this->usesWorkItems()) {
            $itemId = WorkItem::query()
                ->where('source_type', 'project_task')
                ->where('source_id', $projectTaskId)
                ->value('id');
            if ($itemId) {
                return (int) $itemId;
            }
        }

        return $expanded[0] ?? $projectTaskId;
    }

    public function startEdit(int $taskId, string $field): void
    {
        if (! $this->rowWritable($taskId, $field)) {
            return;
        }

        $item = $this->resolveWorkItem($taskId);
        if ($item) {
            $this->editingTaskId = $taskId;
            $this->editingField = $field;
            $this->editingValue = $this->editValueForWorkItem($item, $field);

            return;
        }

        $task = $this->resolveProjectTask($taskId);
        if (! $task || ! $this->canEditTask($task)) {
            return;
        }

        $this->editingTaskId = $taskId;
        $this->editingField = $field;
        $this->editingValue = match ($field) {
            'name' => $task->name,
            'status' => $task->status->value,
            'sprint' => $task->sprint_id ? (string) $task->sprint_id : '',
            'category' => $task->category ?? '',
            'assigned_to' => $task->assigned_to ? (string) $task->assigned_to : '',
            'priority' => $task->priority ? (string) $task->priority : '',
            'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : '',
            'description' => $task->plainDescription(),
            default => '',
        };
    }

    protected function editValueForWorkItem(WorkItem $item, string $field): string
    {
        $task = $item->editableProjectTask();
        $subtask = $item->sourceSubtask();

        return match ($field) {
            'name' => $item->title,
            'status' => $item->status->value,
            'sprint' => $item->sprint_id ? (string) $item->sprint_id : '',
            'category' => $item->category ?? '',
            'assigned_to' => $item->assignee_id ? (string) $item->assignee_id : '',
            'priority' => $item->priority ? (string) $item->priority : '',
            'due_date' => $item->due_at ? $item->due_at->format('Y-m-d') : '',
            'description' => $item->plainDescription(),
            default => match (true) {
                $subtask && $field === 'name' => $subtask->name,
                $task && $field === 'name' => $task->name,
                default => '',
            },
        };
    }

    public function saveEdit(): void
    {
        if (! $this->editingTaskId) {
            return;
        }

        $item = $this->resolveWorkItem($this->editingTaskId);
        if ($item) {
            $field = GridField::tryFrom($this->editingField);
            if (! $field || ! $item->writable($field)) {
                $this->cancelEdit();

                return;
            }

            $item->handler()->write($item, $field, $this->editingValue);
            $this->invalidateViewCounts();
            $this->flash = 'Zapisano.';
            $this->cancelEdit();

            return;
        }

        $task = $this->resolveProjectTask($this->editingTaskId);
        if (! $task || ! $this->canEditTask($task)) {
            $this->cancelEdit();

            return;
        }

        match ($this->editingField) {
            'name' => $task->update(['name' => trim($this->editingValue) ?: $task->name]),
            'status' => $this->applyStatusChange($task, $this->editingValue),
            'sprint' => $this->applySprintChange($task, $this->editingValue),
            'category' => $task->update(['category' => $this->editingValue === '' ? null : trim($this->editingValue)]),
            'assigned_to' => $this->applyAssigneeChange($task, $this->editingValue),
            'priority' => $task->update(['priority' => $this->editingValue === '' ? null : (int) $this->editingValue]),
            'due_date' => $task->update(['due_date' => $this->editingValue === '' ? null : $this->editingValue]),
            'description' => $task->update(['description' => trim($this->editingValue) ?: null]),
            default => null,
        };

        $this->invalidateViewCounts();
        $this->flash = 'Zapisano.';
        $this->cancelEdit();
    }

    public function cancelEdit(): void
    {
        $this->editingTaskId = null;
        $this->editingField = '';
        $this->editingValue = '';
    }

    protected function applyStatusChange(ProjectTask $task, string $status): void
    {
        match ($status) {
            'in_progress' => $task->markInProgress(),
            'completed' => $task->markCompleted(),
            'cancelled' => $task->cancel(),
            'pending' => $task->update(['status' => TaskStatus::PENDING, 'completed_at' => null]),
            default => null,
        };
    }

    protected function applyAssigneeChange(ProjectTask $task, string $value): void
    {
        $newAssignee = $value === '' ? null : (int) $value;
        $task->update(['assigned_to' => $newAssignee]);
    }

    public function quickStatusChange(int $taskId, string $status): void
    {
        $item = $this->resolveWorkItem($taskId);
        if ($item) {
            if (! $item->writable(GridField::Status)) {
                return;
            }
            $item->handler()->write($item, GridField::Status, $status);
            $this->invalidateViewCounts();
            $this->flash = 'Status zaktualizowany.';

            return;
        }

        $task = $this->resolveProjectTask($taskId);
        if (! $task || ! $this->canEditTask($task)) {
            return;
        }
        $this->applyStatusChange($task, $status);
        $this->invalidateViewCounts();
        $this->flash = 'Status zaktualizowany.';
    }

    public function quickSprintChange(int $taskId, string $sprintId): void
    {
        if ($this->isLockedToSprint() || ! $this->rowWritable($taskId, 'sprint')) {
            return;
        }

        $item = $this->resolveWorkItem($taskId);
        if ($item) {
            $item->handler()->write($item, GridField::Sprint, $sprintId);
            $this->invalidateViewCounts();
            $this->flash = 'Sprint zaktualizowany.';

            return;
        }

        $task = $this->resolveProjectTask($taskId);
        if (! $task || ! $this->canEditTask($task)) {
            return;
        }

        $this->applySprintChange($task, $sprintId);
        $this->invalidateViewCounts();
        $this->flash = 'Sprint zaktualizowany.';
    }

    public function quickPriorityChange(int $taskId, string $priority): void
    {
        if (! $this->rowWritable($taskId, 'priority')) {
            return;
        }

        if (! in_array($priority, ['', '1', '2', '3', '4', '5'], true)) {
            return;
        }

        $item = $this->resolveWorkItem($taskId);
        if ($item) {
            $item->handler()->write($item, GridField::Priority, $priority);
            $this->invalidateViewCounts();
            $this->flash = 'Priorytet zaktualizowany.';

            return;
        }

        $task = $this->resolveProjectTask($taskId);
        if (! $task || ! $this->canEditTask($task)) {
            return;
        }

        $task->update(['priority' => $priority === '' ? null : (int) $priority]);
        $this->invalidateViewCounts();
        $this->flash = 'Priorytet zaktualizowany.';
    }

    public function quickAssigneeChange(int $taskId, string $userId): void
    {
        if (! $this->rowWritable($taskId, 'assigned_to')) {
            return;
        }

        if ($userId !== '' && ! ctype_digit($userId)) {
            return;
        }

        $item = $this->resolveWorkItem($taskId);
        if ($item) {
            $item->handler()->write($item, GridField::AssignedTo, $userId);
            $this->invalidateViewCounts();
            $this->flash = 'Przypisanie zaktualizowane.';

            return;
        }

        $task = $this->resolveProjectTask($taskId);
        if (! $task || ! $this->canEditTask($task)) {
            return;
        }

        $this->applyAssigneeChange($task, $userId);
        $this->invalidateViewCounts();
        $this->flash = 'Przypisanie zaktualizowane.';
    }

    public function addTask(): void
    {
        $this->validate([
            'newTaskName' => 'required|string|max:255',
            'newTaskSprint' => 'nullable|exists:sprints,id',
            'newTaskAssignedTo' => 'nullable|exists:users,id',
            'newTaskPriority' => 'nullable|integer|min:1|max:5',
            'newTaskDueDate' => 'nullable|date',
            'newTaskCategory' => 'nullable|string|max:255',
        ]);

        $sprintId = $this->isLockedToSprint()
            ? $this->lockedSprintId
            : ($this->newTaskSprint ?: null);

        ProjectTask::create([
            'name' => $this->newTaskName,
            'sprint_id' => $sprintId,
            'sprint_position' => $sprintId
                ? (int) ProjectTask::query()->where('sprint_id', $sprintId)->max('sprint_position') + 1
                : null,
            'assigned_to' => $this->newTaskAssignedTo ?: null,
            'priority' => $this->newTaskPriority ?: null,
            'due_date' => $this->newTaskDueDate ?: null,
            'category' => $this->newTaskCategory ?: null,
            'status' => TaskStatus::PENDING,
            'created_by' => auth()->id(),
        ]);

        $this->newTaskName = '';
        $this->resetErrorBag();
        $this->invalidateViewCounts();
        $this->js('queueMicrotask(() => document.getElementById("tg-add-name")?.focus())');
    }

    public function startAdd(string $kind): void
    {
        if ($this->isPlanQueue()) {
            return;
        }

        if (! in_array($kind, ['task', 'procedure', 'approval', 'meeting'], true)) {
            return;
        }

        if (in_array($kind, ['procedure', 'approval', 'meeting'], true) && ! $this->usesWorkItems()) {
            return;
        }

        if ($kind === 'task') {
            $this->addKind = 'task';
            $this->showAddRow = false;
            $this->resetErrorBag();
            $this->js('queueMicrotask(() => document.getElementById("tg-add-name")?.focus())');

            return;
        }

        $this->addKind = $kind;
        $this->showAddRow = true;
        $this->reset(['newTaskName', 'newTaskCategory', 'newTaskAssignedTo', 'newTaskPriority', 'newTaskDueDate', 'newProcedureTemplateId', 'newProcedureSubjectId', 'newProcedureNameSuffix', 'newMeetingDate', 'newMeetingStart', 'newMeetingEnd', 'newMeetingParticipantIds', 'newMeetingLocation']);
        if ($this->isLockedToSprint()) {
            $this->newTaskSprint = (string) $this->lockedSprintId;
        }
        if ($kind === 'meeting') {
            $this->newMeetingDate = now()->addDay()->format('Y-m-d');
            $this->newMeetingStart = '10:00';
            $this->newMeetingEnd = '11:00';
            $this->newMeetingParticipantIds = array_values(array_filter([auth()->id()]));
        }
        $this->resetErrorBag();
    }

    public function clearAddComposer(): void
    {
        $this->reset(['newTaskName', 'newTaskCategory', 'newTaskAssignedTo']);
        $this->resetErrorBag();
        $this->js('queueMicrotask(() => document.getElementById("tg-add-name")?.focus())');
    }

    public function cancelAdd(): void
    {
        $this->showAddRow = false;
        $this->addKind = 'task';
        $this->resetErrorBag();
    }

    public function updatedNewProcedureTemplateId(string $value): void
    {
        $this->newProcedureSubjectId = '';
        $this->newProcedureNameSuffix = '';
        $this->resetErrorBag(['newProcedureSubjectId', 'newProcedureNameSuffix']);
    }

    public function procedureStartSubjectType(): ?ProcedureSubjectType
    {
        if ($this->newProcedureTemplateId === '') {
            return null;
        }

        $template = ProcedureTemplate::query()->find((int) $this->newProcedureTemplateId);

        return $template?->subjectType();
    }

    /** @return list<array{id: int, label: string}> */
    public function procedureStartSubjectOptions(): array
    {
        return $this->procedureStartSubjectType()?->dropdownOptions() ?? [];
    }

    public function getNewProcedureTaskNamePreviewProperty(): string
    {
        if ($this->newProcedureTemplateId === '') {
            return '';
        }

        $template = ProcedureTemplate::query()->find((int) $this->newProcedureTemplateId);
        if (! $template) {
            return '';
        }

        $subjectType = $template->subjectType();
        $detail = $this->newProcedureNameSuffix;
        if ($subjectType && $this->newProcedureSubjectId !== '') {
            foreach ($subjectType->dropdownOptions() as $option) {
                if ((string) $option['id'] === $this->newProcedureSubjectId) {
                    $detail = $option['label'];
                    break;
                }
            }
        }

        return ProcedureRunService::composeTaskName($template->name, $detail !== '' ? $detail : null);
    }

    public function submitAdd(): void
    {
        match ($this->addKind) {
            'procedure' => $this->startProcedureFromGrid(),
            'approval' => $this->addApproval(),
            'meeting' => $this->addMeeting(),
            default => $this->addTask(),
        };
    }

    public function startProcedureFromGrid(): void
    {
        if (! $this->usesWorkItems()) {
            return;
        }

        $this->validate([
            'newProcedureTemplateId' => 'required|exists:procedure_templates,id',
            'newProcedureNameSuffix' => ['nullable', 'string', 'max:80'],
            'newTaskAssignedTo' => 'nullable|exists:users,id',
            'newTaskDueDate' => 'nullable|date',
        ], [], [
            'newProcedureTemplateId' => 'szablon procedury',
            'newProcedureNameSuffix' => 'dopisek',
        ]);

        $template = ProcedureTemplate::query()->findOrFail((int) $this->newProcedureTemplateId);
        $subjectType = $template->subjectType();
        $subjectTable = null;
        if ($subjectType) {
            $modelClass = $subjectType->modelClass();
            $subjectTable = (new $modelClass)->getTable();
        }

        $this->validate([
            'newProcedureSubjectId' => array_values(array_filter([
                $subjectType ? 'required' : 'nullable',
                'integer',
                $subjectTable ? Rule::exists($subjectTable, 'id') : null,
            ])),
        ], [
            'newProcedureSubjectId.required' => 'Wybierz '.mb_strtolower($subjectType?->label() ?? 'kogo dotyczy').'.',
        ], [
            'newProcedureSubjectId' => $subjectType?->label() ?? 'dotyczy',
        ]);

        try {
            app(ProcedureRunService::class)->startRun($template, [
                'name_suffix' => $subjectType ? null : ($this->newProcedureNameSuffix ?: null),
                'assigned_to' => $this->newTaskAssignedTo ?: null,
                'due_date' => $this->newTaskDueDate ?: null,
                'subject_type' => $subjectType?->value,
                'subject_id' => $this->newProcedureSubjectId !== '' ? (int) $this->newProcedureSubjectId : null,
            ]);
        } catch (\RuntimeException $e) {
            $this->flash = $e->getMessage();

            return;
        }

        $this->resetAddForm();
        $this->flash = 'Procedura uruchomiona.';
    }

    public function addApproval(): void
    {
        if (! $this->usesWorkItems()) {
            return;
        }

        $this->validate([
            'newTaskName' => 'required|string|max:255',
            'newTaskAssignedTo' => 'required|exists:users,id',
            'newTaskSprint' => 'nullable|exists:sprints,id',
            'newTaskPriority' => 'nullable|integer|min:1|max:5',
            'newTaskDueDate' => 'nullable|date',
            'newTaskCategory' => 'nullable|string|max:255',
        ], [], [
            'newTaskName' => 'nazwa',
            'newTaskAssignedTo' => 'zatwierdzający',
        ]);

        $sprintId = $this->isLockedToSprint()
            ? $this->lockedSprintId
            : ($this->newTaskSprint ?: null);

        ApprovalRequest::query()->create([
            'name' => $this->newTaskName,
            'approver_id' => (int) $this->newTaskAssignedTo,
            'created_by' => auth()->id(),
            'sprint_id' => $sprintId,
            'category' => $this->newTaskCategory ?: null,
            'priority' => $this->newTaskPriority ?: null,
            'due_at' => $this->newTaskDueDate ?: null,
        ]);

        $this->resetAddForm();
        $this->flash = 'Prośba o zatwierdzenie wysłana.';
    }

    public function addMeeting(): void
    {
        if (! $this->usesWorkItems()) {
            return;
        }

        $this->newMeetingStart = ProjectTask::normalizeClock($this->newMeetingStart);
        $this->newMeetingEnd = ProjectTask::normalizeClock($this->newMeetingEnd);

        $this->validate([
            'newTaskName' => 'required|string|max:255',
            'newMeetingDate' => 'required|date',
            'newMeetingStart' => 'required|date_format:H:i',
            'newMeetingEnd' => 'required|date_format:H:i',
            'newMeetingParticipantIds' => 'required|array|min:1',
            'newMeetingParticipantIds.*' => 'integer|exists:users,id',
            'newMeetingLocation' => 'nullable|string|max:4000',
            'newTaskSprint' => 'nullable|exists:sprints,id',
            'newTaskPriority' => 'nullable|integer|min:1|max:5',
            'newTaskCategory' => 'nullable|string|max:255',
        ], [
            'newTaskName.required' => 'Podaj temat spotkania.',
            'newMeetingDate.required' => 'Podaj datę spotkania.',
            'newMeetingStart.required' => 'Podaj godzinę rozpoczęcia.',
            'newMeetingEnd.required' => 'Podaj godzinę zakończenia.',
            'newMeetingParticipantIds.required' => 'Wybierz przynajmniej jednego uczestnika.',
            'newMeetingParticipantIds.min' => 'Wybierz przynajmniej jednego uczestnika.',
        ]);

        $window = ProjectTask::meetingWindow(
            $this->newMeetingDate,
            $this->newMeetingStart,
            $this->newMeetingEnd,
            'newMeetingEnd'
        );

        $participantIds = collect($this->newMeetingParticipantIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $sprintId = $this->isLockedToSprint()
            ? $this->lockedSprintId
            : ($this->newTaskSprint ?: null);

        ProjectTask::createIntended(WorkItemType::Meeting, [
            'name' => $this->newTaskName,
            'category' => $this->newTaskCategory ?: 'Spotkanie',
            'status' => TaskStatus::PENDING,
            'due_date' => $this->newMeetingDate,
            'starts_at' => $window['starts_at'],
            'ends_at' => $window['ends_at'],
            'participant_ids' => $participantIds,
            'location' => trim($this->newMeetingLocation) !== '' ? trim($this->newMeetingLocation) : null,
            'assigned_to' => $participantIds[0] ?? auth()->id(),
            'created_by' => auth()->id(),
            'priority' => $this->newTaskPriority ?: null,
            'sprint_id' => $sprintId,
            'sprint_position' => $sprintId
                ? (int) ProjectTask::query()->where('sprint_id', $sprintId)->max('sprint_position') + 1
                : null,
        ]);

        $this->resetAddForm();
        $this->flash = 'Spotkanie umówione.';
    }

    private function resetAddForm(): void
    {
        $this->invalidateViewCounts();
        $this->reset(['newTaskName', 'newTaskSprint', 'newTaskCategory', 'newTaskAssignedTo', 'newTaskPriority', 'newTaskDueDate', 'newProcedureTemplateId', 'newProcedureSubjectId', 'newProcedureNameSuffix', 'newMeetingDate', 'newMeetingStart', 'newMeetingEnd', 'newMeetingParticipantIds', 'newMeetingLocation']);
        if ($this->isLockedToSprint()) {
            $this->newTaskSprint = (string) $this->lockedSprintId;
        }
        $this->showAddRow = false;
        $this->addKind = 'task';
    }

    #[Renderless]
    public function startAddSubtask(int $taskId): void
    {
        $item = $this->resolveWorkItem($taskId);
        if ($item && ! $item->supports(GridField::Subtasks)) {
            return;
        }

        $task = $this->resolveProjectTask($taskId);
        if (! $task) {
            return;
        }

        $this->addingSubtaskForTask = $taskId;
        $this->newSubtaskName = '';
        $this->setTaskExpanded($taskId, true);
        $this->pushExpandFragment($taskId, true);
    }

    #[Renderless]
    public function saveSubtask(): void
    {
        if (! $this->addingSubtaskForTask) {
            return;
        }

        $rowId = (int) $this->addingSubtaskForTask;
        $name = trim($this->newSubtaskName);
        if (! $name) {
            $this->addingSubtaskForTask = null;
            $this->pushExpandFragment($rowId, true);

            return;
        }

        if (! $this->acceptsDroppedSubtasks($this->addingSubtaskForTask)) {
            $this->addingSubtaskForTask = null;
            $this->pushExpandFragment($rowId, true);

            return;
        }

        $parent = $this->resolveProjectTask($this->addingSubtaskForTask);
        if (! $parent) {
            $this->addingSubtaskForTask = null;
            $this->pushExpandFragment($rowId, true);

            return;
        }

        $subtask = TaskSubtask::create([
            'task_id' => $parent->id,
            'name' => $name,
            'created_by' => auth()->id(),
        ]);

        TaskSubtaskEvent::log($subtask, 'created', auth()->id());

        if (auth()->user()) {
            app(UserMentionService::class)->notifySubtaskMentions(
                $parent,
                $subtask,
                $name,
                auth()->user()
            );
        }

        $this->newSubtaskName = '';
        $this->addingSubtaskForTask = null;
        $this->flash = 'Podzadanie dodane.';
        $this->pushExpandFragment($rowId, true);
    }

    #[Renderless]
    public function cancelAddSubtask(): void
    {
        $rowId = $this->addingSubtaskForTask ? (int) $this->addingSubtaskForTask : null;
        $this->addingSubtaskForTask = null;
        $this->newSubtaskName = '';
        if ($rowId) {
            $this->pushExpandFragment($rowId, true);
        }
    }

    #[Renderless]
    public function toggleSubtask(int $subtaskId): void
    {
        $subtask = TaskSubtask::find($subtaskId);
        if (! $subtask) {
            return;
        }

        if ($subtask->is_completed) {
            $subtask->markIncomplete();
            TaskSubtaskEvent::log($subtask, 'reopened', auth()->id());
        } else {
            $subtask->markCompleted();
            TaskSubtaskEvent::log($subtask, 'completed', auth()->id());
        }

        $this->pushExpandFragment($this->expandRowIdForSubtask($subtask), true);
    }

    public function saveView(): void
    {
        if (! $this->gridViewsTableExists()) {
            $this->flash = 'Brak tabeli widoków — uruchom migracje (php artisan migrate).';

            return;
        }

        $name = trim($this->saveViewName);
        if ($name === '') {
            return;
        }

        $slug = Str::slug($name) ?: 'widok';

        $existing = TaskGridView::query()
            ->where('user_id', auth()->id())
            ->where(fn ($q) => $q->where('name', $name)->orWhere('slug', $slug))
            ->first();

        if ($existing) {
            $slug = $existing->slug;
            if ($this->saveViewAsGlobal && $this->visibleSlugTaken($slug, $existing->id)) {
                $this->flash = 'Widok globalny o tej nazwie już istnieje. Wybierz inną.';

                return;
            }
        } elseif ($this->visibleSlugTaken($slug)) {
            $slug = $this->uniqueSlug($name);
        }

        $record = TaskGridView::updateOrCreate(
            ['user_id' => auth()->id(), 'slug' => $slug],
            array_merge([
                'name' => $name,
                'is_global' => $this->saveViewAsGlobal,
            ], $this->viewPayload()),
        );

        $this->view = $record->slug;
        $this->activeViewId = $record->id;
        $this->saveViewName = '';
        $this->saveViewAsGlobal = false;
        $this->flash = $record->is_global
            ? "Widok globalny „{$name}” zapisany."
            : "Widok „{$name}” zapisany.";
    }

    public function loadView(string $slug): void
    {
        $this->loadViewFromSlug($slug);
    }

    public function loadSavedView(int $id): void
    {
        $record = $this->findVisibleView($id);
        if (! $record) {
            $this->flash = 'Nie znaleziono widoku.';
            $this->view = '';
            $this->activeViewId = null;

            return;
        }

        $this->activateView($record);
        $this->flash = "Załadowano „{$record->name}”.";
    }

    public function overwriteView(int $id): void
    {
        $record = $this->findVisibleView($id);
        if (! $record || ! $record->canBeManagedBy(auth()->user())) {
            $this->flash = 'Nie możesz nadpisać tego widoku.';

            return;
        }

        $record->update($this->viewPayload());
        $this->view = $record->slug;
        $this->activeViewId = $record->id;
        $this->flash = "Widok „{$record->name}” zaktualizowany.";
    }

    public function deleteView(int $id): void
    {
        $record = $this->findVisibleView($id);
        if (! $record || ! $record->canBeManagedBy(auth()->user())) {
            $this->flash = 'Nie możesz usunąć tego widoku.';

            return;
        }

        $slug = $record->slug;
        $deletedId = $record->id;
        $record->delete();

        if ($this->view === $slug || $this->activeViewId === $deletedId) {
            $this->view = '';
            $this->activeViewId = null;
        }
    }

    public function clearView(): void
    {
        $this->view = '';
        $this->activeViewId = null;
        $this->flash = 'Widok domyślny.';
    }

    public function setAsMenuDefaultView(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $query = $this->currentQueryParams();

        if (isset($query['view'])) {
            $validSlug = TaskGridView::findVisibleTo($user, $query['view']) !== null;

            if (! $validSlug) {
                unset($query['view']);
            }
        }

        $user->update([
            'default_tasks_view' => 'grid',
            'default_tasks_grid_view_slug' => $query['view'] ?? null,
            'default_tasks_grid_query' => $query !== [] ? $query : null,
        ]);

        $this->flash = 'Domyślny widok z menu zapisany (wraz z filtrami).';
    }

    public function openChronoModal(): void
    {
        $this->showChronoModal = true;
        $this->chronoMode = 'menu';
        $this->chronoLoading = false;
        $this->chronoError = null;
        $this->chronoSummary = null;
        $this->importText = '';
        $this->importMode = 'json';
        $this->importProposals = [];
        $this->importSelected = [];
        $this->exportJson = '';
        $this->exportCount = 0;
        $this->exportTotal = 0;
    }

    #[On('chrono-assist-picked')]
    public function handleChronoAssistPicked(string $key): void
    {
        if ($key === 'export-csv') {
            $this->js('$wire.downloadChronoCsv()');

            return;
        }

        if ($key === 'export-json') {
            $this->chronoChooseExport();

            return;
        }

        if ($key === 'import-json') {
            $this->chronoChooseImport('json');

            return;
        }

        if ($key === 'import-list') {
            $this->chronoChooseImport('list');

            return;
        }

        if ($key === 'mutate-json') {
            $this->chronoChooseEdiImport();

            return;
        }

        if ($key === 'mutate-export') {
            $this->chronoChooseEdiExport();

            return;
        }

        if (str_starts_with($key, 'mutate-')) {
            $this->chronoChooseMutate($key);

            return;
        }

        if (str_starts_with($key, 'summary-')) {
            $this->chronoChooseSummary();
        }
    }

    #[On('chrono-assist-closed')]
    public function closeChronoModal(): void
    {
        $this->showChronoModal = false;
        $this->chronoMode = 'menu';
        $this->chronoLoading = false;
        $this->chronoError = null;
        $this->chronoSummary = null;
        $this->importText = '';
        $this->importMode = 'json';
        $this->importProposals = [];
        $this->importSelected = [];
        $this->exportJson = '';
        $this->exportCount = 0;
        $this->exportTotal = 0;
    }

    public function chronoChooseSummary(): void
    {
        $this->chronoMode = 'summary';
        $this->chronoError = null;
        $this->chronoSummary = null;
        $this->chronoLoading = true;
    }

    public function chronoChooseImport(string $mode = 'json'): void
    {
        $this->chronoMode = 'import';
        $this->importMode = in_array($mode, ['json', 'list'], true) ? $mode : 'json';
        $this->chronoLoading = false;
        $this->chronoError = null;
        $this->importText = '';
        $this->importProposals = [];
        $this->importSelected = [];
    }

    public function chronoChooseExport(): void
    {
        $this->chronoMode = 'export';
        $this->chronoLoading = false;
        $this->chronoError = null;
        $this->buildChronoExport();
    }

    public function chronoChooseMutate(string $intent): void
    {
        if (EdiTaskEdit::fieldsForIntent($intent) === []) {
            return;
        }

        $this->showChronoModal = false;
        $this->chronoMode = 'menu';
        $this->ediIntent = $intent;
        $this->ediLoading = true;
        $this->ediError = null;
        $this->ediChanges = [];
        $this->ediReviewed = 0;
        $this->ediTotal = 0;
        $this->ediEditingRowId = null;
        $this->ediEditingField = '';
        $this->flash = null;
    }

    public function chronoChooseEdiImport(): void
    {
        $this->showChronoModal = true;
        $this->chronoMode = 'edi-import';
        $this->chronoLoading = false;
        $this->chronoError = null;
        $this->importText = '';
    }

    public function parseEdiImportText(TasksFilterMutateService $service): void
    {
        $this->chronoError = null;

        $editable = EdiTaskEdit::EDITABLE;

        try {
            [, $records, $total] = $this->chronoEdiSnapshot($editable, 200);
            $diffs = $service->parseImportedJson($this->importText, $records, $editable);

            $this->ediIntent = 'mutate-json';
            $this->ediLoading = false;
            $this->ediReviewed = count($records);
            $this->ediTotal = $total;
            $this->adoptEdiDiffs($diffs);
            $this->showChronoModal = false;
            $this->chronoMode = 'menu';
            $this->importText = '';
            $this->flash = null;
        } catch (LlmException $e) {
            $this->chronoError = $e->getMessage();
        } catch (\Throwable $e) {
            $this->chronoError = 'Nie udało się wczytać JSON Ediego: '.$e->getMessage();
        }
    }

    public function chronoChooseEdiExport(): void
    {
        $this->showChronoModal = true;
        $this->chronoMode = 'edi-export';
        $this->chronoLoading = false;
        $this->chronoError = null;
        $this->buildEdiExport();
    }

    public function fetchEdiProposals(TasksFilterMutateService $service): void
    {
        if (! $this->ediLoading || ! $this->ediIntent || $this->ediIntent === 'mutate-json') {
            $this->ediLoading = false;

            return;
        }

        $editable = EdiTaskEdit::fieldsForIntent($this->ediIntent);

        try {
            [$labels, $records, $total] = $this->chronoEdiSnapshot($editable);
            $this->ediReviewed = count($records);
            $this->ediTotal = $total;
            $this->adoptEdiDiffs($service->propose($records, $editable, $this->ediIntent, $labels));
        } catch (LlmException $e) {
            $this->ediError = $e->getMessage();
            $this->ediChanges = [];
        } catch (\Throwable $e) {
            $this->ediError = 'Nie udało się przygotować propozycji Ediego: '.$e->getMessage();
            $this->ediChanges = [];
        } finally {
            $this->ediLoading = false;
        }
    }

    public function applyEdiChanges(): void
    {
        if ($this->ediChanges === [] || ! $this->ediIntent) {
            return;
        }

        $applied = 0;

        foreach ($this->ediChanges as $change) {
            if ($this->writeEdiChange($change)) {
                $applied++;
            }
        }

        $this->discardEdiChanges();
        $this->invalidateViewCounts();
        $this->flash = $applied === 1
            ? 'Zastosowano 1 zmianę Ediego.'
            : "Zastosowano {$applied} zmian Ediego.";
    }

    public function discardEdiChanges(): void
    {
        $this->ediIntent = null;
        $this->ediLoading = false;
        $this->ediError = null;
        $this->ediChanges = [];
        $this->ediReviewed = 0;
        $this->ediTotal = 0;
        $this->ediEditingRowId = null;
        $this->ediEditingField = '';
    }

    public function acceptEdiChange(int $rowId, string $field): void
    {
        $index = $this->ediChangeIndex($rowId, $field);
        if ($index === null) {
            return;
        }

        $this->writeEdiChange($this->ediChanges[$index]);
        $this->pullEdiChange($index);
        $this->invalidateViewCounts();

        if ($this->ediChanges === []) {
            $this->discardEdiChanges();
        }
    }

    public function rejectEdiChange(int $rowId, string $field): void
    {
        $index = $this->ediChangeIndex($rowId, $field);
        if ($index === null) {
            return;
        }

        $this->pullEdiChange($index);

        if ($this->ediChanges === []) {
            $this->discardEdiChanges();
        }
    }

    public function startEdiRevise(int $rowId, string $field): void
    {
        if ($this->ediChangeIndex($rowId, $field) === null) {
            return;
        }

        $this->ediEditingRowId = $rowId;
        $this->ediEditingField = $field;
    }

    public function cancelEdiRevise(): void
    {
        $this->ediEditingRowId = null;
        $this->ediEditingField = '';
    }

    public function commitEdiRevise(int $rowId, string $field, mixed $value = null): void
    {
        $this->reviseEdiChange($rowId, $field, $value);
        $this->cancelEdiRevise();
    }

    public function reviseEdiChange(int $rowId, string $field, mixed $value): void
    {
        $index = $this->ediChangeIndex($rowId, $field);
        if ($index === null || ! $this->ediIntent || ! EdiTaskEdit::allows($field, $this->ediIntent)) {
            return;
        }

        $from = $this->ediChanges[$index]['from'] ?? null;
        $to = app(TasksFilterMutateService::class)->normalizeProposed($field, $value);
        $kind = EdiTaskEdit::kind($from, $to);

        if ($kind === null) {
            $this->pullEdiChange($index);
            $this->cancelEdiRevise();
            if ($this->ediChanges === []) {
                $this->discardEdiChanges();
            }

            return;
        }

        $updated = $this->ediChanges[$index];
        $updated['to'] = $to;
        $updated['kind'] = $kind;
        $updated['to_label'] = EdiTaskEdit::label($field, $to);
        $this->ediChanges[$index] = $updated;
    }

    public function isEdiEditing(int $rowId, string $field): bool
    {
        return $this->ediEditingRowId === $rowId && $this->ediEditingField === $field;
    }

    public function isEdiReviewing(): bool
    {
        return $this->ediLoading || $this->ediChanges !== [] || ($this->ediIntent !== null && $this->ediError !== null);
    }

    /**
     * @param  array{row_id?: int, field?: string, to?: mixed}  $change
     */
    protected function writeEdiChange(array $change): bool
    {
        if (! $this->ediIntent) {
            return false;
        }

        $field = GridField::tryFrom($change['field'] ?? '');
        if (! $field || ! EdiTaskEdit::allows($field->value, $this->ediIntent)) {
            return false;
        }

        $rowId = (int) ($change['row_id'] ?? 0);
        $value = $change['to'] ?? '';
        $item = $this->resolveWorkItem($rowId);

        if ($item) {
            if (! $this->canEditTask($item) || ! $item->writable($field)) {
                return false;
            }
            $item->handler()->write($item, $field, $value);

            return true;
        }

        $task = $this->resolveProjectTask($rowId);
        if (! $task || ! $this->canEditTask($task)) {
            return false;
        }

        app(ProjectTaskFields::class)->write($task, $field, $value);

        return true;
    }

    protected function ediChangeIndex(int $rowId, string $field): ?int
    {
        foreach ($this->ediChanges as $index => $change) {
            if ((int) $change['row_id'] === $rowId && $change['field'] === $field) {
                return (int) $index;
            }
        }

        return null;
    }

    protected function pullEdiChange(int $index): void
    {
        unset($this->ediChanges[$index]);
        $this->ediChanges = array_values($this->ediChanges);
    }

    /**
     * @return list<int>
     */
    protected function ediReviewRowIds(): array
    {
        if ($this->ediChanges === []) {
            return [];
        }

        return array_values(array_unique(array_map(
            fn (array $change) => (int) $change['row_id'],
            $this->ediChanges,
        )));
    }

    /**
     * @return array{row_id: int, field: string, kind: string, from: mixed, to: mixed, from_label: string, to_label: string}|null
     */
    public function ediCell(mixed $task, string $field): ?array
    {
        if ($this->ediChanges === []) {
            return null;
        }

        $id = (int) $task->id;

        foreach ($this->ediChanges as $change) {
            if ((int) $change['row_id'] === $id && $change['field'] === $field) {
                return $change;
            }
        }

        return null;
    }

    /**
     * @param  list<array{row_id: int, field: string, kind: string, from: mixed, to: mixed, from_label: string, to_label: string}>  $diffs
     */
    protected function adoptEdiDiffs(array $diffs): void
    {
        $this->ediChanges = $diffs;
        $this->ediError = $diffs === []
            ? 'Edi nie znalazł nic do poprawienia w tym filtrze.'
            : null;
        $this->ediEditingRowId = null;
        $this->ediEditingField = '';

        foreach ($diffs as $change) {
            if ($change['field'] === 'description' && ! in_array($change['row_id'], $this->expandedTasks, true)) {
                $this->expandedTasks[] = $change['row_id'];
            }
        }
    }

    /**
     * @param  list<string>  $editable
     * @return array{0: list<string>, 1: list<array<string, mixed>>, 2: int}
     */
    protected function chronoEdiSnapshot(array $editable, int $max = 40): array
    {
        [$labels, , $total] = $this->chronoFilterSnapshot();

        $query = $this->filteredTasksQuery();
        if ($this->usesWorkItems()) {
            $query->where('type', WorkItemType::Task);
        }

        $max = $max > 0 ? $max : 40;
        $records = [];

        foreach ((clone $query)->with(['assignedTo'])->limit($max)->get() as $item) {
            if ($item instanceof WorkItem && $item->type !== WorkItemType::Task) {
                continue;
            }

            $row = [
                'id' => $item->id,
                'source_id' => $item instanceof WorkItem ? $item->source_id : $item->id,
                'name' => $item instanceof WorkItem ? (string) $item->title : (string) $item->name,
                'description' => method_exists($item, 'plainDescription') ? $item->plainDescription() : (string) ($item->description ?? ''),
                'category' => $item->category,
                'priority' => $item->priority,
                'due_date' => $item instanceof WorkItem
                    ? $item->due_at?->toDateString()
                    : $item->due_date?->toDateString(),
            ];

            $records[] = array_intersect_key($row, array_flip(array_merge(['id', 'source_id', 'name'], $editable)));
        }

        return [$labels, $records, $total];
    }

    public function chronoBackToMenu(): void
    {
        $this->chronoMode = 'menu';
        $this->chronoLoading = false;
        $this->chronoError = null;
        $this->chronoSummary = null;
        $this->importMode = 'json';
        $this->importProposals = [];
        $this->importSelected = [];
        $this->exportJson = '';
        $this->exportCount = 0;
        $this->exportTotal = 0;
    }

    public function fetchChronoSummary(TasksFilterSummaryService $service): void
    {
        if (! $this->chronoLoading || $this->chronoMode !== 'summary') {
            return;
        }

        try {
            [$labels, $sample, $total] = $this->chronoFilterSnapshot();
            $this->chronoSummary = $service->summarize($labels, $sample, $total);
            $this->chronoError = null;
        } catch (LlmException $e) {
            $this->chronoError = $e->getMessage();
            $this->chronoSummary = null;
        } catch (\Throwable $e) {
            $this->chronoError = 'Nie udało się przygotować podsumowania: '.$e->getMessage();
            $this->chronoSummary = null;
        } finally {
            $this->chronoLoading = false;
        }
    }

    public function parseImportText(TasksFilterImportService $service): void
    {
        $this->chronoError = null;

        try {
            $defaults = $this->chronoImportDefaults();
            $this->importProposals = $this->importMode === 'list'
                ? $service->parseLines($this->importText, $defaults)
                : $service->parseJson($this->importText, $defaults);
            $this->importProposals = array_map(function (array $proposal) {
                $bits = [];
                $bits[] = 'Nowe';
                if (! empty($proposal['assignee'])) {
                    $bits[] = '@'.$proposal['assignee'];
                }
                if (! empty($proposal['category'])) {
                    $bits[] = $proposal['category'];
                }
                if (! empty($proposal['priority'])) {
                    $bits[] = 'P'.$proposal['priority'];
                }
                if (($proposal['subtasks'] ?? []) !== []) {
                    $bits[] = count($proposal['subtasks']).' podzadań';
                }
                $proposal['meta'] = implode(' · ', $bits);

                return $proposal;
            }, $this->importProposals);
            $this->importSelected = array_keys($this->importProposals);

            if ($this->importProposals === []) {
                $this->chronoError = 'Nie znaleziono zadań w wklejonym tekście.';
            }
        } catch (LlmException $e) {
            $this->chronoError = $e->getMessage();
            $this->importProposals = [];
            $this->importSelected = [];
        } catch (\Throwable $e) {
            $this->chronoError = 'Nie udało się wczytać zadań: '.$e->getMessage();
            $this->importProposals = [];
            $this->importSelected = [];
        }
    }

    public function confirmImportProposals(TaskCreationService $tasks): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $selected = collect($this->importSelected)
            ->map(fn ($index) => (int) $index)
            ->unique()
            ->filter(fn (int $index) => isset($this->importProposals[$index]))
            ->sort()
            ->values();

        if ($selected->isEmpty()) {
            $this->chronoError = 'Zaznacz co najmniej jedną pozycję.';

            return;
        }

        $defaults = $this->chronoImportDefaults();
        $created = 0;

        foreach ($selected as $index) {
            $proposal = $this->importProposals[$index];
            $name = trim((string) ($proposal['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $tasks->create([
                'name' => $name,
                'description' => ($proposal['description'] ?? '') ?: null,
                'category' => ($proposal['category'] ?? null) ?: ($defaults['category'] ?? null),
                'priority' => isset($proposal['priority']) ? (int) $proposal['priority'] : null,
                'due_date' => $proposal['due_date'] ?? null,
                'assigned_to' => $proposal['assigned_to'] ?? ($defaults['assigned_to'] ?? null),
                'sprint_id' => $proposal['sprint_id'] ?? ($defaults['sprint_id'] ?? null),
                'subtasks' => $this->chronoImportSubtaskNames($proposal['subtasks'] ?? []),
            ], $user);

            $created++;
        }

        $this->closeChronoModal();
        if ($created > 0) {
            $this->invalidateViewCounts();
        }
        $this->flash = $created === 1
            ? 'Utworzono 1 zadanie z importu.'
            : "Utworzono {$created} zadań z importu.";
    }

    /**
     * @param  list<mixed>  $subtasks
     * @return list<string>
     */
    protected function chronoImportSubtaskNames(array $subtasks): array
    {
        $names = [];

        foreach ($subtasks as $subtask) {
            if (is_string($subtask)) {
                $name = trim($subtask);
            } else {
                $name = trim((string) ($subtask['name'] ?? ''));
            }

            if ($name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * @return array{0: list<string>, 1: list<array<string, mixed>>, 2: int}
     */
    protected function chronoFilterSnapshot(): array
    {
        $labels = array_map(
            fn (array $chip) => $chip['label'],
            $this->activeFilterChips(),
        );

        if ($labels === []) {
            $labels = ['Bez dodatkowych filtrów (domyślny widok)'];
        }

        if ($this->isLockedToSprint()) {
            $sprintName = Sprint::query()->whereKey($this->lockedSprintId)->value('name');
            array_unshift($labels, 'Sprint: '.($sprintName ?: '#'.$this->lockedSprintId));
        }

        $query = $this->filteredTasksQuery();
        $total = (clone $query)->count();

        $rows = (clone $query)
            ->with(['assignedTo'])
            ->limit(40)
            ->get()
            ->map(function ($item) {
                if ($item instanceof WorkItem) {
                    return [
                        'id' => $item->id,
                        'name' => $item->title,
                        'status' => $this->chronoStatusValue($item->status),
                        'category' => $item->category,
                        'assignee' => $item->assignedTo?->name,
                        'priority' => $item->priority,
                        'due_date' => $item->due_at?->toDateString(),
                        'type' => $item->type instanceof WorkItemType ? $item->type->value : (string) $item->type,
                    ];
                }

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'status' => $this->chronoStatusValue($item->status),
                    'category' => $item->category,
                    'assignee' => $item->assignedTo?->name,
                    'priority' => $item->priority,
                    'due_date' => $item->due_date?->toDateString(),
                    'type' => 'task',
                ];
            })
            ->all();

        return [$labels, $rows, $total];
    }

    protected function chronoStatusValue(mixed $status): string
    {
        if ($status instanceof WorkItemStatus || $status instanceof TaskStatus) {
            return $status->value;
        }

        if ($status instanceof \BackedEnum) {
            return (string) $status->value;
        }

        return $status === null ? '' : (string) $status;
    }

    /**
     * @return array{category: ?string, assigned_to: ?int, sprint_id: ?int, assignee_label: ?string}
     */
    protected function chronoImportDefaults(): array
    {
        $assignedTo = null;
        $assigneeLabel = null;

        $keys = $this->assignedFilterKeys();
        $ids = $this->resolveUserFilterIds($keys);
        if ($ids !== []) {
            $assignedTo = $ids[0];
            if (($keys[0] ?? '') === 'me') {
                $assigneeLabel = auth()->user()?->name;
            } else {
                $assigneeLabel = User::query()->whereKey($assignedTo)->value('name');
            }
        }

        $category = trim($this->searchCategory);
        if ($this->filterOp('searchCategory') === 'neq' || str_contains($category, '|') || $category === '' || $category === '__none__') {
            $category = null;
        }

        return [
            'category' => $category,
            'assigned_to' => $assignedTo,
            'sprint_id' => $this->isLockedToSprint() ? $this->lockedSprintId : null,
            'assignee_label' => $assigneeLabel,
        ];
    }

    protected function buildChronoExport(): void
    {
        $max = 200;
        $query = $this->filteredTasksQuery();
        $this->exportTotal = (clone $query)->count();

        $items = (clone $query)->limit($max);

        if ($this->usesWorkItems()) {
            $items = $items->with([
                'assignedTo',
                'createdBy',
                'sprint',
                'source' => function (MorphTo $morphTo) {
                    $morphTo->morphWith([
                        ProjectTask::class => ['subtasks.assignedTo', 'createdBy', 'sprint', 'assignedTo'],
                        TaskSubtask::class => ['task', 'assignedTo'],
                        CommentMention::class => ['comment'],
                        \App\Models\ProcedureRun::class => ['task', 'template'],
                        \App\Models\WarehouseDispatch::class => ['tasks'],
                        ApprovalRequest::class => ['approver', 'decidedBy'],
                    ])->morphWithCount([
                        ProjectTask::class => ['comments'],
                    ]);
                },
            ])->get();
        } else {
            $items = $items->with(['assignedTo', 'createdBy', 'sprint', 'subtasks.assignedTo'])
                ->withCount('comments')
                ->get();
        }

        $tasks = [];
        foreach ($items as $item) {
            $tasks[] = $this->chronoExportRow($item);
        }

        $labels = array_map(
            fn (array $chip) => $chip['label'],
            $this->activeFilterChips(),
        );

        $payload = [
            'format' => 'tasks-filter-export',
            'version' => 2,
            'exported_at' => now()->toIso8601String(),
            'filters' => $labels,
            'count' => count($tasks),
            'total_in_filter' => $this->exportTotal,
            'truncated' => $this->exportTotal > $max,
            'tasks' => $tasks,
        ];

        $this->exportCount = count($tasks);
        $this->exportJson = json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        ) ?: '';

        if ($this->exportCount === 0) {
            $this->chronoError = 'Filtr nie zwraca żadnych zadań do eksportu.';
        }
    }

    protected function buildEdiExport(): void
    {
        $editable = EdiTaskEdit::EDITABLE;
        [$labels, $records, $total] = $this->chronoEdiSnapshot($editable, 200);

        $payload = app(TasksFilterMutateService::class)->exportPayload(
            $records,
            $editable,
            $this->ediChanges,
            $labels,
        );
        $payload['total_in_filter'] = $total;
        $payload['truncated'] = $total > count($records);

        $this->exportCount = count($records);
        $this->exportTotal = $total;
        $this->exportJson = json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        ) ?: '';

        if ($this->exportCount === 0) {
            $this->chronoError = 'Filtr nie zwraca żadnych zadań do eksportu Ediego.';
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function chronoExportRow(mixed $item): array
    {
        if ($item instanceof WorkItem) {
            $source = $item->source;
            $parentTask = $source instanceof TaskSubtask ? $source->task : null;
            $projectTask = $source instanceof ProjectTask ? $source : null;

            return [
                'id' => $item->id,
                'type' => $item->type instanceof WorkItemType ? $item->type->value : (string) $item->type,
                'type_label' => $item->type instanceof WorkItemType ? $item->type->label() : null,
                'source_type' => $item->source_type,
                'source_id' => $item->source_id,
                'name' => (string) $item->title,
                'description' => $item->plainDescription(),
                'status' => $this->chronoStatusValue($item->status),
                'priority' => $item->priority,
                'category' => $item->category,
                'sprint_id' => $item->sprint_id,
                'sprint' => $item->sprint?->name,
                'assigned_to' => $item->assignee_id,
                'assignee' => $item->assignedTo?->name,
                'created_by' => $item->created_by_id,
                'created_by_name' => $item->createdBy?->name,
                'due_date' => $item->due_at?->toDateString(),
                'comments_count' => (int) ($projectTask?->comments_count ?? 0),
                'url' => $item->openUrl(),
                'parent' => $parentTask ? [
                    'id' => $parentTask->id,
                    'name' => $parentTask->name,
                ] : null,
                'approval_decision' => $item->approvalDecision()?->value,
                'subtasks' => $projectTask ? $this->chronoExportSubtasks($projectTask) : [],
                'created_at' => $item->created_at?->toIso8601String(),
                'updated_at' => $item->updated_at?->toIso8601String(),
            ];
        }

        /** @var ProjectTask $item */
        $item->loadMissing(['subtasks.assignedTo', 'assignedTo', 'createdBy', 'sprint']);

        return [
            'id' => $item->id,
            'type' => WorkItemType::Task->value,
            'type_label' => WorkItemType::Task->label(),
            'source_type' => $item->getMorphClass(),
            'source_id' => $item->id,
            'name' => (string) $item->name,
            'description' => method_exists($item, 'plainDescription') ? $item->plainDescription() : (string) ($item->description ?? ''),
            'status' => $this->chronoStatusValue($item->status),
            'priority' => $item->priority,
            'category' => $item->category,
            'sprint_id' => $item->sprint_id,
            'sprint' => $item->sprint?->name,
            'assigned_to' => $item->assigned_to,
            'assignee' => $item->assignedTo?->name,
            'created_by' => $item->created_by,
            'created_by_name' => $item->createdBy?->name,
            'due_date' => $item->due_date?->toDateString(),
            'comments_count' => (int) ($item->comments_count ?? 0),
            'url' => route('tasks.show', $item),
            'parent' => null,
            'approval_decision' => null,
            'subtasks' => $this->chronoExportSubtasks($item),
            'created_at' => $item->created_at?->toIso8601String(),
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function chronoExportSubtasks(ProjectTask $task): array
    {
        $task->loadMissing(['subtasks.assignedTo']);

        return $task->subtasks->map(fn (TaskSubtask $subtask) => [
            'id' => $subtask->id,
            'name' => (string) $subtask->name,
            'is_completed' => (bool) $subtask->is_completed,
            'sort_order' => $subtask->sort_order,
            'assigned_to' => $subtask->assigned_to,
            'assignee' => $subtask->assignedTo?->name,
            'created_by' => $subtask->created_by,
        ])->values()->all();
    }

    public function downloadChronoCsv()
    {
        return TaskExport::csv($this->filteredTasksQuery());
    }

    public function downloadChronoExport()
    {
        if ($this->exportJson === '') {
            if ($this->chronoMode === 'edi-export') {
                $this->buildEdiExport();
            } else {
                $this->buildChronoExport();
            }
        }

        if ($this->exportJson === '') {
            $this->chronoError = 'Brak danych do pobrania.';

            return null;
        }

        $filename = $this->chronoMode === 'edi-export'
            ? 'edi-zmiany-'.now()->format('Y-m-d-His').'.json'
            : 'zadania-filtr-'.now()->format('Y-m-d-His').'.json';

        return response()->streamDownload(
            function () {
                echo $this->exportJson;
            },
            $filename,
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }

    /**
     * @return array<string, string>
     */
    public function currentQueryParams(): array
    {
        return TasksGridUrlParams::normalize([
            'view' => $this->view,
            'searchTask' => $this->searchTask,
            'searchCategory' => $this->searchCategory,
            'searchAssignedTo' => $this->searchAssignedTo,
            'priority' => $this->filterPriority,
            'due' => $this->filterDueDate,
            'sprint' => $this->filterSprint,
            'status' => $this->status,
            'assignedFilter' => count($this->assignedFilterKeys()) === 1 ? $this->assignedFilterKeys()[0] : '',
            'createdByFilter' => count($this->createdByFilterKeys()) === 1 ? $this->createdByFilterKeys()[0] : '',
            'statuses' => $this->selectedStatuses,
            'assigned' => $this->assignedFilterKeys(),
            'createdBy' => $this->createdByFilterKeys(),
            'types' => $this->selectedTypes,
            'join' => $this->filterJoin,
            'groupBy' => $this->groupBy,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ]);
    }

    protected function gridViewsTableExists(): bool
    {
        static $exists = null;

        return $exists ??= Schema::hasTable('task_grid_views');
    }

    protected function invalidateViewCounts(): void
    {
        $this->viewCountsCache = [];
        WorkItemListNavigator::forget();
    }

    /**
     * @return array{0: Collection<int, TaskSubtask>, 1: int, 2: int}
     */
    public function rowSubtaskStats(ProjectTask|WorkItem $task, bool $expanded): array
    {
        $src = $task instanceof WorkItem
            ? ($task->source instanceof ProjectTask ? $task->source : null)
            : $task;

        if (! $src) {
            return [collect(), 0, 0];
        }

        if ($expanded && $src->relationLoaded('subtasks')) {
            $all = $src->subtasks->sortBy(['sort_order', 'created_at']);

            return [$all, $all->count(), $all->where('is_completed', true)->count()];
        }

        return [
            collect(),
            (int) ($src->subtasks_count ?? 0),
            (int) ($src->subtasks_completed_count ?? 0),
        ];
    }

    protected function hydrateExpandedSubtasks(mixed $tasks, mixed $groupedTasks): void
    {
        if ($this->expandedTasks === []) {
            return;
        }

        $expanded = array_map('intval', $this->expandedTasks);

        if ($groupedTasks instanceof Collection) {
            $rows = $groupedTasks->flatten();
        } elseif ($tasks instanceof Paginator) {
            $rows = collect($tasks->items());
        } elseif ($tasks instanceof Collection) {
            $rows = $tasks;
        } else {
            return;
        }

        $projectTasks = $rows
            ->map(function ($row) use ($expanded) {
                if (! in_array((int) $row->id, $expanded, true)) {
                    return null;
                }
                if ($row instanceof ProjectTask) {
                    return $row;
                }
                if ($row instanceof WorkItem && $row->source instanceof ProjectTask) {
                    return $row->source;
                }

                return null;
            })
            ->filter()
            ->unique(fn (ProjectTask $task) => $task->id)
            ->values();

        if ($projectTasks->isNotEmpty()) {
            (new EloquentCollection($projectTasks->all()))->load(['subtasks']);
        }
    }

    protected function countForSavedView(TaskGridView $view): int
    {
        $previous = $this->filterSnapshot();
        $this->applyFilterFieldsFromView($view);
        $this->sanitizeFilterState();

        try {
            return $this->filteredTasksQuery()->count();
        } finally {
            $this->restoreFilterSnapshot($previous);
        }
    }

    protected function loadViewFromSlug(string $slug, bool $flash = true): void
    {
        $record = TaskGridView::findVisibleTo(auth()->user(), $slug);

        if (! $record) {
            if ($flash) {
                $this->flash = 'Nie znaleziono widoku.';
            }
            $this->view = '';
            $this->activeViewId = null;

            return;
        }

        $this->activateView($record);

        if ($flash) {
            $this->flash = "Załadowano „{$record->name}”.";
        }
    }

    protected function activateView(TaskGridView $record): void
    {
        $this->view = $record->slug;
        $this->activeViewId = $record->id;
        $this->applyViewRecord($record);
    }

    protected function applyViewRecord(TaskGridView $record): void
    {
        $this->batchingViewPersist = true;
        $this->visibleColumns = $record->visible_columns ?: $this->visibleColumns;
        $this->columnWidths = $record->column_widths ?? [];
        $this->groupBy = $record->group_by ?? '';
        $this->sortField = $record->sort_field ?: 'created_at';
        $this->sortDirection = $record->sort_direction ?: 'desc';
        $this->searchTask = $record->search_task ?? '';
        $this->searchCategory = $record->search_category ?? '';
        $this->searchAssignedTo = $record->search_assigned_to ?? '';
        $this->applyFilterFieldsFromView($record);
        $this->sanitizeRemovedProjectField();
        $this->hideGroupedColumn();
        $this->batchingViewPersist = false;
        $this->resetPage();
        $this->enforcePlanLocks();
        $this->persistGridChrome();
    }

    /**
     * Stare zapisane widoki mogły mieć kolumnę / grupowanie / sort po projekcie.
     */
    protected function sanitizeRemovedProjectField(): void
    {
        $this->visibleColumns = array_values(array_filter(
            $this->visibleColumns,
            fn ($col) => $col !== 'project'
        ));

        if (! $this->usesWorkItems()) {
            $this->visibleColumns = array_values(array_filter(
                $this->visibleColumns,
                fn ($col) => $col !== 'type'
            ));
        }

        if ($this->isLockedToSprint()) {
            $this->visibleColumns = array_values(array_filter(
                $this->visibleColumns,
                fn ($col) => $col !== 'sprint'
            ));
        }

        if ($this->visibleColumns === []) {
            $this->visibleColumns = ['name', 'status', 'sprint', 'category', 'assigned_to', 'created_by', 'priority', 'due_date', 'blocks', 'subtasks'];
            if ($this->usesWorkItems()) {
                $this->insertVisibleColumn('type');
            }
            if ($this->isLockedToSprint()) {
                $this->visibleColumns = array_values(array_filter(
                    $this->visibleColumns,
                    fn ($col) => $col !== 'sprint'
                ));
            }
        }

        $this->sanitizeFilterState();

        if ($this->groupBy === 'project') {
            $this->groupBy = '';
        }

        if ($this->sortField === 'project') {
            $this->sortField = 'created_at';
        }
    }

    protected function sanitizeFilterState(): void
    {
        $this->filterJoin = $this->filterJoin === 'or' ? 'or' : 'and';
        $defaults = $this->defaultFilterOps();
        $merged = array_merge($defaults, array_intersect_key($this->filterOps, $defaults));
        foreach ($merged as $key => $op) {
            $merged[$key] = $op === 'neq' ? 'neq' : 'eq';
        }
        $this->filterOps = $merged;

        $this->selectedStatuses = $this->normalizeStatusSelection($this->selectedStatuses);
        $this->assignedFilters = $this->normalizeUserFilterKeys($this->assignedFilters);
        $this->createdByFilters = $this->normalizeUserFilterKeys($this->createdByFilters);

        if ($this->assignedFilters === [] && $this->assignedFilter !== '') {
            $this->assignedFilters = $this->normalizeUserFilterKeys([$this->assignedFilter]);
        }
        if ($this->createdByFilters === [] && $this->createdByFilter !== '') {
            $this->createdByFilters = $this->normalizeUserFilterKeys([$this->createdByFilter]);
        }

        $looksDefault = $this->sortedCopy($this->selectedStatuses) === $this->sortedCopy($this->defaultStatuses());
        $status = $this->status === 'active' ? '' : $this->status;
        if ($looksDefault && $status !== '' && $status !== 'mixed' && $status !== 'none') {
            $this->selectedStatuses = $this->statusesFromBucket($status);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterSnapshot(): array
    {
        return [
            'searchTask' => $this->searchTask,
            'searchCategory' => $this->searchCategory,
            'searchAssignedTo' => $this->searchAssignedTo,
            'filterPriority' => $this->filterPriority,
            'filterDueDate' => $this->filterDueDate,
            'filterSprint' => $this->filterSprint,
            'status' => $this->status,
            'selectedStatuses' => $this->selectedStatuses,
            'assignedFilter' => $this->assignedFilter,
            'assignedFilters' => $this->assignedFilters,
            'createdByFilter' => $this->createdByFilter,
            'createdByFilters' => $this->createdByFilters,
            'selectedTypes' => $this->selectedTypes,
            'filterJoin' => $this->filterJoin,
            'filterOps' => $this->filterOps,
        ];
    }

    /**
     * @param  array<string, mixed>  $previous
     */
    protected function restoreFilterSnapshot(array $previous): void
    {
        foreach ($previous as $property => $value) {
            $this->{$property} = $value;
        }
    }

    protected function applyFilterFieldsFromView(TaskGridView $view): void
    {
        $this->searchTask = $view->search_task ?? '';
        $this->searchCategory = $view->search_category ?? '';
        $this->searchAssignedTo = $view->search_assigned_to ?? '';
        $this->filterPriority = '';
        $this->filterDueDate = '';
        $this->filterSprint = '';
        $this->status = $view->status ?? '';
        $this->selectedTypes = $view->type_filter ?: $this->defaultSelectedTypes();
        $this->filterJoin = ($view->filter_join ?? 'and') === 'or' ? 'or' : 'and';
        $this->filterOps = is_array($view->filter_ops) ? $view->filter_ops : $this->defaultFilterOps();

        if (is_array($view->status_filter) && $view->status_filter !== []) {
            $this->selectedStatuses = $this->normalizeStatusSelection($view->status_filter);
            $bucket = $this->statusBucketFromSelection($this->selectedStatuses);
            if ($bucket !== 'mixed' && $bucket !== 'none') {
                $this->status = $bucket;
            }
        } else {
            $this->selectedStatuses = $this->statusesFromBucket($this->status);
        }

        if (is_array($view->assigned_filters) && $view->assigned_filters !== []) {
            $this->assignedFilters = $this->normalizeUserFilterKeys($view->assigned_filters);
            $this->assignedFilter = count($this->assignedFilters) === 1 ? $this->assignedFilters[0] : '';
        } else {
            $this->assignedFilter = $view->assigned_filter ?? ($view->my_tasks_only ? 'me' : '');
            $this->assignedFilters = $this->assignedFilter !== '' ? [$this->assignedFilter] : [];
        }

        if (is_array($view->created_by_filters) && $view->created_by_filters !== []) {
            $this->createdByFilters = $this->normalizeUserFilterKeys($view->created_by_filters);
            $this->createdByFilter = count($this->createdByFilters) === 1 ? $this->createdByFilters[0] : '';
        } else {
            $this->createdByFilter = $view->created_by_filter ?? '';
            $this->createdByFilters = $this->createdByFilter !== '' ? [$this->createdByFilter] : [];
        }
    }

    /** @return list<string> */
    protected function statusesFromBucket(string $status): array
    {
        return match ($status) {
            'all' => $this->allStatusValues(),
            'closed' => $this->closedStatuses(),
            '', 'active' => $this->defaultStatuses(),
            default => TaskStatus::tryFrom($status) ? [$status] : $this->defaultStatuses(),
        };
    }

    /**
     * @param  list<string>  $selected
     */
    protected function statusBucketFromSelection(array $selected): string
    {
        $normalized = $this->normalizeStatusSelection($selected);
        if ($normalized === []) {
            return 'none';
        }
        if ($this->sortedCopy($normalized) === $this->sortedCopy($this->allStatusValues())) {
            return 'all';
        }
        if ($this->sortedCopy($normalized) === $this->sortedCopy($this->defaultStatuses())) {
            return '';
        }
        if ($this->sortedCopy($normalized) === $this->sortedCopy($this->closedStatuses())) {
            return 'closed';
        }
        if (count($normalized) === 1) {
            return $normalized[0];
        }

        return 'mixed';
    }

    protected function persistedStatusBucket(): string
    {
        $bucket = $this->statusBucketFromSelection($this->selectedStatuses);
        if ($bucket === 'mixed') {
            return $this->status === 'mixed' ? '' : $this->status;
        }

        return $bucket === 'none' ? 'all' : $bucket;
    }

    public function selectsAllStatuses(): bool
    {
        return $this->sortedCopy($this->selectedStatuses) === $this->sortedCopy($this->allStatusValues());
    }

    public function statusChipLabel(): string
    {
        return match ($this->statusBucketFromSelection($this->selectedStatuses)) {
            '' => 'Aktywne',
            'closed' => 'Zamknięte',
            'all' => 'Wszystkie',
            'none' => 'żaden (0 wyników)',
            default => implode(' lub ', array_map(
                fn (string $value) => TaskStatus::from($value)->label(),
                $this->normalizeStatusSelection($this->selectedStatuses)
            )),
        };
    }

    /**
     * @param  list<mixed>  $selected
     * @return list<string>
     */
    protected function normalizeStatusSelection(array $selected): array
    {
        $picked = [];
        foreach ($selected as $value) {
            $value = (string) $value;
            if (in_array($value, $this->allStatusValues(), true)) {
                $picked[$value] = true;
            }
        }

        $out = [];
        foreach ($this->allStatusValues() as $value) {
            if (isset($picked[$value])) {
                $out[] = $value;
            }
        }

        return $out;
    }

    /** @return list<string> */
    public function assignedFilterKeys(): array
    {
        if ($this->assignedFilters !== []) {
            return $this->assignedFilters;
        }

        return $this->assignedFilter !== '' ? [$this->assignedFilter] : [];
    }

    /** @return list<string> */
    public function createdByFilterKeys(): array
    {
        if ($this->createdByFilters !== []) {
            return $this->createdByFilters;
        }

        return $this->createdByFilter !== '' ? [$this->createdByFilter] : [];
    }

    /**
     * @param  list<string>  $keys
     */
    protected function userFilterChipLabel(array $keys): string
    {
        return implode(' lub ', $this->userFilterChipLabels($keys));
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    protected function userFilterChipLabels(array $keys): array
    {
        $ids = [];
        foreach ($keys as $key) {
            if ($key !== 'me' && ctype_digit((string) $key)) {
                $ids[] = (int) $key;
            }
        }
        $names = $ids === []
            ? collect()
            : User::query()->whereIn('id', $ids)->pluck('name', 'id');

        $labels = [];
        foreach ($keys as $key) {
            if ($key === 'me') {
                $labels[] = 'Ja';
            } elseif ($key === 'unassigned') {
                $labels[] = 'Nieprzypisane';
            } elseif (ctype_digit((string) $key)) {
                $labels[] = $names[(int) $key] ?? '#'.$key;
            }
        }

        return $labels;
    }

    /**
     * @param  list<mixed>  $keys
     * @return list<string>
     */
    protected function normalizeUserFilterKeys(array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $key = (string) $key;
            if (($key === 'me' || $key === 'unassigned' || ctype_digit($key)) && ! in_array($key, $out, true)) {
                $out[] = $key;
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    protected function toggleUserFilterKey(array $keys, string $key): array
    {
        $keys = $this->normalizeUserFilterKeys($keys);
        if ($key === 'unassigned') {
            return in_array('unassigned', $keys, true) ? [] : ['unassigned'];
        }
        if ($key !== 'me' && ! ctype_digit($key)) {
            return $keys;
        }

        $keys = array_values(array_filter($keys, fn (string $existing) => $existing !== 'unassigned'));

        if (in_array($key, $keys, true)) {
            return array_values(array_diff($keys, [$key]));
        }

        $keys[] = $key;

        return $keys;
    }

    /**
     * @param  list<string>  $keys
     * @return list<int>
     */
    protected function resolveUserFilterIds(array $keys): array
    {
        $ids = [];
        foreach ($keys as $key) {
            if ($key === 'me') {
                $id = (int) auth()->id();
                if ($id > 0) {
                    $ids[] = $id;
                }
            } elseif (ctype_digit((string) $key)) {
                $ids[] = (int) $key;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    protected function sortedCopy(array $values): array
    {
        $copy = array_values($values);
        sort($copy);

        return $copy;
    }

    protected function restoreGridChromeFromCookies(): void
    {
        if ($this->isLockedToSprint()) {
            return;
        }

        $cols = request()->cookie($this->chromeColumnsCookie());
        if (is_string($cols) && $cols !== '') {
            $parsed = $this->sanitizeVisibleColumns(explode(',', $cols));
            if ($parsed !== []) {
                $this->visibleColumns = $parsed;
            }
        }

        $groupFromUrl = request()->query('groupBy');
        if (! $this->isPlanQueue() && is_string($groupFromUrl) && $groupFromUrl !== '') {
            return;
        }

        $this->groupBy = $this->sanitizeGroupBy((string) request()->cookie($this->chromeGroupCookie(), ''));
    }

    /**
     * @param  list<string>|array<int, mixed>  $cols
     * @return list<string>
     */
    protected function sanitizeVisibleColumns(array $cols): array
    {
        $allowed = array_keys($this->availableColumns);
        $out = [];
        foreach ($cols as $col) {
            $col = trim((string) $col);
            if ($col !== '' && in_array($col, $allowed, true) && ! in_array($col, $out, true)) {
                $out[] = $col;
            }
        }

        if ($out === [] || ! in_array('name', $out, true)) {
            array_unshift($out, 'name');
            $out = array_values(array_unique($out));
        }

        return $out;
    }

    protected function sanitizeGroupBy(string $field): string
    {
        if ($field === '' || $field === 'name') {
            return '';
        }

        if ($field === 'sprint' && $this->isLockedToSprint()) {
            return '';
        }

        if ($field === 'type' && ! $this->usesWorkItems()) {
            return '';
        }

        return array_key_exists($field, $this->availableColumns) ? $field : '';
    }

    protected function persistGridChrome(): void
    {
        if ($this->isLockedToSprint()) {
            return;
        }

        $minutes = 60 * 24 * 365;
        cookie()->queue($this->chromeColumnsCookie(), implode(',', $this->visibleColumns), $minutes);
        cookie()->queue($this->chromeGroupCookie(), $this->groupBy, $minutes);
    }

    protected function chromeColumnsCookie(): string
    {
        return $this->isPlanQueue() ? 'tg_plan_cols' : 'tg_cols';
    }

    protected function chromeGroupCookie(): string
    {
        return $this->isPlanQueue() ? 'tg_plan_group' : 'tg_group';
    }

    protected function hideGroupedColumn(): void
    {
        if ($this->groupBy === '' || $this->groupBy === 'name') {
            return;
        }

        if (! in_array($this->groupBy, $this->visibleColumns, true)) {
            return;
        }

        $this->visibleColumns = array_values(array_filter(
            $this->visibleColumns,
            fn ($col) => $col !== $this->groupBy
        ));
    }

    protected function syncColumnsAfterGroupChange(string $previous): void
    {
        if ($previous !== '' && $previous !== $this->groupBy) {
            $this->insertVisibleColumn($previous);
        }

        $this->hideGroupedColumn();
    }

    protected function insertVisibleColumn(string $key): void
    {
        if (in_array($key, $this->visibleColumns, true)) {
            return;
        }

        $canonical = array_keys($this->availableColumns);
        $targetIdx = array_search($key, $canonical, true);
        $insertAt = count($this->visibleColumns);

        if ($targetIdx !== false) {
            foreach ($this->visibleColumns as $i => $col) {
                $colIdx = array_search($col, $canonical, true);
                if ($colIdx !== false && $colIdx > $targetIdx) {
                    $insertAt = $i;
                    break;
                }
            }
        }

        array_splice($this->visibleColumns, $insertAt, 0, [$key]);
        $this->visibleColumns = array_values($this->visibleColumns);
    }

    protected function viewPayload(): array
    {
        return [
            'visible_columns' => $this->visibleColumns,
            'column_widths' => $this->columnWidths,
            'group_by' => $this->groupBy,
            'sort_field' => $this->sortField,
            'sort_direction' => $this->sortDirection,
            'search_task' => $this->searchTask,
            'search_project' => '',
            'search_category' => $this->searchCategory,
            'search_assigned_to' => $this->searchAssignedTo,
            'status' => $this->persistedStatusBucket(),
            'status_filter' => $this->selectedStatuses,
            'my_tasks_only' => $this->assignedFilterKeys() === ['me'],
            'assigned_filter' => count($this->assignedFilterKeys()) === 1 ? $this->assignedFilterKeys()[0] : '',
            'assigned_filters' => $this->assignedFilterKeys(),
            'created_by_filter' => count($this->createdByFilterKeys()) === 1 ? $this->createdByFilterKeys()[0] : '',
            'created_by_filters' => $this->createdByFilterKeys(),
            'type_filter' => $this->selectedTypes,
            'filter_join' => $this->filterJoin,
            'filter_ops' => $this->filterOps,
        ];
    }

    protected function detachActiveView(): void
    {
        if ($this->view === '' && $this->activeViewId === null) {
            return;
        }

        $this->view = '';
        $this->activeViewId = null;
    }

    protected function findVisibleView(int $id): ?TaskGridView
    {
        return TaskGridView::query()
            ->visibleTo(auth()->user())
            ->whereKey($id)
            ->first();
    }

    protected function visibleSlugTaken(string $slug, ?int $exceptId = null): bool
    {
        return TaskGridView::query()
            ->visibleTo(auth()->user())
            ->where('slug', $slug)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'widok';
        $slug = $base;
        $i = 2;

        while ($this->visibleSlugTaken($slug)) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function canEditTask(ProjectTask|WorkItem $task): bool
    {
        if ($task instanceof WorkItem) {
            return $this->canEditWorkItem($task);
        }

        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return app(ProjectTaskPolicy::class)->updateStatus($user, $task);
    }

    /**
     * Uprawnienia dla WorkItemu, który mamy już w pamięci (np. wiersz z grida,
     * wczytany raz w render() z pełnym eager-loadem). W przeciwieństwie do
     * canEditRow() NIE odpytuje bazy ponownie — stąd trzeba go wołać zawsze,
     * gdy obiekt WorkItem jest już dostępny (patrz rowWritable/rowRelocatable),
     * inaczej każde wywołanie na wiersz to dodatkowe zapytanie (klasyczny N+1
     * przy tabeli z wieloma wierszami i kolumnami do edycji).
     */
    protected function canEditWorkItem(WorkItem $item): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        $task = $item->editableProjectTask();
        if ($task) {
            return app(ProjectTaskPolicy::class)->updateStatus($user, $task);
        }

        return $user->isAdmin() || $user->hasPermission('tasks.update');
    }

    /**
     * Wariant canEditWorkItem() dla wywołań, w których mamy tylko ID (np. z JS/Alpine
     * albo akcji dotyczącej jednego konkretnego wiersza) — tu doczytanie z bazy jest
     * uzasadnione, bo dotyczy pojedynczego wiersza, nie całej listy.
     */
    public function canEditRow(int $rowId): bool
    {
        $item = $this->resolveWorkItem($rowId);
        if ($item) {
            return $this->canEditWorkItem($item);
        }

        $user = auth()->user();
        if (! $user) {
            return false;
        }

        $task = ProjectTask::query()->find($rowId);

        return $task ? app(ProjectTaskPolicy::class)->updateStatus($user, $task) : false;
    }

    /**
     * Podzadania wiszą tylko na kartach-zadaniach. Procedura / kompletacja / wzmianka
     * mogą mieć ukryty project_task, ale nie pokazują checklisty — drop tam gubi wiersz.
     */
    protected function acceptsDroppedSubtasks(int $rowId): bool
    {
        $item = $this->resolveWorkItem($rowId);
        if ($item) {
            return $item->supports(GridField::Subtasks);
        }

        return $this->resolveProjectTask($rowId) !== null;
    }

    public function rowSupports(ProjectTask|WorkItem $row, string $field): bool
    {
        if ($row instanceof WorkItem) {
            return $row->supports($field);
        }

        return $field !== 'type';
    }

    public function rowWritable(ProjectTask|WorkItem|int $row, string $field): bool
    {
        if (is_int($row)) {
            $item = $this->resolveWorkItem($row);
            if ($item) {
                return $item->writable($field) && $this->canEditWorkItem($item);
            }
            $task = $this->resolveProjectTask($row);

            return $task && $this->canEditTask($task) && $field !== 'type';
        }

        if ($row instanceof WorkItem) {
            return $row->writable($field) && $this->canEditWorkItem($row);
        }

        return $this->canEditTask($row) && $field !== 'type';
    }

    public function rowCanDrag(ProjectTask|WorkItem $row): bool
    {
        if ($this->groupBy === '' || $this->groupBy === 'type') {
            return false;
        }

        return $this->rowRelocatable($row, $this->groupBy);
    }

    public function rowRelocatable(ProjectTask|WorkItem|int $row, string $field): bool
    {
        if (is_int($row)) {
            $item = $this->resolveWorkItem($row);
            if ($item) {
                return $item->relocatable($field) && $this->canEditWorkItem($item);
            }
            $task = $this->resolveProjectTask($row);

            return $task && $this->canEditTask($task) && $field !== 'type';
        }

        if ($row instanceof WorkItem) {
            return $row->relocatable($field) && $this->canEditWorkItem($row);
        }

        return $this->canEditTask($row) && $field !== 'type';
    }

    public function rowExpandable(ProjectTask|WorkItem|int $row): bool
    {
        if (is_int($row)) {
            return $this->resolveWorkItem($row)?->expandable() ?? true;
        }

        if ($row instanceof WorkItem) {
            return $row->expandable();
        }

        return true;
    }

    public function rowStatusWidget(ProjectTask|WorkItem $row): StatusWidget
    {
        if ($row instanceof WorkItem) {
            return $row->statusWidget();
        }

        return StatusWidget::TaskSelect;
    }

    public function rowStatusLabel(ProjectTask|WorkItem $row): string
    {
        if ($row instanceof WorkItem) {
            return $row->statusLabel();
        }

        return $row->status->label();
    }

    public function rowTypeLabel(ProjectTask|WorkItem $row): string
    {
        if ($row instanceof WorkItem) {
            return $row->type->label();
        }

        return WorkItemType::Task->label();
    }

    public function rowTypeIcon(ProjectTask|WorkItem $row): string
    {
        if ($row instanceof WorkItem) {
            return $row->type->icon();
        }

        return WorkItemType::Task->icon();
    }

    protected function resolveWorkItem(int $id): ?WorkItem
    {
        if (! $this->usesWorkItems()) {
            return null;
        }

        $item = WorkItem::query()->with(['source', 'assignedTo', 'sprint'])->find($id);
        if ($item) {
            return $item;
        }

        return WorkItem::query()
            ->with(['source', 'assignedTo', 'sprint'])
            ->where('source_type', 'project_task')
            ->where('source_id', $id)
            ->first();
    }

    protected function resolveProjectTask(int $id): ?ProjectTask
    {
        if ($this->usesWorkItems()) {
            $item = $this->resolveWorkItem($id);
            if ($item) {
                return $item->editableProjectTask();
            }
        }

        return ProjectTask::query()->find($id);
    }

    /**
     * Przenosi zadanie do innej grupy w widoku grupowanym (jak na tablicy Kanban).
     * Zmienia pole, po którym aktualnie grupujemy: osobę, sprint, kategorię, status albo priorytet.
     */
    public function moveTaskToGroup(int $taskId, mixed $groupValue): void
    {
        $field = GridField::tryFrom($this->groupBy);
        if (! $field || ! $field->isGroupable()) {
            return;
        }

        $groupValue = $groupValue === null ? '' : (string) $groupValue;

        $item = $this->resolveWorkItem($taskId);
        if ($item) {
            if (! $item->relocatable($field)) {
                $this->flash = 'Tej pozycji nie przenosi się w tej grupie.';

                return;
            }
            if ($this->groupValueFor($item) === $groupValue) {
                return;
            }
            if (! $this->canEditWorkItem($item)) {
                return;
            }

            $item->handler()->write($item, $field, $groupValue);
            $this->invalidateViewCounts();
            $this->flash = 'Zadanie przeniesione.';

            return;
        }

        $task = $this->resolveProjectTask($taskId);
        if (! $task || ! $this->canEditTask($task)) {
            return;
        }

        if ($this->groupValueFor($task) === $groupValue) {
            return;
        }

        match ($this->groupBy) {
            'status' => $this->applyGroupedStatusChange($task, $groupValue),
            'sprint' => $this->applyGroupedSprintChange($task, $groupValue),
            'category' => $this->applyGroupedCategoryChange($task, $groupValue),
            'assigned_to' => $this->applyGroupedAssigneeChange($task, $groupValue),
            'priority' => $this->applyGroupedPriorityChange($task, $groupValue),
            default => null,
        };

        $this->invalidateViewCounts();
        $this->flash = 'Zadanie przeniesione.';
    }

    protected function applyGroupedStatusChange(ProjectTask $task, string $value): void
    {
        if (TaskStatus::tryFrom($value) === null) {
            return;
        }

        $this->applyStatusChange($task, $value);
    }

    protected function applySprintChange(ProjectTask $task, string $value): void
    {
        $this->applyGroupedSprintChange($task, $value);
    }

    protected function applyGroupedSprintChange(ProjectTask $task, string $value): void
    {
        if ($this->isLockedToSprint()) {
            return;
        }

        if ($value === '') {
            $task->update(['sprint_id' => null, 'sprint_position' => null]);

            return;
        }

        $sprintId = (int) $value;
        if ($sprintId < 1 || ! Sprint::query()->where('id', $sprintId)->exists()) {
            return;
        }

        $position = (int) ProjectTask::query()->where('sprint_id', $sprintId)->max('sprint_position') + 1;
        $task->update([
            'sprint_id' => $sprintId,
            'sprint_position' => $position,
        ]);
    }

    protected function applyGroupedCategoryChange(ProjectTask $task, string $value): void
    {
        $category = $value === '' ? null : mb_substr(trim($value), 0, 255);
        $task->update(['category' => $category === '' ? null : $category]);
    }

    protected function applyGroupedAssigneeChange(ProjectTask $task, string $value): void
    {
        if ($value !== '') {
            $userId = (int) $value;
            if ($userId < 1 || ! User::query()->where('id', $userId)->exists()) {
                return;
            }
        }

        $this->applyAssigneeChange($task, $value);
    }

    protected function applyGroupedPriorityChange(ProjectTask $task, string $value): void
    {
        if ($value === '') {
            $task->update(['priority' => null]);

            return;
        }

        $priority = (int) $value;
        if (! in_array($priority, [1, 2, 3, 4, 5], true)) {
            return;
        }

        $task->update(['priority' => $priority]);
    }

    public function moveSubtask(int $subtaskId, int $targetTaskId, ?int $afterSubtaskId = null): void
    {
        $subtask = TaskSubtask::find($subtaskId);
        if (! $subtask) {
            return;
        }

        if (! $this->acceptsDroppedSubtasks($targetTaskId)) {
            $this->flash = 'Podzadania można przenosić tylko na zadania.';

            return;
        }

        $targetTask = $this->resolveProjectTask($targetTaskId);

        if (! $targetTask || ! $this->canEditTask($targetTask)) {
            return;
        }

        $sourceTaskId = $subtask->task_id;
        $resolvedTargetId = $targetTask->id;
        $isCrossTask = $sourceTaskId !== $resolvedTargetId;

        // #N references in comments are computed from created_at/id order, independent of
        // sort_order — capture the "before" numbering of the source task so we can reconcile
        // comment references once the subtask has moved out of it.
        $sourceTask = $isCrossTask ? ProjectTask::with('subtasks')->find($sourceTaskId) : null;
        $oldSourceMap = $sourceTask?->subtaskDisplayNumbers() ?? [];
        $oldNumber = $oldSourceMap[$subtaskId] ?? null;

        // Grid rows pass work_item ids; the FK on task_subtasks.task_id is project_tasks.id.
        $subtask->update(['task_id' => $resolvedTargetId]);

        if ($isCrossTask) {
            TaskSubtaskEvent::log($subtask, 'moved', auth()->id());
        }

        // Re-compute sort_order within the target task
        $siblings = TaskSubtask::where('task_id', $resolvedTargetId)
            ->where('id', '!=', $subtaskId)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->pluck('id')
            ->toArray();

        if ($afterSubtaskId && in_array($afterSubtaskId, $siblings)) {
            $pos = array_search($afterSubtaskId, $siblings);
            $newOrder = array_merge(
                array_slice($siblings, 0, $pos + 1),
                [$subtaskId],
                array_slice($siblings, $pos + 1),
            );
        } else {
            $newOrder = array_merge($siblings, [$subtaskId]);
        }

        foreach ($newOrder as $i => $id) {
            TaskSubtask::where('id', $id)->update(['sort_order' => $i + 1]);
        }

        if ($isCrossTask && $sourceTask && $oldNumber !== null) {
            $newSourceMap = $sourceTask->refresh()->subtaskDisplayNumbers();
            $newTargetMap = $targetTask->refresh()->subtaskDisplayNumbers();
            $newNumber = $newTargetMap[$subtaskId] ?? null;

            $this->migrateSubtaskCommentReferences(
                $sourceTask,
                $targetTask,
                $subtask,
                $oldNumber,
                $newNumber,
                $oldSourceMap,
                $newSourceMap,
            );
        }

        $this->flash = 'Podzadanie przeniesione.';
    }

    /**
     * Po przeniesieniu podzadania między zadaniami numeracja "#N" w treści komentarzy
     * (liczona po created_at/id) się rozjeżdża. Dla komentarzy odnoszących się WYŁĄCZNIE
     * do przenoszonego podzadania — przenosimy cały komentarz razem z nim i przeliczamy numer.
     * Dla komentarzy mieszanych (odnoszących się też do podzadań, które zostają) —
     * zostają na starym zadaniu, ale ich numery są przeliczane, a odniesienie do
     * przeniesionego podzadania zamieniane na czytelną notatkę (by nie wskazywało po cichu
     * na inne podzadanie po przenumerowaniu).
     */
    protected function migrateSubtaskCommentReferences(
        ProjectTask $sourceTask,
        ProjectTask $targetTask,
        TaskSubtask $movedSubtask,
        int $oldNumber,
        ?int $newNumber,
        array $oldSourceMap,
        array $newSourceMap,
    ): void {
        $stillPresentIds = $newSourceMap; // subtaskId => newNumber, for subtasks remaining in source

        // oldNumber => subtaskId, restricted to subtasks that stayed in the source task
        $oldNumberToStillPresentId = [];
        foreach ($oldSourceMap as $id => $num) {
            if (array_key_exists($id, $stillPresentIds)) {
                $oldNumberToStillPresentId[$num] = $id;
            }
        }

        $regex = \App\Services\UserMentionService::SUBTASK_REF_REGEX;

        $comments = $sourceTask->comments()->get();

        foreach ($comments as $comment) {
            $body = (string) $comment->body;
            if ($body === '' || ! preg_match($regex, $body)) {
                continue;
            }

            preg_match_all($regex, $body, $all);
            $numbers = array_map('intval', $all[1] ?? []);

            $referencesMoved = in_array($oldNumber, $numbers, true);

            $referencesOtherValid = false;
            foreach ($numbers as $n) {
                if ($n !== $oldNumber && isset($oldNumberToStillPresentId[$n])) {
                    $referencesOtherValid = true;
                    break;
                }
            }

            if (! $referencesMoved && ! $referencesOtherValid) {
                // No reference to the moved subtask, and no reference to a still-present
                // subtask whose number shifted — nothing in this comment needs updating.
                continue;
            }

            if ($referencesMoved && ! $referencesOtherValid && $newNumber !== null) {
                // Comment is only about the moved subtask — move it along and renumber.
                $newBody = preg_replace_callback($regex, function ($m) use ($oldNumber, $newNumber) {
                    return ((int) $m[1] === $oldNumber) ? '#'.$newNumber : $m[0];
                }, $body);

                $comment->update([
                    'commentable_id' => $targetTask->id,
                    'body' => $newBody,
                ]);

                continue;
            }

            // Mixed comment — keep on source task, shift still-valid numbers, and replace the
            // dangling reference to the moved subtask with a readable note.
            $newBody = preg_replace_callback($regex, function ($m) use ($oldNumber, $oldNumberToStillPresentId, $newSourceMap, $movedSubtask, $targetTask) {
                $n = (int) $m[1];

                if ($n === $oldNumber) {
                    return '„'.$movedSubtask->name.'” (przeniesione do zadania „'.$targetTask->name.'”)';
                }

                if (isset($oldNumberToStillPresentId[$n])) {
                    return '#'.$newSourceMap[$oldNumberToStillPresentId[$n]];
                }

                return $m[0];
            }, $body);

            if ($newBody !== $body) {
                $comment->update(['body' => $newBody]);
            }
        }
    }

    public function reorderColumns(string $from, string $to): void
    {
        $order = $this->visibleColumns;
        $fromIdx = array_search($from, $order);
        $toIdx = array_search($to, $order);

        if ($fromIdx === false || $toIdx === false || $fromIdx === $toIdx) {
            return;
        }

        array_splice($order, $fromIdx, 1);
        array_splice($order, $toIdx, 0, [$from]);
        $this->visibleColumns = array_values($order);
        $this->detachActiveView();
        $this->persistGridChrome();
        $this->skipRender();
    }

    public function setColumnWidth(string $col, int $width): void
    {
        $this->columnWidths[$col] = max(50, min(1200, $width));
        $this->skipRender();
    }

    public function paginationView(): string
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function groupKeyFor(ProjectTask|WorkItem $task): string
    {
        return match ($this->groupBy) {
            'status' => $task instanceof WorkItem ? $task->statusLabel() : $task->status->label(),
            'sprint' => $task->sprint?->label() ?? 'Poza sprintem',
            'category' => $task->category ?? 'Brak kategorii',
            'assigned_to' => $task->assignedTo?->name ?? 'Nieprzypisane',
            'priority' => $task->priority ? "Priorytet {$task->priority}" : 'Brak priorytetu',
            'type' => $this->rowTypeLabel($task),
            default => 'Wszystkie',
        };
    }

    /**
     * Stabilny identyfikator grupy (ID / wartość pola), niezależny od etykiety na ekranie.
     */
    public function groupValueFor(ProjectTask|WorkItem $task): string
    {
        $assigneeId = $task instanceof WorkItem ? $task->assignee_id : $task->assigned_to;

        return match ($this->groupBy) {
            'status' => $task->status->value,
            'sprint' => $task->sprint_id ? (string) $task->sprint_id : '',
            'category' => $task->category ?? '',
            'assigned_to' => $assigneeId ? (string) $assigneeId : '',
            'priority' => $task->priority ? (string) $task->priority : '',
            'type' => $task instanceof WorkItem ? $task->type->value : WorkItemType::Task->value,
            default => '',
        };
    }

    protected function filteredTasksQuery(): Builder
    {
        if ($this->usesWorkItems()) {
            return $this->filteredWorkItemsQuery();
        }

        $query = ProjectTask::query();

        if ($this->isLockedToSprint()) {
            $query->where('project_tasks.sprint_id', $this->lockedSprintId);
        }

        $this->applyGridFilters($query);

        return $query;
    }

    protected function filteredWorkItemsQuery(): Builder
    {
        $query = WorkItem::query();
        $this->applyGridFilters($query);
        $this->applyPlanQueueConstraints($query);

        return $query;
    }

    protected function applyPlanQueueConstraints(Builder $query): void
    {
        if (! $this->isPlanQueue()) {
            return;
        }

        $user = User::query()->find((int) $this->planUserId);
        if (! $user) {
            $query->whereRaw('1 = 0');

            return;
        }

        app(WorkItemPlanService::class)->applyQueueConstraints(
            $query,
            $user,
            now(),
            $this->planPinId && (int) $this->planPinId > 0 ? (int) $this->planPinId : null,
        );
    }

    /**
     * @return list<\Closure(Builder): void>
     */
    protected function gridFilterClauses(): array
    {
        $workItems = $this->usesWorkItems();
        $clauses = [];

        if ($workItems) {
            $selected = $this->selectedTypes;
            $allTypes = $this->allWorkItemTypeValues();
            $selectedSorted = $selected;
            $allSorted = $allTypes;
            sort($selectedSorted);
            sort($allSorted);
            $selectsAll = $selected !== [] && $selectedSorted === $allSorted;
            $neq = $this->filterOp('selectedTypes') === 'neq';
            if ($neq) {
                if ($selected !== []) {
                    $clauses[] = function (Builder $q) use ($selected) {
                        $q->whereNotIn('work_items.type', $selected);
                    };
                }
            } elseif ($selected === [] || ! $selectsAll) {
                $clauses[] = function (Builder $q) use ($selected) {
                    $q->whereIn('work_items.type', $selected);
                };
            }
        }

        $statusCol = $workItems ? 'work_items.status' : 'project_tasks.status';
        if (! $this->selectsAllStatuses() || $this->filterOp('status') === 'neq') {
            $values = $this->selectedStatuses;
            $neq = $this->filterOp('status') === 'neq';
            $clauses[] = function (Builder $q) use ($statusCol, $values, $neq) {
                if ($neq) {
                    $q->whereNotIn($statusCol, $values);
                } else {
                    $q->whereIn($statusCol, $values);
                }
            };
        }

        $assigneeCol = $workItems ? 'work_items.assignee_id' : 'project_tasks.assigned_to';
        $assignedKeys = $this->assignedFilterKeys();
        if (in_array('unassigned', $assignedKeys, true)) {
            $neq = $this->filterOp('assignedFilter') === 'neq';
            $clauses[] = function (Builder $q) use ($assigneeCol, $neq) {
                if ($neq) {
                    $q->whereNotNull($assigneeCol);
                } else {
                    $q->whereNull($assigneeCol);
                }
            };
        } else {
            $assignedIds = $this->resolveUserFilterIds($assignedKeys);
            if ($assignedIds !== []) {
                $neq = $this->filterOp('assignedFilter') === 'neq';
                $clauses[] = function (Builder $q) use ($assigneeCol, $assignedIds, $neq) {
                    if ($neq) {
                        $q->where(fn (Builder $inner) => $inner->whereNull($assigneeCol)->orWhereNotIn($assigneeCol, $assignedIds));
                    } else {
                        $q->whereIn($assigneeCol, $assignedIds);
                    }
                };
            }
        }

        $createdCol = $workItems ? 'work_items.created_by_id' : 'project_tasks.created_by';
        $createdIds = $this->resolveUserFilterIds($this->createdByFilterKeys());
        if ($createdIds !== []) {
            $neq = $this->filterOp('createdByFilter') === 'neq';
            $clauses[] = function (Builder $q) use ($createdCol, $createdIds, $neq) {
                if ($neq) {
                    $q->where(fn (Builder $inner) => $inner->whereNull($createdCol)->orWhereNotIn($createdCol, $createdIds));
                } else {
                    $q->whereIn($createdCol, $createdIds);
                }
            };
        }

        if ($this->searchTask !== '') {
            $term = '%'.$this->searchTask.'%';
            $neq = $this->filterOp('searchTask') === 'neq';
            if ($workItems) {
                $clauses[] = function (Builder $q) use ($term, $neq) {
                    if ($neq) {
                        $q->where(fn (Builder $inner) => $inner
                            ->whereNull('work_items.title')
                            ->orWhere('work_items.title', 'not like', $term));
                    } else {
                        $q->where('work_items.title', 'like', $term);
                    }
                };
            } else {
                $clauses[] = function (Builder $q) use ($term, $neq) {
                    if ($neq) {
                        $q->where(function (Builder $inner) use ($term) {
                            $inner->where(fn (Builder $q2) => $q2
                                ->whereNull('project_tasks.name')
                                ->orWhere('project_tasks.name', 'not like', $term))
                                ->where(fn (Builder $q2) => $q2
                                    ->whereNull('project_tasks.description')
                                    ->orWhere('project_tasks.description', 'not like', $term));
                        });
                    } else {
                        $q->where(fn (Builder $inner) => $inner
                            ->where('project_tasks.name', 'like', $term)
                            ->orWhere('project_tasks.description', 'like', $term));
                    }
                };
            }
        }

        if ($this->searchCategory !== '') {
            $col = $workItems ? 'work_items.category' : 'project_tasks.category';
            $neq = $this->filterOp('searchCategory') === 'neq';
            $terms = $neq ? $this->splitPinnedValues($this->searchCategory) : [trim($this->searchCategory)];
            $clauses[] = function (Builder $q) use ($col, $terms, $neq) {
                foreach ($terms as $term) {
                    if ($term === '') {
                        continue;
                    }
                    if ($term === '__none__') {
                        if ($neq) {
                            $q->whereNotNull($col)->where($col, '!=', '');
                        } else {
                            $q->where(fn (Builder $inner) => $inner->whereNull($col)->orWhere($col, ''));
                        }

                        continue;
                    }

                    $like = '%'.$term.'%';
                    if ($neq) {
                        $q->where(fn (Builder $inner) => $inner->whereNull($col)->orWhere($col, 'not like', $like));
                    } else {
                        $q->where($col, 'like', $like);
                    }
                }
            };
        }

        if ($this->searchAssignedTo !== '') {
            $term = '%'.$this->searchAssignedTo.'%';
            $neq = $this->filterOp('searchAssignedTo') === 'neq';
            $clauses[] = function (Builder $q) use ($term, $neq) {
                if ($neq) {
                    $q->where(function (Builder $inner) use ($term) {
                        $inner->whereDoesntHave('assignedTo')
                            ->orWhereHas('assignedTo', fn ($u) => $u->where('name', 'not like', $term));
                    });
                } else {
                    $q->whereHas('assignedTo', fn ($u) => $u->where('name', 'like', $term));
                }
            };
        }

        if ($this->filterPriority !== '') {
            $col = $workItems ? 'work_items.priority' : 'project_tasks.priority';
            $neq = $this->filterOp('filterPriority') === 'neq';
            $values = $neq ? $this->splitPinnedValues($this->filterPriority) : [$this->filterPriority];
            $clauses[] = function (Builder $q) use ($col, $values, $neq) {
                if ($neq) {
                    foreach ($values as $value) {
                        if ($value === 'none') {
                            $q->whereNotNull($col);
                        } else {
                            $priority = (int) $value;
                            $q->where(fn (Builder $inner) => $inner->whereNull($col)->orWhere($col, '!=', $priority));
                        }
                    }

                    return;
                }

                $includeNone = in_array('none', $values, true);
                $nums = array_map('intval', array_values(array_filter($values, fn (string $value) => $value !== 'none')));
                $q->where(function (Builder $inner) use ($col, $includeNone, $nums) {
                    if ($nums !== []) {
                        $inner->whereIn($col, $nums);
                    }
                    if ($includeNone) {
                        $nums === [] ? $inner->whereNull($col) : $inner->orWhereNull($col);
                    }
                });
            };
        }

        if ($this->filterSprint !== '') {
            $col = $workItems ? 'work_items.sprint_id' : 'project_tasks.sprint_id';
            $neq = $this->filterOp('filterSprint') === 'neq';
            $values = $neq ? $this->splitPinnedValues($this->filterSprint) : [$this->filterSprint];
            $clauses[] = function (Builder $q) use ($col, $values, $neq) {
                if ($neq) {
                    foreach ($values as $value) {
                        if ($value === 'none') {
                            $q->whereNotNull($col);
                        } elseif (ctype_digit($value)) {
                            $sprintId = (int) $value;
                            $q->where(fn (Builder $inner) => $inner->whereNull($col)->orWhere($col, '!=', $sprintId));
                        }
                    }

                    return;
                }

                $includeNone = in_array('none', $values, true);
                $ids = array_map('intval', array_values(array_filter($values, fn (string $value) => ctype_digit($value))));
                $q->where(function (Builder $inner) use ($col, $includeNone, $ids) {
                    if ($ids !== []) {
                        $inner->whereIn($col, $ids);
                    }
                    if ($includeNone) {
                        $ids === [] ? $inner->whereNull($col) : $inner->orWhereNull($col);
                    }
                });
            };
        }

        if ($this->filterDueDate !== '') {
            $col = $workItems ? 'work_items.due_at' : 'project_tasks.due_date';
            $neq = $this->filterOp('filterDueDate') === 'neq';
            if ($this->filterDueDate === 'none') {
                $clauses[] = function (Builder $q) use ($col, $neq) {
                    if ($neq) {
                        $q->whereNotNull($col);
                    } else {
                        $q->whereNull($col);
                    }
                };
            } else {
                $day = $this->filterDueDate;
                $clauses[] = function (Builder $q) use ($col, $day, $neq) {
                    if ($neq) {
                        $q->whereDate($col, '>', $day);
                    } else {
                        $q->whereDate($col, '<=', $day);
                    }
                };
            }
        }

        return $clauses;
    }

    protected function applyGridFilters(Builder $query): void
    {
        $clauses = $this->gridFilterClauses();
        if ($clauses === []) {
            return;
        }

        foreach ($clauses as $apply) {
            $apply($query);
        }
    }

    public function render()
    {
        $this->enforcePlanLocks();
        $this->sanitizeRemovedProjectField();

        $savedViews = (! $this->isLockedToSprint() && ! $this->isPlanQueue() && $this->gridViewsTableExists())
            ? TaskGridView::query()
                ->visibleTo(auth()->user())
                ->orderByDesc('is_global')
                ->orderBy('name')
                ->get()
            : collect();

        $savedViewIds = $savedViews->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $cachedViewIds = collect($this->viewCountsCache)->keys()->map(fn ($id) => (int) $id)->sort()->values()->all();
        if ($this->viewCountsCache === [] || $savedViewIds !== $cachedViewIds) {
            $viewCounts = [];
            foreach ($savedViews as $savedView) {
                $viewCounts[$savedView->id] = $this->countForSavedView($savedView);
            }
            $this->viewCountsCache = $viewCounts;
        } else {
            $viewCounts = $this->viewCountsCache;
        }

        $query = $this->filteredTasksQuery();

        $subtaskCounts = [
            'subtasks',
            'subtasks as subtasks_completed_count' => fn ($q) => $q->where('is_completed', true),
        ];

        if ($this->usesWorkItems()) {
            $this->applyWorkItemSorting($query);
            $query->with([
                'assignedTo',
                'createdBy',
                'sprint',
                'timeBlocks',
                'source' => function (MorphTo $morphTo) use ($subtaskCounts) {
                    $morphTo->morphWith([
                        ProjectTask::class => ['procedureRun.subject', 'recruitmentProcess', 'subject'],
                        TaskSubtask::class => ['task', 'assignedTo'],
                        CommentMention::class => ['comment.commentable', 'assignedTo'],
                        \App\Models\ProcedureRun::class => ['task', 'template'],
                        \App\Models\WarehouseDispatch::class => ['tasks'],
                        \App\Models\ApprovalRequest::class => ['approver', 'decidedBy'],
                    ])->morphWithCount([
                        ProjectTask::class => array_merge(['comments'], $subtaskCounts),
                    ]);
                },
            ]);
        } else {
            if ($this->sortField === 'sprint') {
                $query->leftJoin('sprints', 'project_tasks.sprint_id', '=', 'sprints.id')
                    ->select('project_tasks.*')
                    ->orderBy('sprints.start_date', $this->sortDirection);
            } elseif (in_array($this->sortField, ['priority', 'due_date', 'sprint_position'])) {
                $query->orderByRaw("ISNULL(project_tasks.{$this->sortField}), project_tasks.{$this->sortField} {$this->sortDirection}");
            } else {
                $query->orderBy("project_tasks.{$this->sortField}", $this->sortDirection);
            }

            if ($this->sortField !== 'created_at' && $this->sortField !== 'sprint_position') {
                $query->orderBy('project_tasks.created_at', 'desc');
            }

            $eager = ['assignedTo', 'createdBy', 'procedureRun.subject', 'recruitmentProcess', 'subject'];
            if (! $this->isLockedToSprint()) {
                $eager[] = 'sprint';
            }

            $query->with($eager)->withCount(array_merge(['comments'], $subtaskCounts));
        }

        $ediIds = $this->ediReviewRowIds();

        if ($ediIds !== []) {
            $query->whereIn($this->usesWorkItems() ? 'work_items.id' : 'project_tasks.id', $ediIds);
            $groupedTasks = null;
            $tasks = $query->get();
        } elseif ($this->groupBy) {
            $allTasks = $query->limit(500)->get();
            $groupedTasks = $allTasks
                ->groupBy(fn ($task) => $this->groupValueFor($task))
                ->sortBy(fn ($tasks) => mb_strtolower($this->groupKeyFor($tasks->first())));
            $tasks = null;
        } else {
            $groupedTasks = null;
            $tasks = $query->paginate(50);
        }

        $this->rememberWorkItemList($tasks, $groupedTasks);
        $this->hydrateExpandedSubtasks($tasks, $groupedTasks);
        $listedRecords = $groupedTasks instanceof Collection
            ? $groupedTasks->flatten(1)
            : collect($tasks instanceof Collection ? $tasks : ($tasks?->items() ?? []));
        $this->listedIds = $listedRecords
            ->filter(fn ($record) => $this->rowSelectable($record))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $needsSprintOptions = $this->showAddRow
            || $this->editingField === 'sprint'
            || ($this->normalizedSelectedIds() !== [] && $this->bulkField === 'sprint')
            || (in_array('sprint', $this->visibleColumns, true) && ! $this->isLockedToSprint());
        $needsProcedureTemplates = $this->usesWorkItems() && $this->showAddRow && $this->addKind === 'procedure';
        $activeViewName = $this->activeViewId
            ? ($savedViews->firstWhere('id', $this->activeViewId)?->name ?? $this->view)
            : null;
        $filterChips = $this->activeFilterChips($activeViewName);
        $chronoOpen = $this->showChronoModal;

        return view('livewire.tasks-grid', [
            'tasks' => $tasks,
            'groupedTasks' => $groupedTasks,
            'allSprints' => $this->isLockedToSprint() || ! $needsSprintOptions
                ? collect()
                : Sprint::query()->orderByDesc('start_date')->get(),
            'allUsers' => User::orderedDirectory(),
            'procedureTemplates' => $needsProcedureTemplates
                ? ProcedureTemplate::query()->orderBy('name')->get(['id', 'name', 'subject_type'])
                : collect(),
            'availableColumns' => $this->availableColumns,
            'savedViews' => $savedViews,
            'viewCounts' => $viewCounts,
            'filterChips' => $filterChips,
            'activeViewName' => $activeViewName,
            'isMenuDefaultView' => auth()->user()?->usesGridAsDefaultTasksView($this->currentQueryParams()) ?? false,
            'llmConfigured' => $chronoOpen && app(LlmClient::class)->isConfigured(),
            'importFormatExample' => ($chronoOpen && $this->chronoMode === 'import')
                ? json_encode(
                    TasksFilterImportService::importFormatExample(),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                )
                : '',
            'chronoFilterLabels' => $chronoOpen
                ? array_map(fn (array $chip) => $chip['label'], $filterChips)
                : [],
            'chronoImportDefaults' => $chronoOpen ? $this->chronoImportDefaults() : [
                'category' => null,
                'assigned_to' => null,
                'sprint_id' => null,
                'assignee_label' => null,
            ],
            'chronoImportDefaultsHint' => $chronoOpen ? $this->chronoImportDefaultsHint() : '',
            'chronoItemCount' => $tasks instanceof \Illuminate\Contracts\Pagination\Paginator
                ? $tasks->total()
                : ($tasks?->count() ?? $groupedTasks?->flatten(1)->count()),
        ]);
    }

    protected function chronoImportDefaultsHint(): string
    {
        $defaults = $this->chronoImportDefaults();
        $bits = [];

        if ($defaults['category']) {
            $bits[] = 'kategoria: '.$defaults['category'];
        }
        if ($defaults['assignee_label']) {
            $bits[] = 'osoba: '.$defaults['assignee_label'];
        }
        if ($defaults['sprint_id']) {
            $bits[] = 'ten sprint';
        }

        return implode(', ', $bits);
    }

    protected function applyWorkItemSorting(Builder $query): void
    {
        if ($this->sortField === 'sprint') {
            $query->leftJoin('sprints', 'work_items.sprint_id', '=', 'sprints.id')
                ->select('work_items.*')
                ->orderBy('sprints.start_date', $this->sortDirection)
                ->orderByDesc('work_items.id');

            return;
        }

        $column = match ($this->sortField) {
            'name' => 'title',
            'due_date' => 'due_at',
            'status', 'created_at', 'updated_at', 'type', 'category', 'priority' => $this->sortField,
            default => 'created_at',
        };

        if (in_array($column, ['due_at'], true)) {
            $query->orderByRaw("ISNULL(work_items.{$column}), work_items.{$column} {$this->sortDirection}");
        } else {
            $query->orderBy("work_items.{$column}", $this->sortDirection);
        }

        if ($column !== 'created_at') {
            $query->orderByDesc('work_items.created_at');
        }
    }
}
