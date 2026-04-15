<?php

namespace App\Livewire\Steps;

use App\Enums\VehiclePosition;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Role;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Services\DeparturePlannerService;
use Carbon\Carbon;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class Step3VehicleAssignments extends Component
{
    // Dane otrzymane z rodzica (read-only)
    public $departureDate;

    public $endDate;

    #[Reactive]
    public $vehicleId; // Vehicle selected in step 1 (reactive - updates when parent changes it)

    public $assignments = []; // Read-only z rodzica

    public $assignmentRanges = []; // Read-only z rodzica

    public $accommodationAssignments = []; // Read-only z rodzica

    public $vehicleAssignments = []; // Read-only z rodzica

    public bool $forTransfer = false;

    public array $allowedEmployeeIds = [];

    // Własne dane (ciężkie obliczenia)
    public $assignedEmployees = [];

    public $vehicles = [];

    public $vehicleSearch = '';

    // Cache for Projects, Roles, and Vehicles to avoid N+1 queries
    protected $projectsCache = [];

    protected $rolesCache = [];

    protected $vehiclesCache = [];

    // Modal state for vehicle assignment
    public $showVehicleModal = false;

    public $selectedEmployee = null;

    public $selectedVehicle = null;

    public $selectedPosition = 'passenger';

    public $vehicleAvailability = [];

    public $selectedStartDate = null;

    public $selectedEndDate = null;

    public $calendarMonthStart = null;

    public $arrivalDate;

    protected $departurePlannerService;

    public function boot(DeparturePlannerService $departurePlannerService)
    {
        $this->departurePlannerService = $departurePlannerService;
    }

    public function mount(
        $departureDate,
        $endDate,
        $vehicleId,
        $assignments = [],
        $assignmentRanges = [],
        $accommodationAssignments = [],
        $vehicleAssignments = [],
        $forTransfer = false,
        $allowedEmployeeIds = []
    ) {
        $this->departureDate = $departureDate;
        $this->endDate = $endDate;
        if (! empty($endDate)) {
            $this->arrivalDate = Carbon::parse($endDate);
        } elseif (! empty($departureDate)) {
            $this->arrivalDate = Carbon::parse($departureDate);
        } else {
            $this->arrivalDate = now()->startOfDay();
        }
        $this->vehicleId = $vehicleId;
        $this->assignments = $assignments;
        $this->assignmentRanges = $assignmentRanges;
        $this->accommodationAssignments = $accommodationAssignments;
        $this->vehicleAssignments = $vehicleAssignments;
        $this->forTransfer = (bool) $forTransfer;
        $this->allowedEmployeeIds = is_array($allowedEmployeeIds)
            ? array_values(array_map('intval', $allowedEmployeeIds))
            : [];

        // Wykonuje ciężkie operacje
        $this->loadAssignedEmployees();
        $this->loadVehicles();
        $this->loadCaches();
    }

    public function updatedVehicleSearch()
    {
        // Keep it reactive and predictable
        $this->dispatch('$refresh');
    }

    public function updatedVehicleId()
    {
        // Reload vehicle list when departure vehicle changes (reactive prop updated by parent)
        $this->loadVehicles();
    }

    public function getFilteredVehiclesProperty(): array
    {
        $items = $this->vehicles ?? [];
        if (empty($items) || ! is_array($items)) {
            return [];
        }

        $search = trim((string) ($this->vehicleSearch ?? ''));
        if ($search === '') {
            return $items;
        }

        $needle = mb_strtolower($search);

        return collect($items)
            ->filter(function ($v) use ($needle) {
                $reg = (string) ($v['registration_number'] ?? '');
                $brand = (string) ($v['brand'] ?? '');
                $model = (string) ($v['model'] ?? '');

                $haystack = mb_strtolower(trim($reg.' '.$brand.' '.$model));

                return $haystack !== '' && str_contains($haystack, $needle);
            })
            ->values()
            ->all();
    }

    protected function loadCaches()
    {
        // Collect project and role IDs from assignments
        $projectIds = [];
        $roleIds = [];

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

        // Load all vehicles into cache as models
        $vehicleIds = array_column($this->vehicles, 'id');
        if (! empty($vehicleIds)) {
            $vehicles = Vehicle::whereIn('id', $vehicleIds)->get();
            foreach ($vehicles as $vehicle) {
                $this->vehiclesCache[$vehicle->id] = $vehicle;
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

    protected function getVehicle($vehicleId)
    {
        if (isset($this->vehiclesCache[$vehicleId])) {
            return $this->vehiclesCache[$vehicleId];
        }

        $vehicle = Vehicle::find($vehicleId);
        if ($vehicle) {
            $this->vehiclesCache[$vehicleId] = $vehicle;
        }

        return $vehicle;
    }

    public function loadAssignedEmployees()
    {
        // Get all unique employee IDs from assignments
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
            if (! in_array($range['employee_id'], $employeeIds)) {
                $employeeIds[] = $range['employee_id'];
            }
        }

        // Load employees with their details and project assignments
        $employees = Employee::whereIn('id', $employeeIds)
            ->with('roles')
            ->get();

        $mapped = $employees->map(function ($employee) {
            // Get project assignments for this employee
            $projectAssignments = $this->getEmployeeProjectAssignments($employee->id);

            return [
                'id' => $employee->id,
                'full_name' => $employee->full_name,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'image_url' => $employee->image_url,
                'project_assignments' => $projectAssignments,
            ];
        })->toArray();

        if ($this->forTransfer && ! empty($this->allowedEmployeeIds)) {
            $allowed = array_flip($this->allowedEmployeeIds);
            $mapped = array_values(array_filter($mapped, fn ($row) => isset($allowed[$row['id']])));
        }

        $this->assignedEmployees = $mapped;
    }

    protected function getEmployeeProjectAssignments($employeeId): array
    {
        $assignments = [];

        // From day-based assignments
        foreach ($this->assignments as $dayKey => $dayAssignments) {
            $dayNum = (int) str_replace('day_', '', $dayKey);
            $dayDate = $this->arrivalDate->copy()->addDays($dayNum - 1);

            foreach ($dayAssignments as $projectId => $roles) {
                foreach ($roles as $roleId => $employeeIds) {
                    if (in_array($employeeId, $employeeIds)) {
                        $project = $this->getProject($projectId);
                        $role = $this->getRole($roleId);

                        if ($project && $role) {
                            $key = "{$projectId}_{$roleId}";
                            if (! isset($assignments[$key])) {
                                $assignments[$key] = [
                                    'project_id' => $projectId,
                                    'project_name' => $project->name,
                                    'role_id' => $roleId,
                                    'role_name' => $role->name,
                                    'dates' => [],
                                ];
                            }
                            $assignments[$key]['dates'][] = $dayDate->format('Y-m-d');
                        }
                    }
                }
            }
        }

        // From range-based assignments
        foreach ($this->assignmentRanges as $range) {
            if ($range['employee_id'] == $employeeId) {
                $project = $this->getProject($range['project_id']);
                $role = $this->getRole($range['role_id']);

                if ($project && $role) {
                    $key = "{$range['project_id']}_{$range['role_id']}";
                    if (! isset($assignments[$key])) {
                        $assignments[$key] = [
                            'project_id' => $range['project_id'],
                            'project_name' => $project->name,
                            'role_id' => $range['role_id'],
                            'role_name' => $role->name,
                            'dates' => [],
                        ];
                    }
                    // Add all dates in range
                    $start = Carbon::parse($range['start_date']);
                    $end = Carbon::parse($range['end_date']);
                    $current = $start->copy();
                    while ($current->lte($end)) {
                        $assignments[$key]['dates'][] = $current->format('Y-m-d');
                        $current->addDay();
                    }
                }
            }
        }

        // Convert dates to ranges for display
        foreach ($assignments as $key => $assignment) {
            $dates = array_unique($assignment['dates']);
            sort($dates);

            // Group consecutive dates into ranges
            $ranges = [];
            if (! empty($dates)) {
                $start = Carbon::parse($dates[0]);
                $end = $start->copy();

                for ($i = 1; $i < count($dates); $i++) {
                    $current = Carbon::parse($dates[$i]);
                    if ($current->diffInDays($end) == 1) {
                        $end = $current;
                    } else {
                        if ($start->eq($end)) {
                            $ranges[] = $start->format('d.m.Y');
                        } else {
                            $ranges[] = $start->format('d.m.Y').' - '.$end->format('d.m.Y');
                        }
                        $start = $current;
                        $end = $current;
                    }
                }

                if ($start->eq($end)) {
                    $ranges[] = $start->format('d.m.Y');
                } else {
                    $ranges[] = $start->format('d.m.Y').' - '.$end->format('d.m.Y');
                }
            }

            $assignments[$key]['date_ranges'] = $ranges;
        }

        return array_values($assignments);
    }

    public function getUnassignedEmployeesProperty()
    {
        // Filter out employees who already have vehicle assignments
        return array_filter($this->assignedEmployees, function ($employee) {
            return ! isset($this->vehicleAssignments[$employee['id']]);
        });
    }

    public function getVehicleProjects($vehicleId): array
    {
        $projectIds = [];
        $projects = [];
        $vehicleId = (int) $vehicleId;

        // 1. Get projects from form assignments (step 3)
        foreach ($this->vehicleAssignments as $employeeId => $assignment) {
            $assignmentVehicleId = isset($assignment['vehicle_id']) ? (int) $assignment['vehicle_id'] : null;

            if ($assignmentVehicleId === $vehicleId) {
                // Get employee's project assignments from form
                $employee = collect($this->assignedEmployees)->firstWhere('id', (int) $employeeId);
                if ($employee && ! empty($employee['project_assignments'])) {
                    foreach ($employee['project_assignments'] as $projectAssignment) {
                        $projectId = (int) $projectAssignment['project_id'];
                        if (! in_array($projectId, $projectIds)) {
                            $projectIds[] = $projectId;
                            $projects[] = [
                                'id' => $projectId,
                                'name' => $projectAssignment['project_name'],
                            ];
                        }
                    }
                }
            }
        }

        // 2. Get projects from existing database assignments (active at arrival date)
        $existingAssignments = VehicleAssignment::where('vehicle_id', $vehicleId)
            ->where('start_date', '<=', $this->arrivalDate)
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $this->arrivalDate);
            })
            ->with(['employee.assignments' => function ($query) {
                $query->where('start_date', '<=', $this->arrivalDate)
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', $this->arrivalDate);
                    })
                    ->with('project');
            }])
            ->get();

        foreach ($existingAssignments as $vehicleAssignment) {
            $employee = $vehicleAssignment->employee;
            if ($employee && $employee->assignments) {
                foreach ($employee->assignments as $projectAssignment) {
                    if ($projectAssignment->project) {
                        $projectId = $projectAssignment->project->id;
                        if (! in_array($projectId, $projectIds)) {
                            $projectIds[] = $projectId;
                            $projects[] = [
                                'id' => $projectId,
                                'name' => $projectAssignment->project->name,
                            ];
                        }
                    }
                }
            }
        }

        return $projects;
    }

    /**
     * Czy przypisanie z kreatora (zakres dat) obejmuje dany dzień.
     */
    protected function vehicleFormAssignmentOverlapsDate(array $assignment, Carbon $date): bool
    {
        if (empty($assignment['start_date']) || empty($assignment['end_date'])) {
            return false;
        }
        $start = Carbon::parse($assignment['start_date'])->startOfDay();
        $end = Carbon::parse($assignment['end_date'])->endOfDay();

        return $date->gte($start) && $date->lte($end);
    }

    public function getVehicleOccupancy($vehicleId): array
    {
        $vehicleId = (int) $vehicleId;
        $vehicle = $this->getVehicle($vehicleId);
        $capacity = $vehicle?->capacity ?? 0;

        // Formularz: tylko osoby, których zakres obejmuje dzień przyjazdu (spójnie z kalendarzem)
        $employeeIds = [];
        foreach ($this->vehicleAssignments as $employeeId => $assignment) {
            $assignmentVehicleId = isset($assignment['vehicle_id']) ? (int) $assignment['vehicle_id'] : null;
            if ($assignmentVehicleId === $vehicleId && $this->vehicleFormAssignmentOverlapsDate($assignment, $this->arrivalDate)) {
                $employeeIds[] = (int) $employeeId;
            }
        }

        // Baza: przypisania aktywne w dniu przyjazdu
        $dbAssignments = VehicleAssignment::where('vehicle_id', $vehicleId)
            ->where('start_date', '<=', $this->arrivalDate)
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $this->arrivalDate);
            })
            ->pluck('employee_id')
            ->toArray();

        $employeeIds = array_unique(array_merge($employeeIds, $dbAssignments));
        $totalOccupied = count($employeeIds);

        $available = max(0, $capacity - $totalOccupied);

        return [
            'occupied' => $totalOccupied,
            'capacity' => $capacity,
            'available' => $available,
        ];
    }

    /**
     * Get vehicle occupancy excluding a specific employee (used when re-assigning or editing).
     */
    public function getVehicleOccupancyExcluding(int $vehicleId, int $excludeEmployeeId): array
    {
        $vehicle = $this->getVehicle($vehicleId);
        $capacity = $vehicle?->capacity ?? 0;

        $employeeIds = [];
        foreach ($this->vehicleAssignments as $employeeId => $assignment) {
            $assignmentVehicleId = isset($assignment['vehicle_id']) ? (int) $assignment['vehicle_id'] : null;
            if (
                $assignmentVehicleId === $vehicleId
                && (int) $employeeId !== $excludeEmployeeId
                && $this->vehicleFormAssignmentOverlapsDate($assignment, $this->arrivalDate)
            ) {
                $employeeIds[] = (int) $employeeId;
            }
        }

        $dbAssignments = VehicleAssignment::where('vehicle_id', $vehicleId)
            ->where('employee_id', '!=', $excludeEmployeeId)
            ->where('start_date', '<=', $this->arrivalDate)
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $this->arrivalDate);
            })
            ->pluck('employee_id')
            ->toArray();

        $employeeIds = array_unique(array_merge($employeeIds, $dbAssignments));
        $totalOccupied = count($employeeIds);
        $available = max(0, $capacity - $totalOccupied);

        return [
            'occupied' => $totalOccupied,
            'capacity' => $capacity,
            'available' => $available,
        ];
    }

    /**
     * Metryki miejsc dla pojazdu w jednym dniu (ta sama logika co kalendarz).
     *
     * @return array{available_capacity: int, available_driver_slots: int, available_passenger_slots: int}
     */
    protected function computeVehicleDaySeatMetrics(Vehicle $vehicle, Carbon $date, int $employeeId): array
    {
        $vehicleCapacity = $vehicle->capacity ?? 0;

        $existingAssignments = $vehicle->assignments()
            ->where('employee_id', '!=', $employeeId)
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            })
            ->get();

        $formAssignmentsCount = 0;
        $formDriverCount = 0;
        $formPassengerCount = 0;

        foreach ($this->vehicleAssignments as $empId => $assignment) {
            if ((int) $assignment['vehicle_id'] !== (int) $vehicle->id) {
                continue;
            }
            if ((int) $empId === $employeeId) {
                continue;
            }

            $assignmentStart = Carbon::parse($assignment['start_date']);
            $assignmentEnd = Carbon::parse($assignment['end_date']);
            if ($date->gte($assignmentStart) && $date->lte($assignmentEnd)) {
                $formAssignmentsCount++;
                if (($assignment['position'] ?? '') === 'driver') {
                    $formDriverCount++;
                } else {
                    $formPassengerCount++;
                }
            }
        }

        $existingDriverCount = $existingAssignments->where('position', VehiclePosition::DRIVER)->count();
        $existingPassengerCount = $existingAssignments->where('position', VehiclePosition::PASSENGER)->count();

        $totalDriverCount = $existingDriverCount + $formDriverCount;
        $totalPassengerCount = $existingPassengerCount + $formPassengerCount;
        $totalOccupied = $existingAssignments->count() + $formAssignmentsCount;

        $availableDriverSlots = ($totalDriverCount < 1) ? 1 : 0;
        $availablePassengerSlots = max(0, $vehicleCapacity - 1 - $totalPassengerCount);
        $availableCapacity = max(0, $vehicleCapacity - $totalOccupied);

        return [
            'available_capacity' => $availableCapacity,
            'available_driver_slots' => $availableDriverSlots,
            'available_passenger_slots' => $availablePassengerSlots,
        ];
    }

    public function loadVehicles()
    {
        // Show only vehicles that will be outside base on arrival date:
        // 1. The departure vehicle itself (if set)
        // 2. Vehicles with existing VehicleAssignments active on the arrival date
        //    (i.e., already deployed to worksite from previous operations)
        $outsideBaseIds = VehicleAssignment::where('start_date', '<=', $this->arrivalDate)
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $this->arrivalDate);
            })
            ->distinct()
            ->pluck('vehicle_id')
            ->toArray();

        // Always include the departure vehicle
        if ($this->vehicleId) {
            $outsideBaseIds[] = (int) $this->vehicleId;
        }

        $outsideBaseIds = array_unique($outsideBaseIds);

        if (empty($outsideBaseIds)) {
            // Fallback: show all company vehicles if no filter matches
            $this->vehicles = Vehicle::where('type', 'company_vehicle')
                ->orderBy('registration_number')
                ->get()
                ->toArray();

            return;
        }

        $this->vehicles = Vehicle::where('type', 'company_vehicle')
            ->whereIn('id', $outsideBaseIds)
            ->orderBy('registration_number')
            ->get()
            ->toArray();
    }

    public function openVehicleModal($employeeId, $vehicleId)
    {
        $this->selectedEmployee = collect($this->assignedEmployees)->firstWhere('id', $employeeId);
        $this->selectedVehicle = $this->getVehicle($vehicleId);

        if (! $this->selectedEmployee || ! $this->selectedVehicle) {
            return;
        }

        // Initialize calendar month start to arrival date
        $this->calendarMonthStart = $this->arrivalDate->copy()->startOfMonth();

        // Load calendar for current month
        $this->loadVehicleAvailabilityForMonth();

        $this->selectedStartDate = null;
        $this->selectedEndDate = null;
        $this->selectedPosition = 'passenger';
        $this->showVehicleModal = true;
    }

    public function closeVehicleModal()
    {
        $this->showVehicleModal = false;
        $this->selectedEmployee = null;
        $this->selectedVehicle = null;
        $this->vehicleAvailability = [];
        $this->selectedStartDate = null;
        $this->selectedEndDate = null;
        $this->selectedPosition = 'passenger';
        $this->calendarMonthStart = null;
    }

    protected function loadVehicleAvailabilityForMonth()
    {
        if (! $this->selectedEmployee || ! $this->selectedVehicle || ! $this->calendarMonthStart) {
            return;
        }

        $monthStart = $this->calendarMonthStart->copy();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $employeeId = $this->selectedEmployee['id'];

        // Load vehicle availability for the month
        $this->vehicleAvailability = $this->getVehicleAvailabilityForMonth(
            $this->selectedVehicle,
            $monthStart,
            $monthEnd,
            $employeeId
        );
    }

    public function previousMonth()
    {
        if ($this->calendarMonthStart) {
            $this->calendarMonthStart = $this->calendarMonthStart->copy()->subMonth()->startOfMonth();
            $this->loadVehicleAvailabilityForMonth();
        }
    }

    public function nextMonth()
    {
        if ($this->calendarMonthStart) {
            $this->calendarMonthStart = $this->calendarMonthStart->copy()->addMonth()->startOfMonth();
            $this->loadVehicleAvailabilityForMonth();
        }
    }

    public function selectDate($date)
    {
        if (! $this->selectedEmployee || ! $this->selectedVehicle) {
            return;
        }

        $dateKey = $date;
        if (! isset($this->vehicleAvailability[$dateKey]) || ! $this->vehicleAvailability[$dateKey]['can_assign']) {
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

    public function confirmVehicleAssignment()
    {
        try {
            if (! $this->selectedEmployee) {
                $this->addError('confirmation', 'Brak wybranego pracownika.');

                return;
            }
            if (! $this->selectedVehicle) {
                $this->addError('confirmation', 'Wybierz pojazd.');

                return;
            }
            if (! $this->selectedStartDate) {
                $this->addError('confirmation', 'Wybierz datę rozpoczęcia w kalendarzu.');

                return;
            }

            $endDate = $this->selectedEndDate ?: $this->selectedStartDate;
            $vehicleId = is_object($this->selectedVehicle) ? $this->selectedVehicle->id : ($this->selectedVehicle['id'] ?? null);

            if (! $vehicleId) {
                $this->addError('confirmation', 'Błąd: nie można odczytać ID pojazdu.');

                return;
            }

            $selectedEmployeeId = (int) ($this->selectedEmployee['id'] ?? 0);

            $vehicle = $this->getVehicle((int) $vehicleId);
            if (! $vehicle) {
                $this->addError('confirmation', 'Nie znaleziono pojazdu.');

                return;
            }

            // Walidacja jak w kalendarzu: każdy dzień zakresu musi mieć wolne miejsce (kierowca vs pasażer)
            $rangeStart = Carbon::parse($this->selectedStartDate)->startOfDay();
            $rangeEnd = Carbon::parse($endDate)->startOfDay();
            if ($rangeEnd->lt($rangeStart)) {
                $this->addError('confirmation', 'Nieprawidłowy zakres dat.');

                return;
            }

            $isDriver = ($this->selectedPosition === 'driver');
            for ($d = $rangeStart->copy(); $d->lte($rangeEnd); $d->addDay()) {
                if ($d->lt($this->arrivalDate->copy()->startOfDay())) {
                    $this->addError('confirmation', 'Zakres nie może zaczynać się przed dniem przyjazdu ('.$this->arrivalDate->format('d.m.Y').').');

                    return;
                }

                $m = $this->computeVehicleDaySeatMetrics($vehicle, $d, $selectedEmployeeId);
                if ($isDriver) {
                    if ($m['available_driver_slots'] < 1) {
                        $this->addError('confirmation', 'Brak wolnego miejsca dla kierowcy w dniu '.$d->format('d.m.Y').'.');

                        return;
                    }
                } elseif ($m['available_capacity'] < 1) {
                    $this->addError('confirmation', 'Pojazd jest pełny w dniu '.$d->format('d.m.Y').' — brak wolnego miejsca dla pasażera.');

                    return;
                }
            }

            $this->dispatch('vehicle-assigned', [
                'employee_id' => $selectedEmployeeId,
                'vehicle_id' => $vehicleId,
                'position' => $this->selectedPosition,
                'start_date' => $this->selectedStartDate,
                'end_date' => $endDate,
            ]);

            $this->closeVehicleModal();
        } catch (\Throwable $e) {
            $this->addError('confirmation', 'Błąd: '.$e->getMessage());
        }
    }

    public function removeVehicleAssignment($employeeId)
    {
        $this->dispatch('vehicle-assignment-removed', [
            'employee_id' => $employeeId,
        ]);
    }

    protected function getVehicleAvailabilityForMonth(Vehicle $vehicle, Carbon $startDate, Carbon $maxDate, int $employeeId): array
    {
        $monthStart = $startDate->copy();
        $daysToShow = $startDate->diffInDays($maxDate) + 1;
        $availability = [];

        // Get employee's project assignment dates for warning (NOT for blocking)
        $employeeAssignments = $this->getEmployeeProjectAssignments($employeeId);
        $projectDates = [];
        foreach ($employeeAssignments as $assignment) {
            foreach ($assignment['dates'] as $date) {
                $projectDates[] = $date;
            }
        }

        $hasNoProjects = empty($projectDates);

        for ($day = 0; $day < min($daysToShow, 30); $day++) {
            $date = $monthStart->copy()->addDays($day);
            $dateKey = $date->format('Y-m-d');

            $dayAvailability = [
                'date' => $dateKey,
                'available' => true,
                'reason' => null,
                'reason_text' => null,
                'can_assign' => true,
                'available_capacity' => 0,
                'available_driver_slots' => 0,
                'available_passenger_slots' => 0,
                'warning' => false,
                'has_projects' => ! $hasNoProjects,
            ];

            // Hard-block dates before arrival
            if ($date->lt($this->arrivalDate->copy()->startOfDay())) {
                $dayAvailability['available'] = false;
                $dayAvailability['can_assign'] = false;
                $dayAvailability['reason'] = 'before_arrival';
                $dayAvailability['reason_text'] = 'Niedostępne (przed przyjazdem)';
                $availability[$dateKey] = $dayAvailability;

                continue;
            }

            $metrics = $this->computeVehicleDaySeatMetrics($vehicle, $date, $employeeId);
            $availableCapacity = $metrics['available_capacity'];
            $availableDriverSlots = $metrics['available_driver_slots'];
            $availablePassengerSlots = $metrics['available_passenger_slots'];

            $dayAvailability['available_capacity'] = $availableCapacity;
            $dayAvailability['available_driver_slots'] = $availableDriverSlots;
            $dayAvailability['available_passenger_slots'] = $availablePassengerSlots;

            // Block if no capacity
            if ($availableCapacity <= 0) {
                $dayAvailability['reason'] = 'no_capacity';
                $cap = (int) ($vehicle->capacity ?? 0);
                $dayAvailability['reason_text'] = 'Brak wolnych miejsc ('.$cap.' miejsc zajętych)';
                $dayAvailability['can_assign'] = false;
                $dayAvailability['available'] = false;
            } else {
                $dayAvailability['available'] = true;
                $dayAvailability['can_assign'] = true;
            }

            // Check if date is outside project assignment range - WARNING ONLY (not blocking)
            if ($hasNoProjects) {
                $dayAvailability['warning'] = true;
            } elseif (! in_array($dateKey, $projectDates)) {
                $dayAvailability['warning'] = true;
            }

            $availability[$dateKey] = $dayAvailability;
        }

        return $availability;
    }

    public function saveDeparture()
    {
        // Validate that all employees have vehicle assignments
        $unassignedEmployees = $this->getUnassignedEmployeesProperty();
        if (! empty($unassignedEmployees)) {
            $names = array_column($unassignedEmployees, 'full_name');
            $this->dispatch('error', message: 'Następujący pracownicy nie mają przypisanego pojazdu: '.implode(', ', $names));

            return;
        }

        // Wysyła event do rodzica
        $this->dispatch('save-departure');
    }

    public function render()
    {
        return view('livewire.steps.step3-vehicle-assignments');
    }
}
