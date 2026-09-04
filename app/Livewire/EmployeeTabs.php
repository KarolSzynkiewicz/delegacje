<?php

namespace App\Livewire;

use App\Enums\EmployeeTerminationReason;
use App\Models\Employee;
use App\Models\EquipmentIssue;
use App\Services\EmployeeLifecycleService;
use Livewire\Component;

class EmployeeTabs extends Component
{
    public Employee $employee;

    public string $activeTab = 'info';

    public array $availableTabs = [];

    public bool $showTerminateModal = false;

    public string $terminationReason = '';

    public string $terminationNote = '';

    protected $queryString = ['activeTab' => ['except' => 'info', 'as' => 'tab']];

    public function mount(Employee $employee)
    {
        $this->employee = $employee;
        $this->buildAvailableTabs();
        $this->validateActiveTab();
    }

    public function openTerminateModal(): void
    {
        if (! auth()->user()->hasPermission('employees.update')) {
            return;
        }

        $this->terminationReason = '';
        $this->terminationNote = '';
        $this->showTerminateModal = true;
    }

    public function closeTerminateModal(): void
    {
        $this->showTerminateModal = false;
    }

    public function terminate(): void
    {
        if (! auth()->user()->hasPermission('employees.update')) {
            return;
        }

        $this->validate([
            'terminationReason' => ['required', 'in:'.implode(',', array_column(EmployeeTerminationReason::cases(), 'value'))],
            'terminationNote' => ['nullable', 'string', 'max:2000'],
        ], [
            'terminationReason.required' => 'Wybierz powód zwolnienia.',
        ]);

        app(EmployeeLifecycleService::class)->terminate(
            $this->employee,
            EmployeeTerminationReason::from($this->terminationReason),
            $this->terminationNote !== '' ? $this->terminationNote : null,
        );

        $this->employee = $this->employee->fresh();
        $this->showTerminateModal = false;

        session()->flash('success', 'Pracownik został zwolniony.');
    }

    public function reinstate(): void
    {
        if (! auth()->user()->hasPermission('employees.update')) {
            return;
        }

        app(EmployeeLifecycleService::class)->reinstate($this->employee);

        $this->employee = $this->employee->fresh();

        session()->flash('success', 'Zwolnienie zostało cofnięte.');
    }

    protected function buildAvailableTabs()
    {
        // Definicja wszystkich możliwych tabów z przypisanym permission i ikonami
        $allTabs = [
            'info' => ['label' => 'Informacje', 'short' => 'Informacje', 'group' => 'Profil', 'permission' => null, 'icon' => 'bi bi-person'],
            'documents' => ['label' => 'Dokumenty', 'short' => 'Dokumenty', 'group' => 'Profil', 'permission' => 'employee-documents.view', 'icon' => 'bi bi-file-earmark-medical'],
            'rotations' => ['label' => 'Rotacje', 'short' => 'Rotacje', 'group' => 'Praca', 'permission' => 'rotations.view', 'icon' => 'bi bi-arrow-repeat'],
            'assignments' => ['label' => 'Przypisania do projektów', 'short' => 'Projekty', 'group' => 'Praca', 'permission' => 'project-assignments.view', 'icon' => 'bi bi-person-check'],
            'vehicle-assignments' => ['label' => 'Przypisania do aut', 'short' => 'Auta', 'group' => 'Praca', 'permission' => 'vehicle-assignments.view', 'icon' => 'bi bi-car-front-fill'],
            'accommodation-assignments' => ['label' => 'Przypisania do domów', 'short' => 'Domy', 'group' => 'Praca', 'permission' => 'accommodation-assignments.view', 'icon' => 'bi bi-house-fill'],
            'equipment' => ['label' => 'Asortyment', 'short' => 'Asortyment', 'group' => 'Praca', 'permission' => 'equipment.view', 'icon' => 'bi bi-box-seam'],
            'time-logs' => ['label' => 'Godziny', 'short' => 'Godziny', 'group' => 'Praca', 'permission' => 'time-logs.view', 'icon' => 'bi bi-clock'],
            'payrolls' => ['label' => 'Płace', 'short' => 'Płace', 'group' => 'Kadry', 'permission' => 'payrolls.view', 'icon' => 'bi bi-cash-stack'],
            'employee-rates' => ['label' => 'Stawki', 'short' => 'Stawki', 'group' => 'Kadry', 'permission' => 'employee-rates.view', 'icon' => 'bi bi-currency-dollar'],
            'bank' => ['label' => 'Bank', 'short' => 'Bank', 'group' => 'Kadry', 'permission' => 'employee-bank-accounts.view', 'icon' => 'bi bi-bank'],
            'company-assignments' => ['label' => 'Przypisania do spółek', 'short' => 'Spółki', 'group' => 'Kadry', 'permission' => 'company-assignments.view', 'icon' => 'bi bi-building'],
            'advances' => ['label' => 'Zaliczki', 'short' => 'Zaliczki', 'group' => 'Kadry', 'permission' => 'advances.view', 'icon' => 'bi bi-wallet2'],
            'evaluations' => ['label' => 'Oceny', 'short' => 'Oceny', 'group' => 'Kadry', 'permission' => 'employee-evaluations.view', 'icon' => 'bi bi-star-fill'],
            'adjustments' => ['label' => 'Obciążenia i uznania', 'short' => 'Obciążenia', 'group' => 'Kadry', 'permission' => 'adjustments.view', 'icon' => 'bi bi-award'],
        ];

        // Filtracja po permission - tylko taby do których user ma dostęp
        $this->availableTabs = array_filter($allTabs, function ($tab) {
            // permission === null (np. info) zawsze dostępny
            // lub user ma wymagane permission
            return $tab['permission'] === null || auth()->user()->hasPermission($tab['permission']);
        });
    }

