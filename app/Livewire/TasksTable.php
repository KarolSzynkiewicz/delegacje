<?php

namespace App\Livewire;

use App\Models\ProjectTask;
use App\Models\Project;
use Livewire\Component;
use Livewire\WithPagination;

class TasksTable extends Component
{
    use WithPagination;

    public $searchProject = '';
    public $searchTask = '';
    public $status = '';
    
    // Optional filters for /mine/* routes
    public $filterProjectIds = null;
    public $assignedToUserId = null;
    public $isMineView = false; // Flag to determine if we're in /mine/* context

    protected $queryString = [
        'searchProject' => ['except' => ''],
        'searchTask' => ['except' => ''],
        'status' => ['except' => ''],
    ];
    protected $updatesQueryString = ['searchProject', 'searchTask', 'status'];

    public function updating($name, $value)
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->searchProject = '';
        $this->searchTask = '';
        $this->status = '';
        $this->resetPage();
    }

    public function paginationView()
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function render()
    {
        $query = ProjectTask::with(['project', 'assignedTo', 'createdBy']);
        
        // Filtrowanie po projektach (dla /mine/*)
        if ($this->filterProjectIds && is_array($this->filterProjectIds) && !empty($this->filterProjectIds)) {
            $query->whereIn('project_id', $this->filterProjectIds);
        }
        
        // Filtrowanie po przypisanym użytkowniku
        if ($this->assignedToUserId) {
            $query->where('assigned_to', $this->assignedToUserId);
        }
        
        $query->orderBy('due_date', 'asc')->orderBy('created_at', 'desc');

        // Filter by project
        if ($this->searchProject) {
            $query->whereHas('project', function ($q) {
                $q->where('name', 'like', '%' . $this->searchProject . '%');
            });
        }

        // Filter by task name
        if ($this->searchTask) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->searchTask . '%')
                  ->orWhere('description', 'like', '%' . $this->searchTask . '%');
            });
        }

        // Filter by status
        if ($this->status) {
            $query->where('status', $this->status);
        }

        $tasks = $query->paginate(20);

        $projects = Project::orderBy('name')->get();
        $statuses = [
            'pending' => 'Oczekujące',
            'in_progress' => 'W trakcie',
            'completed' => 'Zakończone',
            'cancelled' => 'Anulowane',
        ];

        // Determine if we're in /mine/* context
        $isMineView = $this->filterProjectIds !== null && !empty($this->filterProjectIds);
        
        return view('livewire.tasks-table', [
            'tasks' => $tasks,
            'projects' => $projects,
            'statuses' => $statuses,
            'isMineView' => $isMineView,
        ]);
    }
}
