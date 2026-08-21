<?php

namespace App\Livewire;

use App\Enums\TaskStatus;
use App\Livewire\Concerns\WithTaskQuickEdit;
use App\Models\ProjectTask;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class TasksTable extends Component
{
    use WithPagination;
    use WithTaskQuickEdit;

    public $searchTask = '';

    public $searchCategory = '';

    public $searchAssignedTo = '';

    public $status = ''; // 'active', 'closed', or specific status

    public $myTasksOnly = false; // Toggle for filtering only my tasks

    public $sortField = 'created_at';

    public $sortDirection = 'desc';

    public $assignedToUserId = null;

    public $isMineView = false;

    protected $queryString = [
        'searchTask' => ['except' => ''],
        'searchCategory' => ['except' => ''],
        'searchAssignedTo' => ['except' => ''],
        'status' => ['except' => ''],
        'myTasksOnly' => ['except' => false],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    protected $updatesQueryString = ['searchTask', 'searchCategory', 'searchAssignedTo', 'status', 'myTasksOnly', 'sortField', 'sortDirection'];

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
        $this->searchTask = '';
        $this->searchCategory = '';
        $this->searchAssignedTo = '';
        $this->status = '';
        $this->myTasksOnly = false;
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
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

    public function render()
    {
        try {
            $query = ProjectTask::query();

            if ($this->assignedToUserId && ! $this->myTasksOnly) {
                $query->where('assigned_to', $this->assignedToUserId);
            }

            if ($this->myTasksOnly) {
                $userId = auth()->id();
                if ($userId) {
                    $query->where('assigned_to', $userId);
                }
            }

            if ($this->searchTask) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->searchTask.'%')
                        ->orWhere('description', 'like', '%'.$this->searchTask.'%');
                });
            }

            if ($this->searchCategory) {
                $query->where('category', 'like', '%'.$this->searchCategory.'%');
            }

            if ($this->searchAssignedTo) {
                $query->whereHas('assignedTo', function ($q) {
                    $q->where('name', 'like', '%'.$this->searchAssignedTo.'%');
                });
            }

            if ($this->status === 'active') {
                $query->whereIn('status', [TaskStatus::PENDING, TaskStatus::IN_PROGRESS]);
            } elseif ($this->status === 'closed') {
                $query->whereIn('status', [TaskStatus::COMPLETED, TaskStatus::CANCELLED]);
            } elseif ($this->status) {
                $query->where('status', $this->status);
            } else {
                $query->whereIn('status', [TaskStatus::PENDING, TaskStatus::IN_PROGRESS]);
            }

            if ($this->sortField === 'priority') {
                if ($this->sortDirection === 'asc') {
                    $query->orderByRaw('ISNULL(priority), priority ASC');
                } else {
                    $query->orderByRaw('ISNULL(priority), priority DESC');
                }
            } elseif ($this->sortField === 'due_date') {
                if ($this->sortDirection === 'asc') {
                    $query->orderByRaw('ISNULL(due_date), due_date ASC');
                } else {
                    $query->orderByRaw('ISNULL(due_date), due_date DESC');
                }
            } else {
                $query->orderBy($this->sortField, $this->sortDirection);
            }

            if ($this->sortField !== 'created_at') {
                $query->orderBy('created_at', 'desc');
            }

            $query->with(['assignedTo', 'createdBy', 'subtasks', 'comments.user', 'procedureRun.subject', 'recruitmentProcess', 'subject']);

            $tasks = $query->paginate(20);
        } catch (\Exception $e) {
            \Log::error('TasksTable render error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $tasks = ProjectTask::whereRaw('1 = 0')->paginate(20);
        }

        $statuses = [
            'pending' => 'Oczekujące',
            'in_progress' => 'W trakcie',
            'completed' => 'Zakończone',
            'cancelled' => 'Anulowane',
        ];

        return view('livewire.tasks-table', [
            'tasks' => $tasks,
            'allUsers' => User::orderBy('name')->get(),
            'statuses' => $statuses,
            'isMineView' => $this->isMineView,
        ]);
    }
}
