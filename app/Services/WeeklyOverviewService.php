<?php

namespace App\Services;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Enums\VehiclePosition;
use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\LogisticsEvent;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\ProjectDemand;
use App\Models\Rotation;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Models\VehicleRepair;
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
            'label' => $weekStart->format('d.m').' – '.$weekEnd->format('d.m.Y'),
        ]];
    }

    /**
     * Get all projects with their weekly data.
     *
     * Loads week-scoped relations once (not per project) to avoid N×queries.
     */
    public function getProjectsWithWeeklyData(array $weeks): array
    {
        $weekStart = $weeks[0]['start'];
        $weekEnd = $weeks[0]['end'];

        $projects = Project::with([
            'location',
            'tasks.assignedTo',
            'tasks.createdBy',
        ])
            // Show only projects that overlap the selected week by at least one day.
            ->where(function ($q) use ($weekEnd) {
                $q->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', $weekEnd->toDateString());
            })
            ->where(function ($q) use ($weekStart) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $weekStart->toDateString());
            })
            ->get();

        $ctx = $this->loadWeekContext($projects->pluck('id'), $weekStart, $weekEnd);

        return $projects->map(function ($project) use ($weeks, $ctx) {
            $weeksData = [];

            foreach ($weeks as $week) {
                $weeksData[] = $this->assembleProjectWeekData($project, $week, $ctx);
            }

            return [
                'project' => $project,
                'weeks_data' => $weeksData,
            ];
        })->toArray();
    }

    /**
     * Get project data for a specific week.
     *
     * For a single project this still batch-loads that project's week context
     * (same shape as the multi-project path). Prefer getProjectsWithWeeklyData
     * when rendering many projects.
     */
    public function getProjectWeekData(Project $project, array $week): array
    {
        $ctx = $this->loadWeekContext(collect([$project->id]), $week['start'], $week['end']);

        return $this->assembleProjectWeekData($project, $week, $ctx);
    }

    /**
     * Batch-load everything needed to assemble per-project week cards.
     *
     * @param  Collection<int, int|string>  $projectIds
     * @return array<string, mixed>
     */
    protected function loadWeekContext(Collection $projectIds, Carbon $weekStart, Carbon $weekEnd): array
    {
        $projectIds = $projectIds->filter()->unique()->values();

        if ($projectIds->isEmpty()) {
            return $this->emptyWeekContext();
        }

        $demands = ProjectDemand::whereIn('project_id', $projectIds)
            ->overlappingWith($weekStart, $weekEnd)
            ->with('role')
            ->get();

        $assignments = ProjectAssignment::whereIn('project_id', $projectIds)
            ->overlappingWith($weekStart, $weekEnd)
            ->with(['employee.roles', 'role', 'project'])
            ->get();

        $employeeIds = $assignments->pluck('employee_id')->unique()->filter()->values();

        $employeeAccommodationAssignments = $employeeIds->isEmpty()
            ? collect()
            : AccommodationAssignment::whereIn('employee_id', $employeeIds)
                ->overlappingWith($weekStart, $weekEnd)
                ->with(['accommodation', 'employee'])
                ->get();

        $employeeVehicleAssignments = $employeeIds->isEmpty()
            ? collect()
            : VehicleAssignment::whereIn('employee_id', $employeeIds)
                ->overlappingWith($weekStart, $weekEnd)
                ->with(['vehicle', 'employee'])
                ->get();

        $accommodationIds = $employeeAccommodationAssignments->pluck('accommodation_id')->unique()->filter()->values();
        $vehicleIds = $employeeVehicleAssignments->pluck('vehicle_id')->unique()->filter()->values();

        $accommodations = $accommodationIds->isEmpty()
            ? collect()
            : Accommodation::whereIn('id', $accommodationIds)->get()->keyBy('id');

        $allAccommodationAssignments = $accommodationIds->isEmpty()
            ? collect()
            : AccommodationAssignment::whereIn('accommodation_id', $accommodationIds)
                ->overlappingWith($weekStart, $weekEnd)
                ->get();

        $vehicles = $vehicleIds->isEmpty()
            ? collect()
            : Vehicle::whereIn('id', $vehicleIds)->get()->keyBy('id');

        $allVehicleAssignments = $vehicleIds->isEmpty()
            ? collect()
            : VehicleAssignment::whereIn('vehicle_id', $vehicleIds)
                ->overlappingWith($weekStart, $weekEnd)
                ->with('employee')
                ->get();

        $returnTripsByVehicle = collect();
        $departureEventsByVehicle = collect();
        $transferEventsByVehicle = collect();

        if ($vehicleIds->isNotEmpty()) {
            $returnTripsByVehicle = LogisticsEvent::where('type', LogisticsEventType::RETURN)
                ->where('status', '!=', LogisticsEventStatus::CANCELLED)
                ->whereIn('vehicle_id', $vehicleIds)
                ->whereNotNull('end_date')
                ->whereBetween('end_date', [$weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay()])
                ->with(['participants.employee', 'vehicle'])
                ->get()
                ->groupBy('vehicle_id');

            $departureEventsByVehicle = LogisticsEvent::query()
                ->where('type', LogisticsEventType::DEPARTURE)
                ->where('status', '!=', LogisticsEventStatus::CANCELLED)
                ->whereNotNull('vehicle_id')
                ->whereIn('vehicle_id', $vehicleIds)
                ->where('event_date', '<=', $weekEnd->copy()->endOfDay())
                ->whereRaw('COALESCE(end_date, event_date) >= ?', [$weekStart->copy()->startOfDay()])
                ->orderBy('event_date')
                ->get()
                ->groupBy('vehicle_id');

            $transferEventsByVehicle = LogisticsEvent::query()
                ->where('type', LogisticsEventType::TRANSFER)
                ->where('status', '!=', LogisticsEventStatus::CANCELLED)
                ->whereNotNull('vehicle_id')
                ->whereIn('vehicle_id', $vehicleIds)
                ->whereBetween('event_date', [$weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay()])
                ->with(['fromLocation', 'toLocation'])
                ->orderBy('event_date')
                ->get()
                ->groupBy('vehicle_id');
        }

        $rotations = $employeeIds->isEmpty()
            ? collect()
            : Rotation::whereIn('employee_id', $employeeIds)
                ->where(function ($q) use ($weekStart, $weekEnd) {
                    $q->where(function ($query) use ($weekStart, $weekEnd) {
                        $query->whereDate('start_date', '<=', $weekEnd->toDateString())
                            ->whereDate('end_date', '>=', $weekStart->toDateString());
                    })->orWhereNull('end_date');
                })
                ->orderBy('end_date', 'asc')
                ->get()
                ->groupBy('employee_id');

        $serviceRepairs = VehicleRepair::whereIn('project_id', $projectIds)
            ->overlappingWith($weekStart, $weekEnd)
            ->with(['vehicle', 'location'])
            ->orderBy('start_date')
            ->get();

        return [
            'demands' => $demands,
            'assignments' => $assignments,
            'accommodation_assignments_by_employee' => $employeeAccommodationAssignments->groupBy('employee_id'),
            'vehicle_assignments_by_employee' => $employeeVehicleAssignments->groupBy('employee_id'),
            'accommodations' => $accommodations,
            'all_accommodation_assignments_by_accommodation' => $allAccommodationAssignments->groupBy('accommodation_id'),
            'vehicles' => $vehicles,
            'all_vehicle_assignments_by_vehicle' => $allVehicleAssignments->groupBy('vehicle_id'),
            'return_trips_by_vehicle' => $returnTripsByVehicle,
            'departure_events_by_vehicle' => $departureEventsByVehicle,
            'transfer_events_by_vehicle' => $transferEventsByVehicle,
            'rotations' => $rotations,
            'service_repairs' => $serviceRepairs,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyWeekContext(): array
    {
        return [
            'demands' => collect(),
            'assignments' => collect(),
            'accommodation_assignments_by_employee' => collect(),
            'vehicle_assignments_by_employee' => collect(),
            'accommodations' => collect(),
            'all_accommodation_assignments_by_accommodation' => collect(),
            'vehicles' => collect(),
            'all_vehicle_assignments_by_vehicle' => collect(),
            'return_trips_by_vehicle' => collect(),
            'departure_events_by_vehicle' => collect(),
            'transfer_events_by_vehicle' => collect(),
            'rotations' => collect(),
            'service_repairs' => collect(),
        ];
    }

    /**
     * Assemble one project's week card from a preloaded week context (no extra queries).
     *
     * @param  array<string, mixed>  $ctx
     */
    protected function assembleProjectWeekData(Project $project, array $week, array $ctx): array
    {
        $weekStart = $week['start'];
        $weekEnd = $week['end'];

        $demands = $this->groupDemandsForWeek(
            $ctx['demands']->where('project_id', $project->id)
        );

        $assignments = $ctx['assignments']->where('project_id', $project->id)->values();

        $requirementsSummary = $this->calculateRequirementsSummary($demands, $assignments, $weekStart, $weekEnd);

        $accommodations = $this->buildAccommodationsForWeek($assignments, $ctx);
        $vehicles = $this->buildVehiclesForWeek($assignments, $ctx);
        $assignedEmployees = $this->buildAssignedEmployeesDetails($assignments, $weekStart, $weekEnd, $ctx);

        $serviceRepairs = $ctx['service_repairs']
            ->where('project_id', $project->id)
            ->map(function (VehicleRepair $repair) {
                return [
                    'repair' => $repair,
                    'vehicle' => $repair->vehicle,
                    'vehicle_name' => $repair->vehicle
                        ? trim("{$repair->vehicle->brand} {$repair->vehicle->model} {$repair->vehicle->registration_number}")
                        : '—',
                ];
            })
            ->values();

        return [
            'week' => $week,
            'demands' => $demands,
            'assignments' => $assignments,
            'requirements_summary' => $requirementsSummary,
            'accommodations' => $accommodations,
            'vehicles' => $vehicles,
            'assigned_employees' => $assignedEmployees,
            'tasks' => $project->tasks,
            'service_repairs' => $serviceRepairs,
            'has_data' => $demands->isNotEmpty() || $assignments->isNotEmpty(),
        ];
    }

    /**
     * Group overlapping demands by role (in-memory).
     */
    protected function groupDemandsForWeek(Collection $demands): Collection
    {
        return $demands
            ->groupBy('role_id')
            ->map(function ($roleDemands) {
                return [
                    'role' => $roleDemands->first()->role,
                    'required_count' => $roleDemands->sum('required_count'),
                    'demands' => $roleDemands,
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
     *
     * Note: We don't use ->active() here because it only checks if assignment is active TODAY.
     * For weekly overview, we need assignments that are active during the week, even if the week is in the future.
     * The overlappingWith() scope already filters assignments that intersect with the week.
     */
    protected function getAssignmentsForWeek(Project $project, Carbon $weekStart, Carbon $weekEnd): Collection
    {
        return ProjectAssignment::where('project_id', $project->id)
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
                // Count UNIQUE employees per day (not assignments!)
                $assignedPerDay = [];
                foreach ($days as $day) {
                    $uniqueEmployeesOnDay = [];
                    foreach ($roleAssignments as $assignment) {
                        if ($assignment->start_date->lte($day) &&
                            ($assignment->end_date === null || $assignment->end_date->gte($day))) {
                            // Count unique employee, not assignment
                            $uniqueEmployeesOnDay[$assignment->employee_id] = true;
                        }
                    }
                    $assignedPerDay[] = count($uniqueEmployeesOnDay);
                }

                $assignedMin = min($assignedPerDay);
                $assignedMax = max($assignedPerDay);
                $assigned = $assignedMin === $assignedMax ? $assignedMin : null; // null means variable
                $isStable = $assignedMin === $assignedMax;
            } else {
                // Count unique employees (not assignments!)
                $uniqueEmployeeIds = $roleAssignments->pluck('employee_id')->unique();
                $assigned = $uniqueEmployeeIds->count();
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
        $assignmentsWithoutDemand = $assignments->filter(function ($assignment) use ($demandRoleIds) {
            return ! in_array($assignment->role_id, $demandRoleIds);
        });

        // Dodaj role bez zapotrzebowania jako nadmiar
        $excessRoles = [];
        foreach ($assignmentsWithoutDemand->groupBy('role_id') as $roleId => $roleAssignments) {
            $role = $roleAssignments->first()->role;
            // Count unique employees (not assignments!)
            $uniqueEmployeeIds = $roleAssignments->pluck('employee_id')->unique();
            $uniqueCount = $uniqueEmployeeIds->count();
            $excessRoles[] = [
                'role' => $role,
                'needed' => 0,
                'assigned' => $uniqueCount,
                'assigned_min' => $uniqueCount,
                'assigned_max' => $uniqueCount,
                'is_stable' => true,
                'missing' => 0,
                'excess' => $uniqueCount,
            ];
        }

        $roleDetails = array_merge($roleDetails, $excessRoles);

        // Oblicz całkowite wartości - tylko dla ról z zapotrzebowaniem
        $totalAssignedForNeededRoles = 0;
        $totalAssignedMin = 0;
        $totalAssignedMax = 0;
        $allStable = true;

        foreach ($roleDetails as $roleDetail) {
            if (! empty($roleDetail['assigned']) && $roleDetail['is_stable']) {
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
     * Build accommodations used by this project's assigned employees (from preloaded context).
     *
     * @param  array<string, mixed>  $ctx
     */
    protected function buildAccommodationsForWeek(Collection $assignments, array $ctx): Collection
    {
        $employeeIds = $assignments->pluck('employee_id')->unique()->filter();

        if ($employeeIds->isEmpty()) {
            return collect();
        }

        $accommodationIds = collect();
        foreach ($employeeIds as $employeeId) {
            $rows = $ctx['accommodation_assignments_by_employee']->get($employeeId) ?? collect();
            $accommodationIds = $accommodationIds->merge($rows->pluck('accommodation_id'));
        }
        $accommodationIds = $accommodationIds->unique()->filter()->values();

        if ($accommodationIds->isEmpty()) {
            return collect();
        }

        return $accommodationIds->map(function ($accommodationId) use ($ctx) {
            $accommodation = $ctx['accommodations']->get($accommodationId);
            if (! $accommodation) {
                return null;
            }

            $assignmentsForAccommodation = $ctx['all_accommodation_assignments_by_accommodation']->get($accommodationId) ?? collect();
            $uniqueEmployeeIds = $assignmentsForAccommodation->pluck('employee_id')->unique();
            $totalEmployeeCount = $uniqueEmployeeIds->count();

            return [
                'accommodation' => $accommodation,
                'employee_count' => $totalEmployeeCount,
                'capacity' => $accommodation->capacity,
                'usage' => "{$totalEmployeeCount}/{$accommodation->capacity}",
                'usage_percentage' => $accommodation->capacity > 0
                    ? round(($totalEmployeeCount / $accommodation->capacity) * 100, 0)
                    : 0,
                'assignments' => $assignmentsForAccommodation->values(),
            ];
        })->filter()->values();
    }

    /**
     * Build vehicles used by this project's assigned employees (from preloaded context).
     *
     * @param  array<string, mixed>  $ctx
     */
    protected function buildVehiclesForWeek(Collection $assignments, array $ctx): Collection
    {
        $employeeIds = $assignments->pluck('employee_id')->unique()->filter();

        if ($employeeIds->isEmpty()) {
            return collect();
        }

        $vehicleIds = collect();
        foreach ($employeeIds as $employeeId) {
            $rows = $ctx['vehicle_assignments_by_employee']->get($employeeId) ?? collect();
            $vehicleIds = $vehicleIds->merge($rows->pluck('vehicle_id'));
        }
        $vehicleIds = $vehicleIds->unique()->filter()->values();

        if ($vehicleIds->isEmpty()) {
            return collect();
        }

        return $vehicleIds->map(function ($vehicleId) use ($ctx) {
            $vehicle = $ctx['vehicles']->get($vehicleId);
            if (! $vehicle) {
                return null;
            }

            $assignmentsForVehicle = $ctx['all_vehicle_assignments_by_vehicle']->get($vehicleId) ?? collect();

            $regularAssignments = $assignmentsForVehicle->filter(fn ($assignment) => ! $assignment->is_return_trip);
            $returnTripAssignments = $assignmentsForVehicle->filter(fn ($assignment) => $assignment->is_return_trip);

            $uniqueEmployees = $regularAssignments->unique('employee_id');
            $totalEmployeeCount = $uniqueEmployees->count();

            $driverAssignment = $uniqueEmployees->first(function ($assignment) {
                $position = $assignment->position;
                if ($position instanceof VehiclePosition) {
                    return $position === VehiclePosition::DRIVER;
                }

                return $position === 'driver' || $position === VehiclePosition::DRIVER->value;
            });

            $returnTrip = $ctx['return_trips_by_vehicle']->get($vehicleId)?->first();

            $returnTripPassengers = collect();
            if ($returnTrip) {
                $returnTripPassengers = $returnTrip->participants
                    ->map(fn ($p) => $p->employee)
                    ->filter()
                    ->unique('id')
                    ->values();
            }

            foreach ($returnTripAssignments as $assignment) {
                if ($assignment->employee && ! $returnTripPassengers->contains('id', $assignment->employee_id)) {
                    $returnTripPassengers->push($assignment->employee);
                }
            }

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
                    $position = $assignment->position;
                    if ($position instanceof VehiclePosition) {
                        return $position === VehiclePosition::DRIVER ? 0 : 1;
                    }

                    return ($position === 'driver' || $position === VehiclePosition::DRIVER->value) ? 0 : 1;
                })->values(),
                'return_trip' => $returnTrip,
                'return_trip_assignments' => $returnTripAssignments->values(),
                'return_trip_passengers' => $returnTripPassengers,
                'departure_events' => ($ctx['departure_events_by_vehicle']->get($vehicleId) ?? collect())->values(),
                'transfer_events' => ($ctx['transfer_events_by_vehicle']->get($vehicleId) ?? collect())->values(),
            ];
        })->filter()->values();
    }

    /**
     * Build assigned employee rows for a project (from preloaded context).
     *
     * @param  array<string, mixed>  $ctx
     */
    protected function buildAssignedEmployeesDetails(
        Collection $assignments,
        Carbon $weekStart,
        Carbon $weekEnd,
        array $ctx
    ): Collection {
        if ($assignments->isEmpty()) {
            return collect();
        }

        $assignmentsByEmployee = $assignments->groupBy('employee_id');
        $uniqueEmployeeIds = $assignments->pluck('employee_id')->unique();
        $days = $this->getDaysInWeek($weekStart, $weekEnd);

        return $uniqueEmployeeIds->map(function ($employeeId) use (
            $assignmentsByEmployee,
            $weekStart,
            $weekEnd,
            $ctx,
            $days
        ) {
            $employeeAssignments = $assignmentsByEmployee->get($employeeId) ?? collect();
            $firstAssignment = $employeeAssignments->first();

            if (! $firstAssignment) {
                return null;
            }

            $employee = $firstAssignment->employee;
            $roleIds = $employeeAssignments->pluck('role_id')->unique();
            $isRoleStable = $roleIds->count() <= 1;

            $accommodationAssignment = ($ctx['accommodation_assignments_by_employee']->get($employeeId) ?? collect())->first();
            $employeeVehicleAssignments = $ctx['vehicle_assignments_by_employee']->get($employeeId) ?? collect();

            $hasVehicleAllDays = true;
            $anyDayAssigned = false;
            $firstVehicleAssignment = null;

            foreach ($days as $day) {
                $isAssignedOnDay = $employeeAssignments->contains(function ($ass) use ($day) {
                    return $ass->start_date->lte($day) &&
                           ($ass->end_date === null || $ass->end_date->gte($day));
                });

                if ($isAssignedOnDay) {
                    $anyDayAssigned = true;
                    $dayVehicleAssignment = $employeeVehicleAssignments->first(function ($vAssignment) use ($day) {
                        return ! $vAssignment->is_return_trip &&
                               $vAssignment->start_date->lte($day) &&
                               ($vAssignment->end_date === null || $vAssignment->end_date->gte($day));
                    });

                    if (! $dayVehicleAssignment) {
                        $hasVehicleAllDays = false;
                    } elseif (! $firstVehicleAssignment) {
                        $firstVehicleAssignment = $dayVehicleAssignment;
                    }
                }
            }

            $hasVehicleInWeek = $anyDayAssigned && $hasVehicleAllDays;
            $vehicleAssignment = $firstVehicleAssignment;

            $coversFullWeek = $employeeAssignments->contains(function ($ass) use ($weekStart, $weekEnd) {
                return $ass->start_date->lte($weekStart) &&
                       ($ass->end_date === null || $ass->end_date->gte($weekEnd));
            });
            $isPartial = ! $coversFullWeek;

            $assignmentStart = $employeeAssignments->min('start_date');
            $assignmentEnd = $employeeAssignments->max(function ($ass) {
                return $ass->end_date ?? Carbon::parse('2099-12-31');
            });

            $assignmentStart = max($assignmentStart, $weekStart);
            $assignmentEnd = min($assignmentEnd ?? $weekEnd, $weekEnd);

            $employeeRotations = $ctx['rotations']->get($employeeId);
            $activeRotation = $employeeRotations?->first();
            $rotationInfo = null;

            if ($activeRotation) {
                $daysLeft = now()->startOfDay()->diffInDays($activeRotation->end_date->startOfDay(), true);
                $rotationInfo = [
                    'id' => $activeRotation->id,
                    'rotation' => $activeRotation,
                    'start_date' => $activeRotation->start_date,
                    'end_date' => $activeRotation->end_date,
                    'days_left' => $daysLeft,
                ];
            }

            $dateRangeText = 'cały tydzień';
            if ($isPartial) {
                $startDay = $assignmentStart->format('N');
                $endDay = $assignmentEnd->format('N');
                $dayNames = ['', 'pon', 'wt', 'śr', 'czw', 'pt', 'sob', 'nie'];
                if ($startDay == $endDay) {
                    $dateRangeText = $dayNames[$startDay];
                } else {
                    $dateRangeText = $dayNames[$startDay].'-'.$dayNames[$endDay];
                }
            }

            return [
                'assignment' => $firstAssignment,
                'employee' => $employee,
                'role' => $firstAssignment->role,
                'role_stable' => $isRoleStable,
                'accommodation' => $accommodationAssignment?->accommodation,
                'accommodation_assignment' => $accommodationAssignment,
                'vehicle' => $vehicleAssignment?->vehicle,
                'vehicle_assignment' => $vehicleAssignment,
                'has_vehicle_in_week' => $hasVehicleInWeek,
                'is_partial' => $isPartial,
                'date_range' => $dateRangeText,
                'rotation' => $rotationInfo,
            ];
        })->filter()
            ->sortBy(fn ($item) => mb_strtolower($item['employee']->last_name.' '.$item['employee']->first_name))
            ->values();
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

        // Wiersze kalendarza pochodzą z przypisań do wybranego projektu, ale komórka „projekt”
        // musi pokazywać faktyczne przypisanie tego dnia (np. po transferze do innej budowy),
        // więc ładujemy wszystkie nakładające się ProjectAssignment dla tych pracowników.
        $allProjectAssignmentsForWeek = ProjectAssignment::whereIn('employee_id', $employeeIds)
            ->overlappingWith($weekStart, $weekEnd)
            ->with(['employee', 'role', 'project'])
            ->get();

        // Get return trips for employees in this week (exclude CANCELLED)
        // Use end_date (arrival date) instead of event_date (departure date)
        $returnTrips = \App\Models\LogisticsEvent::where('type', \App\Enums\LogisticsEventType::RETURN)
            ->where('status', '!=', \App\Enums\LogisticsEventStatus::CANCELLED)
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay()])
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
                // Use end_date (arrival date) instead of event_date (departure date)
                $arrivalDate = $returnTrip->end_date ? $returnTrip->end_date->format('Y-m-d') : $returnTrip->event_date->format('Y-m-d');
                if (! $returnTripsByEmployeeAndDate->has($employeeId)) {
                    $returnTripsByEmployeeAndDate->put($employeeId, collect());
                }
                $returnTripsByEmployeeAndDate->get($employeeId)->put($arrivalDate, $returnTrip);
            }
        }

        // Wyjazdy (dzień = event_date)
        $departures = LogisticsEvent::query()
            ->where('type', LogisticsEventType::DEPARTURE)
            ->where('status', '!=', LogisticsEventStatus::CANCELLED)
            ->whereBetween('event_date', [$weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay()])
            ->whereHas('participants', function ($q) use ($employeeIds) {
                $q->whereIn('employee_id', $employeeIds);
            })
            ->with(['participants.employee', 'vehicle', 'transport', 'fromLocation', 'toLocation'])
            ->get();

        $departuresByEmployeeAndDate = collect();
        foreach ($departures as $departure) {
            foreach ($departure->participants as $participant) {
                $employeeId = $participant->employee_id;
                $dayKey = $departure->event_date->format('Y-m-d');
                if (! $departuresByEmployeeAndDate->has($employeeId)) {
                    $departuresByEmployeeAndDate->put($employeeId, collect());
                }
                $departuresByEmployeeAndDate->get($employeeId)->put($dayKey, $departure);
            }
        }

        // Transfery (dzień = event_date)
        $transfers = LogisticsEvent::query()
            ->where('type', LogisticsEventType::TRANSFER)
            ->where('status', '!=', LogisticsEventStatus::CANCELLED)
            ->whereBetween('event_date', [$weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay()])
            ->whereHas('participants', function ($q) use ($employeeIds) {
                $q->whereIn('employee_id', $employeeIds);
            })
            ->with([
                'participants.employee',
                'vehicle',
                'fromLocation',
                'toLocation',
                'projectAssignments.project',
                'projectAssignments.role',
            ])
            ->get();

        $transfersByEmployeeAndDate = collect();
        foreach ($transfers as $transferEvent) {
            foreach ($transferEvent->participants as $participant) {
                $employeeId = $participant->employee_id;
                $dayKey = $transferEvent->event_date->format('Y-m-d');
                if (! $transfersByEmployeeAndDate->has($employeeId)) {
                    $transfersByEmployeeAndDate->put($employeeId, collect());
                }
                $transfersByEmployeeAndDate->get($employeeId)->put($dayKey, $transferEvent);
            }
        }

        // EAGER LOAD: Load all employees at once
        $employeesCollection = \App\Models\Employee::whereIn('id', $employeeIds)
            ->with('roles')
            ->get()
            ->keyBy('id');

        // EAGER LOAD: Get all accommodation assignments for all employees in the week (single query)
        $allAccommodationAssignments = AccommodationAssignment::whereIn('employee_id', $employeeIds)
            ->where(function ($query) use ($weekStart, $weekEnd) {
                $query->where(function ($q) use ($weekStart, $weekEnd) {
                    $q->where('start_date', '<=', $weekEnd)
                        ->where(function ($q2) use ($weekStart) {
                            $q2->whereNull('end_date')
                                ->orWhere('end_date', '>=', $weekStart);
                        });
                });
            })
            ->orderBy('start_date', 'desc')
            ->orderBy('id', 'desc')
            ->with('accommodation')
            ->get();

        // EAGER LOAD: Get all vehicle assignments for all employees in the week (single query, exclude return trips)
        $allVehicleAssignments = VehicleAssignment::whereIn('employee_id', $employeeIds)
            ->where('is_return_trip', false)
            ->where(function ($query) use ($weekStart, $weekEnd) {
                $query->where(function ($q) use ($weekStart, $weekEnd) {
                    $q->where('start_date', '<=', $weekEnd)
                        ->where(function ($q2) use ($weekStart) {
                            $q2->whereNull('end_date')
                                ->orWhere('end_date', '>=', $weekStart);
                        });
                });
            })
            ->orderBy('start_date', 'desc')
            ->orderBy('id', 'desc')
            ->with('vehicle')
            ->get();

        // EAGER LOAD: Get all accommodation IDs used in this week
        $accommodationIds = $allAccommodationAssignments->pluck('accommodation_id')->unique()->filter();

        // EAGER LOAD: Get all vehicle IDs used in this week
        $vehicleIds = $allVehicleAssignments->pluck('vehicle_id')->unique()->filter();

        // EAGER LOAD: Pre-calculate occupancy for all accommodations for all days (batch query)
        $accommodationOccupancyMap = [];
        if ($accommodationIds->isNotEmpty()) {
            foreach ($days as $day) {
                $dayDate = $day['date']->copy()->startOfDay();
                $occupancyCounts = AccommodationAssignment::whereIn('accommodation_id', $accommodationIds)
                    ->where(function ($query) use ($dayDate) {
                        $query->where('start_date', '<=', $dayDate)
                            ->where(function ($q2) use ($dayDate) {
                                $q2->whereNull('end_date')
                                    ->orWhere('end_date', '>=', $dayDate);
                            });
                    })
                    ->selectRaw('accommodation_id, COUNT(*) as count')
                    ->groupBy('accommodation_id')
                    ->pluck('count', 'accommodation_id')
                    ->toArray();

                foreach ($occupancyCounts as $accId => $count) {
                    $accommodationOccupancyMap[$day['date']->format('Y-m-d')][$accId] = $count;
                }
            }
        }

        // EAGER LOAD: Pre-calculate occupancy for all vehicles for all days (batch query)
        $vehicleOccupancyMap = [];
        if ($vehicleIds->isNotEmpty()) {
            foreach ($days as $day) {
                $dayDate = $day['date']->copy()->startOfDay();
                $occupancyCounts = VehicleAssignment::whereIn('vehicle_id', $vehicleIds)
                    ->where('is_return_trip', false)
                    ->where(function ($query) use ($dayDate) {
                        $query->where('start_date', '<=', $dayDate)
                            ->where(function ($q2) use ($dayDate) {
                                $q2->whereNull('end_date')
                                    ->orWhere('end_date', '>=', $dayDate);
                            });
                    })
                    ->selectRaw('vehicle_id, COUNT(*) as count')
                    ->groupBy('vehicle_id')
                    ->pluck('count', 'vehicle_id')
                    ->toArray();

                foreach ($occupancyCounts as $vehId => $count) {
                    $vehicleOccupancyMap[$day['date']->format('Y-m-d')][$vehId] = $count;
                }
            }
        }

        // Group assignments by employee_id and date for quick lookup
        $accommodationAssignmentsByEmployeeAndDate = collect();
        foreach ($allAccommodationAssignments as $assignment) {
            $employeeId = $assignment->employee_id;
            $startDate = $assignment->start_date->copy()->startOfDay();
            $endDate = $assignment->end_date ? $assignment->end_date->copy()->startOfDay() : $weekEnd->copy()->endOfDay();

            foreach ($days as $day) {
                $dayDate = $day['date']->copy()->startOfDay();
                if ($dayDate->gte($startDate) && $dayDate->lte($endDate)) {
                    $dayKey = $dayDate->format('Y-m-d');
                    if (! $accommodationAssignmentsByEmployeeAndDate->has($employeeId)) {
                        $accommodationAssignmentsByEmployeeAndDate->put($employeeId, collect());
                    }
                    if (! $accommodationAssignmentsByEmployeeAndDate->get($employeeId)->has($dayKey)) {
                        $accommodationAssignmentsByEmployeeAndDate->get($employeeId)->put($dayKey, collect());
                    }
                    $accommodationAssignmentsByEmployeeAndDate->get($employeeId)->get($dayKey)->push($assignment);
                }
            }
        }

        $vehicleAssignmentsByEmployeeAndDate = collect();
        foreach ($allVehicleAssignments as $assignment) {
            $employeeId = $assignment->employee_id;
            $startDate = $assignment->start_date->copy()->startOfDay();
            $endDate = $assignment->end_date ? $assignment->end_date->copy()->startOfDay() : $weekEnd->copy()->endOfDay();

            foreach ($days as $day) {
                $dayDate = $day['date']->copy()->startOfDay();
                if ($dayDate->gte($startDate) && $dayDate->lte($endDate)) {
                    $dayKey = $dayDate->format('Y-m-d');
                    if (! $vehicleAssignmentsByEmployeeAndDate->has($employeeId)) {
                        $vehicleAssignmentsByEmployeeAndDate->put($employeeId, collect());
                    }
                    if (! $vehicleAssignmentsByEmployeeAndDate->get($employeeId)->has($dayKey)) {
                        $vehicleAssignmentsByEmployeeAndDate->get($employeeId)->put($dayKey, collect());
                    }
                    $vehicleAssignmentsByEmployeeAndDate->get($employeeId)->get($dayKey)->push($assignment);
                }
            }
        }

        // For each employee, get daily data (using pre-loaded data)
        $employees = $employeeIds->map(function ($employeeId) use ($allProjectAssignmentsForWeek, $days, $returnTripsByEmployeeAndDate, $departuresByEmployeeAndDate, $transfersByEmployeeAndDate, $employeesCollection, $accommodationAssignmentsByEmployeeAndDate, $vehicleAssignmentsByEmployeeAndDate, $accommodationOccupancyMap, $vehicleOccupancyMap) {
            $employee = $employeesCollection->get($employeeId);
            if (! $employee) {
                return null;
            }

            // Get daily data for each day
            $dailyData = $days->map(function ($day) use ($employee, $allProjectAssignmentsForWeek, $returnTripsByEmployeeAndDate, $departuresByEmployeeAndDate, $transfersByEmployeeAndDate, $accommodationAssignmentsByEmployeeAndDate, $vehicleAssignmentsByEmployeeAndDate, $accommodationOccupancyMap, $vehicleOccupancyMap) {
                $dayDate = $day['date']->copy()->startOfDay();
                $dayDateString = $dayDate->format('Y-m-d');

                // Check if this is a return trip day for this employee
                $returnTrip = $returnTripsByEmployeeAndDate->get($employee->id)?->get($dayDateString);
                $departureEvent = $departuresByEmployeeAndDate->get($employee->id)?->get($dayDateString);
                $transferEvent = $transfersByEmployeeAndDate->get($employee->id)?->get($dayDateString);

                // Wszystkie aktywne przypisania projektowe tego dnia (wszystkie projekty; ta sama kolejność co w LocationTrackingService)
                $projectAssignmentsForDay = $allProjectAssignmentsForWeek
                    ->filter(function ($assignment) use ($employee, $dayDate) {
                        if ($assignment->employee_id !== $employee->id) {
                            return false;
                        }
                        $assignmentStart = $assignment->start_date->copy()->startOfDay();
                        $assignmentEnd = $assignment->end_date ? $assignment->end_date->copy()->startOfDay() : null;

                        return $assignmentStart->lte($dayDate)
                            && ($assignmentEnd === null || $assignmentEnd->gte($dayDate));
                    })
                    ->sort(function ($a, $b) {
                        $c = $b->start_date <=> $a->start_date;

                        return $c !== 0 ? $c : $b->id <=> $a->id;
                    })
                    ->values();

                $projectAssignment = $projectAssignmentsForDay->first();

                $accommodationAssignmentsForDay = $accommodationAssignmentsByEmployeeAndDate->get($employee->id)?->get($dayDateString) ?? collect();
                $accommodationAssignment = $accommodationAssignmentsForDay->first();
                $accommodation = $accommodationAssignment?->accommodation;

                $accommodationOccupancy = 0;
                if ($accommodation) {
                    $accommodationOccupancy = $accommodationOccupancyMap[$dayDateString][$accommodation->id] ?? 0;
                }

                $vehicleAssignmentsForDay = $vehicleAssignmentsByEmployeeAndDate->get($employee->id)?->get($dayDateString) ?? collect();
                $vehicleAssignment = $vehicleAssignmentsForDay->first();
                $vehicle = $vehicleAssignment?->vehicle;

                $vehicleOccupancy = 0;
                if ($vehicle) {
                    $vehicleOccupancy = $vehicleOccupancyMap[$dayDateString][$vehicle->id] ?? 0;
                }

                $resourcesOverlap = $projectAssignmentsForDay->count() > 1
                    || $accommodationAssignmentsForDay->count() > 1
                    || $vehicleAssignmentsForDay->count() > 1;

                $isAssigned = $projectAssignmentsForDay->isNotEmpty()
                    || $vehicleAssignmentsForDay->isNotEmpty()
                    || $accommodationAssignmentsForDay->isNotEmpty();

                return [
                    'date' => $dayDate,
                    'is_assigned' => $isAssigned,
                    'accommodation' => $accommodation,
                    'accommodation_assignment' => $accommodationAssignment,
                    'accommodation_assignments' => $accommodationAssignmentsForDay,
                    'accommodation_capacity' => $accommodation?->capacity,
                    'accommodation_occupancy' => $accommodationOccupancy,
                    'accommodation_occupancy_by_id' => $accommodationOccupancyMap[$dayDateString] ?? [],
                    'vehicle' => $vehicle,
                    'vehicle_capacity' => $vehicle?->capacity,
                    'vehicle_occupancy' => $vehicleOccupancy,
                    'vehicle_assignment' => $vehicleAssignment,
                    'vehicle_assignments' => $vehicleAssignmentsForDay,
                    'vehicle_occupancy_by_id' => $vehicleOccupancyMap[$dayDateString] ?? [],
                    'project_assignment' => $projectAssignment,
                    'project_assignments' => $projectAssignmentsForDay,
                    'project' => $projectAssignment?->project ?? null,
                    'resources_overlap' => $resourcesOverlap,
                    'return_trip' => $returnTrip,
                    'departure_event' => $departureEvent,
                    'transfer_event' => $transferEvent,
                ];
            });

            // Get unique accommodations and vehicles used in this week for this employee
            $uniqueAccommodations = collect();
            $uniqueVehicles = collect();

            foreach ($dailyData as $dayData) {
                foreach ($dayData['accommodation_assignments'] ?? [] as $aa) {
                    $acc = $aa?->accommodation;
                    if ($acc && ! $uniqueAccommodations->contains('id', $acc->id)) {
                        $uniqueAccommodations->push($acc);
                    }
                }
                foreach ($dayData['vehicle_assignments'] ?? [] as $va) {
                    $veh = $va?->vehicle;
                    if ($veh && ! $uniqueVehicles->contains('id', $veh->id)) {
                        $uniqueVehicles->push($veh);
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
     * Get employees who have vehicle or accommodation assignments but no project assignment in the week.
     * Returns collection of employees with their vehicle/accommodation info.
     */
    public function getEmployeesWithoutProjectButWithResources(Carbon $weekStart, Carbon $weekEnd): Collection
    {
        // Get all employees with vehicle assignments in this week
        $vehicleEmployeeIds = VehicleAssignment::where('is_return_trip', false)
            ->overlappingWith($weekStart, $weekEnd)
            ->pluck('employee_id')
            ->unique();

        // Get all employees with accommodation assignments in this week
        $accommodationEmployeeIds = AccommodationAssignment::overlappingWith($weekStart, $weekEnd)
            ->pluck('employee_id')
            ->unique();

        // Combine: employees who have vehicle OR accommodation
        $employeesWithResources = $vehicleEmployeeIds->merge($accommodationEmployeeIds)->unique();

        if ($employeesWithResources->isEmpty()) {
            return collect();
        }

        // Get all employees with project assignments in this week
        $employeesWithProjects = ProjectAssignment::overlappingWith($weekStart, $weekEnd)
            ->pluck('employee_id')
            ->unique();

        // Find employees who have resources but NO project
        $employeesWithoutProject = $employeesWithResources->diff($employeesWithProjects);

        if ($employeesWithoutProject->isEmpty()) {
            return collect();
        }

        // EAGER LOAD: Get all vehicle assignments for all employees at once
        $employeeIdsWithoutProject = $employeesWithoutProject->toArray();

        // Load employees with their assignments and roles
        $employees = \App\Models\Employee::whereIn('id', $employeeIdsWithoutProject)
            ->with('roles')
            ->get();
        $allVehicleAssignmentsForResources = VehicleAssignment::whereIn('employee_id', $employeeIdsWithoutProject)
            ->where('is_return_trip', false)
            ->overlappingWith($weekStart, $weekEnd)
            ->with('vehicle')
            ->get()
            ->groupBy('employee_id');

        // EAGER LOAD: Get all accommodation assignments for all employees at once
        $allAccommodationAssignmentsForResources = AccommodationAssignment::whereIn('employee_id', $employeeIdsWithoutProject)
            ->overlappingWith($weekStart, $weekEnd)
            ->with('accommodation')
            ->get()
            ->groupBy('employee_id');

        // Map to include resource info (using pre-loaded data)
        return $employees->map(function ($employee) use ($allVehicleAssignmentsForResources, $allAccommodationAssignmentsForResources) {
            // Get vehicle assignments from pre-loaded data
            $vehicleAssignments = $allVehicleAssignmentsForResources->get($employee->id) ?? collect();

            // Get accommodation assignments from pre-loaded data
            $accommodationAssignments = $allAccommodationAssignmentsForResources->get($employee->id) ?? collect();

            $vehicles = $vehicleAssignments->pluck('vehicle')->filter()->unique('id');
            $accommodations = $accommodationAssignments->pluck('accommodation')->filter()->unique('id');

            return [
                'employee' => $employee,
                'vehicle_assignments' => $vehicleAssignments,
                'accommodation_assignments' => $accommodationAssignments,
                'vehicles' => $vehicles,
                'accommodations' => $accommodations,
                'has_vehicle' => $vehicles->isNotEmpty(),
                'has_accommodation' => $accommodations->isNotEmpty(),
            ];
        })->filter(function ($data) {
            // Only include if they actually have at least one resource
            return $data['has_vehicle'] || $data['has_accommodation'];
        })->values();
    }

    /**
     * Terminated employees who still have a project / vehicle / accommodation
     * assignment overlapping the week — dangling assignments after termination.
     *
     * @return Collection<int, array{
     *   employee: \App\Models\Employee,
     *   project_assignments: Collection,
     *   vehicle_assignments: Collection,
     *   accommodation_assignments: Collection,
     * }>
     */
    public function getTerminatedEmployeesWithAssignments(Carbon $weekStart, Carbon $weekEnd): Collection
    {
        $terminatedIds = Employee::query()
            ->whereNotNull('terminated_at')
            ->pluck('id');

        if ($terminatedIds->isEmpty()) {
            return collect();
        }

        $projectByEmployee = ProjectAssignment::overlappingWith($weekStart, $weekEnd)
            ->whereIn('employee_id', $terminatedIds)
            ->with(['project', 'role'])
            ->get()
            ->groupBy('employee_id');

        $vehicleByEmployee = VehicleAssignment::where('is_return_trip', false)
            ->overlappingWith($weekStart, $weekEnd)
            ->whereIn('employee_id', $terminatedIds)
            ->with('vehicle')
            ->get()
            ->groupBy('employee_id');

        $accommodationByEmployee = AccommodationAssignment::overlappingWith($weekStart, $weekEnd)
            ->whereIn('employee_id', $terminatedIds)
            ->with('accommodation')
            ->get()
            ->groupBy('employee_id');

        $employeeIds = $projectByEmployee->keys()
            ->merge($vehicleByEmployee->keys())
            ->merge($accommodationByEmployee->keys())
            ->unique()
            ->values();

        if ($employeeIds->isEmpty()) {
            return collect();
        }

        $employees = Employee::query()
            ->whereIn('id', $employeeIds)
            ->with('roles')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->keyBy('id');

        return $employeeIds->map(function ($employeeId) use ($employees, $projectByEmployee, $vehicleByEmployee, $accommodationByEmployee) {
            $employee = $employees->get($employeeId);
            if (! $employee) {
                return null;
            }

            return [
                'employee' => $employee,
                'project_assignments' => $projectByEmployee->get($employeeId) ?? collect(),
                'vehicle_assignments' => $vehicleByEmployee->get($employeeId) ?? collect(),
                'accommodation_assignments' => $accommodationByEmployee->get($employeeId) ?? collect(),
            ];
        })->filter()->values();
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

                // Calculate assigned count for this day - count UNIQUE employees (not assignments!)
                $uniqueEmployeesOnDay = [];
                foreach ($assignments as $assignment) {
                    if ($assignment->role_id == $roleId) {
                        $assignmentStart = $assignment->start_date ? $assignment->start_date->copy()->startOfDay() : null;
                        $assignmentEnd = $assignment->end_date ? $assignment->end_date->copy()->endOfDay() : null;

                        if ($assignmentStart && $dayDate->gte($assignmentStart) && ($assignmentEnd === null || $dayDate->lte($assignmentEnd))) {
                            // Count unique employee, not assignment
                            $uniqueEmployeesOnDay[$assignment->employee_id] = true;
                        }
                    }
                }
                $assignedCount = count($uniqueEmployeesOnDay);

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
