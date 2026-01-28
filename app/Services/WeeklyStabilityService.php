<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Employee;
use App\Models\ProjectAssignment;
use App\Models\VehicleAssignment;
use App\Models\AccommodationAssignment;
use App\Enums\AssignmentStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service for calculating stability of assignments across a week.
 * 
 * This service determines if assignments are stable (consistent) across all days
 * of a week, or if they vary. Used by Weekly Planner 3 to show honest aggregations.
 */
class WeeklyStabilityService
{
    /**
     * Get stability analysis for an employee in a project for a given week.
     * 
     * @return array{
     *     is_role_stable: bool,
     *     role: \App\Models\Role|null,
     *     is_car_stable: bool,
     *     vehicle: \App\Models\Vehicle|null,
     *     is_home_stable: bool,
     *     accommodation: \App\Models\Accommodation|null,
     *     days_assigned: int,
     *     days_in_week: int,
     *     is_partial: bool,
     *     daily_details: array
     * }
     */
    public function getEmployeeStability(
        Employee $employee,
        Project $project,
        Carbon $weekStart,
        Carbon $weekEnd,
        ?Collection $preloadedVehicleAssignments = null,
        ?Collection $preloadedAccommodationAssignments = null,
        ?Collection $preloadedProjectAssignments = null
    ): array {
        $days = $this->getDaysInWeek($weekStart, $weekEnd);
        $dailyDetails = [];
        
        $roles = collect();
        $vehicles = collect();
        $accommodations = collect();
        $daysAssigned = 0;
        
        foreach ($days as $day) {
            $dayData = $this->getDayData($employee, $project, $day, $preloadedVehicleAssignments, $preloadedAccommodationAssignments, $preloadedProjectAssignments);
            $dailyDetails[] = $dayData;
            
            if ($dayData['is_assigned']) {
                $daysAssigned++;
                
                if ($dayData['role']) {
                    $roles->push($dayData['role']->id);
                }
                if ($dayData['vehicle']) {
                    $vehicles->push($dayData['vehicle']->id);
                }
                if ($dayData['accommodation']) {
                    $accommodations->push($dayData['accommodation']->id);
                }
            }
        }
        
        // Stability checks: all assigned days must have the same value
        $isRoleStable = $roles->unique()->count() <= 1;
        $isCarStable = $vehicles->unique()->count() <= 1;
        $isHomeStable = $accommodations->unique()->count() <= 1;
        
        // Get stable values (if stable)
        $firstAssignedDay = collect($dailyDetails)->firstWhere('is_assigned', true);
        
        $role = $isRoleStable && $roles->isNotEmpty() && $firstAssignedDay
            ? $firstAssignedDay['role'] ?? null
            : null;
            
        $vehicle = $isCarStable && $vehicles->isNotEmpty() && $firstAssignedDay
            ? $firstAssignedDay['vehicle'] ?? null
            : null;
            
        $accommodation = $isHomeStable && $accommodations->isNotEmpty() && $firstAssignedDay
            ? $firstAssignedDay['accommodation'] ?? null
            : null;
        
        $isPartial = $daysAssigned < count($days);
        
        return [
            'is_role_stable' => $isRoleStable,
            'role' => $role,
            'is_car_stable' => $isCarStable,
            'vehicle' => $vehicle,
            'is_home_stable' => $isHomeStable,
            'accommodation' => $accommodation,
            'days_assigned' => $daysAssigned,
            'days_in_week' => count($days),
            'is_partial' => $isPartial,
            'daily_details' => $dailyDetails,
        ];
    }
    
