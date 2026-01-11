<?php

namespace App\Services;

use App\Models\ProjectAssignment;
use App\Models\VehicleAssignment;
use App\Models\AccommodationAssignment;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Contracts\AssignmentContract;
use App\Enums\AssignmentStatus;
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
    public function __construct(
        protected EmployeeRepositoryInterface $employeeRepository
    ) {}
    /**
     * Get all active assignments for employees at a specific date.
     * 
     * Returns Collection of AssignmentContract implementations.
     * Includes: ProjectAssignment, AccommodationAssignment, VehicleAssignment
     * 
     * @param array $employeeIds
     * @param Carbon $date
     * @return Collection<AssignmentContract>
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
        return $this->employeeRepository->withActiveProjectOrAccommodationAssignmentsAt($date);
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
}
