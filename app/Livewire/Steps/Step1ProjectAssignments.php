<?php

namespace App\Livewire\Steps;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Role;
use App\Models\Vehicle;
use App\Services\DeparturePlannerService;
use App\Services\ExpiringDocumentsService;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Step1ProjectAssignments extends Component
{
    // Dane otrzymane z rodzica (read-only)
    public $departureDate;
    public $endDate;
    public $vehicleId;
    public $assignments = []; // Read-only z rodzica
    public $assignmentRanges = []; // Read-only z rodzica
    public $vehicleSeats = []; // Updated via events from parent
    
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
    
    public function mount($departureDate, $endDate, $vehicleId, $assignments = [], $assignmentRanges = [], $vehicleSeats = [])
    {
        $this->departureDate = $departureDate;
        $this->endDate = $endDate;
        $this->vehicleId = $vehicleId;
        $this->assignments = $assignments;
        $this->assignmentRanges = $assignmentRanges;
        $this->vehicleSeats = $vehicleSeats;
        
        // Wykonuje ciężkie operacje
        $this->loadData();
        
        // Jeśli vehicleId jest ustawione i vehicleSeats są puste lub nie pasują do capacity,
        // wczytaj przypisania z assignmentRanges
        if ($this->vehicleId && !empty($this->assignmentRanges)) {
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
        if (!$this->departureDate) {
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
                    
                    // Uzupełnij tylko brakujące miejsca, nie nadpisuj istniejących
                    if ($existingSeats < $capacity) {
                        for ($i = $existingSeats; $i < $capacity; $i++) {
                            if (!isset($this->vehicleSeats[$i])) {
                                $this->vehicleSeats[$i] = [
                                    'employee_id' => null,
                                    'position' => 'passenger',
                                ];
                            }
                        }
                    }
                    
                    // Upewnij się, że wszystkie miejsca mają poprawną strukturę
                    for ($i = 0; $i < min($existingSeats, $capacity); $i++) {
                        if (!isset($this->vehicleSeats[$i]['position'])) {
                            $this->vehicleSeats[$i]['position'] = 'passenger';
                        }
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
        $allEmployees = Employee::with(['roles', 'employeeDocuments.document'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(function ($employee) use ($departureDate) {
                // Get rotation for this date
                $rotation = $employee->getActiveRotationForDate($departureDate);
                
                // Get expiring documents (within a month)
                $expiringDocuments = $this->expiringDocumentsService->getExpiringDocumentsForEmployee($employee, 30);
                
                // POPRAWKA: Sprawdź również wszystkie wygasłe wymagane dokumenty (niezależnie od daty wygaśnięcia)
                // aby wyświetlić je w liście pracowników, nawet jeśli wygasły dawno
                $now = Carbon::now();
                $allExpiredRequired = \App\Models\EmployeeDocument::where('employee_id', $employee->id)
                    ->where('kind', 'okresowy')
                    ->whereNotNull('valid_to')
                    ->where('valid_to', '<', $now->format('Y-m-d'))
                    ->with('document')
                    ->get()
                    ->filter(function ($doc) {
                        // Tylko wymagane dokumenty
                        return $doc->document && $doc->document->is_required;
                    })
                    ->map(function ($doc) use ($now) {
                        $validTo = Carbon::parse($doc->valid_to);
                        $daysDiff = $validTo->diffInDays($now);
                        
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
                    return !in_array($doc['id'], $expiringDocumentIds);
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
                    'roles' => $employee->roles->map(fn($role) => [
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
    
    public function loadProjectGaps()
    {
        if (!$this->endDate) {
            return;
        }
        
        $arrivalDate = Carbon::parse($this->endDate);
        $this->projectGaps = $this->departurePlannerService->getProjectGapsForWeek($arrivalDate);
        $this->projectGapsTwoWeeks = $this->departurePlannerService->getProjectGapsForTwoWeeks($arrivalDate);
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
                    // Upewnij się, że wszystkie miejsca mają poprawną strukturę
                    for ($i = 0; $i < $capacity; $i++) {
                        if (!isset($this->vehicleSeats[$i])) {
                            $this->vehicleSeats[$i] = [
                                'employee_id' => null,
                                'position' => 'passenger',
                            ];
                        } elseif (!isset($this->vehicleSeats[$i]['position'])) {
                            $this->vehicleSeats[$i]['position'] = 'passenger';
                        }
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
        if (!$this->vehicle || !$this->vehicle->capacity) {
            // Don't clear vehicleSeats here - they come from parent
            return;
        }
        
        $capacity = $this->vehicle->capacity;
        $existingSeats = count($this->vehicleSeats);
        
        // Initialize seats array if needed - but preserve existing assignments from parent
        if ($existingSeats < $capacity) {
            // Uzupełnij tylko brakujące miejsca, nie nadpisuj istniejących przypisań
            for ($i = $existingSeats; $i < $capacity; $i++) {
                if (!isset($this->vehicleSeats[$i])) {
                    $this->vehicleSeats[$i] = [
                        'employee_id' => null,
                        'position' => 'passenger',
                    ];
                }
            }
        }
        // USUNIĘTE: elseif ($existingSeats > $capacity) - nie usuwaj nadmiarowych miejsc
        // Jeśli capacity się zmniejszyło, zostaw istniejące przypisania
        // (użytkownik może je później usunąć ręcznie lub zmienić pojazd)
        
        // Ensure all seats have the correct structure
        for ($i = 0; $i < min($existingSeats, $capacity); $i++) {
            if (!isset($this->vehicleSeats[$i])) {
                $this->vehicleSeats[$i] = [
                    'employee_id' => null,
                    'position' => 'passenger',
                ];
            } elseif (!isset($this->vehicleSeats[$i]['position'])) {
                $this->vehicleSeats[$i]['position'] = 'passenger';
            }
        }
    }
    
    /**
     * Wczytaj przypisania z assignmentRanges do vehicleSeats
     * Używane przy mount() gdy vehicleId jest ustawione i są assignmentRanges
     */
    public function loadVehicleAssignmentsFromRanges()
    {
        if (!$this->vehicleId || empty($this->assignmentRanges)) {
            return;
        }
        
        $vehicle = Vehicle::find($this->vehicleId);
        if (!$vehicle || !$vehicle->capacity) {
            return;
        }
        
        // Inicjalizuj miejsca jeśli są puste
        if (empty($this->vehicleSeats)) {
            for ($i = 0; $i < $vehicle->capacity; $i++) {
                $this->vehicleSeats[$i] = [
                    'employee_id' => null,
                    'position' => 'passenger',
                ];
            }
        }
        
        // Wczytaj wszystkie przypisania z assignmentRanges
        foreach ($this->assignmentRanges as $assignmentRange) {
            $employeeId = $assignmentRange['employee_id'];
            
            // Sprawdź czy pracownik już nie jest w aucie (duplikaty)
            $alreadyInVehicle = false;
            foreach ($this->vehicleSeats as $seat) {
                if (!empty($seat['employee_id']) && $seat['employee_id'] == $employeeId) {
                    $alreadyInVehicle = true;
                    break;
                }
            }
            
            // Jeśli nie ma go w aucie, znajdź pierwsze wolne miejsce i przypisz
            if (!$alreadyInVehicle) {
                foreach ($this->vehicleSeats as $index => $seat) {
                    if (empty($seat['employee_id'])) {
                        $this->vehicleSeats[$index] = [
                            'employee_id' => $employeeId,
                            'position' => 'passenger', // Domyślnie pasażer
                        ];
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
                if (!in_array($projectId, $projectIds)) {
                    $projectIds[] = $projectId;
                }
                foreach ($roles as $roleId => $employeeIds) {
                    if (!in_array($roleId, $roleIds)) {
                        $roleIds[] = $roleId;
                    }
                }
            }
        }
        
        foreach ($this->assignmentRanges as $range) {
            if (!empty($range['project_id']) && !in_array($range['project_id'], $projectIds)) {
                $projectIds[] = $range['project_id'];
            }
            if (!empty($range['role_id']) && !in_array($range['role_id'], $roleIds)) {
                $roleIds[] = $range['role_id'];
            }
        }
        
        // Load projects and roles in bulk
        if (!empty($projectIds)) {
            $projects = Project::whereIn('id', $projectIds)->get();
            foreach ($projects as $project) {
                $this->projectsCache[$project->id] = $project;
            }
        }
        
        if (!empty($roleIds)) {
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
        if ($startDate && $endDate) {
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
            $hasAnyIssues = !empty($expiringDocs);
            
            // Priority: 0 = no issues (top), 1 = has issues but not expired required (middle), 2 = has expired required (bottom)
            if (!$hasAnyIssues) {
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
                        if (!in_array($employeeId, $employeeIds)) {
                            $employeeIds[] = $employeeId;
                        }
                    }
                }
            }
        }
        
        // From range-based assignments
        foreach ($this->assignmentRanges as $range) {
            if (!empty($range['employee_id']) && !in_array($range['employee_id'], $employeeIds)) {
                $employeeIds[] = $range['employee_id'];
            }
        }
        
        // From vehicle seats
        foreach ($this->vehicleSeats as $seat) {
            if (!empty($seat['employee_id']) && !in_array($seat['employee_id'], $employeeIds)) {
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
            if (!empty($range['employee_id']) && !in_array($range['employee_id'], $employeeIds)) {
                $employeeIds[] = $range['employee_id'];
            }
        }
        
        // From day-based assignments (fallback)
        foreach ($this->assignments as $dayAssignments) {
            foreach ($dayAssignments as $projectAssignments) {
                foreach ($projectAssignments as $roleAssignments) {
                    foreach ($roleAssignments as $employeeId) {
                        if (!in_array($employeeId, $employeeIds)) {
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
        
        if (!$this->selectedEmployee || !$this->selectedProject || !$this->selectedRole) {
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
        if (!$this->selectedEmployee || !$this->selectedProject || !$this->selectedRole || !$this->calendarMonthStart) {
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
            $arrivalDate // minDate - block dates before arrival
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
        if (!$this->selectedEmployee || !$this->selectedProject || !$this->selectedRole) {
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
        if (!isset($this->employeeAvailability[$dateKey]) || !$this->employeeAvailability[$dateKey]['can_assign']) {
            return;
        }

        if (!$this->selectedStartDate) {
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
        if (!$this->selectedEmployee || !$this->selectedProject || !$this->selectedRole) {
            return;
        }
        
        $startDate = $this->selectedStartDate ? Carbon::parse($this->selectedStartDate) : Carbon::parse($this->endDate);
        $endDate = $this->selectedEndDate ? Carbon::parse($this->selectedEndDate) : $startDate;
        
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
        return view('livewire.steps.step1-project-assignments');
    }
}
