<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Vehicle;
use App\Models\Location;
use App\Contracts\AssignmentContract;
use App\Enums\AssignmentStatus;
use Illuminate\Support\Facades\Cache;

/**
 * Service for tracking current location of employees, vehicles, and assignments.
 * 
 * IMPORTANT: This service must be DETERMINISTIC and IDEMPOTENT.
 * 
 * Rules:
 * - Same data state → always same location
 * - Multiple calls with same data → same result
 * - No heuristics dependent on if-order
 * 
 * Priority rules for employee location:
 * 1. VehicleAssignment with status 'in_transit' → vehicle's current_location_id
 * 2. AccommodationAssignment with status 'active' → accommodation's location_id
 * 3. ProjectAssignment with status 'active' → project's location_id
 * 4. No active assignments → base (Location::getBase())
 * 
 * In case of conflicts (e.g., active project + active vehicle in_transit):
 * Priority: VehicleAssignment > AccommodationAssignment > ProjectAssignment
 */
class LocationTrackingService
{
    /**
     * Get the current location of an employee.
     * 
     * DETERMINISTIC: Same employee state → always same location
     * IDEMPOTENT: Multiple calls → same result
     * 
     * @param Employee $employee
     * @return Location|null
     */
    public function forEmployee(Employee $employee): ?Location
    {
        return Cache::remember(
            "employee_location_{$employee->id}",
            now()->addMinutes(5),
            fn() => $this->calculateEmployeeLocation($employee)
        );
    }

    /**
     * Get the current location of a vehicle.
     * 
     * @param Vehicle $vehicle
     * @return Location|null
     */
    public function forVehicle(Vehicle $vehicle): ?Location
    {
        return Cache::remember(
            "vehicle_location_{$vehicle->id}",
            now()->addMinutes(5),
            fn() => $this->calculateVehicleLocation($vehicle)
        );
    }

    /**
     * Get the location for a specific assignment.
     * 
     * @param AssignmentContract $assignment
     * @return Location|null
     */
    public function forAssignment(AssignmentContract $assignment): ?Location
    {
        // ProjectAssignment → project's location
        if ($assignment instanceof \App\Models\ProjectAssignment) {
            return $assignment->project?->location;
        }

        // VehicleAssignment → vehicle's current_location_id (if in_transit)
        if ($assignment instanceof \App\Models\VehicleAssignment) {
            if ($assignment->isInTransit()) {
                return $assignment->vehicle?->currentLocation;
            }
        }

        // AccommodationAssignment → accommodation's location_id
        if ($assignment instanceof \App\Models\AccommodationAssignment) {
            return $assignment->accommodation?->location;
        }

        return null;
    }

    /**
     * Calculate employee location based on priority rules.
     * 
     * PRIORITY ORDER (strict, no heuristics):
     * 1. VehicleAssignment IN_TRANSIT → vehicle location
     * 2. AccommodationAssignment ACTIVE → accommodation location
     * 3. ProjectAssignment ACTIVE → project location
     * 4. Default → base
     * 
     * @param Employee $employee
     * @return Location|null
     */
    private function calculateEmployeeLocation(Employee $employee): ?Location
    {
        // Priority 1: VehicleAssignment with status IN_TRANSIT
        $vehicleAssignment = $employee->vehicleAssignments()
            ->where('status', AssignmentStatus::IN_TRANSIT)
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->with('vehicle.currentLocation')
            ->first();

        if ($vehicleAssignment && $vehicleAssignment->vehicle?->currentLocation) {
            return $vehicleAssignment->vehicle->currentLocation;
        }

        // Priority 2: AccommodationAssignment with status ACTIVE
        $accommodationAssignment = $employee->accommodationAssignments()
            ->where('status', AssignmentStatus::ACTIVE)
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->with('accommodation.location')
            ->first();

        if ($accommodationAssignment && $accommodationAssignment->accommodation?->location) {
            return $accommodationAssignment->accommodation->location;
        }

        // Priority 3: ProjectAssignment with status ACTIVE
        $projectAssignment = $employee->projectAssignments()
            ->where('status', AssignmentStatus::ACTIVE)
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->with('project.location')
            ->first();

        if ($projectAssignment && $projectAssignment->project?->location) {
            return $projectAssignment->project->location;
        }

        // Priority 4: Default → base
        return Location::getBase();
    }

    /**
     * Calculate vehicle location.
     * 
     * @param Vehicle $vehicle
     * @return Location|null
     */
    private function calculateVehicleLocation(Vehicle $vehicle): ?Location
    {
        // If vehicle has current_location_id, use it
        if ($vehicle->current_location_id) {
            return $vehicle->currentLocation;
        }

        // Otherwise, check active assignment and infer location
        $activeAssignment = $vehicle->assignments()
            ->where('status', AssignmentStatus::ACTIVE)
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->with('employee')
            ->first();

        if ($activeAssignment) {
            // Vehicle location = employee location
            return $this->forEmployee($activeAssignment->employee);
        }

        // Default → base
        return Location::getBase();
    }
}
