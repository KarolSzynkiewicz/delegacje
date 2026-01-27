<?php

namespace App\Livewire;

use App\Models\Employee;
use Livewire\Component;

class EmployeeTabs extends Component
{
    public Employee $employee;
    public string $activeTab = 'info';
    public array $availableTabs = [];

    protected $queryString = ['activeTab' => ['except' => 'info', 'as' => 'tab']];

    public function mount(Employee $employee)
    {
        $this->employee = $employee;
        $this->buildAvailableTabs();
        $this->validateActiveTab();
    }

    protected function buildAvailableTabs()
    {
        // Definicja wszystkich możliwych tabów z przypisanym permission
        $allTabs = [
            'info' => ['label' => 'Informacje', 'permission' => null],
            'documents' => ['label' => 'Dokumenty', 'permission' => 'employee-documents.view'],
            'rotations' => ['label' => 'Rotacje', 'permission' => 'rotations.view'],
            'assignments' => ['label' => 'Przypisania do projektów', 'permission' => 'assignments.view'],
            'vehicle-assignments' => ['label' => 'Przypisania do aut', 'permission' => 'vehicle-assignments.view'],
            'accommodation-assignments' => ['label' => 'Przypisania do domów', 'permission' => 'accommodation-assignments.view'],
            'payrolls' => ['label' => 'Płace', 'permission' => 'payrolls.view'],
            'employee-rates' => ['label' => 'Stawki', 'permission' => 'employee-rates.view'],
            'advances' => ['label' => 'Zaliczki', 'permission' => 'advances.view'],
            'time-logs' => ['label' => 'Godziny', 'permission' => 'time-logs.view'],
            'adjustments' => ['label' => 'Kary i nagrody', 'permission' => 'adjustments.view'],
        ];

        // Filtracja po permission - tylko taby do których user ma dostęp
        $this->availableTabs = array_filter($allTabs, function($tab) {
            // permission === null (np. info) zawsze dostępny
            // lub user ma wymagane permission
            return $tab['permission'] === null || auth()->user()->hasPermission($tab['permission']);
        });
    }

    protected function validateActiveTab()
    {
        if (!isset($this->availableTabs[$this->activeTab])) {
            $this->activeTab = array_key_first($this->availableTabs) ?? 'info';
        }
    }

    public function setTab(string $tab)
    {
        if (!isset($this->availableTabs[$tab])) {
            return; // Ignoruj, fallback w validateActiveTab()
        }
        $this->activeTab = $tab;
    }

    protected function getTabData()
    {
        // Filtracja przez relacje hasMany - bez osobnych route
        return match($this->activeTab) {
            'documents' => $this->employee->employeeDocuments()->with('document')->get(),
            'rotations' => $this->employee->rotations()->get(),
            'assignments' => $this->employee->assignments()->with(['project', 'role'])->orderBy('start_date', 'desc')->get(),
            'vehicle-assignments' => $this->employee->vehicleAssignments()->with('vehicle')->orderBy('start_date', 'desc')->get(),
            'accommodation-assignments' => $this->employee->accommodationAssignments()->with('accommodation')->orderBy('start_date', 'desc')->get(),
            'payrolls' => $this->employee->payrolls()->orderBy('period_start', 'desc')->get(),
            'employee-rates' => \App\Models\EmployeeRate::where('employee_id', $this->employee->id)->orderBy('start_date', 'desc')->get(),
            'advances' => $this->employee->advances()->orderBy('date', 'desc')->get(),
            'time-logs' => \App\Models\TimeLog::whereHas('projectAssignment', function($query) {
                    $query->where('employee_id', $this->employee->id);
                })
                ->with(['projectAssignment.project', 'projectAssignment.role'])
                ->orderBy('start_time', 'desc')
                ->get(),
            'adjustments' => $this->employee->adjustments()->orderBy('date', 'desc')->get(),
            default => null,
        };
    }

    public function render()
    {
        $tabData = $this->getTabData();
        
        // Load counts for tabs - użyj snake_case dla loadCount
        $this->employee->loadCount([
            'employeeDocuments',
            'rotations',
            'assignments',
            'vehicleAssignments',
            'accommodationAssignments',
            'payrolls',
            'advances',
            'adjustments'
        ]);
        
        // Load roles for info tab
        $this->employee->load('roles');
        
        // Load employee rates count manually
        $employeeRatesCount = \App\Models\EmployeeRate::where('employee_id', $this->employee->id)->count();
        
        // Load time logs count manually
        $timeLogsCount = \App\Models\TimeLog::whereHas('projectAssignment', function($query) {
            $query->where('employee_id', $this->employee->id);
        })->count();
        
        return view('livewire.employee-tabs', compact('tabData', 'employeeRatesCount', 'timeLogsCount'));
    }
}
