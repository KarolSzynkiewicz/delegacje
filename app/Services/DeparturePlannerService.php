<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectDemand;
use App\Models\ProjectAssignment;
use App\Models\Role;
use App\Services\AssignmentQueryService;
use App\Services\ExpiringDocumentsService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DeparturePlannerService
{
    public function __construct(
        protected AssignmentQueryService $assignmentQueryService,
        protected ExpiringDocumentsService $expiringDocumentsService
    ) {}

    /**
     * Get available employees for a specific date with full details.
     * 
     * Returns employees with:
     * - Photos
     * - Roles
     * - Rotation info for the date
     * - Expiring documents (within a month)
     * - Availability status
     * 
     * @param Carbon $date
     * @return Collection
     */
    public function getAvailableEmployeesForDate(Carbon $date): Collection
    {
        $availableEmployees = $this->assignmentQueryService->getAvailableEmployeesForDeparture($date, $date);

        return $availableEmployees->map(function (Employee $employee) use ($date) {
            // Get rotation for this date
            $rotation = $employee->getActiveRotationForDate($date);
            
            // Get expiring documents (within a month)
            $expiringDocuments = $this->expiringDocumentsService->getExpiringDocumentsForEmployee($employee, 30);
            
            // Get roles
            $roles = $employee->roles;
            
            return [
                'id' => $employee->id,
                'full_name' => $employee->full_name,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'image_url' => $employee->image_url,
                'image_path' => $employee->image_path,
                'roles' => $roles->map(fn($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                ])->toArray(),
                'rotation' => $rotation ? [
                    'id' => $rotation->id,
                    'start_date' => $rotation->start_date->format('Y-m-d'),
                    'end_date' => $rotation->end_date ? $rotation->end_date->format('Y-m-d') : null,
                ] : null,
                'expiring_documents' => $expiringDocuments->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'document_name' => $doc->document->name ?? 'Nieznany dokument',
                        'valid_to' => $doc->valid_to->format('Y-m-d'),
                        'days_until_expiry' => $doc->valid_to->diffInDays(now()),
                        'is_required' => $doc->document->is_required ?? false,
                    ];
                })->toArray(),
            ];
        })->values();
    }

    /**
     * Get project gaps for 30 days starting from arrival date, extended to end of week (Sunday) if needed.
     * 
     * Day 1 = arrival date (end of trip)
     * Days 2-30+ = next days (extended to Sunday if day 30 is not Sunday)
     * 
     * Returns structure:
     * [
     *   'day_1' => [
     *     'date' => '2024-01-15',
     *     'projects' => [
     *       'project_id' => [
     *         'id' => 1,
     *         'name' => 'Project A',
     *         'roles' => [
     *           'role_id' => [
     *             'id' => 1,
     *             'name' => 'Role X',
     *             'gaps' => 2, // number of unsatisfied positions
     *           ]
     *         ]
     *       ]
     *     ]
     *   ],
     *   ...
     * ]
     * 
     * @param Carbon $arrivalDate
     * @return array
     */
    public function getProjectGapsForWeek(Carbon $arrivalDate): array
    {
        $days = [];
        
        // Calculate end date: 30 days from arrival, but extend to Sunday if day 30 is not Sunday
        $day30Date = $arrivalDate->copy()->addDays(29); // Day 30 (0-indexed: 29 days after day 1)
        $day30DayOfWeek = $day30Date->dayOfWeek; // 0 = Sunday, 6 = Saturday
        
        // If day 30 is not Sunday (dayOfWeek != 0), extend to next Sunday
        if ($day30DayOfWeek != 0) {
            $daysToAdd = 7 - $day30DayOfWeek; // Days to add to reach Sunday
            $weekEnd = $day30Date->copy()->addDays($daysToAdd);
        } else {
            $weekEnd = $day30Date->copy();
        }
        
        $weekStart = $arrivalDate->copy();

        // OPTIMIZATION: Load all projects with all demands and assignments for the entire period in one go
        $projects = Project::where('status', 'active')
            ->with([
                'location',
                'demands' => function ($query) use ($weekStart, $weekEnd) {
                    $query->overlappingWith($weekStart, $weekEnd)
                        ->with('role');
                },
                'assignments' => function ($query) use ($weekStart, $weekEnd) {
                    $query->where('is_cancelled', false)
                        ->overlappingWith($weekStart, $weekEnd);
                }
            ])
            ->get();

        // Calculate total number of days
        $totalDays = $arrivalDate->diffInDays($weekEnd) + 1;

        // For each day (1 to totalDays)
        for ($dayNum = 1; $dayNum <= $totalDays; $dayNum++) {
            $dayDate = $arrivalDate->copy()->addDays($dayNum - 1);
            $dayKey = "day_{$dayNum}";
            
            $dayData = [
                'date' => $dayDate->format('Y-m-d'),
                'day_number' => $dayNum,
                'projects' => [],
            ];

            // For each project
            foreach ($projects as $project) {
                $projectData = [
                    'id' => $project->id,
                    'name' => $project->name,
                    'location' => $project->location ? $project->location->name : null,
                    'roles' => [],
                ];

                // OPTIMIZATION: Filter demands in memory instead of querying
                $demandsForDay = $project->demands->filter(function ($demand) use ($dayDate) {
                    return $demand->overlapsWith($dayDate, $dayDate);
                })->groupBy('role_id');

                // OPTIMIZATION: Filter assignments in memory instead of querying
                $assignmentsForDay = $project->assignments->filter(function ($assignment) use ($dayDate) {
                    return $assignment->start_date <= $dayDate && 
                           ($assignment->end_date === null || $assignment->end_date >= $dayDate);
                })->groupBy('role_id');

                // Calculate gaps for each role
                foreach ($demandsForDay as $roleId => $demands) {
                    $role = $demands->first()->role;
                    $requiredCount = $demands->sum('required_count');
                    
                    // Count unique employees assigned to this role on this day
                    $assignedEmployees = $assignmentsForDay->get($roleId, collect())
                        ->pluck('employee_id')
                        ->unique();
                    $assignedCount = $assignedEmployees->count();
                    
                    $gaps = max(0, $requiredCount - $assignedCount);
                    
                    if ($gaps > 0) {
                        $projectData['roles'][$roleId] = [
                            'id' => $role->id,
                            'name' => $role->name,
                            'gaps' => $gaps,
                            'required' => $requiredCount,
                            'assigned' => $assignedCount,
                        ];
                    }
                }

                // Only include project if it has gaps
                if (!empty($projectData['roles'])) {
                    $dayData['projects'][$project->id] = $projectData;
                }
            }

            $days[$dayKey] = $dayData;
        }

        return $days;
    }

    /**
     * Validate if employee can be assigned to a project role on a specific date.
     * 
     * @param Employee $employee
     * @param Project $project
     * @param Role $role
     * @param Carbon $date
     * @param Carbon|null $endDate Optional end date for range validation (for documents)
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateEmployeeAssignment(Employee $employee, Project $project, Role $role, Carbon $date, ?Carbon $endDate = null): array
    {
        $errors = [];

        // 1. Check if employee has the required role
        if (!$employee->hasRole($role->id)) {
            $errors[] = "Pracownik nie ma wymaganej roli: {$role->name}";
        }

        // 2. Check if employee has rotation for this date
        $rotation = $employee->getActiveRotationForDate($date);
        if (!$rotation) {
            $errors[] = "Pracownik nie ma aktywnej rotacji na dzień {$date->format('Y-m-d')}";
        }

        // 3. Check if employee has all required documents
        // If endDate is provided, check for the entire range, otherwise just for this date
        // If endDate is null, skip document check (it was already checked for the entire range)
        if ($endDate !== null) {
            $docEndDate = $endDate;
            if (!$employee->hasAllDocumentsActiveInDateRange($date, $docEndDate)) {
                if ($endDate->ne($date)) {
                    $errors[] = "Pracownik nie ma wszystkich wymaganych dokumentów w okresie od {$date->format('Y-m-d')} do {$docEndDate->format('Y-m-d')}";
                } else {
                    $errors[] = "Pracownik nie ma wszystkich wymaganych dokumentów na dzień {$date->format('Y-m-d')}";
                }
            }
        } else {
            // endDate is null - documents were already checked for entire range, skip here
        }

        // 4. Check if employee is already assigned to another project on this date
        $existingAssignment = ProjectAssignment::where('employee_id', $employee->id)
            ->where('is_cancelled', false)
            ->where(function ($query) use ($date) {
                $query->where('start_date', '<=', $date)
                    ->where(function ($q) use ($date) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', $date);
                    });
            })
            ->where('project_id', '!=', $project->id)
            ->exists();

        if ($existingAssignment) {
            $errors[] = "Pracownik jest już przypisany do innego projektu na dzień {$date->format('Y-m-d')}";
        }

        // 5. Check if employee is already assigned to this project on this date (same or different role)
        $sameProjectAssignment = ProjectAssignment::where('employee_id', $employee->id)
            ->where('project_id', $project->id)
            ->where('is_cancelled', false)
            ->where(function ($query) use ($date) {
                $query->where('start_date', '<=', $date)
                    ->where(function ($q) use ($date) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', $date);
                    });
            })
            ->exists();

        if ($sameProjectAssignment) {
            $errors[] = "Pracownik jest już przypisany do tego projektu na dzień {$date->format('Y-m-d')}";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get aggregated project gaps for 2 weeks (14 days) starting from arrival date.
     * 
     * Returns structure with min-max gaps for each role in each project:
     * [
     *   'project_id' => [
     *     'id' => 1,
     *     'name' => 'Project A',
     *     'location' => 'Warszawa',
     *     'roles' => [
     *       'role_id' => [
     *         'id' => 1,
     *         'name' => 'Malarz',
     *         'min_gaps' => 5,  // minimum gaps across 14 days
     *         'max_gaps' => 7,  // maximum gaps across 14 days
     *       ]
     *     ]
     *   ]
     * ]
     * 
     * @param Carbon $arrivalDate
     * @return array
     */
    public function getProjectGapsForTwoWeeks(Carbon $arrivalDate, array $formAssignments = [], array $formAssignmentRanges = []): array
    {
        $weekStart = $arrivalDate->copy();
        $weekEnd = $arrivalDate->copy()->addDays(13); // 14 days total (0-13 = 14 days)

        // OPTIMIZATION: Load all projects with all demands and assignments for the entire period in one go
        $projects = Project::where('status', 'active')
            ->with([
                'location',
                'demands' => function ($query) use ($weekStart, $weekEnd) {
                    $query->overlappingWith($weekStart, $weekEnd)
                        ->with('role');
                },
                'assignments' => function ($query) use ($weekStart, $weekEnd) {
                    $query->where('is_cancelled', false)
                        ->overlappingWith($weekStart, $weekEnd);
                }
            ])
            ->get();

        $result = [];

        // For each project
        foreach ($projects as $project) {
            $projectData = [
                'id' => $project->id,
                'name' => $project->name,
                'location' => $project->location ? $project->location->name : null,
                'roles' => [],
            ];

            // Track gaps for each role across all 14 days
            $roleGaps = [];

            // For each day (1 to 14)
            for ($dayNum = 1; $dayNum <= 14; $dayNum++) {
                $dayDate = $arrivalDate->copy()->addDays($dayNum - 1);

                // OPTIMIZATION: Filter demands in memory instead of querying
                $demandsForDay = $project->demands->filter(function ($demand) use ($dayDate) {
                    return $demand->overlapsWith($dayDate, $dayDate);
                })->groupBy('role_id');

                // OPTIMIZATION: Filter assignments in memory instead of querying
                $assignmentsForDay = $project->assignments->filter(function ($assignment) use ($dayDate) {
                    return $assignment->start_date <= $dayDate && 
                           ($assignment->end_date === null || $assignment->end_date >= $dayDate);
                })->groupBy('role_id');

                // Calculate gaps for each role on this day
                foreach ($demandsForDay as $roleId => $demands) {
                    $role = $demands->first()->role;
                    $requiredCount = $demands->sum('required_count');
                    
                    // Count unique employees assigned to this role on this day from database
                    $assignedEmployees = $assignmentsForDay->get($roleId, collect())
                        ->pluck('employee_id')
                        ->unique()
                        ->toArray();
                    
                    // Add employees assigned in form for this day (days 1-7)
                    if ($dayNum <= 7) {
                        $dayKey = "day_{$dayNum}";
                        if (isset($formAssignments[$dayKey][$project->id][$roleId])) {
                            foreach ($formAssignments[$dayKey][$project->id][$roleId] as $employeeId) {
                                if (!in_array($employeeId, $assignedEmployees)) {
                                    $assignedEmployees[] = $employeeId;
                                }
                            }
                        }
                    }
                    
                    // Add employees assigned in form ranges for this day (days beyond 7)
                    foreach ($formAssignmentRanges as $rangeKey => $range) {
                        if ($range['project_id'] == $project->id && $range['role_id'] == $roleId) {
                            $rangeStart = Carbon::parse($range['start_date']);
                            $rangeEnd = Carbon::parse($range['end_date']);
                            
                            if ($dayDate->gte($rangeStart) && $dayDate->lte($rangeEnd)) {
                                $employeeId = $range['employee_id'];
                                if (!in_array($employeeId, $assignedEmployees)) {
                                    $assignedEmployees[] = $employeeId;
                                }
                            }
                        }
                    }
                    
                    $assignedCount = count($assignedEmployees);
                    $gaps = max(0, $requiredCount - $assignedCount);
                    
                    if ($gaps > 0) {
                        if (!isset($roleGaps[$roleId])) {
                            $roleGaps[$roleId] = [
                                'id' => $role->id,
                                'name' => $role->name,
                                'gaps' => [],
                            ];
                        }
                        $roleGaps[$roleId]['gaps'][] = $gaps;
                    }
                }
            }

            // Calculate min-max for each role
            foreach ($roleGaps as $roleId => $roleData) {
                if (!empty($roleData['gaps'])) {
                    $projectData['roles'][$roleId] = [
                        'id' => $roleData['id'],
                        'name' => $roleData['name'],
                        'min_gaps' => min($roleData['gaps']),
                        'max_gaps' => max($roleData['gaps']),
                    ];
                }
            }

            // Only include project if it has gaps
            if (!empty($projectData['roles'])) {
                $result[$project->id] = $projectData;
            }
        }

        return $result;
    }

    /**
     * Get employee availability for a month starting from arrival date.
     * 
     * Returns array with availability status for each day:
     * [
     *   '2024-01-15' => [
     *     'date' => '2024-01-15',
     *     'available' => true/false,
     *     'reason' => 'no_demand'|'no_documents'|'no_rotation'|'already_assigned'|null,
     *     'can_assign' => true/false,
     *   ],
     *   ...
     * ]
     * 
     * @param Employee $employee
     * @param Project $project
     * @param Role $role
     * @param Carbon $arrivalDate
     * @param array $formAssignments Optional: assignments from form [day_1 => [project_id => [role_id => [employee_id => ...]]]]
     * @param array $formAssignmentRanges Optional: assignment ranges from form [employee_id_projectId_roleId => ['start_date' => ..., 'end_date' => ...]]
     * @return array
     */
    public function getEmployeeAvailabilityForMonth(Employee $employee, Project $project, Role $role, Carbon $arrivalDate, array $formAssignments = [], array $formAssignmentRanges = []): array
    {
        $monthStart = $arrivalDate->copy();
        $monthEnd = $arrivalDate->copy()->addDays(29); // 30 days total
        
        $availability = [];

        // For each day in the month
        for ($day = 0; $day < 30; $day++) {
            $date = $monthStart->copy()->addDays($day);
            $dateKey = $date->format('Y-m-d');
            
            $dayAvailability = [
                'date' => $dateKey,
                'available' => false,
                'reason' => null,
                'can_assign' => false,
            ];

            // 1. Check if project has demand for this role on this day
            $hasDemand = $project->demands()
                ->overlappingWith($date, $date)
                ->where('role_id', $role->id)
                ->exists();

            if (!$hasDemand) {
                $dayAvailability['reason'] = 'no_demand';
                $dayAvailability['reason_text'] = 'Brak zapotrzebowania na tę rolę';
                $availability[$dateKey] = $dayAvailability;
                continue;
            }

            // 2. Check if employee has the required role
            if (!$employee->hasRole($role->id)) {
                $dayAvailability['reason'] = 'no_role';
                $dayAvailability['reason_text'] = 'Pracownik nie ma wymaganej roli';
                $availability[$dateKey] = $dayAvailability;
                continue;
            }

            // 3. Check if employee has rotation for this date
            $rotation = $employee->getActiveRotationForDate($date);
            if (!$rotation) {
                $dayAvailability['reason'] = 'no_rotation';
                $dayAvailability['reason_text'] = 'Brak aktywnej rotacji';
                $availability[$dateKey] = $dayAvailability;
                continue;
            }

            // 4. Check if employee has all required documents
            if (!$employee->hasAllDocumentsActiveInDateRange($date, $date)) {
                $dayAvailability['reason'] = 'no_documents';
                $dayAvailability['reason_text'] = 'Brak wymaganych dokumentów';
                $availability[$dateKey] = $dayAvailability;
                continue;
            }

            // 5. Check if employee is already assigned in form to another project/role on this date
            $isAssignedInForm = false;
            $dayNum = $day + 1; // day_1, day_2, etc.
            $dayKey = "day_{$dayNum}";
            
            // Check form assignments for this day
            if (isset($formAssignments[$dayKey])) {
                foreach ($formAssignments[$dayKey] as $formProjectId => $formRoles) {
                    foreach ($formRoles as $formRoleId => $formEmployeeIds) {
                        if (in_array($employee->id, $formEmployeeIds)) {
                            // If assigned to different project or different role in same project
                            if ($formProjectId != $project->id || ($formProjectId == $project->id && $formRoleId != $role->id)) {
                                $isAssignedInForm = true;
                                break 2;
                            }
                        }
                    }
                }
            }
            
            // Check form assignment ranges
            if (!$isAssignedInForm) {
                foreach ($formAssignmentRanges as $rangeKey => $range) {
                    if ($range['employee_id'] == $employee->id) {
                        $rangeStart = Carbon::parse($range['start_date']);
                        $rangeEnd = Carbon::parse($range['end_date']);
                        
                        if ($date->gte($rangeStart) && $date->lte($rangeEnd)) {
                            // If assigned to different project or different role in same project
                            if ($range['project_id'] != $project->id || ($range['project_id'] == $project->id && $range['role_id'] != $role->id)) {
                                $isAssignedInForm = true;
                                break;
                            }
                        }
                    }
                }
            }
            
            if ($isAssignedInForm) {
                $dayAvailability['reason'] = 'already_assigned';
                $dayAvailability['reason_text'] = 'Już przypisany do innego projektu/roli';
                $availability[$dateKey] = $dayAvailability;
                continue;
            }

            // 6. Check if employee is already assigned to another project on this date (in database)
            $existingAssignment = ProjectAssignment::where('employee_id', $employee->id)
                ->where('is_cancelled', false)
                ->where('project_id', '!=', $project->id)
                ->where(function ($query) use ($date) {
                    $query->where('start_date', '<=', $date)
                        ->where(function ($q) use ($date) {
                            $q->whereNull('end_date')
                              ->orWhere('end_date', '>=', $date);
                        });
                })
                ->exists();

            if ($existingAssignment) {
                $dayAvailability['reason'] = 'already_assigned';
                $dayAvailability['reason_text'] = 'Już przypisany do innego projektu';
                $availability[$dateKey] = $dayAvailability;
                continue;
            }

            // 7. Check if employee is already assigned to this project on this date (in database)
            $sameProjectAssignment = ProjectAssignment::where('employee_id', $employee->id)
                ->where('project_id', $project->id)
                ->where('is_cancelled', false)
                ->where(function ($query) use ($date) {
                    $query->where('start_date', '<=', $date)
                        ->where(function ($q) use ($date) {
                            $q->whereNull('end_date')
                              ->orWhere('end_date', '>=', $date);
                        });
                })
                ->exists();

            if ($sameProjectAssignment) {
                $dayAvailability['reason'] = 'already_assigned_same_project';
                $dayAvailability['reason_text'] = 'Już przypisany do tego projektu';
                $availability[$dateKey] = $dayAvailability;
                continue;
            }

            // All checks passed - employee is available
            $dayAvailability['available'] = true;
            $dayAvailability['can_assign'] = true;
            $availability[$dateKey] = $dayAvailability;
        }

        return $availability;
    }
    
    /**
     * Get employee availability for a date range (month).
     * Similar to getEmployeeAvailabilityForMonth but accepts start and end dates.
     * 
     * @param Employee $employee
     * @param Project $project
     * @param Role $role
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $formAssignments Optional: assignments from form
     * @param array $formAssignmentRanges Optional: assignment ranges from form
     * @param Carbon|null $minDate Optional: minimum allowed date (e.g., arrival date)
     * @return array
     */
    public function getEmployeeAvailabilityForMonthRange(Employee $employee, Project $project, Role $role, Carbon $startDate, Carbon $endDate, array $formAssignments = [], array $formAssignmentRanges = [], ?Carbon $minDate = null): array
    {
        $availability = [];
        $currentDate = $startDate->copy();
        
        // For each day in the range
        while ($currentDate->lte($endDate)) {
            $dateKey = $currentDate->format('Y-m-d');
            
            $dayAvailability = [
                'date' => $dateKey,
                'available' => false,
                'reason' => null,
                'can_assign' => false,
            ];

            // 0. Check if date is before minimum date (e.g., arrival date)
            if ($minDate && $currentDate->lt($minDate)) {
                $dayAvailability['reason'] = 'before_arrival';
                $dayAvailability['reason_text'] = 'Data przed przyjazdem';
                $availability[$dateKey] = $dayAvailability;
                $currentDate->addDay();
                continue;
            }

            // 1. Check if project has demand for this role on this day
            $hasDemand = $project->demands()
                ->overlappingWith($currentDate, $currentDate)
                ->where('role_id', $role->id)
                ->exists();

            if (!$hasDemand) {
                $dayAvailability['reason'] = 'no_demand';
                $dayAvailability['reason_text'] = 'Brak zapotrzebowania na tę rolę';
                $availability[$dateKey] = $dayAvailability;
                $currentDate->addDay();
                continue;
            }

            // 2. Check if employee has the required role
            if (!$employee->hasRole($role->id)) {
                $dayAvailability['reason'] = 'no_role';
                $dayAvailability['reason_text'] = 'Pracownik nie ma wymaganej roli';
                $availability[$dateKey] = $dayAvailability;
                $currentDate->addDay();
                continue;
            }

            // 3. Check if employee has rotation for this date
            $rotation = $employee->getActiveRotationForDate($currentDate);
            if (!$rotation) {
                $dayAvailability['reason'] = 'no_rotation';
                $dayAvailability['reason_text'] = 'Brak aktywnej rotacji';
                $availability[$dateKey] = $dayAvailability;
                $currentDate->addDay();
                continue;
            }

            // 4. Check if employee has all required documents
            if (!$employee->hasAllDocumentsActiveInDateRange($currentDate, $currentDate)) {
                $dayAvailability['reason'] = 'no_documents';
                $dayAvailability['reason_text'] = 'Brak wymaganych dokumentów';
                $availability[$dateKey] = $dayAvailability;
                $currentDate->addDay();
                continue;
            }

            // 5. Check if employee is already assigned in form to another project/role on this date
            $isAssignedInForm = false;
            
            // Calculate day number relative to arrival date (for day-based assignments)
            // We need to find which day this is relative to the original arrival date
            // For now, check all form assignments regardless of day number
            foreach ($formAssignments as $dayKey => $dayAssignments) {
                foreach ($dayAssignments as $formProjectId => $formRoles) {
                    foreach ($formRoles as $formRoleId => $formEmployeeIds) {
                        if (in_array($employee->id, $formEmployeeIds)) {
                            // If assigned to different project or different role in same project
                            if ($formProjectId != $project->id || ($formProjectId == $project->id && $formRoleId != $role->id)) {
                                $isAssignedInForm = true;
                                break 3;
                            }
                        }
                    }
                }
            }
            
            // Check form assignment ranges
            if (!$isAssignedInForm) {
                foreach ($formAssignmentRanges as $rangeKey => $range) {
                    if ($range['employee_id'] == $employee->id) {
                        $rangeStart = Carbon::parse($range['start_date']);
                        $rangeEnd = Carbon::parse($range['end_date']);
                        
                        if ($currentDate->gte($rangeStart) && $currentDate->lte($rangeEnd)) {
                            // If assigned to different project or different role in same project
                            if ($range['project_id'] != $project->id || ($range['project_id'] == $project->id && $range['role_id'] != $role->id)) {
                                $isAssignedInForm = true;
                                break;
                            }
                        }
                    }
                }
            }
            
            if ($isAssignedInForm) {
                $dayAvailability['reason'] = 'already_assigned';
                $dayAvailability['reason_text'] = 'Już przypisany do innego projektu/roli';
                $availability[$dateKey] = $dayAvailability;
                $currentDate->addDay();
                continue;
            }

            // 6. Check if employee is already assigned to another project on this date (in database)
            $existingAssignment = ProjectAssignment::where('employee_id', $employee->id)
                ->where('is_cancelled', false)
                ->where('project_id', '!=', $project->id)
                ->where(function ($query) use ($currentDate) {
                    $query->where('start_date', '<=', $currentDate)
                        ->where(function ($q) use ($currentDate) {
                            $q->whereNull('end_date')
                              ->orWhere('end_date', '>=', $currentDate);
                        });
                })
                ->exists();

            if ($existingAssignment) {
                $dayAvailability['reason'] = 'already_assigned';
                $dayAvailability['reason_text'] = 'Już przypisany do innego projektu';
                $availability[$dateKey] = $dayAvailability;
                $currentDate->addDay();
                continue;
            }

            // All checks passed - employee is available
            $dayAvailability['available'] = true;
            $dayAvailability['can_assign'] = true;
            $availability[$dateKey] = $dayAvailability;
            
            $currentDate->addDay();
        }

        return $availability;
    }
}
