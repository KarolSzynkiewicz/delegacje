<?php

namespace App\Services;

use App\Models\LogisticsEvent;
use App\Models\LogisticsEventParticipant;
use App\Models\Location;
use App\Models\Vehicle;
use App\Enums\LogisticsEventType;
use App\Enums\LogisticsEventStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Service for handling departures (wyjazdy) - employees going from base to project location.
 * 
 * This service implements the domain model where Departure is a domain event
 * that records employees leaving base for a project location.
 */
class DepartureService
{
    /**
     * Commit the departure (create the logistics event).
     * 
     * Creates a LogisticsEvent of type DEPARTURE with participants.
     * 
     * @param array $employeeIds
     * @param Carbon $departureDate
     * @param int $toLocationId
     * @param int|null $vehicleId
     * @param string|null $notes
     * @param LogisticsEvent|null $existingEvent If provided, updates existing event instead of creating new one
     * @param LogisticsEventStatus|null $status If provided, sets this status (only for updates)
     * @return LogisticsEvent
     */
    public function commitDeparture(
        array $employeeIds,
        Carbon $departureDate,
        int $toLocationId,
        ?int $vehicleId = null,
        ?string $notes = null,
        ?LogisticsEvent $existingEvent = null,
        ?LogisticsEventStatus $status = null
    ): LogisticsEvent {
        $baseLocation = Location::getBase();

        return DB::transaction(function () use ($employeeIds, $departureDate, $toLocationId, $vehicleId, $notes, $baseLocation, $existingEvent, $status) {
            // Create or update LogisticsEvent as domain fact
            if ($existingEvent) {
                // Update existing event
                $event = $existingEvent;
                $updateData = [
                    'event_date' => $departureDate,
                    'vehicle_id' => $vehicleId,
                    'from_location_id' => $baseLocation->id,
                    'to_location_id' => $toLocationId,
                    'notes' => $notes,
                ];
                
                // Update status if provided
                if ($status !== null) {
                    $updateData['status'] = $status;
                }
                
                $event->update($updateData);
                
                // Delete old participants
                $event->participants()->delete();
            } else {
                // Create new event
                $event = LogisticsEvent::create([
                    'type' => LogisticsEventType::DEPARTURE,
                    'event_date' => $departureDate,
                    'has_transport' => false,
                    'vehicle_id' => $vehicleId,
                    'transport_id' => null,
                    'from_location_id' => $baseLocation->id,
                    'to_location_id' => $toLocationId,
                    'status' => $status ?? LogisticsEventStatus::PLANNED,
                    'notes' => $notes,
                    'created_by' => auth()->id() ?? 1,
                ]);
            }

            // Create participants (no assignments for departures - they're going TO projects)
            foreach ($employeeIds as $employeeId) {
                LogisticsEventParticipant::create([
                    'logistics_event_id' => $event->id,
                    'employee_id' => $employeeId,
                    'assignment_type' => null,
                    'assignment_id' => null,
                    'status' => 'pending',
                ]);
            }

            // Update vehicle location if vehicle specified
            if ($vehicleId) {
                $vehicle = Vehicle::find($vehicleId);
                if ($vehicle) {
                    $toLocation = Location::find($toLocationId);
                    if ($toLocation) {
                        $vehicle->update([
                            'current_location_id' => $toLocation->id,
                        ]);
                    }
                }
            }

            return $event;
        });
    }

    /**
     * Reverse a departure - clean up before editing.
     * 
     * @param LogisticsEvent $departure The departure to reverse
     * @return void
     */
    public function reverseDeparture(LogisticsEvent $departure): void
    {
        if ($departure->type !== LogisticsEventType::DEPARTURE) {
            throw new \InvalidArgumentException('Can only reverse departures.');
        }

        DB::transaction(function () use ($departure) {
            // Delete all participants (they will be recreated with new data)
            $departure->participants()->delete();
        });
    }
}
