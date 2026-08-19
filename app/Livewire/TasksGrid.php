<?php

namespace App\Livewire;

use App\Enums\TaskStatus;
use App\Models\Comment;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TaskGridView;
use App\Models\TaskSubtask;
use App\Models\User;
use App\Notifications\TaskAssigned;
use App\Policies\ProjectTaskPolicy;
use App\Services\UserMentionService;
use App\Support\TasksGridUrlParams;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class TasksGrid extends Component
{
    use WithPagination;

    // Filters
    public string $searchTask = '';

    public string $searchProject = '';

    public string $searchCategory = '';

    public string $searchAssignedTo = '';

    public string $status = ''; // '' = active (pending+in_progress), 'closed', 'all'

    public bool $myTasksOnly = false;

    // Sorting
    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    // Grouping
    public string $groupBy = '';

    /** @var list<string> */
    public array $collapsedGroups = [];

    // Column management
    public array $visibleColumns = ['name', 'status', 'project', 'category', 'assigned_to', 'priority', 'due_date', 'subtasks'];

    public array $columnWidths = [];

    // Saved views (slug in URL → ?view=moj-widok)
    public string $view = '';

    public string $saveViewName = '';

    // Expanded rows (task IDs)
    public array $expandedTasks = [];

    // Inline editing
    public ?int $editingTaskId = null;

    public string $editingField = '';

    public string $editingValue = '';

    // Inline add task
    public bool $showAddRow = false;

    public string $newTaskName = '';

    public string $newTaskProject = '';

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

    protected $queryString = [
        'searchTask' => ['except' => '', 'history' => true],
        'searchProject' => ['except' => '', 'history' => true],
        'searchCategory' => ['except' => '', 'history' => true],
        'searchAssignedTo' => ['except' => '', 'history' => true],
        'status' => ['except' => '', 'history' => true],
        'myTasksOnly' => ['except' => false, 'history' => true],
        'sortField' => ['except' => 'created_at', 'history' => true],
        'sortDirection' => ['except' => 'desc', 'history' => true],
        'groupBy' => ['except' => '', 'history' => true],
        'view' => ['except' => '', 'history' => true],
    ];

    /** @var list<string> */
    protected array $persistableViewProperties = [
        'searchTask',
        'searchProject',
        'searchCategory',
        'searchAssignedTo',
        'status',
        'myTasksOnly',
        'groupBy',
        'sortField',
        'sortDirection',
        'visibleColumns',
        'columnWidths',
    ];

    public function mount(): void
    {
        if ($this->view !== '' && $this->gridViewsTableExists()) {
            $this->loadViewFromSlug($this->view, flash: false);
        }
    }

    public function getAvailableColumnsProperty(): array
    {
        return [
            'name' => ['label' => 'Nazwa', 'sortable' => true, 'always' => true],
            'status' => ['label' => 'Status', 'sortable' => true],
            'project' => ['label' => 'Projekt', 'sortable' => true],
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
        if (in_array($name, ['searchTask', 'searchProject', 'searchCategory', 'searchAssignedTo', 'status', 'myTasksOnly'], true)) {
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

    public function clearFilters(): void
    {
        $this->batchingViewPersist = true;
        $this->searchTask = '';
        $this->searchProject = '';
        $this->searchCategory = '';
        $this->searchAssignedTo = '';
        $this->status = '';
        $this->myTasksOnly = false;
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
        $this->groupBy = '';
        $this->batchingViewPersist = false;
        $this->resetPage();
        $this->persistActiveView();
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
        $this->groupBy = $this->groupBy === $field ? '' : $field;
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

        if (in_array($key, $this->visibleColumns)) {
            $this->visibleColumns = array_values(array_filter($this->visibleColumns, fn ($c) => $c !== $key));
        } else {
            $this->visibleColumns[] = $key;
        }
    }

    public function toggleExpand(int $taskId): void
    {
        if (in_array($taskId, $this->expandedTasks)) {
            $this->expandedTasks = array_values(array_filter($this->expandedTasks, fn ($id) => $id !== $taskId));
        } else {
            $this->expandedTasks[] = $taskId;
        }
    }

    public function startEdit(int $taskId, string $field): void
    {
        $task = ProjectTask::find($taskId);
        if (! $task || ! $this->canEditTask($task)) {
            return;
        }

        $this->editingTaskId = $taskId;
        $this->editingField = $field;
        $this->editingValue = match ($field) {
            'name' => $task->name,
            'status' => $task->status->value,
            'project' => $task->project_id ? (string) $task->project_id : '',
            'category' => $task->category ?? '',
            'assigned_to' => $task->assigned_to ? (string) $task->assigned_to : '',
            'priority' => $task->priority ? (string) $task->priority : '',
            'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : '',
            'description' => $task->plainDescription(),
            default => '',
        };
    }

    public function saveEdit(): void
    {
        if (! $this->editingTaskId) {
            return;
        }

        $task = ProjectTask::find($this->editingTaskId);
        if (! $task || ! $this->canEditTask($task)) {
            $this->cancelEdit();

            return;
        }

        match ($this->editingField) {
            'name' => $task->update(['name' => trim($this->editingValue) ?: $task->name]),
            'status' => $this->applyStatusChange($task, $this->editingValue),
            'project' => $task->update(['project_id' => $this->editingValue === '' ? null : (int) $this->editingValue]),
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
        $task = ProjectTask::find($taskId);
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
            'newTaskProject' => 'nullable|exists:projects,id',
            'newTaskAssignedTo' => 'nullable|exists:users,id',
            'newTaskPriority' => 'nullable|integer|min:1|max:5',
            'newTaskDueDate' => 'nullable|date',
            'newTaskCategory' => 'nullable|string|max:255',
        ]);

        ProjectTask::create([
            'name' => $this->newTaskName,
            'project_id' => $this->newTaskProject ?: null,
            'assigned_to' => $this->newTaskAssignedTo ?: null,
            'priority' => $this->newTaskPriority ?: null,
            'due_date' => $this->newTaskDueDate ?: null,
            'category' => $this->newTaskCategory ?: null,
            'status' => TaskStatus::PENDING,
            'created_by' => auth()->id(),
        ]);

        $this->reset(['newTaskName', 'newTaskProject', 'newTaskCategory', 'newTaskAssignedTo', 'newTaskPriority', 'newTaskDueDate']);
        $this->showAddRow = false;
        $this->flash = 'Zadanie dodane.';
    }

    public function startAddSubtask(int $taskId): void
    {
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

        $subtask = TaskSubtask::create([
            'task_id' => $this->addingSubtaskForTask,
            'name' => $name,
            'created_by' => auth()->id(),
        ]);

        $parent = ProjectTask::query()->find($this->addingSubtaskForTask);
        if ($parent && auth()->user()) {
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

        $subtask->is_completed ? $subtask->markIncomplete() : $subtask->markCompleted();
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
            'searchProject' => $this->searchProject,
            'searchCategory' => $this->searchCategory,
            'searchAssignedTo' => $this->searchAssignedTo,
            'status' => $this->status,
            'myTasksOnly' => $this->myTasksOnly,
            'groupBy' => $this->groupBy,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ]);
    }

    protected function gridViewsTableExists(): bool
    {
        return Schema::hasTable('task_grid_views');
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
        $this->searchProject = $record->search_project ?? '';
        $this->searchCategory = $record->search_category ?? '';
        $this->searchAssignedTo = $record->search_assigned_to ?? '';
        $this->status = $record->status ?? '';
        $this->myTasksOnly = (bool) ($record->my_tasks_only ?? false);
        $this->batchingViewPersist = false;
        $this->resetPage();
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
            'search_project' => $this->searchProject,
            'search_category' => $this->searchCategory,
            'search_assigned_to' => $this->searchAssignedTo,
            'status' => $this->status,
            'my_tasks_only' => $this->myTasksOnly,
        ];
    }

    protected function persistActiveView(): void
    {
        if ($this->view === '' || ! $this->gridViewsTableExists()) {
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

    public function canEditTask(ProjectTask $task): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return app(ProjectTaskPolicy::class)->updateStatus($user, $task);
    }

    public function moveSubtask(int $subtaskId, int $targetTaskId, ?int $afterSubtaskId = null): void
    {
        $subtask = TaskSubtask::find($subtaskId);
        $targetTask = ProjectTask::find($targetTaskId);

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

    protected function groupKeyFor(ProjectTask $task): string
    {
        return match ($this->groupBy) {
            'status' => $task->status->label(),
            'project' => $task->project?->name ?? 'Brak projektu',
            'category' => $task->category ?? 'Brak kategorii',
            'assigned_to' => $task->assignedTo?->name ?? 'Nieprzypisane',
            'priority' => $task->priority ? "Priorytet {$task->priority}" : 'Brak priorytetu',
            default => 'Wszystkie',
        };
    }

    protected function filteredTasksQuery(): Builder
    {
        $query = ProjectTask::query();

        if ($this->myTasksOnly) {
            $query->where('project_tasks.assigned_to', auth()->id());
        }

        if ($this->searchProject) {
            $s = strtolower($this->searchProject);
            if ($s === 'brak projektu') {
                $query->whereNull('project_tasks.project_id');
            } else {
                $query->whereHas('project', fn ($q) => $q->where('name', 'like', '%'.$this->searchProject.'%'));
            }
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

    public function render()
    {
        $savedViews = $this->gridViewsTableExists()
            ? TaskGridView::query()
                ->where('user_id', auth()->id())
                ->orderBy('name')
                ->get(['slug', 'name'])
                ->all()
            : [];

        $query = $this->filteredTasksQuery();

        // Sorting
        if ($this->sortField === 'project') {
            $query->leftJoin('projects', 'project_tasks.project_id', '=', 'projects.id')
                ->select('project_tasks.*')
                ->orderBy('projects.name', $this->sortDirection);
        } elseif (in_array($this->sortField, ['priority', 'due_date'])) {
            $query->orderByRaw("ISNULL(project_tasks.{$this->sortField}), project_tasks.{$this->sortField} {$this->sortDirection}");
        } else {
            $query->orderBy("project_tasks.{$this->sortField}", $this->sortDirection);
        }

        if ($this->sortField !== 'created_at') {
            $query->orderBy('project_tasks.created_at', 'desc');
        }

        $query->with(['project', 'assignedTo', 'createdBy', 'subtasks', 'comments', 'procedureRun.subject', 'recruitmentProcess', 'subject']);

        if ($this->groupBy) {
            $allTasks = $query->limit(500)->get();
            $groupedTasks = $allTasks->groupBy(fn ($task) => $this->groupKeyFor($task))->sortKeys();
            $tasks = null;
        } else {
            $groupedTasks = null;
            $tasks = $query->paginate(50);
        }

        return view('livewire.tasks-grid', [
            'tasks' => $tasks,
            'groupedTasks' => $groupedTasks,
            'allProjects' => Project::orderBy('name')->get(),
            'allUsers' => User::orderBy('name')->get(),
            'availableColumns' => $this->availableColumns,
            'savedViews' => $savedViews,
            'activeViewName' => $this->view !== ''
                ? (collect($savedViews)->firstWhere('slug', $this->view)?->name ?? $this->view)
                : null,
            'isMenuDefaultView' => auth()->user()?->usesGridAsDefaultTasksView($this->currentQueryParams()) ?? false,
        ]);
    }
}