    protected function validateActiveTab()
    {
        if (! isset($this->availableTabs[$this->activeTab])) {
            $this->activeTab = array_key_first($this->availableTabs) ?? 'info';
        }
    }

    public function setTab(string $tab)
    {
        if (! isset($this->availableTabs[$tab])) {
            return; // Ignoruj, fallback w validateActiveTab()
        }
        $this->activeTab = $tab;
    }

    protected function getTabData()
    {
        // Filtracja przez relacje hasMany - bez osobnych route
        return match ($this->activeTab) {
            'documents' => $this->employee->employeeDocuments()->with('document')->get(),
            'payrolls' => $this->employee->payrolls()->orderBy('period_start', 'desc')->get(),
            'employee-rates' => \App\Models\EmployeeRate::where('employee_id', $this->employee->id)->orderBy('start_date', 'desc')->get(),
            'bank' => $this->employee->bankAccounts()->orderBy('start_date', 'desc')->get(),
            'company-assignments' => $this->employee->companyAssignments()->with('company')->orderBy('start_date', 'desc')->get(),
            'advances' => $this->employee->advances()->orderBy('date', 'desc')->get(),
            'evaluations' => $this->employee->evaluations()->with('createdBy')->orderBy('created_at', 'desc')->get(),
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
            'companyAssignments',
            'payrolls',
            'advances',
            'evaluations',
            'adjustments',
            'bankAccounts',
            'equipmentIssues' => fn ($issues) => $issues->whereNotIn('status', [
                EquipmentIssue::STATUS_UNFULFILLED,
            ]),
        ]);

        // Load roles for info tab
        $this->employee->load('roles');

        // Load employee rates count manually
        $employeeRatesCount = \App\Models\EmployeeRate::where('employee_id', $this->employee->id)->count();

        // Load time logs count manually
        $timeLogsCount = \App\Models\TimeLog::whereHas('projectAssignment', function ($query) {
            $query->where('employee_id', $this->employee->id);
        })->count();

        // Przygotuj taby dla komponentu
        $tabsForComponent = [];
        foreach ($this->availableTabs as $tabKey => $tab) {
            $count = match ($tabKey) {
                'documents' => $this->employee->employee_documents_count ?? 0,
                'rotations' => $this->employee->rotations_count ?? 0,
                'assignments' => $this->employee->assignments_count ?? 0,
                'vehicle-assignments' => $this->employee->vehicle_assignments_count ?? 0,
                'accommodation-assignments' => $this->employee->accommodation_assignments_count ?? 0,
                'company-assignments' => $this->employee->company_assignments_count ?? 0,
                'payrolls' => $this->employee->payrolls_count ?? 0,
                'employee-rates' => $employeeRatesCount,
                'bank' => $this->employee->bank_accounts_count ?? 0,
                'advances' => $this->employee->advances_count ?? 0,
                'time-logs' => $timeLogsCount,
                'evaluations' => $this->employee->evaluations_count ?? 0,
                'adjustments' => $this->employee->adjustments_count ?? 0,
                'equipment' => $this->employee->equipment_issues_count ?? 0,
                default => null,
            };

            $tabsForComponent[$tabKey] = [
                'label' => $tab['label'],
                'short' => $tab['short'] ?? $tab['label'],
                'group' => $tab['group'] ?? 'Profil',
                'icon' => $tab['icon'] ?? null,
                'count' => $count,
                'wireClick' => "setTab('{$tabKey}')",
            ];
        }

        $tabGroups = [];
        foreach ($tabsForComponent as $tabKey => $tab) {
            $tabGroups[$tab['group']][$tabKey] = $tab;
        }

        $currentBankAccount = $this->employee->currentBankAccount();
        $locationStatus = app(\App\Services\LocationTrackingService::class)
            ->getLocationStatus($this->employee, now());
        $initials = mb_strtoupper(
            mb_substr((string) $this->employee->first_name, 0, 1).
            mb_substr((string) $this->employee->last_name, 0, 1)
        );

        return view('livewire.employee-tabs', compact(
            'tabData',
            'employeeRatesCount',
            'timeLogsCount',
            'tabsForComponent',
            'tabGroups',
            'currentBankAccount',
            'locationStatus',
            'initials',
        ));
    }
}
