<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Role;
use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\ProjectAssignment;
use App\Services\DeparturePlannerService;
use Livewire\Component;
use Carbon\Carbon;

class DepartureAccommodationPlanner extends Component
{
    // Step 1 data from session
    public $departureDate;
    public $endDate;
    public $arrivalDate;
    public $assignments = [];
    public $assignmentRanges = [];
    
    // Assigned employees with their project assignments
    public $assignedEmployees = [];
    
    // Available accommodations
    public $accommodations = [];
    
    // Accommodation assignments state
    public $accommodationAssignments = []; // [employee_id => ['accommodation_id' => ..., 'start_date' => ..., 'end_date' => ...]]
    
    // Cache for Projects, Roles, and Accommodations to avoid N+1 queries
    protected $projectsCache = [];
    protected $rolesCache = [];
    protected $accommodationsCache = [];
    
    // Modal state for accommodation assignment
    public $showAccommodationModal = false;
    public $selectedEmployee = null;
    public $selectedAccommodation = null;
    public $accommodationAvailability = [];
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
        
        $this->departureDate = $step1['departure_date'];
        $this->endDate = $step1['end_date'];
        $this->arrivalDate = Carbon::parse($this->endDate);
        $this->assignments = $step1['assignments'] ?? [];
        $this->assignmentRanges = $step1['assignment_ranges'] ?? [];
        
        // Load accommodation assignments from step 2 if returning
        $this->accommodationAssignments = $step2['accommodation_assignments'] ?? [];

