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
     * Get weeks data for the overview (current week + 2 next weeks).
     */
    public function getWeeks(): array
    {
        $weeks = [];
        $startOfWeek = Carbon::now()->startOfWeek();
        
        for ($i = 0; $i < 3; $i++) {
            $weekStart = $startOfWeek->copy()->addWeeks($i);
            $weekEnd = $weekStart->copy()->endOfWeek();
            
            $weeks[] = [
                'number' => $i + 1,
                'start' => $weekStart,
                'end' => $weekEnd,
                'start_formatted' => $weekStart->format('d.m.Y'),
                'end_formatted' => $weekEnd->format('d.m.Y'),
                'label' => $weekStart->format('d.m') . ' – ' . $weekEnd->format('d.m.Y'),
            ];
        }
        
        return $weeks;
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
     */
    protected function getAccommodationsForWeek(Collection $assignments, Carbon $weekStart, Carbon $weekEnd): Collection
    {
        $employeeIds = $assignments->pluck('employee_id')->unique();
        
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
        
        // Group by accommodation and count employees
        return $accommodationAssignments
            ->groupBy('accommodation_id')
            ->map(function ($assignments) use ($weekStart, $weekEnd) {
                $accommodation = $assignments->first()->accommodation;
                $employeeCount = $assignments->count();
                
                return [
                    'accommodation' => $accommodation,
                    'employee_count' => $employeeCount,
                    'capacity' => $accommodation->capacity,
                    'usage' => "{$employeeCount}/{$accommodation->capacity}",
                ];
            })
            ->values();
    }

    /**
     * Get vehicles used in this week by assigned employees.
     */
    protected function getVehiclesForWeek(Collection $assignments, Carbon $weekStart, Carbon $weekEnd): Collection
    {
        $employeeIds = $assignments->pluck('employee_id')->unique();
        
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
        
        // Group by vehicle and get driver
        return $vehicleAssignments
            ->groupBy('vehicle_id')
            ->map(function ($assignments) {
                $vehicle = $assignments->first()->vehicle;
                $driver = $assignments->first()->employee; // First employee is driver
                
                return [
                    'vehicle' => $vehicle,
                    'driver' => $driver,
                    'vehicle_name' => "{$vehicle->brand} {$vehicle->model} {$vehicle->registration_number}",
                ];
            })
            ->values();
    }

    /**
     * Get assigned employees with their accommodation and vehicle details.
     */
    protected function getAssignedEmployeesDetails(Collection $assignments, Carbon $weekStart, Carbon $weekEnd): Collection
    {
        return $assignments->map(function ($assignment) use ($weekStart, $weekEnd) {
            $employee = $assignment->employee;
            
            // Get accommodation for this employee in this week
            $accommodationAssignment = AccommodationAssignment::where('employee_id', $employee->id)
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
                ->first();
            
            // Get vehicle for this employee in this week
            $vehicleAssignment = VehicleAssignment::where('employee_id', $employee->id)
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
                ->first();
            
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

