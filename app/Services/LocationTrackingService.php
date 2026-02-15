<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Vehicle;
use App\Models\Location;
use App\Models\ProjectAssignment;
use App\Models\VehicleAssignment;
use App\Models\AccommodationAssignment;
use App\Models\LogisticsEvent;
use App\Enums\LogisticsEventType;
use App\Enums\LogisticsEventStatus;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

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
 * NEW MODEL (2026-02-15):
 * Employee location is determined by:
 * 1. in_transit (LogisticsEvent between event_date and end_date)
 * 2. accommodation_location (AccommodationAssignment active on date)
 * 3. project_location (ProjectAssignment active on date)
 * 4. outside_base flag (Employee.outside_base)
 * 
 * Function getLocationStatus() returns all 4 pieces of information.
 */
class LocationTrackingService
{
    /**
     * Get complete location status for employee on specific date.
     * 
     * Returns array with:
     * - accommodation_location: Location|null (where employee lives during project)
     * - project_location: Location|null (where employee works)
     * - in_transit: bool (is traveling between locations)
     * - outside_base: bool (is outside base location)
     * 
     * @param Employee $employee
     * @param Carbon $date
     * @return array
     */
    public function getLocationStatus(Employee $employee, Carbon $date): array
    {
        return Cache::remember(
            "employee_location_status_{$employee->id}_{$date->format('Y-m-d')}",
            now()->addMinutes(5),
            fn() => $this->calculateLocationStatus($employee, $date)
        );
    }

    /**
     * Calculate location status for employee on date.
     * 
     * @param Employee $employee
     * @param Carbon $date
     * @return array
     */
    protected function calculateLocationStatus(Employee $employee, Carbon $date): array
    {
        // 1. Sync outside_base flag (ensure it's current)
        $this->syncOutsideBaseFlag($employee, $date);
        
        // 2. Check if in transit
        $inTransit = LogisticsEvent::isEmployeeInTransit($employee, $date);
        
        // 3. Get accommodation location
        $accommodationLocation = $this->getAccommodationLocationOnDate($employee, $date);
        
        // 4. Get project location
        $projectLocation = $this->getProjectLocationOnDate($employee, $date);
        
        return [
            'accommodation_location' => $accommodationLocation,
            'project_location' => $projectLocation,
            'in_transit' => $inTransit,
            'outside_base' => (bool) $employee->outside_base,
        ];
    }

    /**
     * Sync outside_base flag based on logistics events and assignments.
     * Ensures flag is always accurate for given date.
     * 
     * @param Employee $employee
     * @param Carbon $date
     * @return void
     */
    /**
     * Synchronize the outside_base flag for an employee on a specific date.
     * 
     * @param Employee $employee
     * @param Carbon $date
     * @return void
     */
    public function syncOutsideBaseFlag(Employee $employee, Carbon $date): void
    {
        $shouldBeOutside = false;
        
        // Check last logistics event up to this date (ignore cancelled events)
        $lastEvent = LogisticsEvent::whereHas('participants', 
            fn($q) => $q->where('employee_id', $employee->id)
        )
        ->whereIn('type', [LogisticsEventType::DEPARTURE, LogisticsEventType::RETURN])
        ->whereIn('status', [LogisticsEventStatus::PLANNED, LogisticsEventStatus::COMPLETED])
        ->where(function($q) use ($date) {
            // Events that started or ended by this date
            $q->where('event_date', '<=', $date)
              ->orWhere('end_date', '<=', $date);
        })
        ->orderBy('event_date', 'desc')
        ->orderBy('end_date', 'desc')
        ->first();
        
        if ($lastEvent) {
            // DEPARTURE event_date <= date → outside_base = true
            if ($lastEvent->type === LogisticsEventType::DEPARTURE && 
                $lastEvent->event_date->lte($date)) {
                $shouldBeOutside = true;
            }
            
            // RETURN end_date <= date → outside_base = false
            // (Return overrides departure)
            if ($lastEvent->type === LogisticsEventType::RETURN && 
                $lastEvent->end_date && 
                $lastEvent->end_date->lte($date)) {
                $shouldBeOutside = false;
            }
        }
        
        // Check active assignments (can force outside_base = true)
        if (!$shouldBeOutside) {
            $hasActiveProject = $employee->assignments()
                ->where('is_cancelled', false)
                ->where('start_date', '<=', $date)
                ->where(function ($q) use ($date) {
                    $q->whereNull('end_date')
                      ->orWhere('end_date', '>=', $date);
                })
                ->exists();
            
            if (!$hasActiveProject) {
                $hasActiveAccommodation = $employee->accommodationAssignments()
                    ->where('start_date', '<=', $date)
                    ->where(function ($q) use ($date) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', $date);
                    })
                    ->exists();
                
                $shouldBeOutside = $hasActiveAccommodation;
            } else {
                $shouldBeOutside = true;
            }
        }
        
