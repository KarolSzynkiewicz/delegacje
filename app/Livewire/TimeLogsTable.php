<?php

namespace App\Livewire;

use App\Models\TimeLog;
use App\Models\Employee;
use App\Models\Project;
use Livewire\Component;
use Livewire\WithPagination;

class TimeLogsTable extends Component
{
    use WithPagination;

    public $employeeFilter = '';
    public $projectFilter = '';
    public $dateFrom = '';
    public $dateTo = '';

    protected $queryString = [
        'employeeFilter' => ['except' => ''],
        'projectFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

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
        $this->employeeFilter = '';
        $this->projectFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = TimeLog::with('projectAssignment.employee', 'projectAssignment.project');

        // Filtrowanie po pracowniku
        if ($this->employeeFilter) {
            $query->whereHas('projectAssignment', function($q) {
                $q->where('employee_id', $this->employeeFilter);
            });
        }

        // Filtrowanie po projekcie
        if ($this->projectFilter) {
            $query->whereHas('projectAssignment', function($q) {
                $q->where('project_id', $this->projectFilter);
            });
        }

        // Filtrowanie po dacie od
        if ($this->dateFrom) {
            $query->whereDate('start_time', '>=', $this->dateFrom);
        }

        // Filtrowanie po dacie do
        if ($this->dateTo) {
            $query->whereDate('start_time', '<=', $this->dateTo);
        }

        $timeLogs = $query->orderBy('start_time', 'desc')
            ->paginate(20);

        // Pobierz listy dla dropdownów
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        $projects = Project::orderBy('name')->get();

        return view('livewire.time-logs-table', compact('timeLogs', 'employees', 'projects'));
    }
}
