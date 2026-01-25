<?php

namespace App\Livewire;

use App\Models\ProjectAssignment;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Role;
use Livewire\Component;
use Livewire\WithPagination;

class AssignmentsTable extends Component
{
    use WithPagination;

    public $searchEmployee = '';
    public $searchProject = '';
    public $searchRole = '';
    public $status = '';
    public $dateFrom = '';
    public $dateTo = '';

    protected $queryString = [
        'searchEmployee' => ['except' => ''],
        'searchProject' => ['except' => ''],
        'searchRole' => ['except' => ''],
        'status' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];
    protected $updatesQueryString = ['searchEmployee', 'searchProject', 'searchRole', 'status', 'dateFrom', 'dateTo'];

    public function updating($name, $value)
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->searchEmployee = '';
        $this->searchProject = '';
        $this->searchRole = '';
        $this->status = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = ProjectAssignment::with(['employee', 'project', 'role'])
            ->orderBy('start_date', 'asc');

        // Filter by employee
        if ($this->searchEmployee) {
            $query->whereHas('employee', function ($q) {
                $q->where('first_name', 'like', '%' . $this->searchEmployee . '%')
                  ->orWhere('last_name', 'like', '%' . $this->searchEmployee . '%');
            });
        }

        // Filter by project
        if ($this->searchProject) {
            $query->whereHas('project', function ($q) {
                $q->where('name', 'like', '%' . $this->searchProject . '%');
            });
        }

        // Filter by role
        if ($this->searchRole) {
            $query->whereHas('role', function ($q) {
                $q->where('name', 'like', '%' . $this->searchRole . '%');
            });
        }

        // Filter by status
        if ($this->status) {
            if ($this->status === 'active') {
                // For 'active', use scope active() which filters by dates
                $query->active()->where('is_cancelled', false);
            } elseif ($this->status === 'completed') {
                // For 'completed', filter by past assignments
                $query->where(function($q) {
                    $q->whereNotNull('end_date')
                      ->where('end_date', '<', now());
                })->where('is_cancelled', false);
            } elseif ($this->status === 'cancelled') {
                // For 'cancelled', filter by is_cancelled flag
                $query->where('is_cancelled', true);
            }
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

        $employees = Employee::orderBy('last_name')->get();
        $projects = Project::orderBy('name')->get();
        $roles = Role::orderBy('name')->get();

        return view('livewire.assignments-table', [
            'assignments' => $assignments,
            'employees' => $employees,
            'projects' => $projects,
            'roles' => $roles,
        ]);
    }
}
