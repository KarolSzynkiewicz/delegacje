<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectDemand;
use App\Models\ProjectAssignment;
use App\Models\AccommodationAssignment;
use App\Models\VehicleAssignment;
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
        $requirementsSummary = $this->calculateRequirementsSummary($demands, $assignments);
        
        // Get accommodations and vehicles used in this week
        $accommodations = $this->getAccommodationsForWeek($assignments, $weekStart, $weekEnd);
        $vehicles = $this->getVehiclesForWeek($assignments, $weekStart, $weekEnd);
        
        // Get assigned employees with their details
        $assignedEmployees = $this->getAssignedEmployeesDetails($assignments, $weekStart, $weekEnd);
        
        return [
            'week' => $week,
            'demands' => $demands,
            'assignments' => $assignments,
            'requirements_summary' => $requirementsSummary,
            'accommodations' => $accommodations,
            'vehicles' => $vehicles,
            'assigned_employees' => $assignedEmployees,
            'has_data' => $demands->isNotEmpty() || $assignments->isNotEmpty(),
        ];
    }

    /**
     * Get demands that overlap with the week.
     */
    protected function getDemandsForWeek(Project $project, Carbon $weekStart, Carbon $weekEnd): Collection
    {
        return ProjectDemand::where('project_id', $project->id)
            ->where(function ($query) use ($weekStart, $weekEnd) {
                $query->where(function ($q) use ($weekStart, $weekEnd) {
                    // Demand starts in week
                    $q->whereBetween('date_from', [$weekStart, $weekEnd])
                      ->orWhereBetween('date_to', [$weekStart, $weekEnd])
                      // Demand covers the whole week
                      ->orWhere(function ($q2) use ($weekStart, $weekEnd) {
                          $q2->where('date_from', '<=', $weekStart)
                             ->where('date_to', '>=', $weekEnd);
                      });
                });
            })
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
     * Get assignments that overlap with the week.
     */
    protected function getAssignmentsForWeek(Project $project, Carbon $weekStart, Carbon $weekEnd): Collection
    {
        return ProjectAssignment::where('project_id', $project->id)
            ->where('status', 'active')
            ->where(function ($query) use ($weekStart, $weekEnd) {
                $query->where(function ($q) use ($weekStart, $weekEnd) {
                    // Assignment starts in week
                    $q->whereBetween('start_date', [$weekStart, $weekEnd])
                      ->orWhereBetween('end_date', [$weekStart, $weekEnd])
                      // Assignment covers the whole week
                      ->orWhere(function ($q2) use ($weekStart, $weekEnd) {
                          $q2->where('start_date', '<=', $weekStart)
                             ->where(function ($q3) use ($weekEnd) {
                                 $q3->where('end_date', '>=', $weekEnd)
                                    ->orWhereNull('end_date');
                             });
                      });
                });
            })
            ->with(['employee', 'role'])
            ->get();
    }

    /**
     * Calculate requirements summary (needed vs assigned).
     */
    protected function calculateRequirementsSummary(Collection $demands, Collection $assignments): array
    {
        $totalNeeded = 0;
        $totalAssigned = $assignments->count();
        $roleDetails = [];
        
        foreach ($demands as $roleId => $demandData) {
            $needed = $demandData['required_count'];
            $assigned = $assignments->where('role_id', $roleId)->count();
            
            $totalNeeded += $needed;
            
            $roleDetails[] = [
                'role' => $demandData['role'],
                'needed' => $needed,
                'assigned' => $assigned,
                'missing' => max(0, $needed - $assigned),
            ];
        }
        
        return [
            'total_needed' => $totalNeeded,
            'total_assigned' => $totalAssigned,
            'total_missing' => max(0, $totalNeeded - $totalAssigned),
            'role_details' => $roleDetails,
        ];
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
        $accommodationAssignments = AccommodationAssignment::whereIn('employee_id', $employeeIds)
            ->where(function ($query) use ($weekStart, $weekEnd) {
                $query->where(function ($q) use ($weekStart, $weekEnd) {
                    $q->whereBetween('start_date', [$weekStart, $weekEnd])
                      ->orWhereBetween('end_date', [$weekStart, $weekEnd])
                      ->orWhere(function ($q2) use ($weekStart, $weekEnd) {
                          $q2->where('start_date', '<=', $weekStart)
                             ->where(function ($q3) use ($weekEnd) {
                                 $q3->where('end_date', '>=', $weekEnd)
                                    ->orWhereNull('end_date');
                             });
                      });
                });
            })
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
            ->where(function ($query) use ($weekStart, $weekEnd) {
                $query->where(function ($q) use ($weekStart, $weekEnd) {
                    $q->whereBetween('start_date', [$weekStart, $weekEnd])
                      ->orWhereBetween('end_date', [$weekStart, $weekEnd])
                      ->orWhere(function ($q2) use ($weekStart, $weekEnd) {
                          $q2->where('start_date', '<=', $weekStart)
                             ->where(function ($q3) use ($weekEnd) {
                                 $q3->where('end_date', '>=', $weekEnd)
                                    ->orWhereNull('end_date');
                             });
                      });
                });
            })
            ->get()
            ->groupBy('accommodation_id');
        
        // Map accommodations with their counts
        return $accommodations->map(function ($accommodation) use ($allAccommodationAssignments) {
            $totalEmployeeCount = $allAccommodationAssignments->get($accommodation->id)?->count() ?? 0;
            
            return [
                'accommodation' => $accommodation,
                'employee_count' => $totalEmployeeCount,
                'capacity' => $accommodation->capacity,
                'usage' => "{$totalEmployeeCount}/{$accommodation->capacity}",
                'usage_percentage' => $accommodation->capacity > 0 
                    ? round(($totalEmployeeCount / $accommodation->capacity) * 100, 0) 
                    : 0,
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
        $vehicleAssignments = VehicleAssignment::whereIn('employee_id', $employeeIds)
            ->where(function ($query) use ($weekStart, $weekEnd) {
                $query->where(function ($q) use ($weekStart, $weekEnd) {
                    $q->whereBetween('start_date', [$weekStart, $weekEnd])
                      ->orWhereBetween('end_date', [$weekStart, $weekEnd])
                      ->orWhere(function ($q2) use ($weekStart, $weekEnd) {
                          $q2->where('start_date', '<=', $weekStart)
                             ->where(function ($q3) use ($weekEnd) {
                                 $q3->where('end_date', '>=', $weekEnd)
                                    ->orWhereNull('end_date');
                             });
                      });
                });
            })
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
            ->where(function ($query) use ($weekStart, $weekEnd) {
                $query->where(function ($q) use ($weekStart, $weekEnd) {
                    $q->whereBetween('start_date', [$weekStart, $weekEnd])
                      ->orWhereBetween('end_date', [$weekStart, $weekEnd])
                      ->orWhere(function ($q2) use ($weekStart, $weekEnd) {
                          $q2->where('start_date', '<=', $weekStart)
                             ->where(function ($q3) use ($weekEnd) {
                                 $q3->where('end_date', '>=', $weekEnd)
                                    ->orWhereNull('end_date');
                             });
                      });
                });
            })
            ->with('employee')
            ->get()
            ->groupBy('vehicle_id');
        
        // Map vehicles with their counts and drivers
        return $vehicles->map(function ($vehicle) use ($allVehicleAssignments) {
            $assignments = $allVehicleAssignments->get($vehicle->id) ?? collect();
            $totalEmployeeCount = $assignments->count();
            $driverAssignment = $assignments->first();
            
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
        $accommodationAssignments = AccommodationAssignment::whereIn('employee_id', $employeeIds)
            ->where(function ($query) use ($weekStart, $weekEnd) {
                $query->where(function ($q) use ($weekStart, $weekEnd) {
                    $q->whereBetween('start_date', [$weekStart, $weekEnd])
                      ->orWhereBetween('end_date', [$weekStart, $weekEnd])
                      ->orWhere(function ($q2) use ($weekStart, $weekEnd) {
                          $q2->where('start_date', '<=', $weekStart)
                             ->where(function ($q3) use ($weekEnd) {
                                 $q3->where('end_date', '>=', $weekEnd)
                                    ->orWhereNull('end_date');
                             });
                      });
                });
            })
            ->with('accommodation')
            ->get()
            ->groupBy('employee_id');
        
        // Eager load all vehicle assignments for these employees in this week (single query)
        $vehicleAssignments = VehicleAssignment::whereIn('employee_id', $employeeIds)
            ->where(function ($query) use ($weekStart, $weekEnd) {
                $query->where(function ($q) use ($weekStart, $weekEnd) {
                    $q->whereBetween('start_date', [$weekStart, $weekEnd])
                      ->orWhereBetween('end_date', [$weekStart, $weekEnd])
                      ->orWhere(function ($q2) use ($weekStart, $weekEnd) {
                          $q2->where('start_date', '<=', $weekStart)
                             ->where(function ($q3) use ($weekEnd) {
                                 $q3->where('end_date', '>=', $weekEnd)
                                    ->orWhereNull('end_date');
                             });
                      });
                });
            })
            ->with('vehicle')
            ->get()
            ->groupBy('employee_id');
        
        // Map assignments with their details
        return $assignments->map(function ($assignment) use ($accommodationAssignments, $vehicleAssignments, $weekStart, $weekEnd) {
            $employee = $assignment->employee;
            
            // Get accommodation and vehicle from pre-loaded collections
            $accommodationAssignment = $accommodationAssignments->get($employee->id)?->first();
            $vehicleAssignment = $vehicleAssignments->get($employee->id)?->first();
            
            // Check if assignment is partial (not full week)
            $isPartial = $assignment->start_date->gt($weekStart) || 
                        ($assignment->end_date && $assignment->end_date->lt($weekEnd));
            
            $assignmentStart = max($assignment->start_date, $weekStart);
            $assignmentEnd = min($assignment->end_date ?? $weekEnd, $weekEnd);
            
            return [
                'assignment' => $assignment,
                'employee' => $employee,
                'role' => $assignment->role,
                'accommodation' => $accommodationAssignment?->accommodation,
                'vehicle' => $vehicleAssignment?->vehicle,
                'is_partial' => $isPartial,
                'date_range' => $isPartial ? 
                    $assignmentStart->format('d.m') . '–' . $assignmentEnd->format('d.m') : 
                    'cały tydzień',
            ];
        });
    }
}

