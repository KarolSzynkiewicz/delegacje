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
        try {
            // Używamy leftJoin dla project żeby obsłużyć nullable project_id
            $query = ProjectTask::query()
                ->leftJoin('projects', 'project_tasks.project_id', '=', 'projects.id')
                ->leftJoin('users as assigned_users', 'project_tasks.assigned_to', '=', 'assigned_users.id')
                ->leftJoin('users as created_users', 'project_tasks.created_by', '=', 'created_users.id')
                ->select(
                    'project_tasks.*',
                    'projects.name as project_name',
                    'projects.id as project_id_real',
                    'assigned_users.name as assigned_user_name',
                    'created_users.name as created_user_name'
                );
            
            // Filtrowanie po projektach (dla /mine/*)
            if ($this->filterProjectIds && is_array($this->filterProjectIds) && !empty($this->filterProjectIds)) {
                $query->whereIn('project_tasks.project_id', $this->filterProjectIds);
            }
            
            // Filtrowanie po przypisanym użytkowniku
            if ($this->assignedToUserId) {
                $query->where('project_tasks.assigned_to', $this->assignedToUserId);
            }
            
            $query->orderBy('project_tasks.due_date', 'asc')
                  ->orderBy('project_tasks.created_at', 'desc');

            // Filter by project (including tasks without project)
            if ($this->searchProject) {
                $searchLower = strtolower($this->searchProject);
                if ($searchLower === 'brak projektu' || $searchLower === 'bez projektu') {
                    $query->whereNull('project_tasks.project_id');
                } else {
                    $query->where('projects.name', 'like', '%' . $this->searchProject . '%');
                }
            }

            // Filter by task name
            if ($this->searchTask) {
                $query->where(function ($q) {
                    $q->where('project_tasks.name', 'like', '%' . $this->searchTask . '%')
                      ->orWhere('project_tasks.description', 'like', '%' . $this->searchTask . '%');
                });
            }

            // Filter by status
            if ($this->status) {
                $query->where('project_tasks.status', $this->status);
            }

            $tasks = $query->paginate(20);
            
            // Załaduj relacje dla każdego zadania
            $taskIds = $tasks->pluck('id')->toArray();
            $tasksWithRelations = ProjectTask::with(['project', 'assignedTo', 'createdBy'])
                ->whereIn('id', $taskIds)
                ->get()
                ->keyBy('id');
            
            // Zastąp zadania z joinów modelami z relacjami
            $tasks->getCollection()->transform(function ($task) use ($tasksWithRelations) {
                return $tasksWithRelations->get($task->id) ?? $task;
            });
        } catch (\Exception $e) {
            // Fallback - użyj prostszego zapytania
            \Log::error('TasksTable render error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            try {
                // Prostsze zapytanie bez joinów
                $query = ProjectTask::query();
                
                if ($this->filterProjectIds && is_array($this->filterProjectIds) && !empty($this->filterProjectIds)) {
                    $query->whereIn('project_id', $this->filterProjectIds);
                }
                
                if ($this->assignedToUserId) {
                    $query->where('assigned_to', $this->assignedToUserId);
                }
                
                if ($this->searchTask) {
                    $query->where(function ($q) {
                        $q->where('name', 'like', '%' . $this->searchTask . '%')
                          ->orWhere('description', 'like', '%' . $this->searchTask . '%');
                    });
                }
                
                if ($this->status) {
                    $query->where('status', $this->status);
                }
                
                $tasks = $query->orderBy('due_date', 'asc')
                               ->orderBy('created_at', 'desc')
                               ->paginate(20);
                
                // Załaduj relacje
                $tasks->load(['project', 'assignedTo', 'createdBy']);
            } catch (\Exception $e2) {
                \Log::error('TasksTable fallback error: ' . $e2->getMessage());
                $tasks = ProjectTask::whereRaw('1 = 0')->paginate(20);
            }
        }

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
