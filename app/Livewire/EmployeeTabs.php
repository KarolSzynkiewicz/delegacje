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
        // Definicja wszystkich możliwych tabów z przypisanym permission i ikonami
        $allTabs = [
            'info' => ['label' => 'Informacje', 'permission' => null, 'icon' => 'bi bi-info-circle'],
            'documents' => ['label' => 'Dokumenty', 'permission' => 'employee-documents.view', 'icon' => 'bi bi-file-earmark-medical'],
            'rotations' => ['label' => 'Rotacje', 'permission' => 'rotations.view', 'icon' => 'bi bi-arrow-repeat'],
            'assignments' => ['label' => 'Przypisania do projektów', 'permission' => 'assignments.view', 'icon' => 'bi bi-person-check'],
            'vehicle-assignments' => ['label' => 'Przypisania do aut', 'permission' => 'vehicle-assignments.view', 'icon' => 'bi bi-car-front-fill'],
            'accommodation-assignments' => ['label' => 'Przypisania do domów', 'permission' => 'accommodation-assignments.view', 'icon' => 'bi bi-house-fill'],
            'payrolls' => ['label' => 'Płace', 'permission' => 'payrolls.view', 'icon' => 'bi bi-cash-stack'],
            'employee-rates' => ['label' => 'Stawki', 'permission' => 'employee-rates.view', 'icon' => 'bi bi-currency-dollar'],
            'advances' => ['label' => 'Zaliczki', 'permission' => 'advances.view', 'icon' => 'bi bi-wallet2'],
            'time-logs' => ['label' => 'Godziny', 'permission' => 'time-logs.view', 'icon' => 'bi bi-clock'],
            'adjustments' => ['label' => 'Kary i nagrody', 'permission' => 'adjustments.view', 'icon' => 'bi bi-award'],
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
        
        // Przygotuj taby dla komponentu
        $tabsForComponent = [];
        foreach ($this->availableTabs as $tabKey => $tab) {
            $count = match($tabKey) {
                'documents' => $this->employee->employee_documents_count ?? 0,
                'rotations' => $this->employee->rotations_count ?? 0,
                'assignments' => $this->employee->assignments_count ?? 0,
                'vehicle-assignments' => $this->employee->vehicle_assignments_count ?? 0,
                'accommodation-assignments' => $this->employee->accommodation_assignments_count ?? 0,
                'payrolls' => $this->employee->payrolls_count ?? 0,
                'employee-rates' => $employeeRatesCount,
                'advances' => $this->employee->advances_count ?? 0,
                'time-logs' => $timeLogsCount,
                'adjustments' => $this->employee->adjustments_count ?? 0,
                default => null,
            };
            
            $tabsForComponent[$tabKey] = [
                'label' => $tab['label'],
                'icon' => $tab['icon'] ?? null,
                'count' => $count,
                'wireClick' => "setTab('{$tabKey}')",
            ];
        }
        
        return view('livewire.employee-tabs', compact('tabData', 'employeeRatesCount', 'timeLogsCount', 'tabsForComponent'));
    }
}
