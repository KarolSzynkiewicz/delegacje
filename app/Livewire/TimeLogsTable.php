<?php

namespace App\Livewire;

use App\Models\TimeLog;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectAssignment;
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

        // Pobierz listy dla dropdownów z filtrowaniem
        $employeesQuery = Employee::query();
        
        // Filtruj pracowników na podstawie wybranego projektu i zakresu dat
        if ($this->projectFilter || $this->dateFrom || $this->dateTo) {
            $employeesQuery->whereHas('assignments', function($q) {
                // Filtruj po projekcie
                if ($this->projectFilter) {
                    $q->where('project_id', $this->projectFilter);
                }
                
                // Filtruj po zakresie dat - przypisanie musi pokrywać się z zakresem
                if ($this->dateFrom || $this->dateTo) {
                    $q->where(function($dateQuery) {
                        if ($this->dateFrom && $this->dateTo) {
                            // Jeśli mamy obie daty, przypisanie musi pokrywać cały zakres
                            $dateQuery->where('start_date', '<=', $this->dateTo)
                                      ->where(function($q2) {
                                          $q2->whereNull('end_date')
                                             ->orWhere('end_date', '>=', $this->dateFrom);
                                      });
                        } elseif ($this->dateFrom) {
                            // Tylko data od - przypisanie musi być aktywne w tym dniu lub później
                            $dateQuery->where('start_date', '<=', $this->dateFrom)
                                      ->where(function($q2) {
                                          $q2->whereNull('end_date')
                                             ->orWhere('end_date', '>=', $this->dateFrom);
                                      });
                        } elseif ($this->dateTo) {
                            // Tylko data do - przypisanie musi być aktywne w tym dniu lub wcześniej
                            $dateQuery->where('start_date', '<=', $this->dateTo)
                                      ->where(function($q2) {
                                          $q2->whereNull('end_date')
                                             ->orWhere('end_date', '>=', $this->dateTo);
                                      });
                        }
                    });
                }
            });
        }
        
        $employees = $employeesQuery->orderBy('last_name')->orderBy('first_name')->get();
        $projects = Project::orderBy('name')->get();

        return view('livewire.time-logs-table', compact('timeLogs', 'employees', 'projects'));
    }
}
