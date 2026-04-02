<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\ProjectAssignment;
use Livewire\Component;
use Livewire\WithPagination;

class AssignmentsTable extends Component
{
    use WithPagination;

    public $searchEmployee = '';

    public $searchProject = '';

    public $searchRole = '';

    /** Filtr po konkretnym projekcie (ID z listy) */
    public $projectFilter = '';

    public $status = '';

    public $dateFrom = '';

    public $dateTo = '';

    // Optional filter for /mine/* routes
    public $filterProjectIds = null;

    protected $queryString = [
        'searchEmployee' => ['except' => ''],
        'searchProject' => ['except' => ''],
        'searchRole' => ['except' => ''],
        'projectFilter' => ['except' => ''],
        'status' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    protected $updatesQueryString = ['searchEmployee', 'searchProject', 'searchRole', 'projectFilter', 'status', 'dateFrom', 'dateTo'];

    public function updating($name, $value)
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->searchEmployee = '';
        $this->searchProject = '';
        $this->searchRole = '';
        $this->projectFilter = '';
        $this->status = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function paginationView()
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function render()
    {
        $query = ProjectAssignment::with(['employee', 'project', 'role']);

        // Filtrowanie po projektach (dla /mine/*)
        if ($this->filterProjectIds && is_array($this->filterProjectIds) && ! empty($this->filterProjectIds)) {
            $query->whereIn('project_id', $this->filterProjectIds);
        }

        $query->orderBy('start_date', 'asc');

        // Filter by employee
        if ($this->searchEmployee) {
            $query->whereHas('employee', function ($q) {
                $q->where('first_name', 'like', '%'.$this->searchEmployee.'%')
                    ->orWhere('last_name', 'like', '%'.$this->searchEmployee.'%');
            });
        }

        // Filtr: konkretny projekt (select)
        if ($this->projectFilter !== '' && $this->projectFilter !== null) {
            $query->where('project_id', (int) $this->projectFilter);
        }

        // Filter by project name (tekst)
        if ($this->searchProject) {
            $query->whereHas('project', function ($q) {
                $q->where('name', 'like', '%'.$this->searchProject.'%');
            });
        }

        // Filter by role
        if ($this->searchRole) {
            $query->whereHas('role', function ($q) {
                $q->where('name', 'like', '%'.$this->searchRole.'%');
            });
        }

        // Filter by status
        if ($this->status) {
            if ($this->status === 'active') {
                // For 'active', use scope active() which filters by dates
                $query->active();
            } elseif ($this->status === 'completed') {
                // For 'completed', filter by past assignments
                $query->where(function ($q) {
                    $q->whereNotNull('end_date')
                        ->where('end_date', '<', now());
                });
            }
            // Note: 'cancelled' filter removed - assignments are physically deleted when cancelled
        }

        // Filter by date range
        if ($this->dateFrom) {
            $query->where('start_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where(function ($q) {
                $q->where('end_date', '<=', $this->dateTo)
                    ->orWhereNull('end_date');
            });
        }

        $assignments = $query->paginate(20);

        $projects = Project::orderBy('name')->get();

        return view('livewire.assignments-table', [
            'assignments' => $assignments,
            'projects' => $projects,
        ]);
    }
}
