<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeListView;
use App\Models\Role;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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

    /** @var list<string> */
    public array $visibleColumns = ['id', 'qualifications', 'where', 'hired', 'rotation', 'company'];

    public string $view = '';

    public ?int $activeViewId = null;

    public string $saveViewName = '';

    public bool $saveViewAsGlobal = false;

    public string $notice = '';

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
        'view' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->restoreColumnsFromCookie();

        if ($this->view !== '') {
            $this->loadViewFromSlug($this->view, quiet: true);
        }
    }

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
        $this->resetPage();
    }

    public function clearFilter(string $key): void
    {
        match ($key) {
            'search' => $this->search = '',
            'roleFilter' => $this->roleFilter = '',
            'locationFilter' => $this->locationFilter = '',
            'rotationFilter' => $this->rotationFilter = '',
            'companyFilter' => $this->companyFilter = '',
            'statusDate' => $this->statusDate = '',
            'hirePeriod' => $this->hirePeriod = '',
            'komornikFilter' => $this->komornikFilter = '',
            'showTerminated' => $this->showTerminated = false,
            default => null,
        };
        $this->resetPage();
    }

    /**
     * @return array<string, string>
     */
    public function columnCatalog(): array
    {
        return [
            'id' => 'ID',
            'qualifications' => 'Kwalifikacje',
            'where' => 'Gdzie jest',
            'hired' => 'Zatrudniony',
            'rotation' => 'Rotacja',
            'company' => 'Spółka',
        ];
    }

    public function showsColumn(string $key): bool
    {
        if ($key === 'employee') {
            return true;
        }

        return in_array($key, $this->visibleColumns, true);
    }

    public function visibleColumnCount(): int
    {
        return count($this->visibleColumns) + 1;
    }

    public function toggleColumn(string $key): void
    {
        if (! array_key_exists($key, $this->columnCatalog())) {
            return;
        }

        if (in_array($key, $this->visibleColumns, true)) {
            $this->visibleColumns = array_values(array_filter(
                $this->visibleColumns,
                fn (string $column) => $column !== $key,
            ));
        } else {
            $this->visibleColumns[] = $key;
        }

        $this->rememberColumns();
    }

    public function saveView(): void
    {
        if (! $this->viewsTableExists() || ! auth()->user()) {
            $this->notice = 'Nie można zapisać widoku.';

            return;
        }

        $name = trim($this->saveViewName);
        if ($name === '') {
            return;
        }

        $slug = Str::slug($name) ?: 'widok';
        $existing = EmployeeListView::query()
            ->where('user_id', auth()->id())
            ->where(fn ($q) => $q->where('name', $name)->orWhere('slug', $slug))
            ->first();

        if ($existing) {
            $slug = $existing->slug;
        } elseif ($this->slugTaken($slug)) {
            $slug .= '-'.substr(uniqid(), -4);
        }

        if ($this->saveViewAsGlobal && $this->globalSlugTaken($slug, $existing?->id)) {
            $this->notice = 'Widok globalny o tej nazwie już istnieje.';

            return;
        }

        $record = EmployeeListView::updateOrCreate(
            ['user_id' => auth()->id(), 'slug' => $slug],
            [
                'name' => $name,
                'is_global' => $this->saveViewAsGlobal,
                'visible_columns' => $this->visibleColumns,
                'sort_field' => $this->sortField,
                'sort_direction' => $this->sortDirection,
                'filters' => $this->currentFilters(),
            ],
        );

        $this->view = $record->slug;
        $this->activeViewId = $record->id;
        $this->saveViewName = '';
        $this->saveViewAsGlobal = false;
        $this->notice = $record->is_global
            ? "Widok globalny „{$name}” zapisany."
            : "Widok „{$name}” zapisany.";
    }

    public function loadSavedView(int $id): void
    {
        $record = $this->findVisibleView($id);
        if (! $record) {
            $this->notice = 'Nie znaleziono widoku.';

            return;
        }

        $this->activateView($record);
        $this->notice = "Załadowano „{$record->name}”.";
    }

    public function overwriteView(int $id): void
    {
        $record = $this->findVisibleView($id);
        if (! $record || ! $record->canBeManagedBy(auth()->user())) {
            $this->notice = 'Nie możesz nadpisać tego widoku.';

            return;
        }

        $record->update([
            'visible_columns' => $this->visibleColumns,
            'sort_field' => $this->sortField,
            'sort_direction' => $this->sortDirection,
            'filters' => $this->currentFilters(),
        ]);
        $this->view = $record->slug;
        $this->activeViewId = $record->id;
        $this->notice = "Widok „{$record->name}” zaktualizowany.";
    }

    public function deleteView(int $id): void
    {
        $record = $this->findVisibleView($id);
        if (! $record || ! $record->canBeManagedBy(auth()->user())) {
            $this->notice = 'Nie możesz usunąć tego widoku.';

            return;
        }

        $record->delete();
        if ($this->activeViewId === $id) {
            $this->view = '';
            $this->activeViewId = null;
        }
        $this->notice = 'Widok usunięty.';
    }

    public function clearView(): void
    {
        $this->view = '';
        $this->activeViewId = null;
        $this->notice = 'Widok domyślny.';
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
            'latestEvaluation.createdBy',
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
        $plannerDocuments = app(\App\Services\WeeklyOverviewService::class)
            ->loadPlannerDocumentsByEmployee(collect($employees->items())->pluck('id'));

        $savedViews = $this->viewsTableExists() && auth()->user()
            ? EmployeeListView::query()->visibleTo(auth()->user())->orderBy('name')->get()
            : collect();

        return view('livewire.employees-table', [
            'employees' => $employees,
            'roles' => $roles,
            'companies' => $companies,
            'filterProjectIds' => $this->filterProjectIds,
            'checkDate' => $checkDate,
            'plannerDocuments' => $plannerDocuments,
            'filterChips' => $this->filterChips($roles, $companies),
            'savedViews' => $savedViews,
            'activeViewName' => $savedViews->firstWhere('id', $this->activeViewId)?->name,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function currentFilters(): array
    {
        return [
            'search' => (string) $this->search,
            'roleFilter' => (string) $this->roleFilter,
            'locationFilter' => (string) $this->locationFilter,
            'rotationFilter' => (string) $this->rotationFilter,
            'companyFilter' => (string) $this->companyFilter,
            'statusDate' => (string) $this->statusDate,
            'showTerminated' => $this->showTerminated,
            'hirePeriod' => $this->hirePeriod,
            'komornikFilter' => $this->komornikFilter,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Role>  $roles
     * @param  \Illuminate\Support\Collection<int, Company>  $companies
     * @return list<array{key: string, label: string}>
     */
    protected function filterChips($roles, $companies): array
    {
        $chips = [];

        if ($this->search !== '') {
            $chips[] = ['key' => 'search', 'label' => 'Szukaj: '.$this->search];
        }
        if ($this->statusDate !== '') {
            $chips[] = ['key' => 'statusDate', 'label' => 'Stan na '.\Carbon\Carbon::parse($this->statusDate)->format('d.m.Y')];
        }
        if ($this->hirePeriod === '7d') {
            $chips[] = ['key' => 'hirePeriod', 'label' => 'Zatrudniony: ostatnie 7 dni'];
        } elseif ($this->hirePeriod === '30d') {
            $chips[] = ['key' => 'hirePeriod', 'label' => 'Zatrudniony: ostatnie 30 dni'];
        }
        if ($this->roleFilter !== '') {
            $name = $roles->firstWhere('id', (int) $this->roleFilter)?->name ?? 'rola';
            $chips[] = ['key' => 'roleFilter', 'label' => 'Rola: '.$name];
        }
        if ($this->locationFilter !== '') {
            $label = match ($this->locationFilter) {
                'base' => 'Baza',
                'field' => 'W terenie',
                'transit' => 'W podróży',
                default => $this->locationFilter,
            };
            $chips[] = ['key' => 'locationFilter', 'label' => 'Lokalizacja: '.$label];
        }
        if ($this->rotationFilter === 'active') {
            $chips[] = ['key' => 'rotationFilter', 'label' => 'Rotacja: aktywna'];
        } elseif ($this->rotationFilter === 'inactive') {
            $chips[] = ['key' => 'rotationFilter', 'label' => 'Rotacja: nieaktywna'];
        }
        if ($this->komornikFilter === 'yes') {
            $chips[] = ['key' => 'komornikFilter', 'label' => 'Z komornikiem'];
        } elseif ($this->komornikFilter === 'no') {
            $chips[] = ['key' => 'komornikFilter', 'label' => 'Bez komornika'];
        }
        if ($this->companyFilter !== '') {
            $name = $companies->firstWhere('id', (int) $this->companyFilter)?->name ?? 'spółka';
            $chips[] = ['key' => 'companyFilter', 'label' => 'Spółka: '.$name];
        }
        if ($this->showTerminated) {
            $chips[] = ['key' => 'showTerminated', 'label' => 'Ze zwolnionymi'];
        }

        return $chips;
    }

    protected function viewsTableExists(): bool
    {
        return Schema::hasTable('employee_list_views');
    }

    protected function rememberColumns(): void
    {
        cookie()->queue(cookie(
            'emp_list_columns',
            implode(',', $this->visibleColumns),
            60 * 24 * 180,
        ));
    }

    protected function restoreColumnsFromCookie(): void
    {
        $raw = request()->cookie('emp_list_columns');
        if (! is_string($raw) || $raw === '') {
            return;
        }

        $known = array_keys($this->columnCatalog());
        $picked = array_values(array_intersect(explode(',', $raw), $known));
        $this->visibleColumns = $picked;
    }

    protected function loadViewFromSlug(string $slug, bool $quiet = false): void
    {
        $user = auth()->user();
        if (! $user || ! $this->viewsTableExists()) {
            return;
        }

        $record = EmployeeListView::query()
            ->visibleTo($user)
            ->where('slug', $slug)
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$user->id])
            ->first();

        if (! $record) {
            $this->view = '';
            $this->activeViewId = null;

            return;
        }

        $this->activateView($record);
        if (! $quiet) {
            $this->notice = "Załadowano „{$record->name}”.";
        }
    }

    protected function activateView(EmployeeListView $record): void
    {
        $filters = $record->filters ?? [];
        $this->search = (string) ($filters['search'] ?? '');
        $this->roleFilter = (string) ($filters['roleFilter'] ?? '');
        $this->locationFilter = (string) ($filters['locationFilter'] ?? '');
        $this->rotationFilter = (string) ($filters['rotationFilter'] ?? '');
        $this->companyFilter = (string) ($filters['companyFilter'] ?? '');
        $this->statusDate = (string) ($filters['statusDate'] ?? '');
        $this->showTerminated = (bool) ($filters['showTerminated'] ?? false);
        $this->hirePeriod = (string) ($filters['hirePeriod'] ?? '');
        $this->komornikFilter = (string) ($filters['komornikFilter'] ?? '');
        $this->sortField = $record->sort_field ?: 'last_name';
        $this->sortDirection = $record->sort_direction ?: 'asc';

        $known = array_keys($this->columnCatalog());
        $columns = array_values(array_intersect($record->visible_columns ?? [], $known));
        $this->visibleColumns = $columns;
        $this->rememberColumns();

        $this->view = $record->slug;
        $this->activeViewId = $record->id;
        $this->resetPage();
    }

    protected function findVisibleView(int $id): ?EmployeeListView
    {
        $user = auth()->user();
        if (! $user || ! $this->viewsTableExists()) {
            return null;
        }

        return EmployeeListView::query()->visibleTo($user)->whereKey($id)->first();
    }

    protected function slugTaken(string $slug): bool
    {
        return EmployeeListView::query()
            ->where('user_id', auth()->id())
            ->where('slug', $slug)
            ->exists();
    }

    protected function globalSlugTaken(string $slug, ?int $ignoreId = null): bool
    {
        return EmployeeListView::query()
            ->where('is_global', true)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }
}
