<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Policies\ProjectTaskPolicy;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithPagination;

class TasksTable extends Component
{
    use WithPagination;

    public $searchProject = '';

    public $searchTask = '';

    public $searchCategory = '';

    public $searchAssignedTo = '';

    public $status = ''; // 'active', 'closed', or specific status

    public $myTasksOnly = false; // Toggle for filtering only my tasks

    public $sortField = 'due_date';

    public $sortDirection = 'asc';

    // Optional filters for /mine/* routes
    public $filterProjectIds = null;

    public $assignedToUserId = null;

    public $isMineView = false; // Flag to determine if we're in /mine/* context

    /** @var int|null */
    public $quickEditTaskId = null;

    public string $qeProjectId = '';

    public string $qeCategory = '';

    public string $qeDueDate = '';

    public ?string $quickEditFlash = null;

    /** project|category|due_date */
    public string $quickEditField = 'project';

    public ?float $quickEditClientX = null;

    public ?float $quickEditClientY = null;

    protected $queryString = [
        'searchProject' => ['except' => ''],
        'searchTask' => ['except' => ''],
        'searchCategory' => ['except' => ''],
        'searchAssignedTo' => ['except' => ''],
        'status' => ['except' => ''],
        'myTasksOnly' => ['except' => false],
        'sortField' => ['except' => 'due_date'],
        'sortDirection' => ['except' => 'asc'],
    ];

    protected $updatesQueryString = ['searchProject', 'searchTask', 'searchCategory', 'searchAssignedTo', 'status', 'myTasksOnly', 'sortField', 'sortDirection'];

    public function updating($name, $value)
    {
        $this->resetPage();
    }

    public function toggleMyTasks()
    {
        $this->myTasksOnly = ! $this->myTasksOnly;
        $this->resetPage();
    }

