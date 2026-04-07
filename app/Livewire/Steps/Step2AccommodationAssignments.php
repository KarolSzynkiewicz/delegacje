<?php

namespace App\Livewire\Steps;

use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Role;
use App\Services\DeparturePlannerService;
use Carbon\Carbon;
use Livewire\Component;

class Step2AccommodationAssignments extends Component
{
    // Dane otrzymane z rodzica (read-only)
    public $departureDate;

    public $endDate;

    public $assignments = []; // Read-only z rodzica

    public $assignmentRanges = []; // Read-only z rodzica

    public $accommodationAssignments = []; // Read-only z rodzica

    public bool $forTransfer = false;

    public array $allowedEmployeeIds = [];

    // Własne dane (ciężkie obliczenia)
    public $assignedEmployees = [];

    public $accommodations = [];

    public $accommodationSearch = '';

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

    public function mount(
        $departureDate,
        $endDate,
        $assignments = [],
        $assignmentRanges = [],
        $accommodationAssignments = [],
        $forTransfer = false,
        $allowedEmployeeIds = []
    ) {
        $this->departureDate = $departureDate;
        $this->endDate = $endDate;
        $this->arrivalDate = Carbon::parse($endDate);
        $this->assignments = $assignments;
        $this->assignmentRanges = $assignmentRanges;
        $this->accommodationAssignments = $accommodationAssignments;
        $this->forTransfer = (bool) $forTransfer;
        $this->allowedEmployeeIds = is_array($allowedEmployeeIds)
            ? array_values(array_map('intval', $allowedEmployeeIds))
            : [];

        // Wykonuje ciężkie operacje
        $this->loadAssignedEmployees();
        $this->loadAccommodations();
        $this->loadCaches();
    }

    public function updatedAccommodationSearch()
    {
        // Keep it reactive and predictable
        $this->dispatch('$refresh');
    }

