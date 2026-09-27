<?php

namespace App\Services;

use App\Enums\VehicleLifecycleEventType;
use App\Enums\VehicleRetirementReason;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLifecycleEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VehicleLifecycleService
{
    public function __construct(
        protected VehicleAssignmentReleaseService $assignments
    ) {}

    public function retire(Vehicle $vehicle, VehicleRetirementReason $reason, ?string $note, User $actor): void
    {
        if ($vehicle->isRetired()) {
            throw ValidationException::withMessages([
                'reason' => 'Ten pojazd jest już wycofany z floty.',
            ]);
        }

        DB::transaction(function () use ($vehicle, $reason, $note, $actor) {
            $this->assignments->releaseFromToday($vehicle);

            $at = now();

            $vehicle->update([
                'retired_at' => $at,
                'retirement_reason' => $reason,
                'retirement_note' => $note,
            ]);

            VehicleLifecycleEvent::create([
                'vehicle_id' => $vehicle->id,
                'type' => VehicleLifecycleEventType::Retired,
                'occurred_at' => $at,
                'reason' => $reason,
                'note' => $note,
                'created_by' => $actor->id,
            ]);
        });
    }

    public function reinstate(Vehicle $vehicle, User $actor): void
    {
        if (! $vehicle->isRetired()) {
            throw ValidationException::withMessages([
                'reason' => 'Ten pojazd jest już w aktywnej flocie.',
            ]);
        }

        DB::transaction(function () use ($vehicle, $actor) {
            $vehicle->update([
                'retired_at' => null,
                'retirement_reason' => null,
                'retirement_note' => null,
            ]);

            VehicleLifecycleEvent::create([
                'vehicle_id' => $vehicle->id,
                'type' => VehicleLifecycleEventType::Reinstated,
                'occurred_at' => now(),
                'reason' => null,
                'note' => null,
                'created_by' => $actor->id,
            ]);
        });
    }
}
