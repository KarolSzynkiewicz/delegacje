<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeesTable extends Component
{
    use WithPagination;

    public $search = '';

    public $roleFilter = '';

    public $locationFilter = '';

    public $rotationFilter = '';

    public $companyFilter = '';

    public $statusDate = ''; // Nowy filtr daty

    public $sortField = 'last_name';

    public $sortDirection = 'asc';

    // Optional filter for /mine/* routes
    public $filterEmployeeIds = null;

    public $filterProjectIds = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'roleFilter' => ['except' => ''],
        'locationFilter' => ['except' => ''],
        'rotationFilter' => ['except' => ''],
        'companyFilter' => ['except' => ''],
        'statusDate' => ['except' => ''],
        'sortField' => ['except' => 'last_name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }

    public function updatingLocationFilter()
    {
        $this->resetPage();
    }

    public function updatingRotationFilter()
    {
        $this->resetPage();
    }

    public function updatingCompanyFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusDate()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->roleFilter = '';
        $this->locationFilter = '';
        $this->rotationFilter = '';
        $this->companyFilter = '';
        $this->statusDate = '';
        $this->sortField = 'last_name';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    public function paginationView()
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function render()
    {
        // OPTIMIZATION: Eager load all relations needed for location status calculation
        $query = Employee::with([
            'roles',
            'assignments.project.location',
            'assignments.role',
            'accommodationAssignments.accommodation',
            'vehicleAssignments' => fn ($q) => $q->where('is_return_trip', false),
            'vehicleAssignments.vehicle',
            'rotations',
            'companyAssignments' => fn ($q) => $q->active()->orderByDesc('start_date')->with('company'),
        ]);

        // Filtrowanie po pracownikach (dla /mine/*)
        if ($this->filterEmployeeIds && is_array($this->filterEmployeeIds) && ! empty($this->filterEmployeeIds)) {
            $query->whereIn('id', $this->filterEmployeeIds);
        }

        // Filtrowanie po imieniu/nazwisku/emailu/telefonie
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('first_name', 'like', '%'.$this->search.'%')
                    ->orWhere('last_name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')
                    ->orWhere('phone', 'like', '%'.$this->search.'%');
            });
        }

        // Filtrowanie po roli
        if ($this->roleFilter) {
            $query->whereHas('roles', function ($q) {
                $q->where('roles.id', $this->roleFilter);
            });
        }

        // Filtrowanie po spółce (aktywne przypisanie)
        if ($this->companyFilter) {
            $query->whereHas('companyAssignments', function ($q) {
                $q->where('company_id', $this->companyFilter)->active();
            });
        }

        // Sortowanie
        if ($this->sortField === 'name') {
            $query->orderBy('last_name', $this->sortDirection)
                ->orderBy('first_name', $this->sortDirection);
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        // Determine date for status check
        $checkDate = $this->statusDate ? \Carbon\Carbon::parse($this->statusDate) : now();

        // Get base query results (without location/rotation filters)
        if ($this->locationFilter || $this->rotationFilter) {
            // For these filters, we need to get all employees first, then filter
            $allEmployees = $query->get();
            $locationTracker = app(\App\Services\LocationTrackingService::class);

            $filteredEmployees = $allEmployees->filter(function ($employee) use ($locationTracker, $checkDate) {
                if ($this->locationFilter) {
                    $status = $locationTracker->getLocationStatus($employee, $checkDate);

                    $locationMatch = false;
                    if ($this->locationFilter === 'base') {
                        $locationMatch = $status['state'] === \App\Enums\EmployeeLocationState::IN_BASE;
                    } elseif ($this->locationFilter === 'transit') {
                        $locationMatch = $status['state'] === \App\Enums\EmployeeLocationState::IN_TRANSIT;
                    } elseif ($this->locationFilter === 'field') {
                        $locationMatch = $status['state'] === \App\Enums\EmployeeLocationState::OUTSIDE_BASE;
                    }

                    if (! $locationMatch) {
                        return false;
                    }
                }

                // Rotation filter
                if ($this->rotationFilter) {
                    // Check loaded rotations collection for active rotation
                    $hasActiveRotation = $employee->rotations->filter(function ($rotation) use ($checkDate) {
                        $startDate = $rotation->start_date ? \Carbon\Carbon::parse($rotation->start_date) : null;
                        $endDate = $rotation->end_date ? \Carbon\Carbon::parse($rotation->end_date) : null;

                        if (! $startDate) {
                            return false;
                        }

                        return $startDate->lte($checkDate)
                            && ($endDate === null || $endDate->gte($checkDate));
                    })->isNotEmpty();

                    $rotationMatch = false;
                    if ($this->rotationFilter === 'active' && $hasActiveRotation) {
                        $rotationMatch = true;
                    } elseif ($this->rotationFilter === 'inactive' && ! $hasActiveRotation) {
                        $rotationMatch = true;
                    }

                    if (! $rotationMatch) {
                        return false;
                    }
                }

                return true;
            });

            // Paginate manually
            $currentPage = $this->getPage();
            $perPage = 10;
            $currentPageItems = $filteredEmployees->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $employees = new \Illuminate\Pagination\LengthAwarePaginator(
                $currentPageItems,
                $filteredEmployees->count(),
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        } else {
            $employees = $query->paginate(10);
        }

        $roles = Role::orderBy('name')->get();
        $companies = Company::orderBy('name')->get();

        return view('livewire.employees-table', [
            'employees' => $employees,
            'roles' => $roles,
            'companies' => $companies,
            'filterProjectIds' => $this->filterProjectIds,
            'checkDate' => $checkDate,
        ]);
    }
}
