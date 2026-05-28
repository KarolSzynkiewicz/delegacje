<?php

namespace App\Livewire\Steps;

use App\Livewire\Concerns\ManagesVehicleSeats;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Role;
use App\Models\Vehicle;
use App\Services\DeparturePlannerService;
use App\Services\ExpiringDocumentsService;
use Carbon\Carbon;
use Livewire\Component;

class Step1ProjectAssignments extends Component
{
    use ManagesVehicleSeats;

    // Dane otrzymane z rodzica (read-only)
    public $departureDate;

    public $endDate;

    public $vehicleId;

    public $assignments = []; // Read-only z rodzica

    public $assignmentRanges = []; // Read-only z rodzica

    public $vehicleSeats = []; // Updated via events from parent

    /** Tryb kreatora przeniesienia (transfer): wąska lista pracowników, inne teksty UI. */
    public bool $forTransfer = false;

    /** W trybie transferu — tylko ci pracownicy (np. uczestnicy transferu). */
    public array $allowedEmployeeIds = [];

    // Własne dane (ciężkie obliczenia)
    public $availableEmployees = [];

    public $allAvailableEmployees = []; // Full list for pagination

    public $employeesPage = 1;

    public $employeesPerPage = 10;

    public $hasMoreEmployees = false;

    public $projectGaps = [];

    public $projectGapsTwoWeeks = [];

    public $vehicle;

    public $roleFilter = null;

    public $employeeSearch = '';

    public $projectSearch = '';

    // Cache for Projects and Roles to avoid N+1 queries
    protected $projectsCache = [];

    protected $rolesCache = [];

    protected $employeesCache = [];

    // Modal state for employee assignment
    public $showEmployeeModal = false;

    public $selectedEmployee = null;

    public $selectedProject = null;

    public $selectedRole = null;

    public $employeeAvailability = [];

    public $selectedStartDate = null;

    public $selectedEndDate = null;

    public $calendarMonthStart = null;

    protected $departurePlannerService;

    protected $expiringDocumentsService;

    protected $listeners = [
        'refresh-assignments' => 'refreshAssignments',
        'vehicle-seats-updated' => 'updateVehicleSeatsFromParent',
    ];

    public function boot(DeparturePlannerService $departurePlannerService, ExpiringDocumentsService $expiringDocumentsService)
    {
        $this->departurePlannerService = $departurePlannerService;
        $this->expiringDocumentsService = $expiringDocumentsService;
    }

    public function refreshAssignments()
    {
        // Refresh available employees list when assignments change
        $this->loadAvailableEmployees();
    }

    public function updateVehicleSeatsFromParent($vehicleSeats)
    {
        // Update vehicleSeats from parent event
        $this->vehicleSeats = $vehicleSeats;

        // Don't call loadVehicle() here - it might overwrite vehicleSeats
        // Just refresh the employees list to show correct assignments
        $this->loadAvailableEmployees();

        // Force re-render to update the view
        $this->dispatch('$refresh');
    }

    public function mount(
        $departureDate,
        $endDate,
        $vehicleId,
        $assignments = [],
        $assignmentRanges = [],
        $vehicleSeats = [],
        $forTransfer = false,
        $allowedEmployeeIds = []
    ) {
        $this->departureDate = $departureDate;
        $this->endDate = $endDate;
        $this->vehicleId = $vehicleId;
        $this->assignments = $assignments;
        $this->assignmentRanges = $assignmentRanges;
        $this->vehicleSeats = $vehicleSeats;
        $this->forTransfer = (bool) $forTransfer;
        $this->allowedEmployeeIds = is_array($allowedEmployeeIds)
            ? array_values(array_map('intval', $allowedEmployeeIds))
            : [];

        // Wykonuje ciężkie operacje
        $this->loadData();

        // Jeśli vehicleId jest ustawione i vehicleSeats są puste lub nie pasują do capacity,
        // wczytaj przypisania z assignmentRanges
        if ($this->vehicleId && ! empty($this->assignmentRanges)) {
            $this->loadVehicleAssignmentsFromRanges();
        }
    }

    public function updatedDepartureDate()
    {
        $this->loadData();
    }

    public function updatedEndDate()
    {
        $this->loadData();
    }

    public function updatedVehicleId()
    {
        $this->loadVehicle();
    }

    public function updatedRoleFilter()
    {
        // Reset pagination when filter changes
        $this->employeesPage = 1;
    }

    public function updatedEmployeeSearch()
    {
        // Reset pagination when filter changes
        $this->employeesPage = 1;
    }

    public function updatedProjectSearch()
    {
        // No pagination on projects list yet, but keep it reactive and predictable
        $this->dispatch('$refresh');
    }

    public function updatedAssignments()
    {
        // When assignments change in parent, reload available employees
        $this->loadAvailableEmployees();
    }