    public function updatedMyTasksOnly($value)
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->searchProject = '';
        $this->searchTask = '';
        $this->searchCategory = '';
        $this->searchAssignedTo = '';
        $this->status = '';
        $this->myTasksOnly = false;
        $this->sortField = 'due_date';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function paginationView()
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function canQuickEditTask(ProjectTask $task): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return app(ProjectTaskPolicy::class)->updateStatus($user, $task);
    }

    public function openQuickEdit(int $taskId, string $field, ?float $clientX = null, ?float $clientY = null): void
    {
        $this->quickEditFlash = null;

        if (! in_array($field, ['project', 'category', 'due_date'], true)) {
            $field = 'project';
        }

        $this->quickEditField = $field;
        $this->quickEditClientX = $clientX;
        $this->quickEditClientY = $clientY;

        $task = ProjectTask::query()->find($taskId);
        if (! $task || ! $this->canQuickEditTask($task)) {
            return;
        }

        $this->quickEditTaskId = $task->id;
        $this->qeProjectId = $task->project_id ? (string) $task->project_id : '';
        $this->qeCategory = (string) ($task->category ?? '');
        $this->qeDueDate = $task->due_date ? $task->due_date->format('Y-m-d') : '';
    }

    public function closeQuickEdit(): void
    {
        $this->quickEditTaskId = null;
        $this->qeProjectId = '';
        $this->qeCategory = '';
        $this->qeDueDate = '';
        $this->quickEditField = 'project';
        $this->quickEditClientX = null;
        $this->quickEditClientY = null;
    }

    public function saveQuickEdit(): void
    {
        if (! $this->quickEditTaskId) {
            return;
        }

        $task = ProjectTask::query()->find($this->quickEditTaskId);
        if (! $task || ! $this->canQuickEditTask($task)) {
            abort(403);
        }

        if (! in_array($this->quickEditField, ['project', 'category', 'due_date'], true)) {
            $this->quickEditField = 'project';
        }

        match ($this->quickEditField) {
            'project' => $this->saveQuickEditProject($task),
            'category' => $this->saveQuickEditCategory($task),
            'due_date' => $this->saveQuickEditDueDate($task),
        };

        $this->quickEditFlash = 'Zapisano zmiany.';
        $this->closeQuickEdit();
    }

    protected function saveQuickEditProject(ProjectTask $task): void
    {
        Validator::make(
            [
                'qeProjectId' => $this->qeProjectId === '' ? null : $this->qeProjectId,
            ],
            [
                'qeProjectId' => ['nullable', 'integer', 'exists:projects,id'],
            ],
            [
                'qeProjectId.exists' => 'Wybrany projekt nie istnieje.',
            ]
        )->validate();

        $projectId = $this->qeProjectId === '' ? null : (int) $this->qeProjectId;
        $task->update(['project_id' => $projectId]);
    }

    protected function saveQuickEditCategory(ProjectTask $task): void
    {
        Validator::make(
            ['qeCategory' => $this->qeCategory],
            ['qeCategory' => ['nullable', 'string', 'max:255']],
        )->validate();

        $task->update([
            'category' => $this->qeCategory === '' ? null : $this->qeCategory,
        ]);
    }

    protected function saveQuickEditDueDate(ProjectTask $task): void
    {
        Validator::make(
            ['qeDueDate' => $this->qeDueDate === '' ? null : $this->qeDueDate],
            ['qeDueDate' => ['nullable', 'date']],
            ['qeDueDate.date' => 'Nieprawidłowa data terminu.']
        )->validate();

        $task->update([
            'due_date' => $this->qeDueDate === '' ? null : $this->qeDueDate,
        ]);
    }

    public function render()
    {
        try {
            // Prostsze zapytanie - bez joinów, użyj eager loading
            $query = ProjectTask::query();

            // Filtrowanie po projektach (dla /mine/*)
            if ($this->filterProjectIds && is_array($this->filterProjectIds) && ! empty($this->filterProjectIds)) {
                $query->whereIn('project_id', $this->filterProjectIds);
            }

            // Filtrowanie po przypisanym użytkowniku (tylko jeśli ustawione i myTasksOnly nie jest aktywne)
            // UWAGA: Jeśli assignedToUserId jest ustawione, pokazuje tylko zadania przypisane do tego użytkownika
            // Jeśli chcesz zobaczyć wszystkie zadania, nie ustawiaj assignedToUserId
            if ($this->assignedToUserId && ! $this->myTasksOnly) {
                $query->where('assigned_to', $this->assignedToUserId);
            }

            // Filtrowanie "Moje zadania" - tylko zadania przypisane do zalogowanego użytkownika
            // Ma priorytet nad assignedToUserId jeśli oba są ustawione
            if ($this->myTasksOnly) {
                $userId = auth()->id();
                if ($userId) {
                    $query->where('assigned_to', $userId);
                }
            }

            // Filter by project (including tasks without project)
            if ($this->searchProject) {
                $searchLower = strtolower($this->searchProject);
                if ($searchLower === 'brak projektu' || $searchLower === 'bez projektu') {
                    $query->whereNull('project_id');
                } else {
                    $query->whereHas('project', function ($q) {
                        $q->where('name', 'like', '%'.$this->searchProject.'%');
                    });
                }
            }

            // Filter by task name
            if ($this->searchTask) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->searchTask.'%')
                        ->orWhere('description', 'like', '%'.$this->searchTask.'%');
                });
            }

            // Filter by category
            if ($this->searchCategory) {
                $query->where('category', 'like', '%'.$this->searchCategory.'%');
            }

            // Filter by assigned user
            if ($this->searchAssignedTo) {
                $query->whereHas('assignedTo', function ($q) {
                    $q->where('name', 'like', '%'.$this->searchAssignedTo.'%');
                });
            }

            // Filter by status
            // 'active' = pending + in_progress (domyślnie)
            // 'closed' = completed + cancelled
            // konkretny status = dokładny status
            if ($this->status === 'active') {
                $query->whereIn('status', [\App\Enums\TaskStatus::PENDING, \App\Enums\TaskStatus::IN_PROGRESS]);
            } elseif ($this->status === 'closed') {
                $query->whereIn('status', [\App\Enums\TaskStatus::COMPLETED, \App\Enums\TaskStatus::CANCELLED]);
            } elseif ($this->status) {
                $query->where('status', $this->status);
            } else {
                // Domyślnie pokazuj tylko aktywne (pending + in_progress)
                $query->whereIn('status', [\App\Enums\TaskStatus::PENDING, \App\Enums\TaskStatus::IN_PROGRESS]);
            }

            // Apply sorting
            // Handle special cases for related fields
            if ($this->sortField === 'project') {
                $query->leftJoin('projects', 'project_tasks.project_id', '=', 'projects.id')
                    ->orderBy('projects.name', $this->sortDirection)
                    ->select('project_tasks.*');
            } elseif ($this->sortField === 'priority') {
                // Sort by priority: null values last
                if ($this->sortDirection === 'asc') {
                    $query->orderByRaw('ISNULL(priority), priority ASC');
                } else {
                    $query->orderByRaw('ISNULL(priority), priority DESC');
                }
            } elseif ($this->sortField === 'due_date') {
                // Sort by due_date: null values last
                if ($this->sortDirection === 'asc') {
                    $query->orderByRaw('ISNULL(due_date), due_date ASC');
                } else {
                    $query->orderByRaw('ISNULL(due_date), due_date DESC');
                }
            } else {
                $query->orderBy($this->sortField, $this->sortDirection);
            }

            // Secondary sort by created_at if not primary sort
            if ($this->sortField !== 'created_at') {
                $query->orderBy('created_at', 'desc');
            }

            // ✅ NAPRAWIONE: Eager loading PRZED paginacją (zapobiega N+1 query)
            $query->with(['project', 'assignedTo', 'createdBy', 'subtasks', 'comments.user']);

            $tasks = $query->paginate(20);
        } catch (\Exception $e) {
            \Log::error('TasksTable render error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            // Zwróć puste wyniki zamiast błędu
            $tasks = ProjectTask::whereRaw('1 = 0')->paginate(20);
        }

        $projects = $this->searchProject ? Project::orderBy('name')->get() : collect([]);
        $allProjects = Project::orderBy('name')->get();
        $statuses = [
            'pending' => 'Oczekujące',
            'in_progress' => 'W trakcie',
            'completed' => 'Zakończone',
            'cancelled' => 'Anulowane',
        ];

        // Determine if we're in /mine/* context
        $isMineView = $this->filterProjectIds !== null && ! empty($this->filterProjectIds);

        return view('livewire.tasks-table', [
            'tasks' => $tasks,
            'projects' => $projects,
            'allProjects' => $allProjects,
            'statuses' => $statuses,
            'isMineView' => $isMineView,
        ]);
    }
}
