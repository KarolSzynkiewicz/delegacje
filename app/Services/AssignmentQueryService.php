<?php

namespace App\Services;

use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\LogisticsEvent;
use App\Models\ProjectAssignment;
use App\Models\VehicleAssignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service for querying assignments.
 *
 * Centralizes common assignment queries to avoid duplication.
 * Follows DRY principle.
 */
class AssignmentQueryService
{
    /**
     * Get all active assignments for employees at a specific date.
     *
     * Returns Collection of assignment models.
     * Includes: ProjectAssignment, AccommodationAssignment, VehicleAssignment
     *
     * @return Collection<ProjectAssignment|VehicleAssignment|AccommodationAssignment>
     */
    public function getActiveAssignmentsForEmployees(array $employeeIds, Carbon $date): Collection
    {
        $assignments = collect();

        // Get project assignments
        $projectAssignments = ProjectAssignment::whereIn('employee_id', $employeeIds)
            ->activeAtDate($date)
            ->get();

        $assignments = $assignments->merge($projectAssignments);

        // Get accommodation assignments
        $accommodationAssignments = AccommodationAssignment::whereIn('employee_id', $employeeIds)
            ->activeAtDate($date)
            ->get();

        $assignments = $assignments->merge($accommodationAssignments);

        // Get vehicle assignments (operacyjne — bez legów zjazdowych z flagą is_return_trip)
        $vehicleAssignments = VehicleAssignment::whereIn('employee_id', $employeeIds)
            ->where('is_return_trip', false)
            ->activeAtDate($date)
            ->get();

        $assignments = $assignments->merge($vehicleAssignments);

        return $assignments;
    }

    /**
     * Check if employee has any active assignment at a specific date.
     */
    public function hasActiveAssignment(int $employeeId, Carbon $date): bool
    {
        return ProjectAssignment::where('employee_id', $employeeId)
            ->activeAtDate($date)
            ->exists() ||
            AccommodationAssignment::where('employee_id', $employeeId)
                ->activeAtDate($date)
                ->exists();
    }

    /**
     * Get employees with active assignments at a specific date.
     *
     * @return Collection<Employee>
     */
    public function getEmployeesWithActiveAssignments(Carbon $date): Collection
    {
        return Employee::whereHas('assignments', function ($query) use ($date) {
            $query->activeAtDate($date);
        })
            ->orWhereHas('accommodationAssignments', function ($query) use ($date) {
                $query->activeAtDate($date);
            })
            ->with(['assignments' => function ($query) use ($date) {
                $query->activeAtDate($date);
            }])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Get active vehicle assignment for employee at a specific date.
     */
    public function getActiveVehicleAssignment(int $employeeId, Carbon $date): ?VehicleAssignment
    {
        return VehicleAssignment::where('employee_id', $employeeId)
            ->where('is_return_trip', false)
            ->activeAtDate($date)
            ->first();
    }

    /**
     * Get available employees for departure (not in projects, with active rotation, with all required documents).
     *
     * Available means:
     * - NOT assigned to any project in the date range
     * - Has active rotation for the entire date range
     * - Has all required documents active for the entire date range
     * - NOT in transit (traveling) during the date range
     *
     * @param  Carbon  $startDate  Departure date
     * @param  Carbon|null  $endDate  Arrival date (optional, defaults to startDate)
     * @return Collection<Employee>
     */
    public function getAvailableEmployeesForDeparture(Carbon $startDate, ?Carbon $endDate = null): Collection
    {
        $endDate = $endDate ?? $startDate;

        // OPTIMIZATION: Pre-fetch all data to avoid N+1 queries
        // Get all employees with eager loading
        $allEmployees = Employee::with([
            'rotations',
            'employeeDocuments.document',
            'assignments' => function ($query) use ($startDate, $endDate) {
                $query->overlappingWith($startDate, $endDate);
            },
        ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // OPTIMIZATION: Get all employee IDs with project assignments in one query
        $employeesWithProjectAssignments = ProjectAssignment::overlappingWith($startDate, $endDate)
            ->pluck('employee_id')
            ->unique()
            ->toArray();

        // OPTIMIZATION: Get all employee IDs in transit in one query
        $employeesInTransit = LogisticsEvent::whereHas('participants', function ($q) {
            // No need to filter by employee_id here - we'll check in the main query
        })
            ->whereIn('type', [\App\Enums\LogisticsEventType::DEPARTURE, \App\Enums\LogisticsEventType::RETURN])
            ->whereIn('status', [\App\Enums\LogisticsEventStatus::PLANNED, \App\Enums\LogisticsEventStatus::COMPLETED])
            ->where('event_date', '<=', $endDate)
            ->where(function ($q) use ($startDate) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $startDate);
            })
            ->with('participants')
            ->get()
            ->flatMap(function ($event) {
                return $event->participants->pluck('employee_id');
            })
            ->unique()
            ->toArray();

        // Filter employees who are available for the entire period
        return $allEmployees->filter(function (Employee $employee) use ($startDate, $endDate, $employeesWithProjectAssignments, $employeesInTransit) {
            // 1. Check if employee is NOT assigned to any project during the date range
            // Use pre-fetched list instead of querying for each employee
            if (in_array($employee->id, $employeesWithProjectAssignments)) {
                return false;
            }

            // 2. Check if employee has active rotation for the entire date range
            // Use eager loaded rotations
            $hasActiveRotation = $employee->rotations->filter(function ($rotation) use ($startDate, $endDate) {
                return $rotation->isActiveAt($startDate) &&
                       ($rotation->end_date === null || $rotation->end_date->gte($endDate));
            })->isNotEmpty();

            if (! $hasActiveRotation) {
                return false;
            }

            // 3. Check if employee has all required documents active for the entire date range
            // Documents are already eager loaded
            if (! $employee->hasAllDocumentsActiveInDateRange($startDate, $endDate)) {
                return false;
            }

            // 4. Check if employee is NOT in transit (traveling) during the date range
            // Use pre-fetched list instead of querying for each employee
            if (in_array($employee->id, $employeesInTransit)) {
                return false;
            }

            return true;
        })->values();
    }
}
