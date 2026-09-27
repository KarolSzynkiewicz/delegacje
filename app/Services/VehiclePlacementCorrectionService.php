<?php

namespace App\Services;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VehiclePlacementCorrectionService
{
    public function __construct(
        protected LocationTrackingService $locationTracking,
        protected VehicleAssignmentReleaseService $assignments
    ) {}

    /**
     * Immediate placement fix. The event is dated today with no travel window,
     * so it becomes the latest fact and clears an open „w podróży” on this vehicle.
     */
    public function correct(Vehicle $vehicle, bool $outsideBase, string $notes, User $actor): LogisticsEvent
    {
        $today = Carbon::now();
        $current = $this->locationTracking->getVehicleLocationStatus($vehicle, $today);

        if (! $current['in_transit'] && $current['outside_base'] === $outsideBase) {
            throw ValidationException::withMessages([
                'placement' => 'Auto już ma to położenie.',
            ]);
        }

        $base = Location::getBase();

        return DB::transaction(function () use ($vehicle, $outsideBase, $notes, $actor, $today, $base) {
            $event = LogisticsEvent::create([
                'type' => LogisticsEventType::PLACEMENT_CORRECTION,
                'event_date' => $today->copy()->startOfDay(),
                'end_date' => null,
                'has_transport' => false,
                'vehicle_id' => $vehicle->id,
                'from_location_id' => $base->id,
                'to_location_id' => $base->id,
                'status' => LogisticsEventStatus::COMPLETED,
                'notes' => $notes,
                'created_by' => $actor->id,
                'sets_outside_base' => $outsideBase,
            ]);

            $locationId = $vehicle->current_location_id;
            if (! $outsideBase) {
                $locationId = $base->id;
            } elseif ((int) $locationId === (int) $base->id) {
                $locationId = null;
            }

            $vehicle->update([
                'outside_base' => $outsideBase,
                'last_departure_id' => null,
                'current_location_id' => $locationId,
            ]);

            if (! $outsideBase) {
                $this->assignments->releaseFromToday($vehicle);
            }

            return $event;
        });
    }
}
