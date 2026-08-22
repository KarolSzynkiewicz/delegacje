<?php

namespace App\Livewire;

use App\Enums\TaskStatus;
use App\Enums\WorkItemType;
use App\Models\Comment;
use App\Models\CommentMention;
use App\Models\ProjectTask;
use App\Models\Sprint;
use App\Models\TaskGridView;
use App\Models\TaskSubtask;
use App\Models\TaskSubtaskEvent;
use App\Models\User;
use App\Models\WorkItem;
use App\Notifications\TaskAssigned;
use App\Policies\ProjectTaskPolicy;
use App\Services\UserMentionService;
use App\Support\TasksGridUrlParams;
use App\WorkItems\GridField;
use App\WorkItems\StatusWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class TasksGrid extends Component
{
    use WithPagination;

    // Filters
    public string $searchTask = '';

    public string $searchCategory = '';

    public string $searchAssignedTo = '';

    public string $status = ''; // '' = active (pending+in_progress), 'closed', 'all'

    /** '' = wszyscy, 'me' = zalogowany użytkownik, w innym wypadku ID użytkownika jako string. */
    public string $assignedFilter = '';

    /**
     * Zaznaczone typy work itemów (checkboxy „Typ pracy” w panelu filtrów,
     * zastępują dawny pojedynczy przełącznik „pokaż oddzwonienia rekrutacji”).
     * Domyślnie bez Oddzwonień (WorkItemType::Callback) — to automatyczne
     * przypomnienia z rekrutacji, osobny workflow, nie mają zaśmiecać backlogu.
     * Pusta tablica = świadomie odznaczone wszystko = brak wyników.
     *
     * @var list<string>
     */
    public array $selectedTypes = ['task', 'subtask', 'procedure_run', 'dispatch', 'follow_up'];

    // Sorting
    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    // Grouping
    public string $groupBy = '';

    /** @var list<string> */
    public array $collapsedGroups = [];

    // Column management
    public array $visibleColumns = ['name', 'type', 'status', 'sprint', 'category', 'assigned_to', 'priority', 'due_date', 'subtasks'];

    public array $columnWidths = [];

    // Saved views (slug in URL → ?view=moj-widok)
    public string $view = '';

    public string $saveViewName = '';

    /** Gdy ustawione, siatka pokazuje tylko zadania tego sprintu (np. na stronie sprintu). */
    public ?int $lockedSprintId = null;

    // Expanded rows (task IDs)
    public array $expandedTasks = [];

    // Inline editing
    public ?int $editingTaskId = null;

    public string $editingField = '';

    public string $editingValue = '';

    // Inline add task
    public bool $showAddRow = false;

    public string $newTaskName = '';

    public string $newTaskSprint = '';

    public string $newTaskCategory = '';

    public string $newTaskAssignedTo = '';

    public string $newTaskPriority = '';

    public string $newTaskDueDate = '';

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
            'status' => ['except' => '', 'history' => true],
            'assignedFilter' => ['except' => '', 'history' => true],
            'selectedTypes' => ['except' => $this->defaultSelectedTypes(), 'as' => 'types', 'history' => true],
            'sortField' => ['except' => 'created_at', 'history' => true],
            'sortDirection' => ['except' => 'desc', 'history' => true],
            'groupBy' => ['except' => '', 'history' => true],
            'view' => ['except' => '', 'history' => true],
        ];
    }

    /** @var list<string> */
    protected array $persistableViewProperties = [
        'searchTask',
        'searchCategory',
        'searchAssignedTo',
        'status',
        'assignedFilter',
        'selectedTypes',
        'groupBy',
        'sortField',
        'sortDirection',
        'visibleColumns',
        'columnWidths',
    ];

    public function mount(): void
    {
        if ($this->isLockedToSprint()) {
            $this->status = 'all';
            $this->sortField = 'sprint_position';
            $this->sortDirection = 'asc';
            $this->groupBy = '';
            $this->view = '';
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
        if (in_array($name, ['searchTask', 'searchCategory', 'searchAssignedTo', 'status', 'assignedFilter', 'selectedTypes'], true)) {
            $this->resetPage();
        }
    }

    public function updated(string $property): void
    {
        if ($this->batchingViewPersist || ! in_array($property, $this->persistableViewProperties, true)) {
            return;
        }

        $this->persistActiveView();
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
        $this->status = 'all';
        $this->assignedFilter = '';
        $this->selectedTypes = $this->allWorkItemTypeValues();
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
        $this->persistActiveView();
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function activeFilterChips(): array
    {
        $chips = [];

        if ($this->view !== '') {
            $viewName = TaskGridView::query()
                ->where('user_id', auth()->id())
                ->where('slug', $this->view)
                ->value('name') ?? $this->view;
            $chips[] = ['key' => 'view', 'label' => 'Widok: '.$viewName];
        }

        if ($this->searchTask !== '') {
            $chips[] = ['key' => 'searchTask', 'label' => 'Szukaj: '.$this->searchTask];
        }

        if ($this->searchCategory !== '') {
            $chips[] = ['key' => 'searchCategory', 'label' => 'Kategoria: '.$this->searchCategory];
        }

        if ($this->searchAssignedTo !== '') {
            $chips[] = ['key' => 'searchAssignedTo', 'label' => 'Osoba: '.$this->searchAssignedTo];
        }

        // "all" to jedyna wartość statusu, która niczego nie odfiltrowuje —
        // chip pokazujemy dla każdej innej wartości, ŁĄCZNIE z domyślnym ""
        // (aktywne), bo to i tak realnie ukrywa zamknięte/anulowane zadania.
        // Wcześniej domyślne "" było traktowane jak "brak filtra" i chip się
        // nie pokazywał — stąd user widział np. 15 z 129 zadań bez żadnej
        // wskazówki, że coś jest odfiltrowane.
        if ($this->status !== 'all') {
            $statusLabel = match ($this->status) {
                '' => 'Aktywne',
                'closed' => 'Zamknięte',
                'all' => 'Wszystkie',
                default => TaskStatus::tryFrom($this->status)?->label() ?? $this->status,
            };
            $chips[] = ['key' => 'status', 'label' => 'Status: '.$statusLabel];
        }

        if ($this->assignedFilter === 'me') {
            $chips[] = ['key' => 'assignedFilter', 'label' => 'Przypisany: Ja'];
        } elseif ($this->assignedFilter !== '') {
            $name = User::query()->whereKey((int) $this->assignedFilter)->value('name');
            $chips[] = ['key' => 'assignedFilter', 'label' => 'Przypisany: '.($name ?: '#'.$this->assignedFilter)];
        }

        if ($this->usesWorkItems()) {
            $allTypes = $this->allWorkItemTypeValues();
            $selected = $this->selectedTypes;
            $missing = array_values(array_diff($allTypes, $selected));

            if ($missing !== []) {
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

        if ($key === 'status') {
            // Usunięcie chipa = "przestań zawężać", czyli pokaż wszystko —
            // nie wracaj do domyślnego "Aktywne", bo to by wyglądało jak nic
            // się nie zmieniło (patrz komentarz przy activeFilterChips()).
            $this->status = 'all';
            $this->resetPage();

            return;
        }

        if ($key === 'selectedTypes') {
            $this->selectedTypes = $this->allWorkItemTypeValues();
            $this->resetPage();

            return;
        }

        if ($key === 'assignedFilter') {
            $this->assignedFilter = '';
            $this->resetPage();

            return;
        }

        if (in_array($key, ['searchTask', 'searchCategory', 'searchAssignedTo'], true)) {
            $this->{$key} = '';
            $this->resetPage();
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

        $this->reset(['newTaskName', 'newTaskSprint', 'newTaskCategory', 'newTaskAssignedTo', 'newTaskPriority', 'newTaskDueDate']);
        if ($this->isLockedToSprint()) {
            $this->newTaskSprint = (string) $this->lockedSprintId;
        }
        $this->showAddRow = false;
        $this->flash = 'Zadanie dodane.';
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
        } elseif (TaskGridView::query()->where('user_id', auth()->id())->where('slug', $slug)->exists()) {
            $slug = $this->uniqueSlug($name);
        }

        TaskGridView::updateOrCreate(
            ['user_id' => auth()->id(), 'slug' => $slug],
            array_merge(['name' => $name], $this->viewPayload()),
        );

        $this->view = $slug;
        $this->saveViewName = '';
        $this->flash = "Widok „{$name}” zapisany.";
    }

    public function loadView(string $slug): void
    {
        $this->loadViewFromSlug($slug);
    }

    public function deleteView(string $slug): void
    {
        TaskGridView::query()
            ->where('user_id', auth()->id())
            ->where('slug', $slug)
            ->delete();

        if ($this->view === $slug) {
            $this->view = '';
        }
    }

    public function clearView(): void
    {
        $this->view = '';
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
            $validSlug = TaskGridView::query()
                ->where('user_id', $user->id)
                ->where('slug', $query['view'])
                ->exists();

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
            'status' => $this->status,
            'assignedFilter' => $this->assignedFilter,
            'types' => $this->selectedTypes,
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
        $previous = [
            'searchTask' => $this->searchTask,
            'searchCategory' => $this->searchCategory,
            'searchAssignedTo' => $this->searchAssignedTo,
            'status' => $this->status,
            'assignedFilter' => $this->assignedFilter,
            'selectedTypes' => $this->selectedTypes,
        ];

        $this->searchTask = $view->search_task ?? '';
        $this->searchCategory = $view->search_category ?? '';
        $this->searchAssignedTo = $view->search_assigned_to ?? '';
        $this->status = $view->status ?? '';
        $this->assignedFilter = $view->assigned_filter ?? ($view->my_tasks_only ? 'me' : '');
        $this->selectedTypes = $view->type_filter ?: $this->defaultSelectedTypes();

        try {
            return $this->filteredTasksQuery()->count();
        } finally {
            $this->searchTask = $previous['searchTask'];
            $this->searchCategory = $previous['searchCategory'];
            $this->searchAssignedTo = $previous['searchAssignedTo'];
            $this->status = $previous['status'];
            $this->assignedFilter = $previous['assignedFilter'];
            $this->selectedTypes = $previous['selectedTypes'];
        }
    }

    protected function loadViewFromSlug(string $slug, bool $flash = true): void
    {
        $record = TaskGridView::query()
            ->where('user_id', auth()->id())
            ->where('slug', $slug)
            ->first();

        if (! $record) {
            if ($flash) {
                $this->flash = 'Nie znaleziono widoku.';
            }
            $this->view = '';

            return;
        }

        $this->view = $slug;
        $this->applyViewRecord($record);

        if ($flash) {
            $this->flash = "Załadowano „{$record->name}”.";
        }
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
        $this->status = $record->status ?? '';
        $this->assignedFilter = $record->assigned_filter ?? ($record->my_tasks_only ? 'me' : '');
        $this->selectedTypes = $record->type_filter ?: $this->defaultSelectedTypes();
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
            $this->visibleColumns = ['name', 'status', 'sprint', 'category', 'assigned_to', 'priority', 'due_date', 'subtasks'];
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

        if ($this->usesWorkItems() && $this->groupBy !== 'type' && ! in_array('type', $this->visibleColumns, true)) {
            $this->insertVisibleColumn('type');
        }

        if ($this->groupBy === 'project') {
            $this->groupBy = '';
        }

        if ($this->sortField === 'project') {
            $this->sortField = 'created_at';
        }
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
            'status' => $this->status,
            'my_tasks_only' => $this->assignedFilter === 'me',
            'assigned_filter' => $this->assignedFilter,
            'type_filter' => $this->selectedTypes,
        ];
    }

    protected function persistActiveView(): void
    {
        if ($this->isLockedToSprint() || $this->view === '' || ! $this->gridViewsTableExists()) {
            return;
        }

        TaskGridView::query()
            ->where('user_id', auth()->id())
            ->where('slug', $this->view)
            ->update($this->viewPayload());
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'widok';
        $slug = $base;
        $i = 2;

        while (TaskGridView::query()
            ->where('user_id', auth()->id())
            ->where('slug', $slug)
            ->exists()) {
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

        if ($this->assignedFilter === 'me') {
            $query->where('project_tasks.assigned_to', auth()->id());
        } elseif ($this->assignedFilter !== '' && ctype_digit($this->assignedFilter)) {
            $query->where('project_tasks.assigned_to', (int) $this->assignedFilter);
        }

        if ($this->searchTask) {
            $query->where(fn ($q) => $q
                ->where('project_tasks.name', 'like', '%'.$this->searchTask.'%')
                ->orWhere('project_tasks.description', 'like', '%'.$this->searchTask.'%'));
        }

        if ($this->searchCategory) {
            $query->where('project_tasks.category', 'like', '%'.$this->searchCategory.'%');
        }

        if ($this->searchAssignedTo) {
            $query->whereHas('assignedTo', fn ($q) => $q->where('name', 'like', '%'.$this->searchAssignedTo.'%'));
        }

        if ($this->status === '' || $this->status === 'active') {
            $query->whereIn('project_tasks.status', [TaskStatus::PENDING, TaskStatus::IN_PROGRESS]);
        } elseif ($this->status === 'closed') {
            $query->whereIn('project_tasks.status', [TaskStatus::COMPLETED, TaskStatus::CANCELLED]);
        } elseif ($this->status !== 'all' && $this->status) {
            $query->where('project_tasks.status', $this->status);
        }

        return $query;
    }

    protected function filteredWorkItemsQuery(): Builder
    {
        $query = WorkItem::query();

        // Checkboxy "Typ pracy" w panelu filtrów — pusta tablica jest świadomym
        // wyborem usera (odznaczył wszystko) i celowo zwraca zero wyników,
        // whereIn Laravela sam skompiluje to jako zawsze-fałszywy warunek.
        $query->whereIn('work_items.type', $this->selectedTypes);

        if ($this->assignedFilter === 'me') {
            $query->where('work_items.assignee_id', auth()->id());
        } elseif ($this->assignedFilter !== '' && ctype_digit($this->assignedFilter)) {
            $query->where('work_items.assignee_id', (int) $this->assignedFilter);
        }

        if ($this->searchTask) {
            $query->where('work_items.title', 'like', '%'.$this->searchTask.'%');
        }

        if ($this->searchCategory) {
            $query->where('work_items.category', 'like', '%'.$this->searchCategory.'%');
        }

        if ($this->searchAssignedTo) {
            $query->whereHas('assignedTo', fn ($q) => $q->where('name', 'like', '%'.$this->searchAssignedTo.'%'));
        }

        if ($this->status === '' || $this->status === 'active') {
            $query->whereIn('work_items.status', [TaskStatus::PENDING, TaskStatus::IN_PROGRESS]);
        } elseif ($this->status === 'closed') {
            $query->whereIn('work_items.status', [TaskStatus::COMPLETED, TaskStatus::CANCELLED]);
        } elseif ($this->status !== 'all' && $this->status) {
            $query->where('work_items.status', $this->status);
        }

        return $query;
    }

    public function render()
    {
        $this->sanitizeRemovedProjectField();

        $savedViews = $this->gridViewsTableExists()
            ? TaskGridView::query()
                ->where('user_id', auth()->id())
                ->orderBy('name')
                ->get()
            : collect();

        $viewCounts = [];
        foreach ($savedViews as $savedView) {
            $viewCounts[$savedView->slug] = $this->countForSavedView($savedView);
        }

        $query = $this->filteredTasksQuery();

        if ($this->usesWorkItems()) {
            $this->applyWorkItemSorting($query);
            $query->with([
                'assignedTo',
                'sprint',
                'source' => function (MorphTo $morphTo) {
                    $morphTo->morphWith([
                        ProjectTask::class => ['subtasks', 'procedureRun.subject', 'recruitmentProcess', 'subject', 'createdBy', 'sprint', 'assignedTo'],
                        TaskSubtask::class => ['task', 'assignedTo'],
                        CommentMention::class => ['comment.commentable', 'assignedTo'],
                        \App\Models\ProcedureRun::class => ['task', 'template'],
                        \App\Models\WarehouseDispatch::class => ['tasks'],
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

        if ($this->groupBy) {
            $allTasks = $query->limit(500)->get();
            $groupedTasks = $allTasks
                ->groupBy(fn ($task) => $this->groupValueFor($task))
                ->sortBy(fn ($tasks) => mb_strtolower($this->groupKeyFor($tasks->first())));
            $tasks = null;
        } else {
            $groupedTasks = null;
            $tasks = $query->paginate(50);
        }

        return view('livewire.tasks-grid', [
            'tasks' => $tasks,
            'groupedTasks' => $groupedTasks,
            'allSprints' => $this->isLockedToSprint()
                ? collect()
                : Sprint::query()->orderByDesc('start_date')->get(),
            'allUsers' => User::orderedDirectory(),
            'availableColumns' => $this->availableColumns,
            'savedViews' => $savedViews,
            'viewCounts' => $viewCounts,
            'activeViewName' => $this->view !== ''
                ? ($savedViews->firstWhere('slug', $this->view)?->name ?? $this->view)
                : null,
            'isMenuDefaultView' => auth()->user()?->usesGridAsDefaultTasksView($this->currentQueryParams()) ?? false,
        ]);
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
