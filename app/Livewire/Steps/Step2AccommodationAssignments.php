<?php

namespace App\Livewire\Steps;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Role;
use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\ProjectAssignment;
use App\Services\DeparturePlannerService;
use Livewire\Component;
use Carbon\Carbon;

class Step2AccommodationAssignments extends Component
{
    // Dane otrzymane z rodzica (read-only)
    public $departureDate;
    public $endDate;
    public $assignments = []; // Read-only z rodzica
    public $assignmentRanges = []; // Read-only z rodzica
    public $accommodationAssignments = []; // Read-only z rodzica
    
    // Własne dane (ciężkie obliczenia)
    public $assignedEmployees = [];
    public $accommodations = [];
    
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
    public $calendarMonthStart = null;
    
    public $arrivalDate;
    
    protected $departurePlannerService;
    
    public function boot(DeparturePlannerService $departurePlannerService)
    {
        $this->departurePlannerService = $departurePlannerService;
    }
    
    public function mount($departureDate, $endDate, $assignments = [], $assignmentRanges = [], $accommodationAssignments = [])
    {
        $this->departureDate = $departureDate;
        $this->endDate = $endDate;
        $this->arrivalDate = Carbon::parse($endDate);
        $this->assignments = $assignments;
        $this->assignmentRanges = $assignmentRanges;
        $this->accommodationAssignments = $accommodationAssignments;
        
        // Wykonuje ciężkie operacje
        $this->loadAssignedEmployees();
        $this->loadAccommodations();
        $this->loadCaches();
    }
    
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
    
    protected function getEmployeeProjectAssignments($employeeId): array
    {
        $assignments = [];
        
        // From day-based assignments
        foreach ($this->assignments as $dayKey => $dayAssignments) {
            foreach ($dayAssignments as $projectId => $roles) {
                foreach ($roles as $roleId => $employeeIds) {
                    if (in_array($employeeId, $employeeIds)) {
                        $project = $this->getProject($projectId);
                        $role = $this->getRole($roleId);
                        
                        if ($project && $role) {
                            $dayNumber = (int) str_replace('day_', '', $dayKey);
                            $date = $this->arrivalDate->copy()->addDays($dayNumber - 1);
                            
                            $key = $projectId . '_' . $roleId;
                            if (!isset($assignments[$key])) {
                                $assignments[$key] = [
                                    'project_id' => $projectId,
                                    'project_name' => $project->name,
                                    'role_id' => $roleId,
                                    'role_name' => $role->name,
                                    'date_ranges' => [],
                                ];
                            }
                            
                            $assignments[$key]['date_ranges'][] = $date->format('d.m.Y');
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
                    $startDate = Carbon::parse($range['start_date']);
                    $endDate = Carbon::parse($range['end_date']);
                    
                    $key = $range['project_id'] . '_' . $range['role_id'];
                    if (!isset($assignments[$key])) {
                        $assignments[$key] = [
                            'project_id' => $range['project_id'],
                            'project_name' => $project->name,
                            'role_id' => $range['role_id'],
                            'role_name' => $role->name,
                            'date_ranges' => [],
                        ];
                    }
                    
                    if ($startDate->eq($endDate)) {
                        $assignments[$key]['date_ranges'][] = $startDate->format('d.m.Y');
                    } else {
                        $assignments[$key]['date_ranges'][] = $startDate->format('d.m.Y') . ' - ' . $endDate->format('d.m.Y');
                    }
                }
            }
        }
        
        return array_values($assignments);
    }
    
    public function getUnassignedEmployeesProperty()
    {
        // Filter out employees who already have accommodation assignments
        return array_filter($this->assignedEmployees, function ($employee) {
            return !isset($this->accommodationAssignments[$employee['id']]);
        });
    }
    
    public function getAccommodationOccupancy($accommodationId): array
    {
        $accommodationId = (int) $accommodationId;
        $accommodation = $this->getAccommodation($accommodationId);
        $capacity = $accommodation?->capacity ?? 0;
        
        // Count from form assignments
        $formOccupied = 0;
        foreach ($this->accommodationAssignments as $employeeId => $assignment) {
            $assignmentAccommodationId = isset($assignment['accommodation_id']) ? (int) $assignment['accommodation_id'] : null;
            if ($assignmentAccommodationId === $accommodationId) {
                $assignmentStart = Carbon::parse($assignment['start_date']);
                $assignmentEnd = Carbon::parse($assignment['end_date']);
                if ($this->arrivalDate->gte($assignmentStart) && $this->arrivalDate->lte($assignmentEnd)) {
                    $formOccupied++;
                }
            }
        }
        
        // Count from existing database assignments
        $dbOccupied = AccommodationAssignment::where('accommodation_id', $accommodationId)
            ->where('start_date', '<=', $this->arrivalDate)
            ->where(function($query) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $this->arrivalDate);
            })
            ->count();
        
        // Total occupied (form + database, but avoid double counting)
        $totalOccupied = max($formOccupied, $dbOccupied);
        
        if ($formOccupied > 0) {
            $totalOccupied = $formOccupied + $dbOccupied;
        } else {
            $totalOccupied = $dbOccupied;
        }
        
        $available = $capacity - $totalOccupied;
        
        return [
            'occupied' => $totalOccupied,
            'capacity' => $capacity,
            'available' => max(0, $available),
        ];
    }
    
