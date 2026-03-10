<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Models\ProjectAssignment;
use App\Enums\VehiclePosition;
use App\Services\DeparturePlannerService;
use Livewire\Component;
use Carbon\Carbon;

class DepartureVehiclePlanner extends Component
{
    // Step 1 data from session
    public $departureDate;
    public $endDate;
    public $arrivalDate;
    public $assignments = [];
    public $assignmentRanges = [];
    
    // Assigned employees with their project assignments
    public $assignedEmployees = [];
    
    // Available vehicles
    public $vehicles = [];
    
    // Vehicle assignments state
    public $vehicleAssignments = []; // [employee_id => ['vehicle_id' => ..., 'position' => ..., 'start_date' => ..., 'end_date' => ...]]
    
    // Selected vehicle from step 1
    public $selectedVehicleId = null;
    
    // Cache for Projects, Roles, and Vehicles to avoid N+1 queries
    protected $projectsCache = [];
    protected $rolesCache = [];
    protected $vehiclesCache = [];
    
    // Modal state for vehicle assignment
    public $showVehicleModal = false;
    public $selectedEmployee = null;
    public $selectedVehicle = null;
    public $selectedPosition = 'passenger'; // 'driver' or 'passenger'
    public $vehicleAvailability = [];
    public $selectedStartDate = null;
    public $selectedEndDate = null;
    public $calendarMonthStart = null; // Start date for calendar month view
    
    protected $departurePlannerService;

    public function boot(DeparturePlannerService $departurePlannerService)
    {
        $this->departurePlannerService = $departurePlannerService;
    }

    public function mount()
    {
        $departureData = session('departure_v2');
        
        if (!$departureData || !isset($departureData['step1'])) {
            $this->redirect(route('departures.create-v2'), navigate: true);
            return;
        }

        $step1 = $departureData['step1'];
        $step2 = $departureData['step2'] ?? [];
        $step3 = $departureData['step3'] ?? [];
        
        $this->departureDate = $step1['departure_date'];
        $this->endDate = $step1['end_date'];
        $this->arrivalDate = Carbon::parse($this->endDate);
        $this->assignments = $step1['assignments'] ?? [];
        $this->assignmentRanges = $step1['assignment_ranges'] ?? [];
        $this->selectedVehicleId = $step1['vehicle_id'] ?? null; // Vehicle selected in step 1
        
        // Load accommodation assignments from step 2 (for display)
        // Load vehicle assignments from step 3 if returning
        $this->vehicleAssignments = $step3['vehicle_assignments'] ?? [];

        $this->loadAssignedEmployees();
        $this->loadVehicles();
        $this->loadCaches();
    }
    
    /**
     * Load Projects, Roles, and Vehicles into cache to avoid N+1 queries
     */
    protected function loadCaches()
    {
        // Collect project and role IDs from assignments
        $projectIds = [];
        $roleIds = [];
        
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
            $projects = \App\Models\Project::whereIn('id', $projectIds)->get();
            foreach ($projects as $project) {
                $this->projectsCache[$project->id] = $project;
            }
        }
        
        if (!empty($roleIds)) {
            $roles = \App\Models\Role::whereIn('id', $roleIds)->get();
            foreach ($roles as $role) {
                $this->rolesCache[$role->id] = $role;
            }
        }
        
