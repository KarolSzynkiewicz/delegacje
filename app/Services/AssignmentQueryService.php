<?php

namespace App\Services;

use App\Models\ProjectAssignment;
use App\Models\VehicleAssignment;
use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\LogisticsEvent;
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
     * @param array $employeeIds
     * @param Carbon $date
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
     * - NOT assigned to any project in the date range
     * - Has active rotation for the entire date range
     * - Has all required documents active for the entire date range
     * - NOT in transit (traveling) during the date range
     * 
     * @param Carbon $startDate Departure date
     * @param Carbon|null $endDate Arrival date (optional, defaults to startDate)
     * @return Collection<Employee>
     */
    public function getAvailableEmployeesForDeparture(Carbon $startDate, ?Carbon $endDate = null): Collection
    {
        $endDate = $endDate ?? $startDate;
        
        // Get all employees
        $allEmployees = Employee::with(['rotations', 'employeeDocuments.document', 'assignments'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // Filter employees who are available for the entire period
        return $allEmployees->filter(function (Employee $employee) use ($startDate, $endDate) {
            // 1. Check if employee is NOT assigned to any project during the date range
            // IMPORTANT: Exclude cancelled assignments
            $hasProjectAssignment = ProjectAssignment::where('employee_id', $employee->id)
                ->where('is_cancelled', false)
                ->overlappingWith($startDate, $endDate)
                ->exists();
            
            if ($hasProjectAssignment) {
                return false;
            }

            // 2. Check if employee has active rotation for the entire date range
            // Use eager loaded relations if available, otherwise query
            $hasActiveRotation = false;
            if ($employee->relationLoaded('rotations')) {
                // Use eager loaded rotations - check if any rotation covers the entire range
                $hasActiveRotation = $employee->rotations->filter(function ($rotation) use ($startDate, $endDate) {
                    return $rotation->isActiveAt($startDate) && 
                           ($rotation->end_date === null || $rotation->end_date->gte($endDate));
                })->isNotEmpty();
            } else {
                // Fallback to query - check if rotation covers entire range
                $hasActiveRotation = $employee->rotations()
                    ->where('start_date', '<=', $startDate)
                    ->where(function($q) use ($endDate) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', $endDate);
                    })
                    ->exists();
            }
            
            if (!$hasActiveRotation) {
                return false;
            }

            // 3. Check if employee has all required documents active for the entire date range
            // Ensure documents are loaded
            if (!$employee->relationLoaded('employeeDocuments')) {
                $employee->load('employeeDocuments.document');
            }
            
            if (!$employee->hasAllDocumentsActiveInDateRange($startDate, $endDate)) {
                return false;
            }

            // 4. Check if employee is NOT in transit (traveling) during the date range
            // Employee is in transit if they have an active departure/return event
            // that overlaps with the departure period (event_date <= endDate AND (end_date >= startDate OR end_date is null))
            $isInTransit = LogisticsEvent::whereHas('participants', function($q) use ($employee) {
                    $q->where('employee_id', $employee->id);
                })
                ->whereIn('type', [\App\Enums\LogisticsEventType::DEPARTURE, \App\Enums\LogisticsEventType::RETURN])
                ->whereIn('status', [\App\Enums\LogisticsEventStatus::PLANNED, \App\Enums\LogisticsEventStatus::COMPLETED])
                ->where('event_date', '<=', $endDate) // Event started before or on end date
                ->where(function($q) use ($startDate) {
                    $q->whereNull('end_date')
                      ->orWhere('end_date', '>=', $startDate); // Event ends after or on start date
                })
                ->exists();
            
            if ($isInTransit) {
                return false;
            }

            return true;
        })->values();
    }
}