    public function getAccommodationProjects($accommodationId): array
    {
        $accommodationId = (int) $accommodationId;
        $projects = [];
        
        // Get projects from form assignments
        foreach ($this->accommodationAssignments as $employeeId => $assignment) {
            $assignmentAccommodationId = isset($assignment['accommodation_id']) ? (int) $assignment['accommodation_id'] : null;
            if ($assignmentAccommodationId === $accommodationId) {
                // Get employee's project assignments
                $employeeProjects = $this->getEmployeeProjectAssignments($employeeId);
                foreach ($employeeProjects as $assignment) {
                    $projectId = $assignment['project_id'];
                    if (!isset($projects[$projectId])) {
                        $project = $this->getProject($projectId);
                        if ($project) {
                            $projects[$projectId] = [
                                'id' => $project->id,
                                'name' => $project->name,
                            ];
                        }
                    }
                }
            }
        }
        
        // Get projects from existing database assignments
        $dbAssignments = AccommodationAssignment::where('accommodation_id', $accommodationId)
            ->where('start_date', '<=', $this->arrivalDate)
            ->where(function($query) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $this->arrivalDate);
            })
            ->with('employee.projectAssignments.project')
            ->get();
        
        foreach ($dbAssignments as $assignment) {
            $employee = $assignment->employee;
            if ($employee) {
                $employeeProjects = $this->getEmployeeProjectAssignments($employee->id);
                foreach ($employeeProjects as $empAssignment) {
                    $projectId = $empAssignment['project_id'];
                    if (!isset($projects[$projectId])) {
                        $project = $this->getProject($projectId);
                        if ($project) {
                            $projects[$projectId] = [
                                'id' => $project->id,
                                'name' => $project->name,
                            ];
                        }
                    }
                }
            }
        }
        
        return array_values($projects);
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
        
        $startDate = Carbon::parse($this->selectedStartDate);
        $endDate = $this->selectedEndDate ? Carbon::parse($this->selectedEndDate) : $startDate;
        
        // Wysyła event do rodzica
        $this->dispatch('accommodation-assigned', [
            'employee_id' => $this->selectedEmployee['id'],
            'accommodation_id' => $this->selectedAccommodation->id,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);
        
        $this->closeAccommodationModal();
    }
    
    public function removeAccommodationAssignment($employeeId)
    {
        $this->dispatch('accommodation-removed', [
            'employee_id' => $employeeId,
        ]);
    }
    
    protected function getAccommodationAvailabilityForMonth(Accommodation $accommodation, Carbon $startDate, Carbon $maxDate, int $employeeId): array
    {
        $availability = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($maxDate)) {
            $dateKey = $currentDate->format('Y-m-d');
            
            // Check capacity
            $occupancy = $this->getAccommodationOccupancy($accommodation->id);
            $isOverbooked = $occupancy['occupied'] >= $occupancy['capacity'];
            
            // Check lease end date
            $leaseEnded = false;
            if ($accommodation->type === 'wynajmowany' && $accommodation->lease_end_date) {
                $leaseEnd = Carbon::parse($accommodation->lease_end_date);
                $leaseEnded = $currentDate->gt($leaseEnd);
            }
            
            $canAssign = !$isOverbooked && !$leaseEnded;
            
            $availability[$dateKey] = [
                'date' => $dateKey,
                'available' => $canAssign,
                'can_assign' => $canAssign,
                'reason' => $isOverbooked ? 'overbooked' : ($leaseEnded ? 'lease_ended' : null),
                'reason_text' => $isOverbooked ? 'Brak miejsc' : ($leaseEnded ? 'Koniec wynajmu' : null),
                'warning' => false,
            ];
            
            $currentDate->addDay();
        }
        
        return $availability;
    }
    
    public function goToNextStep()
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
        
        // Wysyła event do rodzica
        $this->dispatch('go-to-step', 3);
    }
    
    public function confirmGoToNextStep()
    {
        // User confirmed, proceed to step 3
        $this->dispatch('go-to-step', 3);
    }
    
    public function render()
    {
        return view('livewire.steps.step2-accommodation-assignments');
    }
}
