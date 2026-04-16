<?php

namespace App\Services;

use App\Enums\LogisticsEventStatus;
use App\Models\LogisticsEvent;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
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
    public function __construct(
        protected VehicleValidationService $vehicleValidationService
    ) {}

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
        if (! $vehicle) {
            return; // No vehicle = no validation needed
        }

        // Use centralized validation service
        $this->vehicleValidationService->validateForLogisticsEventOrFail(
            $vehicle,
            $startDate,
            $endDate,
            $excludeEventId
        );
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

        // Check logistics events (transfer „tylko logistyka” nie zajmuje pojazdu w tej walidacji)
        $events = LogisticsEvent::forLocationTracking()
            ->where('vehicle_id', $vehicle->id)
            ->where('status', '!=', LogisticsEventStatus::CANCELLED->value)
            ->when($excludeEventId, fn ($q) => $q->where('id', '!=', $excludeEventId))
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where(function ($query) use ($startDate, $endDate) {
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
                'description' => "{$event->type->label()} {$event->event_date->format('d.m.Y')} - {$event->toLocation->name}",
            ];
        }

        // Check vehicle assignments
        $assignments = VehicleAssignment::where('vehicle_id', $vehicle->id)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where('start_date', '<=', $endDate)
                    ->where(function ($q2) use ($startDate) {
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
                'description' => "Przypisanie do projektu: {$assignment->employee->full_name}",
            ];
        }

        return $conflicts;
    }
}
