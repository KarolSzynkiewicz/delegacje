<?php

namespace App\Livewire;

use App\Livewire\Concerns\ScopesToEmployee;
use App\Models\Employee;
use App\Models\Project;
use App\Models\TimeLog;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class TimeLogsTable extends Component
{
    use ScopesToEmployee;
    use WithoutUrlPagination;
    use WithPagination;

    public $employeeFilter = '';

    public $projectFilter = '';

    public $dateFrom = '';

    public $dateTo = '';

    public $filterAssignmentIds = null;

    public $isMineView = false;

    public function mount()
    {
        if ($this->filterAssignmentIds && is_array($this->filterAssignmentIds) && ! empty($this->filterAssignmentIds)) {
            $this->isMineView = true;
            $this->projectFilter = '';
        }
    }

    protected function queryString(): array
    {
        return $this->scopedQueryString([
            'employeeFilter' => ['except' => ''],
            'projectFilter' => ['except' => ''],
            'dateFrom' => ['except' => ''],
            'dateTo' => ['except' => ''],
        ]);
    }

    public function updatingEmployeeFilter()
    {
        $this->resetPage();
    }

    public function updatingProjectFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        if (! $this->isEmployeeScoped()) {
            $this->employeeFilter = '';
        }
        if (! $this->isMineView) {
            $this->projectFilter = '';
        }
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return (bool) ((! $this->isEmployeeScoped() && $this->employeeFilter)
            || (! $this->isMineView && $this->projectFilter)
            || $this->dateFrom
            || $this->dateTo);
    }

    public function paginationView()
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function render()
    {
        $query = TimeLog::with('projectAssignment.employee', 'projectAssignment.project');

        $isMineView = $this->filterAssignmentIds && is_array($this->filterAssignmentIds) && ! empty($this->filterAssignmentIds);

        if ($isMineView) {
            $query->whereIn('project_assignment_id', $this->filterAssignmentIds);
            $this->isMineView = true;
            if ($this->projectFilter) {
                $this->projectFilter = '';
            }
        } else {
            $this->isMineView = false;
        }

        if ($this->isEmployeeScoped()) {
            $query->whereHas('projectAssignment', function ($q) {
                $q->where('employee_id', $this->employeeId);
            });
        } elseif ($this->employeeFilter) {
            $query->whereHas('projectAssignment', function ($q) {
                $q->where('employee_id', $this->employeeFilter);
            });
        }

        if ($this->projectFilter && ! $this->isMineView) {
            $query->whereHas('projectAssignment', function ($q) {
                $q->where('project_id', $this->projectFilter);
            });
        }

        if ($this->dateFrom) {
            $query->whereDate('start_time', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('start_time', '<=', $this->dateTo);
        }

        $totalHours = (clone $query)->sum('hours_worked');

        $timeLogs = $query->orderBy('start_time', 'desc')->paginate(20);

        $employees = collect();
        if (! $this->isEmployeeScoped()) {
            $employeesQuery = Employee::query();

            if ($this->projectFilter || $this->dateFrom || $this->dateTo) {
                $employeesQuery->join('project_assignments', 'employees.id', '=', 'project_assignments.employee_id')
                    ->distinct();

                if ($this->projectFilter) {
                    $employeesQuery->where('project_assignments.project_id', $this->projectFilter);
                }

                if ($this->dateFrom || $this->dateTo) {
                    $employeesQuery->where(function ($dateQuery) {
                        if ($this->dateFrom && $this->dateTo) {
                            $dateQuery->where('project_assignments.start_date', '<=', $this->dateTo)
                                ->where(function ($q2) {
                                    $q2->whereNull('project_assignments.end_date')
                                        ->orWhere('project_assignments.end_date', '>=', $this->dateFrom);
                                });
                        } elseif ($this->dateFrom) {
                            $dateQuery->where('project_assignments.start_date', '<=', $this->dateFrom)
                                ->where(function ($q2) {
                                    $q2->whereNull('project_assignments.end_date')
                                        ->orWhere('project_assignments.end_date', '>=', $this->dateFrom);
                                });
                        } elseif ($this->dateTo) {
                            $dateQuery->where('project_assignments.start_date', '<=', $this->dateTo)
                                ->where(function ($q2) {
                                    $q2->whereNull('project_assignments.end_date')
                                        ->orWhere('project_assignments.end_date', '>=', $this->dateTo);
                                });
                        }
                    });
                }

                $employeesQuery->select('employees.*');
            }

            $employees = $employeesQuery->orderBy('employees.last_name')->orderBy('employees.first_name')->get();
        }

        if ($isMineView) {
            $userProjectIds = auth()->user()->getManagedProjectIds();
            $projects = Project::query()
                ->whereIn('id', $userProjectIds)
                ->orderBy('name')
                ->get();
        } else {
            $projects = Project::query()->orderBy('name')->get();
        }

        return view('livewire.time-logs-table', compact('timeLogs', 'employees', 'projects', 'totalHours'));
    }
}
