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

    /** When false (default), terminated employees are hidden from the list. */
    public bool $showTerminated = false;

    /** '' | 7d | 30d — hired_at window */
    public string $hirePeriod = '';

    /** '' | yes | no */
    public string $komornikFilter = '';

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
        'showTerminated' => ['except' => false],
        'hirePeriod' => ['except' => ''],
        'komornikFilter' => ['except' => ''],
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

    public function updatingShowTerminated()
    {
        $this->resetPage();
    }

    public function updatingHirePeriod()
    {
        $this->resetPage();
    }

    public function updatingKomornikFilter()
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
        $this->showTerminated = false;
        $this->hirePeriod = '';
        $this->komornikFilter = '';
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

        // Domyślnie ukrywaj zwolnionych; checkbox „Pokaż zwolnionych” pokazuje wszystkich
        if (! $this->showTerminated) {
            $query->whereNull('terminated_at');
        }

        if ($this->hirePeriod === '7d') {
            $query->where('hired_at', '>=', now()->subDays(7));
        } elseif ($this->hirePeriod === '30d') {
            $query->where('hired_at', '>=', now()->subDays(30));
        }

        if ($this->komornikFilter === 'yes') {
            $query->where('has_komornik', true);
        } elseif ($this->komornikFilter === 'no') {
            $query->where('has_komornik', false);
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

        if ($this->locationFilter) {
            $locationTracker = app(\App\Services\LocationTrackingService::class);
            $notInBase = $locationTracker->employeeIdsNotInBaseOn($checkDate);
            $outside = $locationTracker->employeeIdsOutsideBaseOn($checkDate);
            $locationIds = match ($this->locationFilter) {
                'field' => $outside,
                'transit' => array_values(array_diff($notInBase, $outside)),
                default => null,
            };

            if ($this->locationFilter === 'base') {
                if ($notInBase !== []) {
                    $query->whereNotIn('id', $notInBase);
                }
            } else {
                $query->whereIn('id', $locationIds ?? []);
            }
        }

        if ($this->rotationFilter) {
            $allEmployees = $query->get();

            $filteredEmployees = $allEmployees->filter(function ($employee) use ($checkDate) {
                $hasActiveRotation = $employee->rotations->contains(function ($rotation) use ($checkDate) {
                    $startDate = $rotation->start_date ? \Carbon\Carbon::parse($rotation->start_date) : null;
                    if (! $startDate || $startDate->gt($checkDate)) {
                        return false;
                    }
                    $endDate = $rotation->end_date ? \Carbon\Carbon::parse($rotation->end_date) : null;

                    return $endDate === null || $endDate->gte($checkDate);
                });

                return $this->rotationFilter === 'active' ? $hasActiveRotation : ! $hasActiveRotation;
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