    public function updatedAssignmentRanges()
    {
        // When assignment ranges change in parent, reload available employees
        $this->loadAvailableEmployees();
    }

    public function updatedVehicleSeats()
    {
        // When vehicle seats change (via reactive prop), reload available employees
        // This is triggered automatically when parent updates vehicleSeats
        $this->loadAvailableEmployees();
    }

    public function loadData()
    {
        if (! $this->departureDate) {
            return;
        }

        $this->loadAvailableEmployees();
        $this->loadProjectGaps();

        // Jeśli vehicleSeats już są z props (np. przy powrocie do kroku 1), nie wywołuj loadVehicle()
        // bo może nadpisać istniejące przypisania
        if (empty($this->vehicleSeats)) {
            $this->loadVehicle();
        } else {
            // Jeśli vehicleSeats już są, tylko pobierz obiekt pojazdu (bez inicjalizacji miejsc)
            if ($this->vehicleId) {
                $this->vehicle = Vehicle::find($this->vehicleId);
                // Upewnij się, że vehicleSeats mają poprawną strukturę i uzupełnij brakujące miejsca
                if ($this->vehicle && $this->vehicle->capacity) {
                    $capacity = $this->vehicle->capacity;
                    $existingSeats = count($this->vehicleSeats);

                    if ($existingSeats < $capacity) {
                        for ($i = $existingSeats; $i < $capacity; $i++) {
                            if (! isset($this->vehicleSeats[$i])) {
                                $this->vehicleSeats[$i] = $this->buildSeatRow($i);
                            }
                        }
                    }

                    for ($i = 0; $i < min($existingSeats, $capacity); $i++) {
                        $this->vehicleSeats[$i] = $this->normalizeSeatRowFromPartial($i, $this->vehicleSeats[$i] ?? []);
                    }
                }
            }
        }

        $this->loadCaches();
    }