        // Update flag if changed (quietly - no events)
        if ($employee->outside_base !== $shouldBeOutside) {
            $employee->outside_base = $shouldBeOutside;
            $employee->saveQuietly();
        }
    }

    /**
     * Get accommodation location on specific date.
     * 
     * @param Employee $employee
     * @param Carbon $date
     * @return Location|null
     */
    protected function getAccommodationLocationOnDate(Employee $employee, Carbon $date): ?Location
    {
        $accommodationAssignment = $employee->accommodationAssignments()
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $date);
            })
            ->with('accommodation.location')
            ->first();
        
        return $accommodationAssignment?->accommodation?->location;
    }

    /**
     * Get project location on specific date.
     * 
     * @param Employee $employee
     * @param Carbon $date
     * @return Location|null
     */
    protected function getProjectLocationOnDate(Employee $employee, Carbon $date): ?Location
    {
        $projectAssignment = $employee->assignments()
            ->where('is_cancelled', false)
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $date);
            })
            ->with('project.location')
            ->first();
        
        return $projectAssignment?->project?->location;
    }
    /**
     * Get the current location of an employee.
     * 
     * DEPRECATED: Use getLocationStatus() for full context.
     * This method returns simplified location for backward compatibility.
     * 
     * Priority:
     * 1. In transit → null (can't determine single location)
     * 2. Accommodation → accommodation.location
     * 3. Project → project.location
     * 4. Base
     * 
     * @param Employee $employee
     * @return Location|null
     */
    public function forEmployee(Employee $employee): ?Location
    {
        $status = $this->getLocationStatus($employee, now());
        
        // Priority: accommodation > project > base
        if ($status['accommodation_location']) {
            return $status['accommodation_location'];
        }
        
        if ($status['project_location']) {
            return $status['project_location'];
        }
        
        if (!$status['outside_base']) {
            return Location::getBase();
        }
        
        // Outside base but no assignments yet
        return null;
    }

    /**
     * Get the location of an employee on a specific date.
     * 
     * DEPRECATED: Use getLocationStatus() for full context.
     * 
     * Returns:
     * - Location object if employee has active assignment or is at base
     * - "W PODRÓŻY" string if employee is traveling (between departure and arrival)
     * - null if outside base without assignments
     * 
     * @param Employee $employee
     * @param \Carbon\Carbon $date
     * @return Location|string|null
     */
    public function forEmployeeOnDate(Employee $employee, \Carbon\Carbon $date): Location|string|null
    {
        $status = $this->getLocationStatus($employee, $date);
        
        // In transit
        if ($status['in_transit']) {
            return "W PODRÓŻY";
        }
        
        // Priority: accommodation > project > base
        if ($status['accommodation_location']) {
            return $status['accommodation_location'];
        }
        
        if ($status['project_location']) {
            return $status['project_location'];
        }
        
        if (!$status['outside_base']) {
            return Location::getBase();
        }
        
        // Outside base but no assignments yet
        return null;
    }

    /**
     * Get the current location of a vehicle.
     * 
     * @param Vehicle $vehicle
     * @return Location|null
     */
    public function forVehicle(Vehicle $vehicle): ?Location
    {
        // If vehicle has current_location_id, use it
        if ($vehicle->current_location_id) {
            return $vehicle->currentLocation;
        }

        // Otherwise, check active assignment and infer location from employee
        $activeAssignment = $vehicle->assignments()
            ->active()
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