        // Load all vehicles into cache as models
        $vehicleIds = array_column($this->vehicles, 'id');
        if (!empty($vehicleIds)) {
            $vehicles = Vehicle::whereIn('id', $vehicleIds)->get();
            foreach ($vehicles as $vehicle) {
                $this->vehiclesCache[$vehicle->id] = $vehicle;
            }
        }
    }
    
    /**
     * Get cached project or load it
     */
    protected function getProject($projectId)
    {
        if (isset($this->projectsCache[$projectId])) {
            return $this->projectsCache[$projectId];
        }
        
        $project = \App\Models\Project::find($projectId);
        if ($project) {
            $this->projectsCache[$projectId] = $project;
        }
        return $project;
    }
    
    /**
     * Get cached role or load it
     */
    protected function getRole($roleId)
    {
        if (isset($this->rolesCache[$roleId])) {
            return $this->rolesCache[$roleId];
        }
        
        $role = \App\Models\Role::find($roleId);
        if ($role) {
            $this->rolesCache[$roleId] = $role;
        }
        return $role;
    }
    
    /**
     * Get cached vehicle or load it
     */
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
                        if (!in_array($employeeId, $employeeIds)) {
                            $employeeIds[] = $employeeId;
                        }
                    }
                }
            }
        }
        
        // From range-based assignments
        foreach ($this->assignmentRanges as $range) {
            if (!in_array($range['employee_id'], $employeeIds)) {
                $employeeIds[] = $range['employee_id'];
            }
        }

        // Load employees with their details and project assignments
        $employees = Employee::whereIn('id', $employeeIds)
            ->with('roles')
            ->get();

        $this->assignedEmployees = $employees->map(function ($employee) {
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
    }

    public function getUnassignedEmployeesProperty()
    {
        // Filter out employees who already have vehicle assignments
        return array_filter($this->assignedEmployees, function ($employee) {
            return !isset($this->vehicleAssignments[$employee['id']]);
        });
    }

    /**
     * Get unique projects for employees assigned to a specific vehicle
     * Includes both form assignments and existing database assignments
     */
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
                if ($employee && !empty($employee['project_assignments'])) {
                    foreach ($employee['project_assignments'] as $projectAssignment) {
                        $projectId = (int) $projectAssignment['project_id'];
                        if (!in_array($projectId, $projectIds)) {
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
        $arrivalDate = $this->arrivalDate ?? Carbon::parse($this->endDate);
        
        // Get all employees assigned to this vehicle in database (active at arrival date)
        $existingAssignments = VehicleAssignment::where('vehicle_id', $vehicleId)
            ->where('start_date', '<=', $arrivalDate)
            ->where(function($query) use ($arrivalDate) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $arrivalDate);
            })
            ->with(['employee.assignments' => function($query) use ($arrivalDate) {
                $query->where('start_date', '<=', $arrivalDate)
                      ->where(function($q) use ($arrivalDate) {
                          $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', $arrivalDate);
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
                        if (!in_array($projectId, $projectIds)) {
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
     * Get occupied capacity for vehicle at arrival date
     * Includes both form assignments and existing database assignments
     */
    public function getVehicleOccupancy($vehicleId): array
    {
        $vehicleId = (int) $vehicleId;
        $arrivalDate = $this->arrivalDate ?? Carbon::parse($this->endDate);
        $vehicle = $this->getVehicle($vehicleId);
        $capacity = $vehicle?->capacity ?? 0;
        
        // Count from form assignments
        $employeeIds = [];
        foreach ($this->vehicleAssignments as $employeeId => $assignment) {
            $assignmentVehicleId = isset($assignment['vehicle_id']) ? (int) $assignment['vehicle_id'] : null;
            if ($assignmentVehicleId === $vehicleId) {
                $assignmentStart = Carbon::parse($assignment['start_date']);
                $assignmentEnd = Carbon::parse($assignment['end_date']);
                if ($arrivalDate->gte($assignmentStart) && $arrivalDate->lte($assignmentEnd)) {
                    $employeeIds[] = (int) $employeeId;
                }
            }
        }
        
        // Count from existing database assignments
        $dbAssignments = VehicleAssignment::where('vehicle_id', $vehicleId)
            ->where('start_date', '<=', $arrivalDate)
            ->where(function($query) use ($arrivalDate) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $arrivalDate);
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
                            if (!isset($assignments[$key])) {
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
                    if (!isset($assignments[$key])) {
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
            if (!empty($dates)) {
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
                            $ranges[] = $start->format('d.m.Y') . ' - ' . $end->format('d.m.Y');
                        }
                        $start = $current;
                        $end = $current;
                    }
                }
                
                if ($start->eq($end)) {
                    $ranges[] = $start->format('d.m.Y');
                } else {
                    $ranges[] = $start->format('d.m.Y') . ' - ' . $end->format('d.m.Y');
                }
            }
            
            $assignments[$key]['date_ranges'] = $ranges;
        }
        
        return array_values($assignments);
    }

    public function loadVehicles()
    {
        $this->vehicles = Vehicle::orderBy('registration_number')->get()->toArray();
    }

    public function openVehicleModal($employeeId, $vehicleId)
    {
        $this->selectedEmployee = collect($this->assignedEmployees)->firstWhere('id', $employeeId);
        $this->selectedVehicle = $this->getVehicle($vehicleId);
        
        if (!$this->selectedEmployee || !$this->selectedVehicle) {
            return;
        }

        // Initialize calendar month start to arrival date
        $this->calendarMonthStart = $this->arrivalDate->copy()->startOfMonth();
        
        // Load calendar for current month
        $this->loadVehicleAvailabilityForMonth();
        
        $this->selectedStartDate = null;
        $this->selectedEndDate = null;
        $this->selectedPosition = 'passenger'; // Default to passenger
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
        if (!$this->selectedEmployee || !$this->selectedVehicle || !$this->calendarMonthStart) {
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
        if (!$this->selectedEmployee || !$this->selectedVehicle) {
            return;
        }

        $dateKey = $date;
        if (!isset($this->vehicleAvailability[$dateKey]) || !$this->vehicleAvailability[$dateKey]['can_assign']) {
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

    public function confirmVehicleAssignment()
    {
        if (!$this->selectedEmployee || !$this->selectedVehicle || !$this->selectedStartDate) {
            return;
        }

        $endDate = $this->selectedEndDate ?: $this->selectedStartDate;
        
        // Store assignment
        $this->vehicleAssignments[$this->selectedEmployee['id']] = [
            'vehicle_id' => $this->selectedVehicle->id,
            'position' => $this->selectedPosition,
            'start_date' => $this->selectedStartDate,
            'end_date' => $endDate,
        ];

        $this->closeVehicleModal();
        $this->dispatch('assignment-success', message: 'Przypisanie pojazdu zostało utworzone');
    }

    public function removeVehicleAssignment($employeeId)
    {
        unset($this->vehicleAssignments[$employeeId]);
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
                'can_assign' => true, // Default to true - only block if no capacity
                'available_capacity' => 0,
                'available_driver_slots' => 0,
                'available_passenger_slots' => 0,
                'warning' => false, // Warning if date is outside project range
                'has_projects' => !$hasNoProjects, // Whether employee has project assignments
            ];

            // Check available capacity from database
            $vehicleCapacity = $vehicle->capacity ?? 0;
            
            // Get existing assignments for this vehicle on this date
            $existingAssignments = $vehicle->assignments()
                ->where('start_date', '<=', $date)
                ->where(function ($q) use ($date) {
                    $q->whereNull('end_date')
                      ->orWhere('end_date', '>=', $date);
                })
                ->get();
            
            // Count form assignments for this vehicle on this date (excluding current employee if editing)
            $formAssignmentsCount = 0;
            $formDriverCount = 0;
            $formPassengerCount = 0;
            
            foreach ($this->vehicleAssignments as $empId => $assignment) {
                if ($assignment['vehicle_id'] == $vehicle->id) {
                    // If this is the current employee being edited, don't count it (allows editing)
                    if ($empId == $employeeId) {
                        continue;
                    }
                    
                    $assignmentStart = Carbon::parse($assignment['start_date']);
                    $assignmentEnd = Carbon::parse($assignment['end_date']);
                    if ($date->gte($assignmentStart) && $date->lte($assignmentEnd)) {
                        $formAssignmentsCount++;
                        if ($assignment['position'] === 'driver') {
                            $formDriverCount++;
                        } else {
                            $formPassengerCount++;
                        }
                    }
                }
            }
            
            // Count existing assignments by position
            $existingDriverCount = $existingAssignments->where('position', VehiclePosition::DRIVER)->count();
            $existingPassengerCount = $existingAssignments->where('position', VehiclePosition::PASSENGER)->count();
            
            // Calculate available slots
            $totalDriverCount = $existingDriverCount + $formDriverCount;
            $totalPassengerCount = $existingPassengerCount + $formPassengerCount;
            $totalOccupied = $existingAssignments->count() + $formAssignmentsCount;
            
            $availableDriverSlots = ($totalDriverCount < 1) ? 1 : 0; // Only one driver allowed
            $availablePassengerSlots = max(0, $vehicleCapacity - 1 - $totalPassengerCount); // Capacity minus driver
            $availableCapacity = max(0, $vehicleCapacity - $totalOccupied);
            
            $dayAvailability['available_capacity'] = $availableCapacity;
            $dayAvailability['available_driver_slots'] = $availableDriverSlots;
            $dayAvailability['available_passenger_slots'] = $availablePassengerSlots;

            // Block if no capacity (for passengers) or no driver slot (for drivers)
            // We'll check position when assigning, but show general availability
            if ($availableCapacity <= 0) {
                $dayAvailability['reason'] = 'no_capacity';
                $dayAvailability['reason_text'] = 'Brak wolnych miejsc (' . $vehicleCapacity . ' miejsc zajętych)';
                $dayAvailability['can_assign'] = false;
                $dayAvailability['available'] = false;
            } else {
                $dayAvailability['available'] = true;
                $dayAvailability['can_assign'] = true;
            }

            // Check if date is outside project assignment range - WARNING ONLY (not blocking)
            if ($hasNoProjects) {
                // No projects at all - show warning for all days
                $dayAvailability['warning'] = true;
            } elseif (!in_array($dateKey, $projectDates)) {
                // Has projects but this date is outside range
                $dayAvailability['warning'] = true;
            }

            // Always save the day availability
            $availability[$dateKey] = $dayAvailability;
        }
        
        return $availability;
    }

    public function saveDeparture()
    {
        // Validate that all employees have vehicle assignments
        $unassignedEmployees = $this->getUnassignedEmployeesProperty();
        if (!empty($unassignedEmployees)) {
            $names = array_column($unassignedEmployees, 'full_name');
            $this->dispatch('validation-error', message: 'Następujący pracownicy nie mają przypisanego pojazdu: ' . implode(', ', $names));
            return;
        }
        
        // Save all data to session
        $departureData = session('departure_v2', []);
        $departureData['step3'] = [
            'vehicle_assignments' => $this->vehicleAssignments,
        ];
        session(['departure_v2' => $departureData]);
        
        // Redirect to store method (route accepts both GET and POST)
        return redirect()->route('departures.store-v2');
    }

    public function render()
    {
        return view('livewire.departure-vehicle-planner');
    }
}
