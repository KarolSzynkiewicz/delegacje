<?php

namespace App\Livewire;

use App\Livewire\Concerns\ScopesToEmployee;
use App\Models\Project;
use App\Models\ProjectAssignment;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class AssignmentsTable extends Component
{
    use ScopesToEmployee;
    use WithoutUrlPagination;
    use WithPagination;

    public $searchEmployee = '';

    public $searchProject = '';

    public $searchRole = '';

    public $projectFilter = '';

    public $status = '';

    public $dateFrom = '';

    public $dateTo = '';

    public $filterProjectIds = null;

    protected function queryString(): array
    {
        return $this->scopedQueryString([
            'searchEmployee' => ['except' => ''],
            'searchProject' => ['except' => ''],
            'searchRole' => ['except' => ''],
            'projectFilter' => ['except' => ''],
            'status' => ['except' => ''],
            'dateFrom' => ['except' => ''],
            'dateTo' => ['except' => ''],
        ]);
    }

    public function updating($name, $value)
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        if (! $this->isEmployeeScoped()) {
            $this->searchEmployee = '';
        }
        $this->searchProject = '';
        $this->searchRole = '';
        $this->projectFilter = '';
        $this->status = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return (bool) ((! $this->isEmployeeScoped() && $this->searchEmployee)
            || $this->searchProject
            || $this->searchRole
            || $this->projectFilter
            || $this->status
            || $this->dateFrom
            || $this->dateTo);
    }

    public function paginationView()
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function render()
    {
        $query = ProjectAssignment::with(['employee', 'project', 'role']);

        if ($this->isEmployeeScoped()) {
            $query->where('employee_id', $this->employeeId);
        }

        if ($this->filterProjectIds && is_array($this->filterProjectIds) && ! empty($this->filterProjectIds)) {
            $query->whereIn('project_id', $this->filterProjectIds);
        }

        $query->orderBy('start_date', 'desc');

        if (! $this->isEmployeeScoped() && $this->searchEmployee) {
            $query->whereHas('employee', function ($q) {
                $q->where('first_name', 'like', '%'.$this->searchEmployee.'%')
                    ->orWhere('last_name', 'like', '%'.$this->searchEmployee.'%');
            });
        }

        if ($this->projectFilter !== '' && $this->projectFilter !== null) {
            $query->where('project_id', (int) $this->projectFilter);
        }

        if ($this->searchProject) {
            $query->whereHas('project', function ($q) {
                $q->where('name', 'like', '%'.$this->searchProject.'%');
            });
        }

        if ($this->searchRole) {
            $query->whereHas('role', function ($q) {
                $q->where('name', 'like', '%'.$this->searchRole.'%');
            });
        }

        if ($this->status) {
            if ($this->status === 'active') {
                $query->active();
            } elseif ($this->status === 'completed') {
                $query->where(function ($q) {
                    $q->whereNotNull('end_date')
                        ->where('end_date', '<', now());
                });
            }
        }

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

        $projects = $this->isEmployeeScoped()
            ? Project::query()
                ->whereIn('id', ProjectAssignment::query()->where('employee_id', $this->employeeId)->select('project_id'))
                ->orderBy('name')
                ->get()
            : Project::orderBy('name')->get();

        return view('livewire.assignments-table', [
            'assignments' => $assignments,
            'projects' => $projects,
        ]);
    }
}