    /**
     * Get data for a specific day.
     * OPTIMIZED: Uses pre-loaded assignments to avoid N+1 queries.
     */
    protected function getDayData(
        Employee $employee, 
        Project $project, 
        Carbon $day,
        ?Collection $preloadedVehicleAssignments = null,
        ?Collection $preloadedAccommodationAssignments = null,
        ?Collection $preloadedProjectAssignments = null
    ): array {
        // Check project assignment (from pre-loaded assignments if available)
        $projectAssignment = null;
        if ($preloadedProjectAssignments) {
            $projectAssignment = $preloadedProjectAssignments->first(function($assignment) use ($employee, $project, $day) {
                return $assignment->employee_id === $employee->id
                    && $assignment->project_id === $project->id
                    && $assignment->start_date->lte($day)
                    && ($assignment->end_date === null || $assignment->end_date->gte($day))
                    && $assignment->is_cancelled === false;
            });
        } else {
            // Fallback to query if pre-loaded data not available
            $projectAssignment = ProjectAssignment::where('employee_id', $employee->id)
                ->where('project_id', $project->id)
                ->where('start_date', '<=', $day)
                ->where(function ($q) use ($day) {
                    $q->where('end_date', '>=', $day)
                      ->orWhereNull('end_date');
                })
                ->active()
                ->with('role')
                ->first();
        }
        
        if (!$projectAssignment) {
            return [
                'date' => $day,
                'is_assigned' => false,
                'role' => null,
                'vehicle' => null,
                'accommodation' => null,
            ];
        }
        
        // Get vehicle from pre-loaded data if available
        $vehicleAssignment = null;
        if ($preloadedVehicleAssignments) {
            $vehicleAssignment = $preloadedVehicleAssignments->first(function($assignment) use ($employee, $day) {
                return $assignment->employee_id === $employee->id
                    && $assignment->start_date->lte($day)
                    && ($assignment->end_date === null || $assignment->end_date->gte($day));
            });
        } else {
            // Fallback to query if pre-loaded data not available
            $vehicleAssignment = VehicleAssignment::where('employee_id', $employee->id)
                ->where('is_return_trip', false)
                ->where('start_date', '<=', $day)
                ->where(function ($q) use ($day) {
                    $q->where('end_date', '>=', $day)
                      ->orWhereNull('end_date');
                })
                ->active()
                ->with('vehicle')
                ->first();
        }
        
        // Get accommodation from pre-loaded data if available
        $accommodationAssignment = null;
        if ($preloadedAccommodationAssignments) {
            $accommodationAssignment = $preloadedAccommodationAssignments->first(function($assignment) use ($employee, $day) {
                return $assignment->employee_id === $employee->id
                    && $assignment->start_date->lte($day)
                    && ($assignment->end_date === null || $assignment->end_date->gte($day));
            });
        } else {
            // Fallback to query if pre-loaded data not available
            $accommodationAssignment = AccommodationAssignment::where('employee_id', $employee->id)
                ->where('start_date', '<=', $day)
                ->where(function ($q) use ($day) {
                    $q->where('end_date', '>=', $day)
                      ->orWhereNull('end_date');
                })
                ->active()
                ->with('accommodation')
                ->first();
        }
        
        return [
            'date' => $day,
            'is_assigned' => true,
            'role' => $projectAssignment->role,
            'vehicle' => $vehicleAssignment?->vehicle,
            'accommodation' => $accommodationAssignment?->accommodation,
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
     * Get aggregated stability data for a project in a week.
     * 
     * @return array{
     *     employees: Collection,
     *     vehicles: array,
     *     accommodations: array,
     *     potential_issues: array,
     *     requirements_summary: array,
     *     demands: Collection,
     *     assignments: Collection
     * }
     */
    public function getProjectStability(Project $project, Carbon $weekStart, Carbon $weekEnd): array
    {
        // Get all project assignments for this week
        $assignments = ProjectAssignment::where('project_id', $project->id)
            ->active()
            ->overlappingWith($weekStart, $weekEnd)
            ->with(['employee', 'role'])
            ->get();
        
        $employeeIds = $assignments->pluck('employee_id')->unique();
        
        if ($employeeIds->isEmpty()) {
            return [
                'employees' => collect(),
                'assigned_employees' => collect(),
                'vehicles' => [],
                'accommodations' => [],
                'potential_issues' => [],
                'requirements_summary' => [],
                'demands' => collect(),
                'assignments' => $assignments,
            ];
        }
        
        // EAGER LOAD: Load all employees at once
        $employeesCollection = \App\Models\Employee::whereIn('id', $employeeIds)
            ->with('roles')
            ->get()
            ->keyBy('id');
        
        // EAGER LOAD: Pre-load all vehicle and accommodation assignments for all employees and all days
        $allVehicleAssignments = VehicleAssignment::whereIn('employee_id', $employeeIds)
            ->where('is_return_trip', false)
            ->where(function($query) use ($weekStart, $weekEnd) {
                $query->where(function($q) use ($weekStart, $weekEnd) {
                    $q->where('start_date', '<=', $weekEnd)
                      ->where(function($q2) use ($weekStart) {
                          $q2->whereNull('end_date')
                             ->orWhere('end_date', '>=', $weekStart);
                      });
                });
            })
            ->with('vehicle')
            ->get();
        
        $allAccommodationAssignments = AccommodationAssignment::whereIn('employee_id', $employeeIds)
            ->where(function($query) use ($weekStart, $weekEnd) {
                $query->where(function($q) use ($weekStart, $weekEnd) {
                    $q->where('start_date', '<=', $weekEnd)
                      ->where(function($q2) use ($weekStart) {
                          $q2->whereNull('end_date')
                             ->orWhere('end_date', '>=', $weekStart);
                      });
                });
            })
            ->with('accommodation')
            ->get();
        
        // EAGER LOAD: Pre-load all project assignments for all employees (for getDayData)
        $allProjectAssignments = $assignments; // Already loaded above
        
        // Get stability for each employee (using pre-loaded data)
        $employees = collect();
        foreach ($employeeIds as $employeeId) {
            $employee = $employeesCollection->get($employeeId);
            if (!$employee) {
                continue;
            }
            
            $stability = $this->getEmployeeStability($employee, $project, $weekStart, $weekEnd, $allVehicleAssignments, $allAccommodationAssignments, $allProjectAssignments);
            $employees->push([
                'employee' => $employee,
                'stability' => $stability,
            ]);
        }
        
        // Aggregate vehicles and accommodations
        $vehicles = $this->aggregateVehicles($employees, $weekStart, $weekEnd);
        $accommodations = $this->aggregateAccommodations($employees, $weekStart, $weekEnd);
        
        // Detect potential issues
        $potentialIssues = $this->detectPotentialIssues($employees, $vehicles, $accommodations);
        
        // Get demands and calculate requirements summary with stability
        $demands = $this->getDemandsForWeek($project, $weekStart, $weekEnd);
        $assignments = $this->getAssignmentsForWeek($project, $weekStart, $weekEnd);
        $requirementsSummary = $this->calculateRequirementsSummaryWithStability($demands, $assignments, $employees, $weekStart, $weekEnd);
        
        // Format employees data similar to getAssignedEmployeesDetails
        $assignedEmployees = $this->formatEmployeesForDisplay($employees, $assignments, $weekStart, $weekEnd);
        
        return [
            'employees' => $employees,
            'assigned_employees' => $assignedEmployees,
            'vehicles' => $vehicles,
            'accommodations' => $accommodations,
            'potential_issues' => $potentialIssues,
            'requirements_summary' => $requirementsSummary,
            'demands' => $demands,
            'assignments' => $assignments,
            'has_data' => $demands->isNotEmpty() || $assignments->isNotEmpty(),
        ];
    }
    
    /**
     * Format employees data for display (similar to getAssignedEmployeesDetails).
     */
    protected function formatEmployeesForDisplay(Collection $employees, Collection $assignments, Carbon $weekStart, Carbon $weekEnd): Collection
    {
        return $employees->map(function($employeeData) use ($assignments, $weekStart, $weekEnd) {
            $employee = $employeeData['employee'];
            $stability = $employeeData['stability'];
            
            // Find assignment for this employee
            $assignment = $assignments->firstWhere('employee_id', $employee->id);
            
            // Determine date range
            $daysAssigned = $stability['days_assigned'];
            $daysInWeek = $stability['days_in_week'];
            $dateRange = $daysAssigned === $daysInWeek ? 'cały tydzień' : "{$daysAssigned}/{$daysInWeek} dni";
            
            return [
                'employee' => $employee,
                'assignment' => $assignment,
                'role' => $stability['is_role_stable'] ? $stability['role'] : null,
                'role_stable' => $stability['is_role_stable'],
                'vehicle' => $stability['is_car_stable'] ? $stability['vehicle'] : null,
                'vehicle_stable' => $stability['is_car_stable'],
                'accommodation' => $stability['is_home_stable'] ? $stability['accommodation'] : null,
                'accommodation_stable' => $stability['is_home_stable'],
                'date_range' => $dateRange,
                'stability' => $stability,
            ];
        })->values();
    }
    
    /**
     * Get demands for a week.
     */
    protected function getDemandsForWeek(Project $project, Carbon $weekStart, Carbon $weekEnd): Collection
    {
        return \App\Models\ProjectDemand::where('project_id', $project->id)
            ->overlappingWith($weekStart, $weekEnd)
            ->with('role')
            ->get()
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
     * Get assignments for a week.
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
     * Calculate requirements summary with stability awareness.
     * Shows min/max assigned per role across the week.
     */
    protected function calculateRequirementsSummaryWithStability(
        Collection $demands,
        Collection $assignments,
        Collection $employees,
        Carbon $weekStart,
        Carbon $weekEnd
    ): array {
        $days = $this->getDaysInWeek($weekStart, $weekEnd);
        $roleDetails = [];
        $totalNeeded = 0;
        $totalAssignedMin = 0;
        $totalAssignedMax = 0;
        
        // For each role with demand, calculate min/max assigned across days
        foreach ($demands as $roleId => $demandData) {
            $role = $demandData['role'];
            $needed = $demandData['required_count'];
            $totalNeeded += $needed;
            
            // Count assigned per day
            $assignedPerDay = [];
            foreach ($days as $day) {
                $assignedOnDay = 0;
                foreach ($employees as $employeeData) {
                    $stability = $employeeData['stability'];
                    $dayData = collect($stability['daily_details'])->first(function($d) use ($day) {
                        return $d['date']->format('Y-m-d') === $day->format('Y-m-d');
                    });
                    
                    if ($dayData && $dayData['is_assigned'] && $dayData['role'] && $dayData['role']->id == $roleId) {
                        $assignedOnDay++;
                    }
                }
                $assignedPerDay[] = $assignedOnDay;
            }
            
            $assignedMin = min($assignedPerDay);
            $assignedMax = max($assignedPerDay);
            $isStable = $assignedMin === $assignedMax;
            
            $totalAssignedMin += $assignedMin;
            $totalAssignedMax += $assignedMax;
            
            $roleDetails[] = [
                'role' => $role,
                'needed' => $needed,
                'assigned_min' => $assignedMin,
                'assigned_max' => $assignedMax,
                'assigned' => $isStable ? $assignedMin : null, // Only show if stable
                'is_stable' => $isStable,
                'missing' => max(0, $needed - $assignedMax), // Worst case
                'excess' => max(0, $assignedMin - $needed), // Best case
            ];
        }
        
        // Also count assignments without demand
        $assignmentsWithoutDemand = $assignments->filter(function($assignment) use ($demands) {
            return !$demands->has($assignment->role_id);
        });
        
        $totalExcess = $assignmentsWithoutDemand->count();
        
        return [
            'total_needed' => $totalNeeded,
            'total_assigned_min' => $totalAssignedMin,
            'total_assigned_max' => $totalAssignedMax,
            'total_assigned' => $totalAssignedMin === $totalAssignedMax ? $totalAssignedMin : null, // Only if stable
            'is_stable' => $totalAssignedMin === $totalAssignedMax,
            'total_missing' => max(0, $totalNeeded - $totalAssignedMax),
            'total_excess' => $totalExcess,
            'role_details' => $roleDetails,
        ];
    }
    
    /**
     * Aggregate vehicle usage across the week.
     */
    protected function aggregateVehicles(Collection $employees, Carbon $weekStart, Carbon $weekEnd): array
    {
        $vehicleDays = [];
        $days = $this->getDaysInWeek($weekStart, $weekEnd);
        
        foreach ($days as $day) {
            foreach ($employees as $employeeData) {
                $employee = $employeeData['employee'];
                $stability = $employeeData['stability'];
                
                // Find day data
                $dayData = collect($stability['daily_details'])->first(function($d) use ($day) {
                    return $d['date']->format('Y-m-d') === $day->format('Y-m-d');
                });
                
                if ($dayData && $dayData['is_assigned'] && $dayData['vehicle']) {
                    $vehicleId = $dayData['vehicle']->id;
                    if (!isset($vehicleDays[$vehicleId])) {
                        $vehicleDays[$vehicleId] = [
                            'vehicle' => $dayData['vehicle'],
                            'days' => [],
                            'employees' => collect(),
                        ];
                    }
                    $vehicleDays[$vehicleId]['days'][] = $day;
                    $vehicleDays[$vehicleId]['employees']->push($employee);
                }
            }
        }
        
        // Convert to array format
        $result = [];
        foreach ($vehicleDays as $vehicleId => $data) {
            $result[] = [
                'vehicle' => $data['vehicle'],
                'min_occupancy' => 1, // At least one person
                'max_occupancy' => $data['employees']->unique('id')->count(),
                'days_used' => count(array_unique(array_map(fn($d) => $d->format('Y-m-d'), $data['days']))),
                'is_stable' => count(array_unique(array_map(fn($d) => $d->format('Y-m-d'), $data['days']))) === count($days),
            ];
        }
        
        return $result;
    }
    
    /**
     * Aggregate accommodation usage across the week.
     */
    protected function aggregateAccommodations(Collection $employees, Carbon $weekStart, Carbon $weekEnd): array
    {
        $accommodationDays = [];
        $days = $this->getDaysInWeek($weekStart, $weekEnd);
        
        foreach ($days as $day) {
            foreach ($employees as $employeeData) {
                $employee = $employeeData['employee'];
                $stability = $employeeData['stability'];
                
                // Find day data
                $dayData = collect($stability['daily_details'])->first(function($d) use ($day) {
                    return $d['date']->format('Y-m-d') === $day->format('Y-m-d');
                });
                
                if ($dayData && $dayData['is_assigned'] && $dayData['accommodation']) {
                    $accommodationId = $dayData['accommodation']->id;
                    if (!isset($accommodationDays[$accommodationId])) {
                        $accommodationDays[$accommodationId] = [
                            'accommodation' => $dayData['accommodation'],
                            'days' => [],
                            'employees' => collect(),
                        ];
                    }
                    $accommodationDays[$accommodationId]['days'][] = $day;
                    $accommodationDays[$accommodationId]['employees']->push($employee);
                }
            }
        }
        
        // Convert to array format
        $result = [];
        foreach ($accommodationDays as $accommodationId => $data) {
            $uniqueEmployees = $data['employees']->unique('id');
            $result[] = [
                'accommodation' => $data['accommodation'],
                'min_occupancy' => 1,
                'max_occupancy' => $uniqueEmployees->count(),
                'days_used' => count(array_unique(array_map(fn($d) => $d->format('Y-m-d'), $data['days']))),
                'is_stable' => count(array_unique(array_map(fn($d) => $d->format('Y-m-d'), $data['days']))) === count($days),
            ];
        }
        
        return $result;
    }
    
    /**
     * Detect potential issues that need daily verification.
     */
    protected function detectPotentialIssues(Collection $employees, array $vehicles, array $accommodations): array
    {
        $issues = [];
        
        // Check for employees without vehicle
        foreach ($employees as $employeeData) {
            $employee = $employeeData['employee'];
            $stability = $employeeData['stability'];
            
            if ($stability['days_assigned'] > 0) {
                // Has days without vehicle
                $daysWithoutVehicle = collect($stability['daily_details'])
                    ->filter(fn($d) => $d['is_assigned'] && !$d['vehicle'])
                    ->count();
                
                if ($daysWithoutVehicle > 0) {
                    $issues[] = [
                        'type' => 'no_vehicle',
                        'employee' => $employee,
                        'days_affected' => $daysWithoutVehicle,
                        'message' => "{$employee->full_name} - brak auta ({$daysWithoutVehicle} dni)",
                    ];
                }
            }
        }
        
        // Check for employees without accommodation
        foreach ($employees as $employeeData) {
            $employee = $employeeData['employee'];
            $stability = $employeeData['stability'];
            
            if ($stability['days_assigned'] > 0) {
                $daysWithoutAccommodation = collect($stability['daily_details'])
                    ->filter(fn($d) => $d['is_assigned'] && !$d['accommodation'])
                    ->count();
                
                if ($daysWithoutAccommodation > 0) {
                    $issues[] = [
                        'type' => 'no_accommodation',
                        'employee' => $employee,
                        'days_affected' => $daysWithoutAccommodation,
                        'message' => "{$employee->full_name} - brak domu ({$daysWithoutAccommodation} dni)",
                    ];
                }
            }
        }
        
        // Check for overcapacity (simplified - would need capacity data)
        // This is a placeholder for more complex logic
        
        return $issues;
    }
}
