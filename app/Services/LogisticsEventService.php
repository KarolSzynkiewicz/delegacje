<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\LogisticsEvent;
use App\Models\VehicleAssignment;
use App\Enums\LogisticsEventStatus;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Service for validating logistics events (departures/returns).
 * 
 * Ensures vehicles are not double-booked between:
 * - Multiple logistics events
 * - Logistics events and vehicle assignments (projects)
 */
class LogisticsEventService
{
    /**
     * Validate vehicle availability for logistics event.
     * 
     * Checks:
     * - No overlapping LogisticsEvents using same vehicle
     * - No VehicleAssignments (projects) using this vehicle
     * 
     * @throws ValidationException
     */
    public function validateVehicleAvailability(
        ?Vehicle $vehicle,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeEventId = null
    ): void {
        if (!$vehicle) {
            return; // No vehicle = no validation needed
        }
        
        // 1. Check other logistics events using this vehicle
        $hasConflictingEvent = LogisticsEvent::where('vehicle_id', $vehicle->id)
            ->whereNotIn('status', [LogisticsEventStatus::CANCELLED])
            ->when($excludeEventId, fn($q) => $q->where('id', '!=', $excludeEventId))
            ->where(function($q) use ($startDate, $endDate) {
                // Check if dates overlap
                $q->where(function($query) use ($startDate, $endDate) {
                    $query->where('event_date', '<=', $endDate)
                          ->where(function($q2) use ($startDate) {
                              // If end_date is null, use event_date as end
                              $q2->whereRaw('COALESCE(end_date, event_date) >= ?', [$startDate]);
                          });
                });
            })
            ->exists();
            
        if ($hasConflictingEvent) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'Pojazd jest już zajęty przez inny wyjazd/zjazd w tym okresie.'
            ]);
        }
        
        // 2. Check vehicle assignments (project assignments)
        $hasProjectAssignment = VehicleAssignment::where('vehicle_id', $vehicle->id)
            ->where('is_cancelled', false)
            ->where(function($q) use ($startDate, $endDate) {
                $q->where('start_date', '<=', $endDate)
                  ->where(function($q2) use ($startDate) {
                      $q2->whereNull('end_date')
                         ->orWhere('end_date', '>=', $startDate);
                  });
            })
            ->exists();
            
        if ($hasProjectAssignment) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'Pojazd jest przypisany do projektu w tym okresie.'
            ]);
        }
    }

    /**
     * Check if vehicle is available (returns bool instead of throwing).
     */
    public function isVehicleAvailable(
        ?Vehicle $vehicle,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeEventId = null
    ): bool {
        try {
            $this->validateVehicleAvailability($vehicle, $startDate, $endDate, $excludeEventId);
            return true;
        } catch (ValidationException $e) {
            return false;
        }
    }

    /**
     * Get conflicting events for a vehicle in a date range.
     * Useful for displaying what's blocking the vehicle.
     */
    public function getConflictingEvents(
        Vehicle $vehicle,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeEventId = null
    ): array {
        $conflicts = [];

        // Check logistics events
        $events = LogisticsEvent::where('vehicle_id', $vehicle->id)
            ->whereNotIn('status', [LogisticsEventStatus::CANCELLED])
            ->when($excludeEventId, fn($q) => $q->where('id', '!=', $excludeEventId))
            ->where(function($q) use ($startDate, $endDate) {
                $q->where(function($query) use ($startDate, $endDate) {
                    $query->where('event_date', '<=', $endDate)
                          ->whereRaw('COALESCE(end_date, event_date) >= ?', [$startDate]);
                });
            })
            ->with(['toLocation', 'fromLocation'])
            ->get();

        foreach ($events as $event) {
            $conflicts[] = [
                'type' => 'logistics_event',
                'model' => $event,
                'description' => "{$event->type->label()} {$event->event_date->format('d.m.Y')} - {$event->toLocation->name}"
            ];
        }

        // Check vehicle assignments
        $assignments = VehicleAssignment::where('vehicle_id', $vehicle->id)
            ->where('is_cancelled', false)
            ->where(function($q) use ($startDate, $endDate) {
                $q->where('start_date', '<=', $endDate)
                  ->where(function($q2) use ($startDate) {
                      $q2->whereNull('end_date')
                         ->orWhere('end_date', '>=', $startDate);
                  });
            })
            ->with('employee')
            ->get();

        foreach ($assignments as $assignment) {
            $conflicts[] = [
                'type' => 'vehicle_assignment',
                'model' => $assignment,
                'description' => "Przypisanie do projektu: {$assignment->employee->full_name}"
            ];
        }

        return $conflicts;
    }
}