    public function getFilteredAccommodationsProperty(): array
    {
        $items = $this->accommodations ?? [];
        if (empty($items) || ! is_array($items)) {
            return [];
        }

        $search = trim((string) ($this->accommodationSearch ?? ''));
        if ($search === '') {
            return $items;
        }

        $needle = mb_strtolower($search);

        return collect($items)
            ->filter(function ($acc) use ($needle) {
                $name = (string) ($acc['name'] ?? '');
                $address = (string) ($acc['address'] ?? '');
                $city = (string) ($acc['city'] ?? '');
                $country = (string) ($acc['country'] ?? '');

                $haystack = mb_strtolower(trim($name.' '.$address.' '.$city.' '.$country));

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

        // Load all accommodations into cache as models
        $accommodationIds = array_column($this->accommodations, 'id');
        if (! empty($accommodationIds)) {
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
                                    'date_ranges' => [],
                                ];
                            }
                            $assignments[$key]['dates'][] = $dayDate->format('Y-m-d');
                            $assignments[$key]['date_ranges'][] = $dayDate->format('d.m.Y');
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
                            'date_ranges' => [],
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

                    // Add date range for display
                    if ($start->eq($end)) {
                        $assignments[$key]['date_ranges'][] = $start->format('d.m.Y');
                    } else {
                        $assignments[$key]['date_ranges'][] = $start->format('d.m.Y').' - '.$end->format('d.m.Y');
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
            return ! isset($this->accommodationAssignments[$employee['id']]);
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
            ->where(function ($query) {
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

    /**
     * Get accommodation occupancy for a specific date
     */
    protected function getAccommodationOccupancyForDate($accommodationId, Carbon $date): array
    {
        $accommodationId = (int) $accommodationId;
        $accommodation = $this->getAccommodation($accommodationId);
        $capacity = $accommodation?->capacity ?? 0;

        // Count from form assignments for this specific date
        $formOccupied = 0;
        foreach ($this->accommodationAssignments as $employeeId => $assignment) {
            $assignmentAccommodationId = isset($assignment['accommodation_id']) ? (int) $assignment['accommodation_id'] : null;
            if ($assignmentAccommodationId === $accommodationId) {
                $assignmentStart = Carbon::parse($assignment['start_date']);
                $assignmentEnd = Carbon::parse($assignment['end_date']);
                if ($date->gte($assignmentStart) && $date->lte($assignmentEnd)) {
                    $formOccupied++;
                }
            }
        }

        // Count from existing database assignments for this specific date
        $dbOccupied = AccommodationAssignment::where('accommodation_id', $accommodationId)
            ->where('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            })
            ->count();

        // Total occupied (form + database)
        $totalOccupied = $formOccupied + $dbOccupied;
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
                    if (! isset($projects[$projectId])) {
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
            ->where(function ($query) {
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
                    if (! isset($projects[$projectId])) {
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
        $this->accommodations = Accommodation::with('activeLease')
            ->orderBy('name')
            ->get()
            ->map(function (Accommodation $a) {
                return [
                    'id' => $a->id,
                    'name' => $a->name,
                    'address' => $a->address,
                    'city' => $a->city,
                    'country' => $a->country instanceof \BackedEnum ? $a->country->value : ($a->country ?? null),
                    'capacity' => $a->capacity,
                    // Compatibility for UI that expects old columns
                    'type' => $a->type,
                    'lease_end_date' => $a->lease_end_date?->format('Y-m-d'),
                ];
            })
            ->toArray();
    }

    public function openAccommodationModal($employeeId, $accommodationId)
    {
        $this->selectedEmployee = collect($this->assignedEmployees)->firstWhere('id', $employeeId);
        $this->selectedAccommodation = $this->getAccommodation($accommodationId);

        if (! $this->selectedEmployee || ! $this->selectedAccommodation) {
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
        if (! $this->selectedEmployee || ! $this->selectedAccommodation || ! $this->calendarMonthStart) {
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
        if (! $this->selectedEmployee || ! $this->selectedAccommodation) {
            return;
        }

        $dateKey = $date;
        if (! isset($this->accommodationAvailability[$dateKey]) || ! $this->accommodationAvailability[$dateKey]['can_assign']) {
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

    public function confirmAccommodationAssignment()
    {
        if (! $this->selectedEmployee || ! $this->selectedAccommodation || ! $this->selectedStartDate) {
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

        // Get employee's project assignment dates
        $employeeAssignments = $this->getEmployeeProjectAssignments($employeeId);
        $projectDates = [];
        foreach ($employeeAssignments as $assignment) {
            foreach ($assignment['dates'] as $date) {
                $projectDates[] = $date;
            }
        }
        $projectDates = array_unique($projectDates);

        $hasNoProjects = empty($projectDates);

        while ($currentDate->lte($maxDate)) {
            $dateKey = $currentDate->format('Y-m-d');

            // Check if employee has project assignment for this date
            $hasProjectOnDate = in_array($dateKey, $projectDates);

            // Check capacity for this specific date
            $occupancy = $this->getAccommodationOccupancyForDate($accommodation->id, $currentDate);
            $isOverbooked = $occupancy['occupied'] >= $occupancy['capacity'];
            $availableSpots = $occupancy['available'];

            // Check lease end date
            $leaseEnded = false;
            if ($accommodation->type === 'wynajmowany' && $accommodation->lease_end_date) {
                $leaseEnd = Carbon::parse($accommodation->lease_end_date);
                $leaseEnded = $currentDate->gt($leaseEnd);
            }

            $canAssign = ! $isOverbooked && ! $leaseEnded;

            // Set warning if no projects or date is outside project range
            $warning = false;
            $warningText = '';
            if ($hasNoProjects) {
                $warning = true;
                $warningText = 'Brak przypisania do projektu';
            } elseif (! $hasProjectOnDate) {
                $warning = true;
                $warningText = 'Data poza zakresem przypisania do projektu';
            }

            // Build reason text for tooltip
            $reasonText = '';
            if ($isOverbooked) {
                $reasonText = 'Brak miejsc ('.$occupancy['capacity'].' miejsc zajętych)';
            } elseif ($leaseEnded) {
                $reasonText = 'Koniec wynajmu';
            } else {
                $reasonText = 'Wolne miejsca: '.$availableSpots.' / '.$occupancy['capacity'];
            }

            // Add warning info to reason text if applicable
            if ($warning && $canAssign) {
                $reasonText = ($reasonText ? $reasonText.'. ' : '').$warningText;
            }

            $availability[$dateKey] = [
                'date' => $dateKey,
                'available' => $canAssign,
                'can_assign' => $canAssign,
                'reason' => $isOverbooked ? 'overbooked' : ($leaseEnded ? 'lease_ended' : null),
                'reason_text' => $reasonText,
                'warning' => $warning,
                'has_projects' => ! $hasNoProjects,
                'available_capacity' => $availableSpots,
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
        if (! empty($unassignedEmployees)) {
            $names = array_column($unassignedEmployees, 'full_name');
            $issues[] = 'Następujący pracownicy nie mają przypisanego domu: '.implode(', ', $names);
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
        if (! empty($issues)) {
            $this->dispatch('step3-validation-issues', issues: $issues);

            return;
        }

        // Wysyła event do rodzica
        $this->dispatch('go-to-step', step: 3);
    }

    public function confirmGoToNextStep()
    {
        // User confirmed, proceed to step 3
        $this->dispatch('go-to-step', step: 3);
    }

    public function render()
    {
        return view('livewire.steps.step2-accommodation-assignments');
    }
}
