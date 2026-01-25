<?php

namespace App\Services;

use App\Models\ProjectAssignment;
use App\Models\VehicleAssignment;
use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Contracts\HasEmployee;
use App\Contracts\HasDateRange;
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
     * Returns Collection of HasEmployee & HasDateRange implementations.
     * Includes: ProjectAssignment, AccommodationAssignment, VehicleAssignment
     * 
     * @param array $employeeIds
     * @param Carbon $date
     * @return Collection<HasEmployee&HasDateRange>
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

        // Get vehicle assignments
        $vehicleAssignments = VehicleAssignment::whereIn('employee_id', $employeeIds)
            ->activeAtDate($date)
            ->get();

        $assignments = $assignments->merge($vehicleAssignments);

        return $assignments;
    }

    /**
     * Check if employee has any active assignment at a specific date.
     * 
     * @param int $employeeId
     * @param Carbon $date
     * @return bool
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
     * @param Carbon $date
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
     * 
     * @param int $employeeId
     * @param Carbon $date
     * @return VehicleAssignment|null
     */
    public function getActiveVehicleAssignment(int $employeeId, Carbon $date): ?VehicleAssignment
    {
        return VehicleAssignment::where('employee_id', $employeeId)
            ->activeAtDate($date)
            ->first();
    }

    /**
     * Get available employees for departure (not in projects, with active rotation, with all required documents).
     * 
     * Available means:
     * - NOT assigned to any project at the given date
     * - Has active rotation at the given date
     * - Has all required documents active at the given date
     * 
     * @param Carbon $date
     * @return Collection<Employee>
     */
    public function getAvailableEmployeesForDeparture(Carbon $date): Collection
    {
        // Get all employees
        $allEmployees = Employee::with(['rotations', 'employeeDocuments.document', 'assignments'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // Filter employees who are available
        return $allEmployees->filter(function (Employee $employee) use ($date) {
            // 1. Check if employee is NOT assigned to any project at the given date
            $hasProjectAssignment = ProjectAssignment::where('employee_id', $employee->id)
                ->activeAtDate($date)
                ->exists();
            
            if ($hasProjectAssignment) {
                return false;
            }

            // 2. Check if employee has active rotation at the given date
            $hasActiveRotation = $employee->rotations()
                ->activeAtDate($date)
                ->exists();
            
            if (!$hasActiveRotation) {
                return false;
            }

            // 3. Check if employee has all required documents active at the given date
            // Use a single date for document check (departure is a single date event)
            if (!$employee->hasAllDocumentsActiveInDateRange($date, $date)) {
                return false;
            }

            return true;
        })->values();
    }
}
