<?php

namespace App\Livewire;

use App\Contracts\Llm\LlmClient;
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
use App\Notifications\TaskAssigned;
use App\Policies\ProjectTaskPolicy;
use App\Services\Llm\TasksFilterImportService;
use App\Services\Llm\TasksFilterMutateService;
use App\Services\Llm\TasksFilterSummaryService;
use App\Services\ProcedureRunService;
use App\Services\TaskCreationService;
use App\Services\UserMentionService;
use App\Support\EdiTaskEdit;
use App\Support\Export\TaskExport;
use App\Support\TasksGridUrlParams;
use App\Support\WorkItemListNavigator;
use App\WorkItems\GridField;
use App\WorkItems\ProjectTaskFields;
use App\WorkItems\StatusWidget;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class TasksGrid extends Component
{
    use WithPagination;

    // Filters
    public string $searchTask = '';

    public string $searchCategory = '';

    public string $searchAssignedTo = '';

    /** Priorytet 1–5 z kliknięcia w komórkę. Pusty = bez tego wymiaru. */
    public string $filterPriority = '';

    /** Termin Y-m-d z kliknięcia w komórkę. Pusty = bez tego wymiaru. */
    public string $filterDueDate = '';

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
    public array $selectedTypes = ['task', 'subtask', 'procedure_run', 'dispatch', 'follow_up', 'approval'];

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
    public array $visibleColumns = ['name', 'type', 'status', 'sprint', 'category', 'assigned_to', 'created_by', 'priority', 'due_date', 'subtasks'];

    public array $columnWidths = [];

    // Saved views (slug in URL → ?view=moj-widok)
    public string $view = '';

    public string $saveViewName = '';

    public bool $saveViewAsGlobal = false;

    public ?int $activeViewId = null;

    /** Gdy ustawione, siatka pokazuje tylko zadania tego sprintu (np. na stronie sprintu). */
    public ?int $lockedSprintId = null;

    // Expanded rows (task IDs)
    public array $expandedTasks = [];

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

    // Inline add subtask
    public ?int $addingSubtaskForTask = null;

    public string $newSubtaskName = '';

    // Flash messages
    public ?string $flash = null;

    private bool $batchingViewPersist = false;

    private ?string $groupByBeforeUpdate = null;

    protected function queryString(): array
    {
        if ($this->isLockedToSprint()) {
            return [];
        }

        return [
            'searchTask' => ['except' => '', 'history' => true],
            'searchCategory' => ['except' => '', 'history' => true],
            'searchAssignedTo' => ['except' => '', 'history' => true],
            'filterPriority' => ['except' => '', 'as' => 'priority', 'history' => true],
            'filterDueDate' => ['except' => '', 'as' => 'due', 'history' => true],
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
        }

        $this->hideGroupedColumn();
    }

    public function isLockedToSprint(): bool
    {
        return (int) $this->lockedSprintId > 0;
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
        if (! $this->usesWorkItems()) {
            WorkItemListNavigator::forget();

            return;
        }

        if ($groupedTasks instanceof Collection) {
            WorkItemListNavigator::remember(
                $groupedTasks->flatten(1)->pluck('id')->map(fn ($id) => (int) $id)->take(500)->all()
            );

            return;
        }

        if ($tasks instanceof Paginator) {
            $idQuery = $this->filteredTasksQuery();
            $this->applyWorkItemSorting($idQuery);
            WorkItemListNavigator::remember(
                $idQuery->limit(500)->pluck('work_items.id')->map(fn ($id) => (int) $id)->all()
            );

            return;
        }

        if ($tasks instanceof Collection) {
            WorkItemListNavigator::remember(
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
            'due_date' => ['label' => 'Termin', 'sortable' => true],
            'subtasks' => ['label' => 'Podzadania', 'sortable' => false],
            'comments' => ['label' => 'Komentarze', 'sortable' => false],
            'created_at' => ['label' => 'Utworzono', 'sortable' => true],
            'updated_at' => ['label' => 'Zmodyfikowano', 'sortable' => true],
        ];
    }

    public function updating(string $name, mixed $value): void
    {
        if (in_array($name, ['searchTask', 'searchCategory', 'searchAssignedTo', 'filterPriority', 'filterDueDate', 'status', 'selectedStatuses', 'assignedFilter', 'assignedFilters', 'createdByFilter', 'createdByFilters', 'selectedTypes', 'filterJoin', 'filterOps'], true)) {
            $this->resetPage();
        }
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
        $this->batchingViewPersist = false;
        $this->resetPage();
        $this->detachActiveView();
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function activeFilterChips(): array
    {
        $chips = [];

        if ($this->view !== '') {
            $viewName = $this->activeViewId
                ? (TaskGridView::query()->visibleTo(auth()->user())->whereKey($this->activeViewId)->value('name') ?? $this->view)
                : (TaskGridView::findVisibleTo(auth()->user(), $this->view)?->name ?? $this->view);
            $chips[] = ['key' => 'view', 'label' => 'Widok: '.$viewName];
        }

        if ($this->searchTask !== '') {
            $neg = $this->filterOp('searchTask') === 'neq';
            $chips[] = ['key' => 'searchTask', 'label' => ($neg ? 'Szukaj ≠ ' : 'Szukaj: ').$this->searchTask];
        }

        if ($this->searchCategory !== '') {
            $neg = $this->filterOp('searchCategory') === 'neq';
            $chips[] = ['key' => 'searchCategory', 'label' => ($neg ? 'Kategoria ≠ ' : 'Kategoria: ').$this->searchCategory];
        }

        if ($this->searchAssignedTo !== '') {
            $neg = $this->filterOp('searchAssignedTo') === 'neq';
            $chips[] = ['key' => 'searchAssignedTo', 'label' => ($neg ? 'Osoba ≠ ' : 'Osoba: ').$this->searchAssignedTo];
        }

        if ($this->filterPriority !== '') {
            $chips[] = ['key' => 'filterPriority', 'label' => 'Priorytet: '.$this->priorityChipLabel($this->filterPriority)];
        }

        if ($this->filterDueDate !== '') {
            $chips[] = ['key' => 'filterDueDate', 'label' => 'Termin: '.$this->dueDateChipLabel($this->filterDueDate)];
        }

        // "all" to jedyna wartość statusu, która niczego nie odfiltrowuje —
        // chip pokazujemy dla każdej innej wartości, ŁĄCZNIE z domyślnym ""
        // (aktywne), bo to i tak realnie ukrywa zamknięte/anulowane zadania.
        // Wcześniej domyślne "" było traktowane jak "brak filtra" i chip się
        // nie pokazywał — stąd user widział np. 15 z 129 zadań bez żadnej
        // wskazówki, że coś jest odfiltrowane.
        if (! $this->selectsAllStatuses() || $this->filterOp('status') === 'neq') {
            $statusLabel = $this->statusChipLabel();
            $chips[] = ['key' => 'status', 'label' => 'Status: '.($this->filterOp('status') === 'neq' ? '≠ ' : '').$statusLabel];
        }

        $assignedKeys = $this->assignedFilterKeys();
        if ($assignedKeys !== []) {
            $neg = $this->filterOp('assignedFilter') === 'neq' ? '≠ ' : '';
            $chips[] = ['key' => 'assignedFilter', 'label' => 'Przypisany: '.$neg.$this->userFilterChipLabel($assignedKeys)];
        }

        $createdKeys = $this->createdByFilterKeys();
        if ($createdKeys !== []) {
            $neg = $this->filterOp('createdByFilter') === 'neq' ? '≠ ' : '';
            $chips[] = ['key' => 'createdByFilter', 'label' => 'Utworzono przez: '.$neg.$this->userFilterChipLabel($createdKeys)];
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

        if (in_array($key, ['searchTask', 'searchCategory', 'searchAssignedTo', 'filterPriority', 'filterDueDate'], true)) {
            $this->{$key} = '';
            if (isset($this->filterOps[$key])) {
                $this->filterOps[$key] = 'eq';
            }
            $this->resetPage();
            $this->detachActiveView();
        }
    }

    public function filterByCategory(string $category): void
    {
        $category = trim($category);
        if ($category === '') {
            return;
        }

        $this->searchCategory = mb_substr($category, 0, 255);
        $this->filterOps['searchCategory'] = 'eq';
        $this->resetPage();
        $this->detachActiveView();
    }

    public function filterByPriority(string $priority): void
    {
        if (! in_array($priority, ['1', '2', '3', '4', '5'], true)) {
            return;
        }

        $this->filterPriority = $priority;
        $this->resetPage();
        $this->detachActiveView();
    }

    public function filterByDueDate(string $date): void
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return;
        }

        $this->filterDueDate = $date;
        $this->resetPage();
        $this->detachActiveView();
    }

    protected function priorityChipLabel(string $priority): string
    {
        return match ($priority) {
            '1' => 'Najniższy',
            '2' => 'Niski',
            '3' => 'Średni',
            '4' => 'Wysoki',
            '5' => 'Krytyczny',
            default => $priority,
        };
    }

    protected function dueDateChipLabel(string $date): string
    {
        try {
            return \Carbon\Carbon::createFromFormat('Y-m-d', $date)->format('d.m.Y');
        } catch (\Throwable) {
            return $date;
        }
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
        $this->detachActiveView();
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
    }

    public function toggleExpand(int $taskId): void
    {
        if (! $this->rowExpandable($taskId)) {
            $this->expandedTasks = array_values(array_filter($this->expandedTasks, fn ($id) => $id !== $taskId));

            return;
        }

        if (in_array($taskId, $this->expandedTasks)) {
            $this->expandedTasks = array_values(array_filter($this->expandedTasks, fn ($id) => $id !== $taskId));
        } else {
            $this->expandedTasks[] = $taskId;
        }
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
        $previousAssignee = $task->assigned_to;
        $task->update(['assigned_to' => $newAssignee]);

        if ($newAssignee && $newAssignee !== $previousAssignee && $newAssignee !== auth()->id()) {
            $assignee = User::find($newAssignee);
            $assignee?->notify(new TaskAssigned($task->fresh(), auth()->user()));
        }
    }

    public function quickStatusChange(int $taskId, string $status): void
    {
        $item = $this->resolveWorkItem($taskId);
        if ($item) {
            if (! $item->writable(GridField::Status)) {
                return;
            }
            $item->handler()->write($item, GridField::Status, $status);
            $this->flash = 'Status zaktualizowany.';

            return;
        }

        $task = $this->resolveProjectTask($taskId);
        if (! $task || ! $this->canEditTask($task)) {
            return;
        }
        $this->applyStatusChange($task, $status);
        $this->flash = 'Status zaktualizowany.';
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

        $task = ProjectTask::create([
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

        if ($task->assigned_to && $task->assigned_to !== auth()->id()) {
            $assignee = User::find($task->assigned_to);
            $assignee?->notify(new TaskAssigned($task, auth()->user()));
        }

        $this->reset(['newTaskName', 'newTaskSprint', 'newTaskCategory', 'newTaskAssignedTo', 'newTaskPriority', 'newTaskDueDate', 'newProcedureTemplateId']);
        if ($this->isLockedToSprint()) {
            $this->newTaskSprint = (string) $this->lockedSprintId;
        }
        $this->showAddRow = false;
        $this->addKind = 'task';
        $this->flash = 'Zadanie dodane.';
    }

    public function startAdd(string $kind): void
    {
        if (! in_array($kind, ['task', 'procedure', 'approval'], true)) {
            return;
        }

        if (in_array($kind, ['procedure', 'approval'], true) && ! $this->usesWorkItems()) {
            return;
        }

        $this->addKind = $kind;
        $this->showAddRow = true;
        $this->reset(['newTaskName', 'newTaskCategory', 'newTaskAssignedTo', 'newTaskPriority', 'newTaskDueDate', 'newProcedureTemplateId']);
        if ($this->isLockedToSprint()) {
            $this->newTaskSprint = (string) $this->lockedSprintId;
        }
        $this->resetErrorBag();
    }

    public function cancelAdd(): void
    {
        $this->showAddRow = false;
        $this->addKind = 'task';
        $this->resetErrorBag();
    }

    public function updatedNewProcedureTemplateId(string $value): void
    {
        $template = ProcedureTemplate::query()->find((int) $value);
        if ($template) {
            $this->newTaskName = $template->name;
        }
    }

    public function submitAdd(): void
    {
        match ($this->addKind) {
            'procedure' => $this->startProcedureFromGrid(),
            'approval' => $this->addApproval(),
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
            'newTaskName' => 'required|string|max:255',
            'newTaskAssignedTo' => 'nullable|exists:users,id',
            'newTaskDueDate' => 'nullable|date',
        ], [], [
            'newProcedureTemplateId' => 'szablon procedury',
            'newTaskName' => 'nazwa',
        ]);

        $template = ProcedureTemplate::query()->findOrFail((int) $this->newProcedureTemplateId);

        try {
            app(ProcedureRunService::class)->startRun($template, [
                'task_name' => $this->newTaskName,
                'assigned_to' => $this->newTaskAssignedTo ?: null,
                'due_date' => $this->newTaskDueDate ?: null,
            ]);
        } catch (\RuntimeException $e) {
            $this->flash = $e->getMessage();

            return;
        }

        $this->reset(['newTaskName', 'newTaskSprint', 'newTaskCategory', 'newTaskAssignedTo', 'newTaskPriority', 'newTaskDueDate', 'newProcedureTemplateId']);
        $this->showAddRow = false;
        $this->addKind = 'task';
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

        $this->reset(['newTaskName', 'newTaskSprint', 'newTaskCategory', 'newTaskAssignedTo', 'newTaskPriority', 'newTaskDueDate', 'newProcedureTemplateId']);
        if ($this->isLockedToSprint()) {
            $this->newTaskSprint = (string) $this->lockedSprintId;
        }
        $this->showAddRow = false;
        $this->addKind = 'task';
        $this->flash = 'Prośba o zatwierdzenie wysłana.';
    }

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
        if (! in_array($taskId, $this->expandedTasks)) {
            $this->expandedTasks[] = $taskId;
        }
    }

    public function saveSubtask(): void
    {
        if (! $this->addingSubtaskForTask) {
            return;
        }

        $name = trim($this->newSubtaskName);
        if (! $name) {
            $this->addingSubtaskForTask = null;

            return;
        }

        $parent = $this->resolveProjectTask($this->addingSubtaskForTask);
        if (! $parent) {
            $this->addingSubtaskForTask = null;

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
    }

    public function cancelAddSubtask(): void
    {
        $this->addingSubtaskForTask = null;
        $this->newSubtaskName = '';
    }

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

        $category = trim($this->searchCategory) !== '' ? trim($this->searchCategory) : null;

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
            $this->visibleColumns = ['name', 'status', 'sprint', 'category', 'assigned_to', 'created_by', 'priority', 'due_date', 'subtasks'];
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
            } elseif (ctype_digit((string) $key)) {
                $labels[] = $names[(int) $key] ?? '#'.$key;
            }
        }

        return implode(' lub ', $labels);
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
            if (($key === 'me' || ctype_digit($key)) && ! in_array($key, $out, true)) {
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
        if ($key !== 'me' && ! ctype_digit($key)) {
            return $keys;
        }

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
        $targetTask = $this->resolveProjectTask($targetTaskId);

        if (! $subtask || ! $targetTask || ! $this->canEditTask($targetTask)) {
            return;
        }

        $sourceTaskId = $subtask->task_id;
        $isCrossTask = $sourceTaskId !== $targetTaskId;

        // #N references in comments are computed from created_at/id order, independent of
        // sort_order — capture the "before" numbering of the source task so we can reconcile
        // comment references once the subtask has moved out of it.
        $sourceTask = $isCrossTask ? ProjectTask::with('subtasks')->find($sourceTaskId) : null;
        $oldSourceMap = $sourceTask?->subtaskDisplayNumbers() ?? [];
        $oldNumber = $oldSourceMap[$subtaskId] ?? null;

        // Move to target task
        $subtask->update(['task_id' => $targetTaskId]);

        if ($isCrossTask) {
            TaskSubtaskEvent::log($subtask, 'moved', auth()->id());
        }

        // Re-compute sort_order within the target task
        $siblings = TaskSubtask::where('task_id', $targetTaskId)
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
    }

    public function setColumnWidth(string $col, int $width): void
    {
        $this->columnWidths[$col] = max(50, min(1200, $width));
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

        return $query;
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
        $assignedIds = $this->resolveUserFilterIds($this->assignedFilterKeys());
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
            $term = '%'.$this->searchCategory.'%';
            $neq = $this->filterOp('searchCategory') === 'neq';
            $clauses[] = function (Builder $q) use ($col, $term, $neq) {
                if ($neq) {
                    $q->where(fn (Builder $inner) => $inner->whereNull($col)->orWhere($col, 'not like', $term));
                } else {
                    $q->where($col, 'like', $term);
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
            $priority = (int) $this->filterPriority;
            $col = $workItems ? 'work_items.priority' : 'project_tasks.priority';
            $clauses[] = function (Builder $q) use ($col, $priority) {
                $q->where($col, $priority);
            };
        }

        if ($this->filterDueDate !== '') {
            $col = $workItems ? 'work_items.due_at' : 'project_tasks.due_date';
            $day = $this->filterDueDate;
            $clauses[] = function (Builder $q) use ($col, $day) {
                $q->whereDate($col, $day);
            };
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
        $this->sanitizeRemovedProjectField();

        $savedViews = $this->gridViewsTableExists()
            ? TaskGridView::query()
                ->visibleTo(auth()->user())
                ->orderByDesc('is_global')
                ->orderBy('name')
                ->get()
            : collect();

        $viewCounts = [];
        foreach ($savedViews as $savedView) {
            $viewCounts[$savedView->id] = $this->countForSavedView($savedView);
        }

        $query = $this->filteredTasksQuery();

        if ($this->usesWorkItems()) {
            $this->applyWorkItemSorting($query);
            $query->with([
                'assignedTo',
                'createdBy',
                'sprint',
                'source' => function (MorphTo $morphTo) {
                    $morphTo->morphWith([
                        ProjectTask::class => ['subtasks', 'procedureRun.subject', 'recruitmentProcess', 'subject', 'createdBy', 'sprint', 'assignedTo'],
                        TaskSubtask::class => ['task', 'assignedTo'],
                        CommentMention::class => ['comment.commentable', 'assignedTo'],
                        \App\Models\ProcedureRun::class => ['task', 'template'],
                        \App\Models\WarehouseDispatch::class => ['tasks'],
                        \App\Models\ApprovalRequest::class => ['approver', 'decidedBy'],
                    ])->morphWithCount([
                        ProjectTask::class => ['comments'],
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

            $eager = ['assignedTo', 'createdBy', 'subtasks', 'procedureRun.subject', 'recruitmentProcess', 'subject'];
            if (! $this->isLockedToSprint()) {
                $eager[] = 'sprint';
            }

            $query->with($eager)->withCount('comments');
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

        return view('livewire.tasks-grid', [
            'tasks' => $tasks,
            'groupedTasks' => $groupedTasks,
            'allSprints' => $this->isLockedToSprint()
                ? collect()
                : Sprint::query()->orderByDesc('start_date')->get(),
            'allUsers' => User::orderedDirectory(),
            'procedureTemplates' => $this->usesWorkItems()
                ? ProcedureTemplate::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'availableColumns' => $this->availableColumns,
            'savedViews' => $savedViews,
            'viewCounts' => $viewCounts,
            'activeViewName' => $this->activeViewId
                ? ($savedViews->firstWhere('id', $this->activeViewId)?->name ?? $this->view)
                : null,
            'isMenuDefaultView' => auth()->user()?->usesGridAsDefaultTasksView($this->currentQueryParams()) ?? false,
            'llmConfigured' => app(LlmClient::class)->isConfigured(),
            'importFormatExample' => json_encode(
                TasksFilterImportService::importFormatExample(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ),
            'chronoFilterLabels' => array_map(
                fn (array $chip) => $chip['label'],
                $this->activeFilterChips(),
            ),
            'chronoImportDefaults' => $this->chronoImportDefaults(),
            'chronoImportDefaultsHint' => $this->chronoImportDefaultsHint(),
            'chronoItemCount' => $tasks instanceof \Illuminate\Contracts\Pagination\Paginator
                ? $tasks->total()
                : ($tasks?->count() ?? $groupedTasks?->flatten()->count()),
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
