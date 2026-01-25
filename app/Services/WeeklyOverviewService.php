<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectDemand;
use App\Models\ProjectAssignment;
use App\Models\AccommodationAssignment;
use App\Models\VehicleAssignment;
use App\Enums\AssignmentStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WeeklyOverviewService
{
    /**
     * Get weeks data for the overview (single week).
     */
    public function getWeeks(?Carbon $startDate = null): array
    {
        $startOfWeek = $startDate ?? Carbon::now()->startOfWeek();
        $weekStart = $startOfWeek->copy()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();
        
        // Get ISO week number (week number in year according to ISO 8601)
        $isoWeekNumber = $weekStart->isoWeek();
        
        return [[
            'number' => $isoWeekNumber,
            'start' => $weekStart,
            'end' => $weekEnd,
            'start_formatted' => $weekStart->format('d.m.Y'),
            'end_formatted' => $weekEnd->format('d.m.Y'),
            'label' => $weekStart->format('d.m') . ' – ' . $weekEnd->format('d.m.Y'),
        ]];
    }

    /**
     * Get all projects with their weekly data.
     */
    public function getProjectsWithWeeklyData(array $weeks): array
    {
        $projects = Project::with(['location', 'demands.role', 'assignments.employee', 'assignments.role'])->get();
        
        return $projects->map(function ($project) use ($weeks) {
            $weeksData = [];
            
            foreach ($weeks as $week) {
                $weeksData[] = $this->getProjectWeekData($project, $week);
            }
            
            return [
                'project' => $project,
                'weeks_data' => $weeksData,
            ];
        })->toArray();
    }

    /**
     * Get project data for a specific week.
     */
    public function getProjectWeekData(Project $project, array $week): array
    {
        $weekStart = $week['start'];
        $weekEnd = $week['end'];
        
        // Get demands for this week
        $demands = $this->getDemandsForWeek($project, $weekStart, $weekEnd);
        
        // Get assignments for this week
        $assignments = $this->getAssignmentsForWeek($project, $weekStart, $weekEnd);
        
        // Calculate requirements summary
        $requirementsSummary = $this->calculateRequirementsSummary($demands, $assignments, $weekStart, $weekEnd);
        
        // Get accommodations and vehicles used in this week
        $accommodations = $this->getAccommodationsForWeek($assignments, $weekStart, $weekEnd);
        $vehicles = $this->getVehiclesForWeek($assignments, $weekStart, $weekEnd);
        
        // Get assigned employees with their details
        $assignedEmployees = $this->getAssignedEmployeesDetails($assignments, $weekStart, $weekEnd);
        
        // Get project tasks
        $tasks = $project->tasks()->with(['assignedTo', 'createdBy'])->get();
        
        return [
            'week' => $week,
            'demands' => $demands,
            'assignments' => $assignments,
            'requirements_summary' => $requirementsSummary,
            'accommodations' => $accommodations,
            'vehicles' => $vehicles,
            'assigned_employees' => $assignedEmployees,
            'tasks' => $tasks,
            'has_data' => $demands->isNotEmpty() || $assignments->isNotEmpty(),
        ];
    }

    /**
     * Get demands that overlap with the week.
     */
    protected function getDemandsForWeek(Project $project, Carbon $weekStart, Carbon $weekEnd): Collection
    {
        return $this->getOverlappingDemands($project, $weekStart, $weekEnd)
            ->groupBy('role_id')
            ->map(function ($demands) {
                return [
                    'role' => $demands->first()->role,
                    'required_count' => $demands->sum('required_count'),
                    'demands' => $demands,
                ];
            });
    }

    /**
     * Get demands that overlap with a date range.
     */
    protected function getOverlappingDemands(Project $project, Carbon $startDate, Carbon $endDate): Collection
    {
        return ProjectDemand::where('project_id', $project->id)
            ->overlappingWith($startDate, $endDate)
            ->with('role')
            ->get();
    }

    /**
     * Get assignments that overlap with the week.
     */
    protected function getAssignmentsForWeek(Project $project, Carbon $weekStart, Carbon $weekEnd): Collection
    {
        return ProjectAssignment::where('project_id', $project->id)
            ->active()
            ->overlappingWith($weekStart, $weekEnd)
            ->with(['employee', 'role', 'project'])
            ->get();
    }

    /**
     * Calculate requirements summary (needed vs assigned).
     */
    protected function calculateRequirementsSummary(Collection $demands, Collection $assignments, ?Carbon $weekStart = null, ?Carbon $weekEnd = null): array
    {
        $totalNeeded = 0;
        $roleDetails = [];
        
        // If week dates provided, calculate min/max per day
        $calculateDaily = $weekStart !== null && $weekEnd !== null;
        $days = $calculateDaily ? $this->getDaysInWeek($weekStart, $weekEnd) : [];
        
        // Przetwórz zapotrzebowania
        foreach ($demands as $roleId => $demandData) {
            $needed = $demandData['required_count'];
            $roleAssignments = $assignments->where('role_id', $roleId);
            
            if ($calculateDaily) {
                // Count assigned per day
                $assignedPerDay = [];
                foreach ($days as $day) {
                    $assignedOnDay = 0;
                    foreach ($roleAssignments as $assignment) {
                        if ($assignment->start_date->lte($day) && 
                            ($assignment->end_date === null || $assignment->end_date->gte($day))) {
                            $assignedOnDay++;
                        }
                    }
                    $assignedPerDay[] = $assignedOnDay;
                }
                
                $assignedMin = min($assignedPerDay);
                $assignedMax = max($assignedPerDay);
                $assigned = $assignedMin === $assignedMax ? $assignedMin : null; // null means variable
                $isStable = $assignedMin === $assignedMax;
            } else {
                // Simple count (backward compatibility)
                $assigned = $roleAssignments->count();
                $assignedMin = $assigned;
                $assignedMax = $assigned;
                $isStable = true;
            }
            
            $totalNeeded += $needed;
            
            $roleDetails[] = [
                'role' => $demandData['role'],
                'needed' => $needed,
                'assigned' => $assigned,
                'assigned_min' => $assignedMin,
                'assigned_max' => $assignedMax,
                'is_stable' => $isStable,
                'missing' => max(0, $needed - ($assigned ?? $assignedMax)),
                'excess' => max(0, ($assigned ?? $assignedMin) - $needed),
            ];
        }
        
        // Znajdź przypisania do ról, które nie mają zapotrzebowania
        $demandRoleIds = $demands->keys()->toArray();
        $assignmentsWithoutDemand = $assignments->filter(function($assignment) use ($demandRoleIds) {
            return !in_array($assignment->role_id, $demandRoleIds);
        });
        
        // Dodaj role bez zapotrzebowania jako nadmiar
        $excessRoles = [];
        foreach ($assignmentsWithoutDemand->groupBy('role_id') as $roleId => $roleAssignments) {
            $role = $roleAssignments->first()->role;
            $excessRoles[] = [
                'role' => $role,
                'needed' => 0,
                'assigned' => $roleAssignments->count(),
                'assigned_min' => $roleAssignments->count(),
                'assigned_max' => $roleAssignments->count(),
                'is_stable' => true,
                'missing' => 0,
                'excess' => $roleAssignments->count(),
            ];
        }
        
        $roleDetails = array_merge($roleDetails, $excessRoles);
        
        // Oblicz całkowite wartości - tylko dla ról z zapotrzebowaniem
        $totalAssignedForNeededRoles = 0;
        $totalAssignedMin = 0;
        $totalAssignedMax = 0;
        $allStable = true;
        
        foreach ($roleDetails as $roleDetail) {
            if (!empty($roleDetail['assigned']) && $roleDetail['is_stable']) {
                $totalAssignedForNeededRoles += $roleDetail['assigned'];
            } else {
                $allStable = false;
            }
            $totalAssignedMin += $roleDetail['assigned_min'];
            $totalAssignedMax += $roleDetail['assigned_max'];
        }
        
        $totalAssigned = $allStable ? $totalAssignedForNeededRoles : null;
        $totalMissing = max(0, $totalNeeded - $totalAssignedMax);
        
        // Oblicz nadmiar - sumuj excess ze wszystkich ról (również tych bez zapotrzebowania)
        $totalExcess = 0;
        foreach ($roleDetails as $roleDetail) {
            if ($roleDetail['excess'] > 0) {
                $totalExcess += $roleDetail['excess'];
            }
        }
        
        return [
            'total_needed' => $totalNeeded,
            'total_assigned' => $totalAssigned, // null if variable
            'total_assigned_min' => $totalAssignedMin,
            'total_assigned_max' => $totalAssignedMax,
            'is_stable' => $allStable,
            'total_missing' => $totalMissing,
            'total_excess' => $totalExcess,
            'role_details' => $roleDetails,
        ];
    }
    
    /**
     * Get all days in the week.
     */
    protected function getDaysInWeek(Carbon $weekStart, Carbon $weekEnd): array
    {
        $days = [];
        $current = $weekStart->copy();
        
        while ($current->lte($weekEnd)) {
            $days[] = $current->copy();
            $current->addDay();
        }
        
        return $days;
    }

    /**
     * Get accommodations used in this week by assigned employees.
     * Returns unique accommodations with total usage count (all employees, not just from this project).
     */
    protected function getAccommodationsForWeek(Collection $assignments, Carbon $weekStart, Carbon $weekEnd): Collection
    {
        // Get employee IDs from project assignments
        $employeeIds = $assignments->pluck('employee_id')->unique();
        
        // Find accommodation assignments for these employees in this week
        $accommodationAssignments = AccommodationAssignment::active()
            ->whereIn('employee_id', $employeeIds)
            ->overlappingWith($weekStart, $weekEnd)
            ->with(['accommodation', 'employee'])
            ->get();
        
        // Get unique accommodation IDs used by project employees
        $accommodationIds = $accommodationAssignments->pluck('accommodation_id')->unique();
        
        if ($accommodationIds->isEmpty()) {
            return collect();
        }
        
        // Eager load all accommodations and their assignments for this week
        $accommodations = \App\Models\Accommodation::whereIn('id', $accommodationIds)->get();
        
        // Get all accommodation assignments for these accommodations in this week (single query)
        $allAccommodationAssignments = AccommodationAssignment::whereIn('accommodation_id', $accommodationIds)
            ->overlappingWith($weekStart, $weekEnd)
            ->get()
            ->groupBy('accommodation_id');
        
        // Map accommodations with their counts
        return $accommodations->map(function ($accommodation) use ($allAccommodationAssignments) {
            $assignments = $allAccommodationAssignments->get($accommodation->id) ?? collect();
            $totalEmployeeCount = $assignments->count();
            
            return [
                'accommodation' => $accommodation,
                'employee_count' => $totalEmployeeCount,
                'capacity' => $accommodation->capacity,
                'usage' => "{$totalEmployeeCount}/{$accommodation->capacity}",
                'usage_percentage' => $accommodation->capacity > 0 
                    ? round(($totalEmployeeCount / $accommodation->capacity) * 100, 0) 
                    : 0,
                'assignments' => $assignments->values(),
            ];
        })->values();
    }

    /**
     * Get vehicles used in this week by assigned employees.
     * Returns unique vehicles with total usage count (all employees, not just from this project).
     */
    protected function getVehiclesForWeek(Collection $assignments, Carbon $weekStart, Carbon $weekEnd): Collection
    {
        // Get employee IDs from project assignments
        $employeeIds = $assignments->pluck('employee_id')->unique();
        
        // Find vehicle assignments for these employees in this week
        $vehicleAssignments = VehicleAssignment::active()
            ->whereIn('employee_id', $employeeIds)
            ->overlappingWith($weekStart, $weekEnd)
            ->with(['vehicle', 'employee'])
            ->get();
        
        // Get unique vehicle IDs used by project employees
        $vehicleIds = $vehicleAssignments->pluck('vehicle_id')->unique();
        
        if ($vehicleIds->isEmpty()) {
            return collect();
        }
        
        // Eager load all vehicles and their assignments for this week
        $vehicles = \App\Models\Vehicle::whereIn('id', $vehicleIds)->get();
        
        // Get all vehicle assignments for these vehicles in this week (single query with eager loading)
        $allVehicleAssignments = VehicleAssignment::whereIn('vehicle_id', $vehicleIds)
            ->overlappingWith($weekStart, $weekEnd)
            ->with('employee')
            ->get()
            ->groupBy('vehicle_id');
        
        // Get return trips (zjazdy) for these vehicles in this week
        $returnTrips = \App\Models\LogisticsEvent::where('type', \App\Enums\LogisticsEventType::RETURN)
            ->whereIn('vehicle_id', $vehicleIds)
            ->whereBetween('event_date', [$weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay()])
            ->with(['participants.employee', 'vehicle'])
            ->get()
            ->groupBy('vehicle_id');
        
        // Map vehicles with their counts and drivers
        return $vehicles->map(function ($vehicle) use ($allVehicleAssignments, $returnTrips, $weekStart, $weekEnd) {
            $assignments = $allVehicleAssignments->get($vehicle->id) ?? collect();
            
            // Separate regular assignments from return trip assignments
            $regularAssignments = $assignments->filter(function ($assignment) {
                return !$assignment->is_return_trip;
            });
            
            $returnTripAssignments = $assignments->filter(function ($assignment) {
                return $assignment->is_return_trip;
            });
            
            // Show all employees who have this vehicle at least one day in the week
            // Get unique employees (don't filter by longest assignment - show anyone who has it any day)
            $uniqueEmployees = $regularAssignments->unique('employee_id');
            
            $totalEmployeeCount = $uniqueEmployees->count();
            
            // Find driver assignment (position = 'driver') from regular assignments
            $driverAssignment = $uniqueEmployees->first(function ($assignment) {
                $position = $assignment->position;
                if ($position instanceof \App\Enums\VehiclePosition) {
                    return $position === \App\Enums\VehiclePosition::DRIVER;
                }
                return $position === 'driver' || $position === \App\Enums\VehiclePosition::DRIVER->value;
            });
            
            // Get return trip for this vehicle
            $returnTrip = $returnTrips->get($vehicle->id)?->first();
            
            return [
                'vehicle' => $vehicle,
                'driver' => $driverAssignment?->employee,
                'vehicle_name' => trim("{$vehicle->brand} {$vehicle->model} {$vehicle->registration_number}"),
                'employee_count' => $totalEmployeeCount,
                'capacity' => $vehicle->capacity,
                'usage' => "{$totalEmployeeCount}/{$vehicle->capacity}",
                'usage_percentage' => $vehicle->capacity > 0 
                    ? round(($totalEmployeeCount / $vehicle->capacity) * 100, 0) 
                    : 0,
                'assignments' => $uniqueEmployees->sortBy(function ($assignment) {
                    // Sort: drivers first, then passengers
                    $position = $assignment->position;
                    if ($position instanceof \App\Enums\VehiclePosition) {
                        return $position === \App\Enums\VehiclePosition::DRIVER ? 0 : 1;
                    }
                    return ($position === 'driver' || $position === \App\Enums\VehiclePosition::DRIVER->value) ? 0 : 1;
                })->values(),
                'return_trip' => $returnTrip, // LogisticsEvent for return trip
                'return_trip_assignments' => $returnTripAssignments->values(), // Vehicle assignments for return trip
            ];
        })->values();
    }

    /**
     * Get assigned employees with their accommodation and vehicle details.
     */
    protected function getAssignedEmployeesDetails(Collection $assignments, Carbon $weekStart, Carbon $weekEnd): Collection
    {
        if ($assignments->isEmpty()) {
            return collect();
        }
        
        // Get all employee IDs
        $employeeIds = $assignments->pluck('employee_id')->unique();
        
        // Eager load all accommodation assignments for these employees in this week (single query)
        $accommodationAssignments = AccommodationAssignment::active()
            ->whereIn('employee_id', $employeeIds)
            ->overlappingWith($weekStart, $weekEnd)
            ->with('accommodation')
            ->get()
            ->groupBy('employee_id');
        
        // Eager load all vehicle assignments for these employees in this week (single query)
        $vehicleAssignments = VehicleAssignment::active()
            ->whereIn('employee_id', $employeeIds)
            ->overlappingWith($weekStart, $weekEnd)
            ->with('vehicle')
            ->get()
            ->groupBy('employee_id');
        
        // Eager load rotations for all employees
        // Pobierz wszystkie rotacje, które przecinają się z tygodniem (nawet jeśli już się skończyły)
        // Rotacja przecina się z tygodniem, jeśli: start_date <= weekEnd AND end_date >= weekStart
        $employeeIds = $assignments->pluck('employee_id')->unique();
        $rotations = \App\Models\Rotation::whereIn('employee_id', $employeeIds)
            ->where(function ($q) use ($weekStart, $weekEnd) {
                $q->where(function ($query) use ($weekStart, $weekEnd) {
                    // Rotacja przecina się z tygodniem
                    $query->whereDate('start_date', '<=', $weekEnd->toDateString())
                          ->whereDate('end_date', '>=', $weekStart->toDateString());
                })
                ->orWhereNull('end_date'); // Rotacje bez daty zakończenia
            })
            ->orderBy('end_date', 'asc')
            ->get()
            ->groupBy('employee_id');
        
        // Get return trips for employees in this week to check if they're returning
        $returnTripsForEmployees = \App\Models\LogisticsEvent::where('type', \App\Enums\LogisticsEventType::RETURN)
            ->whereBetween('event_date', [$weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay()])
            ->whereHas('participants', function ($q) use ($employeeIds) {
                $q->whereIn('employee_id', $employeeIds);
            })
            ->with(['participants.employee', 'vehicle'])
            ->get();
        
        // Create a map: employee_id => return_trip
        $returnTripsByEmployee = collect();
        foreach ($returnTripsForEmployees as $returnTrip) {
            foreach ($returnTrip->participants as $participant) {
                $returnTripsByEmployee->put($participant->employee_id, $returnTrip);
            }
        }
        
        // Group assignments by employee to check role stability
        $assignmentsByEmployee = $assignments->groupBy('employee_id');
        
        // Map assignments with their details
        return $assignments->map(function ($assignment) use ($accommodationAssignments, $vehicleAssignments, $weekStart, $weekEnd, $rotations, $returnTripsByEmployee, $assignmentsByEmployee) {
            $employee = $assignment->employee;
            
            // Check if employee has multiple assignments with different roles in this week
            $employeeAssignments = $assignmentsByEmployee->get($employee->id) ?? collect();
            $roleIds = $employeeAssignments->pluck('role_id')->unique();
            $isRoleStable = $roleIds->count() <= 1;
            
            // Get accommodation and vehicle from pre-loaded collections
            $accommodationAssignment = $accommodationAssignments->get($employee->id)?->first();
            
            // Check if employee has vehicle for all assigned days in this week
            $employeeVehicleAssignments = $vehicleAssignments->get($employee->id) ?? collect();
            $days = $this->getDaysInWeek($weekStart, $weekEnd);
            
            $hasVehicleAllDays = true;
            $anyDayAssigned = false;
            $firstVehicleAssignment = null;

            foreach ($days as $day) {
                $isAssignedOnDay = $assignment->start_date->lte($day) && 
                                  ($assignment->end_date === null || $assignment->end_date->gte($day));
                
                if ($isAssignedOnDay) {
                    $anyDayAssigned = true;
                    $dayVehicleAssignment = $employeeVehicleAssignments->first(function ($vAssignment) use ($day) {
                        return !$vAssignment->is_return_trip &&
                               $vAssignment->start_date->lte($day) &&
                               ($vAssignment->end_date === null || $vAssignment->end_date->gte($day));
                    });
                    
                    if (!$dayVehicleAssignment) {
                        $hasVehicleAllDays = false;
                    } elseif (!$firstVehicleAssignment) {
                        $firstVehicleAssignment = $dayVehicleAssignment;
                    }
                }
            }
            
            // has_vehicle_in_week should be true only if they have it for ALL assigned days
            $hasVehicleInWeek = $anyDayAssigned && $hasVehicleAllDays;
            $hasVehicleAnyDay = $anyDayAssigned && $firstVehicleAssignment !== null;
            
            // Get vehicle assignment for display (first one found)
            $vehicleAssignment = $firstVehicleAssignment;
            
            // Check if assignment is partial (not full week)
            // Assignment is partial if it doesn't cover the entire week
            // It must start on or before weekStart AND end on or after weekEnd
            $coversFullWeek = $assignment->start_date->lte($weekStart) && 
                            ($assignment->end_date === null || $assignment->end_date->gte($weekEnd));
            $isPartial = !$coversFullWeek;
            
            $assignmentStart = max($assignment->start_date, $weekStart);
            $assignmentEnd = min($assignment->end_date ?? $weekEnd, $weekEnd);
            
            // Get rotation info
            $employeeRotations = $rotations->get($employee->id);
            $activeRotation = $employeeRotations?->first();
            $rotationInfo = null;
            
            if ($activeRotation) {
                $daysLeft = now()->diffInDays($activeRotation->end_date, false);
                $rotationInfo = [
                    'id' => $activeRotation->id,
                    'rotation' => $activeRotation, // Przekaż cały obiekt dla łatwego dostępu
                    'start_date' => $activeRotation->start_date,
                    'end_date' => $activeRotation->end_date,
                    'days_left' => $daysLeft,
                ];
            }
            
            // Format date range - show days of week if partial
            $dateRangeText = 'cały tydzień';
            if ($isPartial) {
                $startDay = $assignmentStart->format('N'); // 1-7 (Monday-Sunday)
                $endDay = $assignmentEnd->format('N');
                $dayNames = ['', 'pon', 'wt', 'śr', 'czw', 'pt', 'sob', 'nie'];
                if ($startDay == $endDay) {
                    $dateRangeText = $dayNames[$startDay];
                } else {
                    $dateRangeText = $dayNames[$startDay] . '-' . $dayNames[$endDay];
                }
            }
            
            return [
                'assignment' => $assignment,
                'employee' => $employee,
                'role' => $assignment->role,
                'role_stable' => $isRoleStable,
                'accommodation' => $accommodationAssignment?->accommodation,
                'vehicle' => $vehicleAssignment?->vehicle,
                'vehicle_assignment' => $vehicleAssignment,
                'has_vehicle_in_week' => $hasVehicleInWeek,
                'is_partial' => $isPartial,
                'date_range' => $dateRangeText,
                'rotation' => $rotationInfo,
            ];
        });
    }

    /**
     * Get calendar data for a project - employees with daily assignments (dom/auto/projekt).
     * 
     * Returns data structured for calendar table:
     * - Employees as rows
     * - Days of week as columns
     * - For each day: accommodation, vehicle, project assignment info
     */
    public function getProjectCalendarData(Project $project, array $week): array
    {
        $weekStart = $week['start'];
        $weekEnd = $week['end'];
        
        // Get all assignments for this project in this week
        $assignments = $this->getAssignmentsForWeek($project, $weekStart, $weekEnd);
        
        // Get all days of the week
        $days = $this->getWeekDays($weekStart);
        
        if ($assignments->isEmpty()) {
            // Get daily demands and assignments for each day (even if no assignments)
            $dailyDemands = $this->getDailyDemandsAndAssignments($project, $weekStart, $weekEnd, $days);
            return [
                'employees' => collect(),
                'days' => $days,
                'daily_demands' => $dailyDemands,
            ];
        }
        
        // Get unique employee IDs
        $employeeIds = $assignments->pluck('employee_id')->unique();
        
        // Get return trips for employees in this week
        $returnTrips = \App\Models\LogisticsEvent::where('type', \App\Enums\LogisticsEventType::RETURN)
            ->whereBetween('event_date', [$weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay()])
            ->whereHas('participants', function ($q) use ($employeeIds) {
                $q->whereIn('employee_id', $employeeIds);
            })
            ->with(['participants.employee', 'vehicle'])
            ->get();
        
        // Create a map: employee_id => [date => return_trip]
        $returnTripsByEmployeeAndDate = collect();
        foreach ($returnTrips as $returnTrip) {
            foreach ($returnTrip->participants as $participant) {
                $employeeId = $participant->employee_id;
                $eventDate = $returnTrip->event_date->format('Y-m-d');
                if (!$returnTripsByEmployeeAndDate->has($employeeId)) {
                    $returnTripsByEmployeeAndDate->put($employeeId, collect());
                }
                $returnTripsByEmployeeAndDate->get($employeeId)->put($eventDate, $returnTrip);
            }
        }
        
        // For each employee, get daily data
        $employees = $employeeIds->map(function ($employeeId) use ($assignments, $days, $weekStart, $weekEnd, $returnTripsByEmployeeAndDate, $employeeIds) {
            $employee = \App\Models\Employee::find($employeeId);
            if (!$employee) {
                return null;
            }
            
            // Get daily data for each day
            $dailyData = $days->map(function ($day) use ($employee, $assignments, $weekStart, $weekEnd, $returnTripsByEmployeeAndDate, $employeeIds) {
                $dayDate = $day['date']->copy()->startOfDay();
                $dayDateString = $dayDate->format('Y-m-d');
                
                // Check if this is a return trip day for this employee
                $returnTrip = $returnTripsByEmployeeAndDate->get($employee->id)?->get($dayDateString);
                
                // Get project assignment for this employee for THIS SPECIFIC DAY
                $projectAssignment = $assignments->first(function ($assignment) use ($employee, $dayDate) {
                    if ($assignment->employee_id !== $employee->id) {
                        return false;
                    }
                    $assignmentStart = $assignment->start_date->copy()->startOfDay();
                    $assignmentEnd = $assignment->end_date ? $assignment->end_date->copy()->startOfDay() : null;
                    
                    return $assignmentStart->lte($dayDate) && 
                           ($assignmentEnd === null || $assignmentEnd->gte($dayDate));
                });
                
                // Get accommodation for this day (normalize dates to start of day for comparison)
                $accommodationAssignment = AccommodationAssignment::where('employee_id', $employee->id)
                    ->whereRaw('DATE(start_date) <= ?', [$dayDateString])
                    ->where(function ($q) use ($dayDateString) {
                        $q->whereRaw('DATE(end_date) >= ?', [$dayDateString])
                          ->orWhereNull('end_date');
                    })
                    ->active()
                    ->with('accommodation')
                    ->first();
                
                $accommodation = $accommodationAssignment?->accommodation;
                
                // Count how many people are in this accommodation on this day (normalize dates)
                $accommodationOccupancy = 0;
                if ($accommodation) {
                    $accommodationOccupancy = AccommodationAssignment::where('accommodation_id', $accommodation->id)
                        ->whereRaw('DATE(start_date) <= ?', [$dayDateString])
                        ->where(function ($q) use ($dayDateString) {
                            $q->whereRaw('DATE(end_date) >= ?', [$dayDateString])
                              ->orWhereNull('end_date');
                        })
                        ->active()
                        ->count();
                }
                
                // Get vehicle for this day (exclude return trip assignments, normalize dates)
                $vehicleAssignment = VehicleAssignment::where('employee_id', $employee->id)
                    ->where('is_return_trip', false)
                    ->whereRaw('DATE(start_date) <= ?', [$dayDateString])
                    ->where(function ($q) use ($dayDateString) {
                        $q->whereRaw('DATE(end_date) >= ?', [$dayDateString])
                          ->orWhereNull('end_date');
                    })
                    ->active()
                    ->with('vehicle')
                    ->first();
                
                $vehicle = $vehicleAssignment?->vehicle;
                
                // Count how many people are in this vehicle on this day (normalize dates)
                $vehicleOccupancy = 0;
                if ($vehicle) {
                    $vehicleOccupancy = VehicleAssignment::where('vehicle_id', $vehicle->id)
                        ->where('is_return_trip', false)
                        ->whereRaw('DATE(start_date) <= ?', [$dayDateString])
                        ->where(function ($q) use ($dayDateString) {
                            $q->whereRaw('DATE(end_date) >= ?', [$dayDateString])
                              ->orWhereNull('end_date');
                        })
                        ->active()
                        ->count();
                }
                
                // Check if employee has ANY assignment on this day (project, vehicle, or accommodation)
                $isAssigned = $projectAssignment !== null || $vehicleAssignment !== null || $accommodationAssignment !== null;
                
                return [
                    'date' => $dayDate,
                    'is_assigned' => $isAssigned,
                    'accommodation' => $accommodation,
                    'accommodation_assignment' => $accommodationAssignment, // Store full assignment for link
                    'accommodation_capacity' => $accommodation?->capacity,
                    'accommodation_occupancy' => $accommodationOccupancy,
                    'vehicle' => $vehicle,
                    'vehicle_capacity' => $vehicle?->capacity,
                    'vehicle_occupancy' => $vehicleOccupancy,
                    'vehicle_assignment' => $vehicleAssignment, // Store full assignment for position and link
                    'project_assignment' => $projectAssignment, // Store full assignment for link
                    'project' => $projectAssignment?->project ?? null,
                    'return_trip' => $returnTrip, // LogisticsEvent if this is a return trip day
                ];
            });
            
            // Get unique accommodations and vehicles used in this week for this employee
            $uniqueAccommodations = collect();
            $uniqueVehicles = collect();
            
            foreach ($dailyData as $dayData) {
                if (isset($dayData['accommodation']) && $dayData['accommodation']) {
                    $accommodation = $dayData['accommodation'];
                    if (!$uniqueAccommodations->contains('id', $accommodation->id)) {
                        $uniqueAccommodations->push($accommodation);
                    }
                }
                if (isset($dayData['vehicle']) && $dayData['vehicle']) {
                    $vehicle = $dayData['vehicle'];
                    if (!$uniqueVehicles->contains('id', $vehicle->id)) {
                        $uniqueVehicles->push($vehicle);
                    }
                }
            }
            
            return [
                'employee' => $employee,
                'daily_data' => $dailyData,
                'unique_accommodations' => $uniqueAccommodations,
                'unique_vehicles' => $uniqueVehicles,
            ];
        })->filter()->values();
        
        // Get daily demands and assignments for each day
        $dailyDemands = $this->getDailyDemandsAndAssignments($project, $weekStart, $weekEnd, $days);
        
        return [
            'employees' => $employees,
            'days' => $days,
            'daily_demands' => $dailyDemands,
        ];
    }
    
    /**
     * Get daily demands and assignments for each day of the week.
     * Returns data structured as: [day => [role_id => [required, assigned]]]
     */
    protected function getDailyDemandsAndAssignments(Project $project, Carbon $weekStart, Carbon $weekEnd, Collection $days): array
    {
        // Get all demands that overlap with the week
        $allDemands = $this->getOverlappingDemands($project, $weekStart, $weekEnd);
        
        // Get all assignments for this project in this week
        $assignments = $this->getAssignmentsForWeek($project, $weekStart, $weekEnd);
        
        // Get all unique roles from demands with first demand for each role (for edit link)
        $rolesWithDemands = $allDemands->groupBy('role_id')->map(function ($demands) {
            return [
                'role' => $demands->first()->role,
                'first_demand' => $demands->first(), // First demand for edit link
            ];
        });
        
        $dailyData = [];
        
        foreach ($days as $day) {
            $dayDate = $day['date'];
            $dayData = [];
            
            // For each role, calculate required and assigned for this day
            foreach ($rolesWithDemands as $roleId => $roleData) {
                $role = $roleData['role'];
                
                // Calculate required count for this day
                $requiredCount = 0;
                foreach ($allDemands as $demand) {
                    if ($demand->role_id == $roleId) {
                        $demandStart = $demand->start_date ? $demand->start_date->copy()->startOfDay() : null;
                        $demandEnd = $demand->end_date ? $demand->end_date->copy()->endOfDay() : null;
                        
                        if ($demandStart && $dayDate->gte($demandStart) && ($demandEnd === null || $dayDate->lte($demandEnd))) {
                            $requiredCount += $demand->required_count;
                        }
                    }
                }
                
                // Calculate assigned count for this day
                $assignedCount = 0;
                foreach ($assignments as $assignment) {
                    if ($assignment->role_id == $roleId) {
                        $assignmentStart = $assignment->start_date ? $assignment->start_date->copy()->startOfDay() : null;
                        $assignmentEnd = $assignment->end_date ? $assignment->end_date->copy()->endOfDay() : null;
                        
                        if ($assignmentStart && $dayDate->gte($assignmentStart) && ($assignmentEnd === null || $dayDate->lte($assignmentEnd))) {
                            $assignedCount++;
                        }
                    }
                }
                
                if ($requiredCount > 0 || $assignedCount > 0) {
                    $dayData[$roleId] = [
                        'role' => $role,
                        'required' => $requiredCount,
                        'assigned' => $assignedCount,
                        'first_demand' => $roleData['first_demand'], // For edit link
                    ];
                }
            }
            
            $dailyData[$dayDate->format('Y-m-d')] = $dayData;
        }
        
        return $dailyData;
    }

    /**
     * Get array of days in the week.
     */
    protected function getWeekDays(Carbon $weekStart): Collection
    {
        $days = collect();
        $currentDay = $weekStart->copy();
        
        for ($i = 0; $i < 7; $i++) {
            $days->push([
                'date' => $currentDay->copy(),
                'day_name' => $currentDay->format('D'),
                'day_number' => $currentDay->format('d'),
                'day_name_short' => $this->getDayNameShort($currentDay->dayOfWeek),
            ]);
            $currentDay->addDay();
        }
        
        return $days;
    }

    /**
     * Get short day name in Polish.
     */
    protected function getDayNameShort(int $dayOfWeek): string
    {
        $names = [
            0 => 'Nd',
            1 => 'Pn',
            2 => 'Wt',
            3 => 'Śr',
            4 => 'Cz',
            5 => 'Pt',
            6 => 'Sb',
        ];
        
        return $names[$dayOfWeek] ?? '';
    }
}