    public function loadAvailableEmployees()
    {
        $departureDate = Carbon::parse($this->departureDate);

        // Get all employees (not just available ones) so we can show them in vehicle seats
        // We'll filter out assigned ones in getFilteredEmployees()
        $now = Carbon::now();

        $allEmployees = Employee::with(['roles', 'employeeDocuments.document', 'rotations'])
            ->when($this->forTransfer && ! empty($this->allowedEmployeeIds), function ($q) {
                $q->whereIn('id', $this->allowedEmployeeIds);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(function ($employee) use ($departureDate, $now) {
                // Get rotation for this date — uses eagerly-loaded collection, no extra query
                $rotation = $employee->getActiveRotationForDate($departureDate);

                // Get expiring documents — uses eagerly-loaded relation, no extra queries
                $expiringDocuments = $this->expiringDocumentsService->getExpiringDocumentsForEmployee($employee, 30);

                // Wygasłe wymagane dokumenty — filtruj z już załadowanej relacji (bez dodatkowego DB query)
                $allExpiredRequired = $employee->employeeDocuments
                    ->filter(fn ($doc) => $doc->kind === 'okresowy'
                        && $doc->valid_to !== null
                        && $doc->valid_to->lt($now)
                        && $doc->document
                        && $doc->document->is_required
                    )
                    ->map(function ($doc) use ($now) {
                        $daysDiff = $doc->valid_to->diffInDays($now);

                        return [
                            'id' => $doc->id,
                            'document_name' => $doc->document->name ?? 'Nieznany dokument',
                            'valid_to' => $doc->valid_to->format('Y-m-d'),
                            'days_until_expiry' => -$daysDiff, // Negative for expired
                            'is_expired' => true,
                            'is_required' => true,
                        ];
                    });

                // Przekonwertuj expiring documents na tablice
                $expiringDocumentsArray = $expiringDocuments->map(function ($doc) use ($now) {
                    $validTo = Carbon::parse($doc->valid_to);
                    $isExpired = $validTo->lt($now);
                    $daysDiff = $validTo->diffInDays($now);

                    return [
                        'id' => $doc->id,
                        'document_name' => $doc->document->name ?? 'Nieznany dokument',
                        'valid_to' => $doc->valid_to->format('Y-m-d'),
                        'days_until_expiry' => $isExpired ? -$daysDiff : $daysDiff, // Negative for expired
                        'is_expired' => $isExpired,
                        'is_required' => $doc->document->is_required ?? false,
                    ];
                });

                // POPRAWKA: Sprawdź brakujące wymagane dokumenty (których pracownik w ogóle nie ma)
                // Użyj getAvailabilityStatus() aby sprawdzić wszystkie wymagane dokumenty
                $endDate = $this->endDate ? Carbon::parse($this->endDate) : $departureDate->copy()->addDays(30);
                $availabilityStatus = $employee->getAvailabilityStatus($departureDate, $endDate);
                $missingRequiredDocuments = [];

                if (isset($availabilityStatus['missing_documents'])) {
                    foreach ($availabilityStatus['missing_documents'] as $missingDoc) {
                        // Dodaj tylko wymagane dokumenty, które są całkowicie brakujące (nie mają valid_to)
                        if (($missingDoc['is_required'] ?? false) && ($missingDoc['problem'] ?? '') === 'Brak dokumentu') {
                            $missingRequiredDocuments[] = [
                                'id' => null, // Nie ma dokumentu, więc nie ma ID
                                'document_name' => $missingDoc['document_name'] ?? 'Nieznany dokument',
                                'valid_to' => null,
                                'days_until_expiry' => null,
                                'is_expired' => true, // Traktuj brak dokumentu jako "wygasły"
                                'is_required' => true,
                                'is_missing' => true, // Flaga że dokument w ogóle nie istnieje
                            ];
                        }
                    }
                }

                // Połącz expiring documents z wygasłymi wymaganymi dokumentami
                // Usuń duplikaty (jeśli dokument jest już w expiringDocuments, nie dodawaj go ponownie)
                $expiringDocumentIds = $expiringDocumentsArray->pluck('id')->toArray();
                $allExpiredRequired = $allExpiredRequired->filter(function ($doc) use ($expiringDocumentIds) {
                    return ! in_array($doc['id'], $expiringDocumentIds);
                });

                // Połącz wszystkie dokumenty: expiring, expired, i missing
                // merge() i concat() próbują użyć getKey() na elementach, co nie działa dla tablic
                $allDocuments = collect(array_merge(
                    $expiringDocumentsArray->toArray(),
                    $allExpiredRequired->toArray(),
                    $missingRequiredDocuments
                ));

                return [
                    'id' => $employee->id,
                    'full_name' => $employee->full_name,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'image_url' => $employee->image_url,
                    'roles' => $employee->roles->map(fn ($role) => [
                        'id' => $role->id,
                        'name' => $role->name,
                    ])->toArray(),
                    'rotation' => $rotation ? [
                        'id' => $rotation->id,
                        'start_date' => $rotation->start_date->format('Y-m-d'),
                        'end_date' => $rotation->end_date ? $rotation->end_date->format('Y-m-d') : null,
                    ] : null,
                    'expiring_documents' => $allDocuments->values()->toArray(),
                ];
            })
            ->toArray();

        $this->allAvailableEmployees = $allEmployees;

        // Apply pagination
        $this->employeesPage = 1;
        $this->loadEmployeesPage();
    }

    public function loadEmployeesPage()
    {
        $offset = ($this->employeesPage - 1) * $this->employeesPerPage;
        $this->availableEmployees = array_slice($this->allAvailableEmployees, $offset, $this->employeesPerPage);
        $this->hasMoreEmployees = ($offset + $this->employeesPerPage) < count($this->allAvailableEmployees);
    }

    public function loadMoreEmployees()
    {
        $this->employeesPage++;
        $this->loadEmployeesPage();
    }

    public function loadPrevEmployees()
    {
        if ($this->employeesPage > 1) {
            $this->employeesPage--;
            $this->loadEmployeesPage();
        }
    }

    public function loadProjectGaps()
    {
        if (! $this->endDate) {
            return;
        }

        $arrivalDate = Carbon::parse($this->endDate);
        $this->projectGaps = $this->departurePlannerService->getProjectGapsForWeek($arrivalDate);
        $this->projectGapsTwoWeeks = $this->departurePlannerService->getProjectGapsForTwoWeeks($arrivalDate);
    }

    public function getFilteredProjectGapsTwoWeeksProperty(): array
    {
        $gaps = $this->projectGapsTwoWeeks ?? [];
        if (empty($gaps) || ! is_array($gaps)) {
            return [];
        }

        $search = trim((string) ($this->projectSearch ?? ''));
        if ($search === '') {
            return $gaps;
        }

        $searchLower = mb_strtolower($search);

        return collect($gaps)
            ->filter(function ($project) use ($searchLower) {
                $name = is_array($project) ? ($project['name'] ?? '') : '';
                $nameLower = mb_strtolower((string) $name);

                return str_contains($nameLower, $searchLower);
            })
            ->all();
    }

    public function loadVehicle()
    {
        if ($this->vehicleId) {
            $this->vehicle = Vehicle::find($this->vehicleId);
            if ($this->vehicle && $this->vehicle->capacity) {
                // Initialize seats only if they're empty
                // Jeśli vehicleSeats już mają przypisania, tylko uzupełnij brakujące miejsca
                $capacity = $this->vehicle->capacity;
                $existingSeats = count($this->vehicleSeats);

                if ($existingSeats == 0) {
                    // Jeśli vehicleSeats są puste, zainicjalizuj wszystkie miejsca
                    $this->initializeVehicleSeats();
                } elseif ($existingSeats != $capacity) {
                    // Jeśli capacity się zmieniło, uzupełnij tylko brakujące miejsca
                    $this->initializeVehicleSeats();
                } else {
                    for ($i = 0; $i < $capacity; $i++) {
                        $this->vehicleSeats[$i] = $this->normalizeSeatRowFromPartial($i, $this->vehicleSeats[$i] ?? []);
                    }
                }
            }
        } else {
            $this->vehicle = null;
            // Don't clear vehicleSeats - they come from parent
        }
    }

    public function initializeVehicleSeats()
    {
        if (! $this->vehicle || ! $this->vehicle->capacity) {
            // Don't clear vehicleSeats here - they come from parent
            return;
        }

        $capacity = $this->vehicle->capacity;
        $existingSeats = count($this->vehicleSeats);

        if ($existingSeats < $capacity) {
            for ($i = $existingSeats; $i < $capacity; $i++) {
                if (! isset($this->vehicleSeats[$i])) {
                    $this->vehicleSeats[$i] = $this->buildSeatRow($i);
                }
            }
        }

        for ($i = 0; $i < min($existingSeats, $capacity); $i++) {
            $this->vehicleSeats[$i] = $this->normalizeSeatRowFromPartial($i, $this->vehicleSeats[$i] ?? []);
        }
    }

    /**
     * Wczytaj przypisania z assignmentRanges do vehicleSeats
     * Używane przy mount() gdy vehicleId jest ustawione i są assignmentRanges
     */
    public function loadVehicleAssignmentsFromRanges()
    {
        if (! $this->vehicleId || empty($this->assignmentRanges)) {
            return;
        }

        $vehicle = Vehicle::find($this->vehicleId);
        if (! $vehicle || ! $vehicle->capacity) {
            return;
        }

        // Inicjalizuj miejsca jeśli są puste
        if (empty($this->vehicleSeats)) {
            for ($i = 0; $i < $vehicle->capacity; $i++) {
                $this->vehicleSeats[$i] = $this->buildSeatRow($i);
            }
        }

        // Wczytaj wszystkie przypisania z assignmentRanges
        foreach ($this->assignmentRanges as $assignmentRange) {
            $employeeId = $assignmentRange['employee_id'];

            // Sprawdź czy pracownik już nie jest w aucie (duplikaty)
            $alreadyInVehicle = false;
            foreach ($this->vehicleSeats as $seat) {
                if (! empty($seat['employee_id']) && $seat['employee_id'] == $employeeId) {
                    $alreadyInVehicle = true;
                    break;
                }
            }

            // Jeśli nie ma go w aucie, znajdź pierwsze wolne miejsce i przypisz
            if (! $alreadyInVehicle) {
                foreach ($this->vehicleSeats as $index => $seat) {
                    if (empty($seat['employee_id'])) {
                        $this->vehicleSeats[$index] = $this->buildSeatRow($index, (int) $employeeId, 'passenger', false);
                        break;
                    }
                }
            }
        }
    }

    protected function loadCaches()
    {
        // Load all projects and roles that might be used
        $projectIds = [];
        $roleIds = [];

        // Collect project and role IDs from assignments
        foreach ($this->assignments as $dayAssignments) {
            foreach ($dayAssignments as $projectId => $roles) {
                if (! in_array($projectId, $projectIds)) {
                    $projectIds[] = $projectId;
                }
                foreach ($roles as $roleId => $employeeIds) {
                    if (! in_array($roleId, $roleIds)) {
                        $roleIds[] = $roleId;
                    }
                }
            }
        }

        foreach ($this->assignmentRanges as $range) {
            if (! empty($range['project_id']) && ! in_array($range['project_id'], $projectIds)) {
                $projectIds[] = $range['project_id'];
            }
            if (! empty($range['role_id']) && ! in_array($range['role_id'], $roleIds)) {
                $roleIds[] = $range['role_id'];
            }
        }

        // Load projects and roles in bulk
        if (! empty($projectIds)) {
            $projects = Project::whereIn('id', $projectIds)->get();
            foreach ($projects as $project) {
                $this->projectsCache[$project->id] = $project;
            }
        }

        if (! empty($roleIds)) {
            $roles = Role::whereIn('id', $roleIds)->get();
            foreach ($roles as $role) {
                $this->rolesCache[$role->id] = $role;
            }
        }
    }

    protected function getProject($projectId)
    {
        if (isset($this->projectsCache[$projectId])) {
            return $this->projectsCache[$projectId];
        }

        $project = Project::find($projectId);
        if ($project) {
            $this->projectsCache[$projectId] = $project;
        }

        return $project;
    }

    protected function getRole($roleId)
    {
        if (isset($this->rolesCache[$roleId])) {
            return $this->rolesCache[$roleId];
        }

        $role = Role::find($roleId);
        if ($role) {
            $this->rolesCache[$roleId] = $role;
        }

        return $role;
    }

    public function getFilteredEmployees()
    {
        $assignedIds = $this->getAllAssignedEmployeeIds();

        // Get date range for checking database assignments
        $startDate = $this->departureDate ? Carbon::parse($this->departureDate) : null;
        $endDate = $this->endDate ? Carbon::parse($this->endDate) : null;

        // Get all employee IDs that have assignments in database for this date range
        $employeesWithDbAssignments = [];
        if (! $this->forTransfer && $startDate && $endDate) {
            // Get all employees who have assignments in database overlapping with the departure date range
            $employeesWithDbAssignments = \App\Models\ProjectAssignment::where(function ($query) use ($startDate, $endDate) {
                $query->where(function ($q) use ($startDate, $endDate) {
                    // Assignment starts before or during the range
                    $q->where('start_date', '<=', $endDate)
                        ->where(function ($q2) use ($startDate) {
                            $q2->whereNull('end_date')
                                ->orWhere('end_date', '>=', $startDate);
                        });
                });
            })
                ->pluck('employee_id')
                ->unique()
                ->toArray();
        }

        // Filter from all available employees (not just current page)
        $filtered = collect($this->allAvailableEmployees)->filter(function ($employee) use ($assignedIds, $employeesWithDbAssignments) {
            // Filter out already assigned employees (in form)
            if (in_array($employee['id'], $assignedIds)) {
                return false;
            }

            // Filter out employees who have assignments in database for this date range
            // (unless they're already assigned in form, which is checked above)
            if (in_array($employee['id'], $employeesWithDbAssignments)) {
                return false;
            }

            // Apply role filter if set
            if ($this->roleFilter) {
                foreach ($employee['roles'] ?? [] as $role) {
                    if ($role['id'] == $this->roleFilter) {
                        // Role matches; continue to name search filter
                        if ($this->employeeSearch !== '' && stripos($employee['full_name'], $this->employeeSearch) === false) {
                            return false;
                        }

                        return true;
                    }
                }

                return false;
            }

            // Apply employee live search if set
            if ($this->employeeSearch !== '' && stripos($employee['full_name'], $this->employeeSearch) === false) {
                return false;
            }

            return true;
        });

        // Sort employees: no issues first, expired required documents last
        $sorted = $filtered->sortBy(function ($employee) {
            $expiringDocs = $employee['expiring_documents'] ?? [];

            // Check if employee has expired required documents
            $hasExpiredRequired = false;
            foreach ($expiringDocs as $doc) {
                if (($doc['is_expired'] ?? false) && ($doc['is_required'] ?? false)) {
                    $hasExpiredRequired = true;
                    break;
                }
            }

            // Check if employee has any documents issues
            $hasAnyIssues = ! empty($expiringDocs);

            // Priority: 0 = no issues (top), 1 = has issues but not expired required (middle), 2 = has expired required (bottom)
            if (! $hasAnyIssues) {
                return 0; // No issues - top
            } elseif ($hasExpiredRequired) {
                return 2; // Expired required - bottom
            } else {
                return 1; // Has issues but not expired required - middle
            }
        })->values();

        // Apply pagination
        $offset = ($this->employeesPage - 1) * $this->employeesPerPage;
        $paginated = array_slice($sorted->toArray(), $offset, $this->employeesPerPage);

        return $paginated;
    }

    private function getAllAssignedEmployeeIds(): array
    {
        $employeeIds = [];

        // From day-based assignments
        foreach ($this->assignments as $dayAssignments) {
            foreach ($dayAssignments as $projectAssignments) {
                foreach ($projectAssignments as $roleAssignments) {
                    foreach ($roleAssignments as $employeeId) {
                        if (! in_array($employeeId, $employeeIds)) {
                            $employeeIds[] = $employeeId;
                        }
                    }
                }
            }
        }

        // From range-based assignments
        foreach ($this->assignmentRanges as $range) {
            if (! empty($range['employee_id']) && ! in_array($range['employee_id'], $employeeIds)) {
                $employeeIds[] = $range['employee_id'];
            }
        }

        // From vehicle seats
        foreach ($this->vehicleSeats as $seat) {
            if (! empty($seat['employee_id']) && ! in_array($seat['employee_id'], $employeeIds)) {
                $employeeIds[] = $seat['employee_id'];
            }
        }

        return $employeeIds;
    }

    /**
     * Get all employees assigned to projects (for display when no vehicle is selected)
     */
    public function getAssignedEmployeesForNoVehicle()
    {
        $employeeIds = [];

        // From range-based assignments (primary source)
        foreach ($this->assignmentRanges as $range) {
            if (! empty($range['employee_id']) && ! in_array($range['employee_id'], $employeeIds)) {
                $employeeIds[] = $range['employee_id'];
            }
        }

        // From day-based assignments (fallback)
        foreach ($this->assignments as $dayAssignments) {
            foreach ($dayAssignments as $projectAssignments) {
                foreach ($projectAssignments as $roleAssignments) {
                    foreach ($roleAssignments as $employeeId) {
                        if (! in_array($employeeId, $employeeIds)) {
                            $employeeIds[] = $employeeId;
                        }
                    }
                }
            }
        }

        // Get employee data from allAvailableEmployees
        $assignedEmployees = [];
        foreach ($employeeIds as $employeeId) {
            $employee = collect($this->allAvailableEmployees)->firstWhere('id', $employeeId);
            if ($employee) {
                $assignedEmployees[] = $employee;
            }
        }

        return $assignedEmployees;
    }

    public function openEmployeeModal($employeeId, $projectId, $roleId)
    {
        $this->selectedEmployee = collect($this->allAvailableEmployees)->firstWhere('id', $employeeId);
        $this->selectedProject = $this->getProject($projectId);
        $this->selectedRole = $this->getRole($roleId);

        if (! $this->selectedEmployee || ! $this->selectedProject || ! $this->selectedRole) {
            return;
        }

        // Initialize calendar month start to arrival date
        $arrivalDate = Carbon::parse($this->endDate);
        $this->calendarMonthStart = $arrivalDate->copy()->startOfMonth();

        // Load calendar for current month
        $this->loadEmployeeAvailabilityForMonth();

        $this->selectedStartDate = null;
        $this->selectedEndDate = null;
        $this->showEmployeeModal = true;
    }

    public function closeEmployeeModal()
    {
        $this->showEmployeeModal = false;
        $this->selectedEmployee = null;
        $this->selectedProject = null;
        $this->selectedRole = null;
        $this->employeeAvailability = [];
        $this->selectedStartDate = null;
        $this->selectedEndDate = null;
        $this->calendarMonthStart = null;
    }

    protected function loadEmployeeAvailabilityForMonth()
    {
        if (! $this->selectedEmployee || ! $this->selectedProject || ! $this->selectedRole || ! $this->calendarMonthStart) {
            return;
        }

        $arrivalDate = Carbon::parse($this->endDate);
        $monthStart = $this->calendarMonthStart->copy();
        $monthEnd = $monthStart->copy()->endOfMonth();

        // Load availability for the month range
        // minDate is set to arrival date to block dates before arrival
        $newAvailability = $this->departurePlannerService->getEmployeeAvailabilityForMonthRange(
            Employee::find($this->selectedEmployee['id']),
            $this->selectedProject,
            $this->selectedRole,
            $monthStart,
            $monthEnd,
            $this->assignments,
            $this->assignmentRanges,
            $arrivalDate, // minDate - block dates before arrival / transfer
            $this->forTransfer // przeniesienie: nie blokuj przez stare ProjectAssignment w bazie
        );

        // Merge with existing availability (for cross-month ranges)
        $this->employeeAvailability = array_merge($this->employeeAvailability, $newAvailability);
    }

    public function previousMonth()
    {
        if ($this->calendarMonthStart) {
            $this->calendarMonthStart = $this->calendarMonthStart->copy()->subMonth()->startOfMonth();
            $this->loadEmployeeAvailabilityForMonth();
        }
    }

    public function nextMonth()
    {
        if ($this->calendarMonthStart) {
            $this->calendarMonthStart = $this->calendarMonthStart->copy()->addMonth()->startOfMonth();
            $this->loadEmployeeAvailabilityForMonth();
        }
    }

    public function selectDate($date)
    {
        if (! $this->selectedEmployee || ! $this->selectedProject || ! $this->selectedRole) {
            return;
        }

        $dateKey = $date;
        $dateCarbon = Carbon::parse($dateKey);
        $arrivalDate = Carbon::parse($this->endDate);

        // Block dates before arrival
        if ($dateCarbon->lt($arrivalDate)) {
            return;
        }

        // Check if date is available
        if (! isset($this->employeeAvailability[$dateKey]) || ! $this->employeeAvailability[$dateKey]['can_assign']) {
            return;
        }

        if (! $this->selectedStartDate) {
            // Select start date
            $this->selectedStartDate = $dateKey;
            $this->selectedEndDate = null;
        } else {
            // Select end date
            $start = Carbon::parse($this->selectedStartDate);
            $end = Carbon::parse($dateKey);

            if ($end->lt($start)) {
                // If end is before start, make it the new start
                $this->selectedStartDate = $dateKey;
                $this->selectedEndDate = null;
            } else {
                $this->selectedEndDate = $dateKey;
            }
        }
    }

    public function confirmAssignment()
    {
        if (! $this->selectedEmployee) {
            $this->addError('confirmation', 'Wybierz pracownika.');

            return;
        }
        if (! $this->selectedProject) {
            $this->addError('confirmation', 'Wybierz projekt.');

            return;
        }
        if (! $this->selectedRole) {
            $this->addError('confirmation', 'Wybierz rolę.');

            return;
        }
        if (! $this->selectedStartDate) {
            $this->addError('confirmation', 'Wybierz datę rozpoczęcia w kalendarzu.');

            return;
        }

        $startDate = Carbon::parse($this->selectedStartDate);
        $endDate = $this->selectedEndDate ? Carbon::parse($this->selectedEndDate) : $startDate;

        // Validate against project end date
        if ($this->selectedProject->end_date) {
            $projectEnd = Carbon::parse($this->selectedProject->end_date)->endOfDay();
            if ($startDate->gt($projectEnd) || $endDate->gt($projectEnd)) {
                $this->addError('confirmation', 'Wybrane daty wykraczają poza datę końca projektu ('.$projectEnd->format('d.m.Y').'). Projekt nie jest aktywny w wybranym okresie.');

                return;
            }
        }

        // Always use range-based assignment (single assignment from start to end)
        $this->dispatch('assignment-range-added', [
            'employee_id' => $this->selectedEmployee['id'],
            'project_id' => $this->selectedProject->id,
            'role_id' => $this->selectedRole->id,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);

        $this->closeEmployeeModal();

        // Wait a moment for parent to update, then refresh
        // The parent will dispatch 'refresh-assignments' event
        $this->dispatch('refresh-parent');
    }

    public function removeAssignmentRange(int $employeeId, int $projectId, int $roleId): void
    {
        $this->dispatch('assignment-range-removed', [
            'employee_id' => $employeeId,
            'project_id' => $projectId,
            'role_id' => $roleId,
        ]);
    }

    public function updateVehicleSeat($seatIndex, $employeeId)
    {
        $this->dispatch('vehicle-seat-updated', [
            'seat_index' => $seatIndex,
            'employee_id' => $employeeId,
            'position' => $this->vehicleSeats[$seatIndex]['position'] ?? 'passenger',
        ]);
    }

    public function updateVehicleSeatPosition($seatIndex, $position)
    {
        $this->dispatch('vehicle-seat-updated', [
            'seat_index' => $seatIndex,
            'employee_id' => $this->vehicleSeats[$seatIndex]['employee_id'] ?? null,
            'position' => $position,
        ]);
    }

    public function goToNextStep()
    {
        // Wysyła event do rodzica
        $this->dispatch('go-to-step', step: 2);
    }

    public function render()
    {
        $employeesById = $this->buildEmployeesById();
        $assignedMap = $this->buildAssignedMap();
        $vehicleFlags = $this->computeVehicleFlags();

        return view('livewire.steps.step1-project-assignments', [
            'isVehicleFull' => $vehicleFlags['is_vehicle_full'],
            'showFullBanner' => $vehicleFlags['show_full_banner'],
            'employees' => $this->buildEmployeeRows(),
            'pagination' => $this->buildPaginationData(),
            'projects' => $this->buildProjectsWithChips($employeesById, $assignedMap),
            'projectsEmpty' => empty($this->filteredProjectGapsTwoWeeks),
            'projectsEmptyMsg' => filled($this->projectSearch)
                                  ? 'Brak braków dla filtrów'
                                  : 'Brak braków w rolach na najbliższe 2 tygodnie',
            'calendar' => $this->buildCalendarData(),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Render data assemblers — dostarczają gotowe dane do Blade
    // ──────────────────────────────────────────────────────────────────────

    private function buildEmployeesById(): array
    {
        return array_column($this->allAvailableEmployees, null, 'id');
    }

    private function buildAssignedMap(): array
    {
        $map = [];

        foreach ($this->assignments as $dayAssignments) {
            foreach ($dayAssignments as $projectId => $roles) {
                foreach ($roles as $roleId => $empIds) {
                    foreach ($empIds as $empId) {
                        $map[$projectId][$roleId][$empId] = true;
                    }
                }
            }
        }

        foreach ($this->assignmentRanges as $range) {
            if (! empty($range['employee_id'])) {
                $map[$range['project_id']][$range['role_id']][$range['employee_id']] = true;
            }
        }

        return $map;
    }

    private function buildProjectsWithChips(array $employeesById, array $assignedMap): array
    {
        return collect($this->filteredProjectGapsTwoWeeks)
            ->map(function ($project, $projectId) use ($employeesById, $assignedMap) {
                $roles = collect($project['roles'])
                    ->map(function ($role, $roleId) use ($employeesById, $assignedMap, $projectId) {
                        $assignedIds = array_keys($assignedMap[$projectId][$roleId] ?? []);

                        $chips = collect($assignedIds)
                            ->map(fn ($id) => $this->buildChip((int) $id, $employeesById, $projectId, $roleId))
                            ->filter()
                            ->values()
                            ->all();

                        return array_merge($role, [
                            'id' => $roleId,
                            'gap_label' => $role['min_gaps'] === $role['max_gaps']
                                                ? "{$role['min_gaps']} brak."
                                                : "{$role['min_gaps']}–{$role['max_gaps']} brak.",
                            'assigned_chips' => $chips,
                        ]);
                    })
                    ->values()
                    ->all();

                return array_merge($project, ['id' => $projectId, 'roles' => $roles]);
            })
            ->values()
            ->all();
    }

    private function buildChip(int $empId, array $employeesById, $projectId, $roleId): ?array
    {
        $emp = $employeesById[$empId] ?? null;
        if (! $emp) {
            return null;
        }

        return [
            'employee_id' => $empId,
            'project_id' => $projectId,
            'role_id' => $roleId,
            'name' => $emp['full_name'],
            'initials' => mb_substr($emp['first_name'], 0, 1).mb_substr($emp['last_name'], 0, 1),
            'image_url' => $emp['image_url'],
        ];
    }

    private function buildEmployeeRows(): array
    {
        return collect($this->getFilteredEmployees())
            ->map(fn ($e) => array_merge($e, [
                'initials' => mb_substr($e['first_name'], 0, 1).mb_substr($e['last_name'], 0, 1),
                'rotation_label' => $this->formatRotationLabel($e['rotation'] ?? null),
                'docs_warning' => $this->buildDocsWarning($e['expiring_documents'] ?? []),
            ]))
            ->all();
    }

    private function formatRotationLabel(?array $rotation): ?string
    {
        if (! $rotation) {
            return null;
        }

        $label = Carbon::parse($rotation['start_date'])->format('d.m.Y');
        if ($rotation['end_date']) {
            $label .= ' – '.Carbon::parse($rotation['end_date'])->format('d.m.Y');
        }

        return $label;
    }

    private function buildDocsWarning(array $docs): ?array
    {
        if (empty($docs)) {
            return null;
        }

        $critical = count(array_filter($docs, fn ($d) => $d['is_required'] ?? false));

        return ['total' => count($docs), 'critical' => $critical];
    }

    private function buildPaginationData(): ?array
    {
        $total = count($this->allAvailableEmployees);
        $totalPages = $total > 0 ? (int) ceil($total / $this->employeesPerPage) : 1;

        if ($totalPages <= 1) {
            return null;
        }

        $from = ($this->employeesPage - 1) * $this->employeesPerPage + 1;
        $to = min($this->employeesPage * $this->employeesPerPage, $total);

        return [
            'label' => "{$from}–{$to} / {$total}",
            'can_prev' => $this->employeesPage > 1,
            'can_next' => $this->employeesPage < $totalPages,
        ];
    }

    private function computeVehicleFlags(): array
    {
        $capacity = is_array($this->vehicleSeats) ? count($this->vehicleSeats) : 0;
        $isExternalDriver = (bool) ($this->vehicleSeats[0]['external_driver'] ?? true);
        $isOwnTransport = ! empty($this->vehicleId) && $capacity > 0;
        $totalPeople = count($this->getAllAssignedEmployeeIds()) + ($isExternalDriver ? 1 : 0);
        $isFull = $isOwnTransport && $totalPeople >= $capacity;

        return [
            'is_vehicle_full' => $isFull,
            'show_full_banner' => $isFull && ! $this->forTransfer,
        ];
    }

    private function buildCalendarData(): array
    {
        $arrival = Carbon::parse($this->endDate);
        $calStart = $this->calendarMonthStart
            ? Carbon::parse($this->calendarMonthStart)
            : $arrival->copy()->startOfMonth();

        $selectedRange = null;
        if ($this->selectedStartDate) {
            $startFmt = Carbon::parse($this->selectedStartDate)->format('d.m.Y');
            if ($this->selectedEndDate && $this->selectedStartDate !== $this->selectedEndDate) {
                $endFmt = Carbon::parse($this->selectedEndDate)->format('d.m.Y');
                $selectedRange = ['type' => 'range', 'label' => "{$startFmt} - {$endFmt}"];
            } else {
                $selectedRange = ['type' => 'start', 'label' => $startFmt];
            }
        }

        return [
            'arrival_date' => $arrival->format('Y-m-d'),
            'start_date' => $calStart->format('Y-m-d'),
            'selected_range' => $selectedRange,
        ];
    }
}
