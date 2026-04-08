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
    
    // Optional filter for /mine/* routes
    public $filterAssignmentIds = null;
    public $isMineView = false; // Flag to hide project filter in /mine/* views

    protected $queryString = [
        'employeeFilter' => ['except' => ''],
        'projectFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];
    
    public function mount()
    {
        // Jeśli jesteśmy w widoku /mine/*, wyczyść projectFilter z query string
        if ($this->filterAssignmentIds && is_array($this->filterAssignmentIds) && !empty($this->filterAssignmentIds)) {
            $this->isMineView = true;
            $this->projectFilter = '';
        }
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
        $this->employeeFilter = '';
        // Nie czyść projectFilter w widoku /mine/* - i tak nie jest używany
        if (!$this->isMineView) {
            $this->projectFilter = '';
        }
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
        $query = TimeLog::with('projectAssignment.employee', 'projectAssignment.project');
        
        // Sprawdź czy to widok /mine/* (ma filterAssignmentIds) czy globalny /time-logs
        $isMineView = $this->filterAssignmentIds && is_array($this->filterAssignmentIds) && !empty($this->filterAssignmentIds);
        
        // Filtrowanie po przypisaniach (dla /mine/*)
        if ($isMineView) {
            $query->whereIn('project_assignment_id', $this->filterAssignmentIds);
            $this->isMineView = true;
            // Wyczyść projectFilter jeśli był ustawiony w query string
            if ($this->projectFilter) {
                $this->projectFilter = '';
            }
        } else {
            // Widok globalny /time-logs - middleware już sprawdził uprawnienia, pokazuj wszystko
            $this->isMineView = false;
        }

        // Filtrowanie po pracowniku
        if ($this->employeeFilter) {
            $query->whereHas('projectAssignment', function($q) {
                $q->where('employee_id', $this->employeeFilter);
            });
        }

        // Filtrowanie po projekcie (tylko jeśli nie jesteśmy w widoku /mine/*)
        if ($this->projectFilter && !$this->isMineView) {
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

        // Suma godzin dla aktualnych filtrów (bez paginacji)
        $totalHours = (clone $query)->sum('hours_worked');

        $timeLogs = $query->orderBy('start_time', 'desc')
            ->paginate(20);

        // OPTIMIZATION: Pobierz listy dla dropdownów z filtrowaniem
        // Używamy join zamiast whereHas dla lepszej wydajności
        $employeesQuery = Employee::query();
        
        // Filtruj pracowników na podstawie wybranego projektu i zakresu dat
        if ($this->projectFilter || $this->dateFrom || $this->dateTo) {
            $employeesQuery->join('project_assignments', 'employees.id', '=', 'project_assignments.employee_id')
                ->distinct();
            
            // Filtruj po projekcie
            if ($this->projectFilter) {
                $employeesQuery->where('project_assignments.project_id', $this->projectFilter);
            }
            
            // Filtruj po zakresie dat - przypisanie musi pokrywać się z zakresem
            if ($this->dateFrom || $this->dateTo) {
                $employeesQuery->where(function($dateQuery) {
                    if ($this->dateFrom && $this->dateTo) {
                        // Jeśli mamy obie daty, przypisanie musi pokrywać cały zakres
                        $dateQuery->where('project_assignments.start_date', '<=', $this->dateTo)
                                  ->where(function($q2) {
                                      $q2->whereNull('project_assignments.end_date')
                                         ->orWhere('project_assignments.end_date', '>=', $this->dateFrom);
                                  });
                    } elseif ($this->dateFrom) {
                        // Tylko data od - przypisanie musi być aktywne w tym dniu lub później
                        $dateQuery->where('project_assignments.start_date', '<=', $this->dateFrom)
                                  ->where(function($q2) {
                                      $q2->whereNull('project_assignments.end_date')
                                         ->orWhere('project_assignments.end_date', '>=', $this->dateFrom);
                                  });
                    } elseif ($this->dateTo) {
                        // Tylko data do - przypisanie musi być aktywne w tym dniu lub wcześniej
                        $dateQuery->where('project_assignments.start_date', '<=', $this->dateTo)
                                  ->where(function($q2) {
                                      $q2->whereNull('project_assignments.end_date')
                                         ->orWhere('project_assignments.end_date', '>=', $this->dateTo);
                                  });
                    }
                });
            }
            
            $employeesQuery->select('employees.*');
        }
        
        $employees = $employeesQuery->orderBy('employees.last_name')->orderBy('employees.first_name')->get();
        
        // Projekty w dropdownie
        if ($isMineView) {
            // W widoku /mine/* - pokaż tylko projekty z przypisań kierownika
            $userProjectIds = auth()->user()->getManagedProjectIds();
            $projects = Project::query()
                ->whereIn('id', $userProjectIds)
                ->orderBy('name')
                ->get();
        } else {
            // Widok globalny /time-logs - pokaż wszystkie projekty (middleware już sprawdził uprawnienia)
            $projects = Project::query()->orderBy('name')->get();
        }

        return view('livewire.time-logs-table', compact('timeLogs', 'employees', 'projects', 'totalHours'));
    }
}