        $this->loadAssignedEmployees();
        $this->loadAccommodations();
        $this->loadCaches();
    }
    
    /**
     * Load Projects, Roles, and Accommodations into cache to avoid N+1 queries
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
        
        // Load all accommodations into cache as models
        $accommodationIds = array_column($this->accommodations, 'id');
        if (!empty($accommodationIds)) {
            $accommodations = Accommodation::whereIn('id', $accommodationIds)->get();
            foreach ($accommodations as $accommodation) {
                $this->accommodationsCache[$accommodation->id] = $accommodation;
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
        
        $project = Project::find($projectId);
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
        
        $role = Role::find($roleId);
        if ($role) {
            $this->rolesCache[$roleId] = $role;
        }
        return $role;
    }
    
    /**
     * Get cached accommodation or load it
     */
    protected function getAccommodation($accommodationId)
    {
        if (isset($this->accommodationsCache[$accommodationId])) {
            return $this->accommodationsCache[$accommodationId];
        }
        
        $accommodation = Accommodation::find($accommodationId);
        if ($accommodation) {
            $this->accommodationsCache[$accommodationId] = $accommodation;
        }
        return $accommodation;
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
        // Filter out employees who already have accommodation assignments
        return array_filter($this->assignedEmployees, function ($employee) {
            return !isset($this->accommodationAssignments[$employee['id']]);
        });
    }

    /**
     * Get occupied capacity for accommodation at arrival date
     * Includes both form assignments and existing database assignments
     */
    public function getAccommodationOccupancy($accommodationId): array
    {
        $accommodationId = (int) $accommodationId;
        $arrivalDate = $this->arrivalDate ?? Carbon::parse($this->endDate);
        $accommodation = $this->getAccommodation($accommodationId);
        $capacity = $accommodation?->capacity ?? 0;
        
        // Count from form assignments
        $formOccupied = 0;
        foreach ($this->accommodationAssignments as $employeeId => $assignment) {
            $assignmentAccommodationId = isset($assignment['accommodation_id']) ? (int) $assignment['accommodation_id'] : null;
            if ($assignmentAccommodationId === $accommodationId) {
                $assignmentStart = Carbon::parse($assignment['start_date']);
                $assignmentEnd = Carbon::parse($assignment['end_date']);
                if ($arrivalDate->gte($assignmentStart) && $arrivalDate->lte($assignmentEnd)) {
                    $formOccupied++;
                }
            }
        }
        
        // Count from existing database assignments
        $dbOccupied = AccommodationAssignment::where('accommodation_id', $accommodationId)
            ->where('start_date', '<=', $arrivalDate)
            ->where(function($query) use ($arrivalDate) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $arrivalDate);
            })
            ->count();
        
        // Total occupied (form + database, but avoid double counting)
        // If employee is in both form and DB, count only once
        $totalOccupied = max($formOccupied, $dbOccupied);
        
        // If form has assignments, use form count (it's more current)
        // Otherwise use DB count
        if ($formOccupied > 0) {
            $totalOccupied = $formOccupied + $dbOccupied;
        } else {
            $totalOccupied = $dbOccupied;
        }
        
        // Actually, let's be more precise - count unique employees
        $employeeIds = [];
        
        // From form
        foreach ($this->accommodationAssignments as $employeeId => $assignment) {
            $assignmentAccommodationId = isset($assignment['accommodation_id']) ? (int) $assignment['accommodation_id'] : null;
            if ($assignmentAccommodationId === $accommodationId) {
                $assignmentStart = Carbon::parse($assignment['start_date']);
                $assignmentEnd = Carbon::parse($assignment['end_date']);
                if ($arrivalDate->gte($assignmentStart) && $arrivalDate->lte($assignmentEnd)) {
                    $employeeIds[] = (int) $employeeId;
                }
            }
        }
        
        // From database
        $dbAssignments = AccommodationAssignment::where('accommodation_id', $accommodationId)
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

    /**
     * Get unique projects for employees assigned to a specific accommodation
     * Includes both form assignments and existing database assignments
     */
    public function getAccommodationProjects($accommodationId): array
    {
        $projectIds = [];
        $projects = [];
        $accommodationId = (int) $accommodationId;
        
        // 1. Get projects from form assignments (step 1)
        foreach ($this->accommodationAssignments as $employeeId => $assignment) {
            $assignmentAccommodationId = isset($assignment['accommodation_id']) ? (int) $assignment['accommodation_id'] : null;
            
            if ($assignmentAccommodationId === $accommodationId) {
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
        
        // Get all employees assigned to this accommodation in database (active at arrival date)
        $existingAssignments = AccommodationAssignment::where('accommodation_id', $accommodationId)
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
                      ->where('is_cancelled', false)
                      ->with('project');
            }])
            ->get();
        
        foreach ($existingAssignments as $accommodationAssignment) {
            $employee = $accommodationAssignment->employee;
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

    public function loadAccommodations()
    {
        $this->accommodations = Accommodation::orderBy('name')->get()->toArray();
    }

    public function openAccommodationModal($employeeId, $accommodationId)
    {
        $this->selectedEmployee = collect($this->assignedEmployees)->firstWhere('id', $employeeId);
        $this->selectedAccommodation = $this->getAccommodation($accommodationId);
        
        if (!$this->selectedEmployee || !$this->selectedAccommodation) {
            return;
        }

        // Initialize calendar month start to arrival date
        $this->calendarMonthStart = $this->arrivalDate->copy()->startOfMonth();
        
        // Load calendar for current month
        $this->loadAccommodationAvailabilityForMonth();
        
        $this->selectedStartDate = null;
        $this->selectedEndDate = null;
        $this->showAccommodationModal = true;
    }

    public function closeAccommodationModal()
    {
        $this->showAccommodationModal = false;
        $this->selectedEmployee = null;
        $this->selectedAccommodation = null;
        $this->accommodationAvailability = [];
        $this->selectedStartDate = null;
        $this->selectedEndDate = null;
        $this->calendarMonthStart = null;
    }
    
    protected function loadAccommodationAvailabilityForMonth()
    {
        if (!$this->selectedEmployee || !$this->selectedAccommodation || !$this->calendarMonthStart) {
            return;
        }
        
        $monthStart = $this->calendarMonthStart->copy();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $employeeId = $this->selectedEmployee['id'];
        
        // Load accommodation availability for the month
        $this->accommodationAvailability = $this->getAccommodationAvailabilityForMonth(
            $this->selectedAccommodation,
            $monthStart,
            $monthEnd,
            $employeeId
        );
    }
    
    public function previousMonth()
    {
        if ($this->calendarMonthStart) {
            $this->calendarMonthStart = $this->calendarMonthStart->copy()->subMonth()->startOfMonth();
            $this->loadAccommodationAvailabilityForMonth();
        }
    }
    
    public function nextMonth()
    {
        if ($this->calendarMonthStart) {
            $this->calendarMonthStart = $this->calendarMonthStart->copy()->addMonth()->startOfMonth();
            $this->loadAccommodationAvailabilityForMonth();
        }
    }

    public function selectDate($date)
    {
        if (!$this->selectedEmployee || !$this->selectedAccommodation) {
            return;
        }

        $dateKey = $date;
        if (!isset($this->accommodationAvailability[$dateKey]) || !$this->accommodationAvailability[$dateKey]['can_assign']) {
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

    public function confirmAccommodationAssignment()
    {
        if (!$this->selectedEmployee || !$this->selectedAccommodation || !$this->selectedStartDate) {
            return;
        }

        $endDate = $this->selectedEndDate ?: $this->selectedStartDate;
        
        // Store assignment
        $this->accommodationAssignments[$this->selectedEmployee['id']] = [
            'accommodation_id' => $this->selectedAccommodation->id,
            'start_date' => $this->selectedStartDate,
            'end_date' => $endDate,
        ];

        $this->closeAccommodationModal();
        $this->dispatch('assignment-success', message: 'Przypisanie zakwaterowania zostało utworzone');
    }

    public function removeAccommodationAssignment($employeeId)
    {
        unset($this->accommodationAssignments[$employeeId]);
    }

    protected function getAccommodationAvailabilityForMonth(Accommodation $accommodation, Carbon $startDate, Carbon $maxDate, int $employeeId): array
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
                'can_assign' => true, // Default to true - only block if no capacity or lease ended
                'available_capacity' => 0,
                'warning' => false, // Warning if date is outside project range
                'has_projects' => !$hasNoProjects, // Whether employee has project assignments
            ];

            // 1. Check if accommodation lease ends before this date - BLOCK
            if ($accommodation->type === 'wynajmowany' && $accommodation->lease_end_date) {
                if ($date->gt($accommodation->lease_end_date)) {
                    $dayAvailability['reason'] = 'lease_ended';
                    $dayAvailability['reason_text'] = 'Koniec wynajmu mieszkania';
                    $dayAvailability['can_assign'] = false;
                    $dayAvailability['available'] = false;
                    $availability[$dateKey] = $dayAvailability;
                    continue;
                }
            }

            // 2. Check available capacity from database
            $availableCapacity = $accommodation->getAvailableCapacity($date, $date);
            
            // Count form assignments for this accommodation on this date (excluding current employee if editing)
            $formAssignmentsCount = 0;
            foreach ($this->accommodationAssignments as $empId => $assignment) {
                if ($assignment['accommodation_id'] == $accommodation->id) {
                    // If this is the current employee being edited, don't count it (allows editing)
                    if ($empId == $employeeId) {
                        continue;
                    }
                    
                    $assignmentStart = Carbon::parse($assignment['start_date']);
                    $assignmentEnd = Carbon::parse($assignment['end_date']);
                    if ($date->gte($assignmentStart) && $date->lte($assignmentEnd)) {
                        $formAssignmentsCount++;
                    }
                }
            }
            
            // Reduce available capacity by form assignments
            $availableCapacity = max(0, $availableCapacity - $formAssignmentsCount);
            $dayAvailability['available_capacity'] = $availableCapacity;

            // 3. Block ONLY if no capacity (dom przepełniony)
            if ($availableCapacity <= 0) {
                $dayAvailability['reason'] = 'no_capacity';
                $dayAvailability['reason_text'] = 'Brak wolnych miejsc (' . $accommodation->capacity . ' miejsc zajętych)';
                $dayAvailability['can_assign'] = false;
                $dayAvailability['available'] = false;
            } else {
                $dayAvailability['available'] = true;
                $dayAvailability['can_assign'] = true;
            }

            // 4. Check if date is outside project assignment range - WARNING ONLY (not blocking)
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

    public function goToStep3()
    {
        // Validate accommodation assignments
        $unassignedEmployees = $this->getUnassignedEmployeesProperty();
        $issues = [];
        
        // Check if anyone is missing accommodation
        if (!empty($unassignedEmployees)) {
            $names = array_column($unassignedEmployees, 'full_name');
            $issues[] = 'Następujący pracownicy nie mają przypisanego domu: ' . implode(', ', $names);
        }
        
        // Check if any accommodation starts after arrival date
        $arrivalDateKey = $this->arrivalDate->format('Y-m-d');
        foreach ($this->accommodationAssignments as $employeeId => $assignment) {
            $startDate = Carbon::parse($assignment['start_date']);
            if ($startDate->gt($this->arrivalDate)) {
                $employee = collect($this->assignedEmployees)->firstWhere('id', $employeeId);
                $employeeName = $employee ? $employee['full_name'] : "ID: {$employeeId}";
                $issues[] = "{$employeeName} ma przypisany dom od {$startDate->format('d.m.Y')}, a przyjazd jest {$this->arrivalDate->format('d.m.Y')}";
            }
        }
        
        // If there are issues, dispatch event with issues for JavaScript confirmation
        if (!empty($issues)) {
            $this->dispatch('step3-validation-issues', issues: $issues);
            return;
        }
        
        // Save step 2 data to session
        $departureData = session('departure_v2', []);
        $departureData['step2'] = [
            'accommodation_assignments' => $this->accommodationAssignments,
        ];
        session(['departure_v2' => $departureData]);

        // Redirect to step 3 (vehicles)
        return redirect()->route('departures.create-v2-step3');
    }
    
    public function confirmGoToStep3()
    {
        // User confirmed, proceed to step 3
        $departureData = session('departure_v2', []);
        $departureData['step2'] = [
            'accommodation_assignments' => $this->accommodationAssignments,
        ];
        session(['departure_v2' => $departureData]);

        return redirect()->route('departures.create-v2-step3');
    }

    public function render()
    {
        return view('livewire.departure-accommodation-planner');
    }
}
