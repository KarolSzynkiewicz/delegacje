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
    public $status = ''; // 'active', 'closed', or specific status
    
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
            // Prostsze zapytanie - bez joinów, użyj eager loading
            $query = ProjectTask::query();
            
            // Filtrowanie po projektach (dla /mine/*)
            if ($this->filterProjectIds && is_array($this->filterProjectIds) && !empty($this->filterProjectIds)) {
                $query->whereIn('project_id', $this->filterProjectIds);
            }
            
            // Filtrowanie po przypisanym użytkowniku (tylko jeśli ustawione)
            // UWAGA: Jeśli assignedToUserId jest ustawione, pokazuje tylko zadania przypisane do tego użytkownika
            // Jeśli chcesz zobaczyć wszystkie zadania, nie ustawiaj assignedToUserId
            if ($this->assignedToUserId) {
                $query->where('assigned_to', $this->assignedToUserId);
            }
            
            // Filter by project (including tasks without project)
            if ($this->searchProject) {
                $searchLower = strtolower($this->searchProject);
                if ($searchLower === 'brak projektu' || $searchLower === 'bez projektu') {
                    $query->whereNull('project_id');
                } else {
                    $query->whereHas('project', function ($q) {
                        $q->where('name', 'like', '%' . $this->searchProject . '%');
                    });
                }
            }

            // Filter by task name
            if ($this->searchTask) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->searchTask . '%')
                      ->orWhere('description', 'like', '%' . $this->searchTask . '%');
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
            
            $query->orderBy('due_date', 'asc')->orderBy('created_at', 'desc');
            
            // ✅ NAPRAWIONE: Eager loading PRZED paginacją (zapobiega N+1 query)
            $query->with(['project', 'assignedTo', 'createdBy']);
            
            $tasks = $query->paginate(20);
        } catch (\Exception $e) {
            \Log::error('TasksTable render error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            // Zwróć puste wyniki zamiast błędu
            $tasks = ProjectTask::whereRaw('1 = 0')->paginate(20);
        }

        // ✅ OPTYMALIZACJA: Ładuj projekty tylko jeśli są potrzebne (dla filtrowania)
        // Jeśli nie ma filtrowania po projekcie, nie ładuj wszystkich projektów
        $projects = $this->searchProject ? Project::orderBy('name')->get() : collect([]);
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
