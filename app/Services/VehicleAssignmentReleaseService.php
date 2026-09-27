<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class VehicleAssignmentReleaseService
{
    /**
     * People assigned today or later must not stay on a car that is in base or out of the fleet.
     * A span that already started stays as history through yesterday. A span that starts today or later is removed.
     *
     * @return Collection<int, VehicleAssignment>
     */
    public function coveringTodayOrLater(Vehicle $vehicle): Collection
    {
        $today = Carbon::today()->toDateString();

        return $vehicle->assignments()
            ->with('employee')
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $today);
            })
            ->orderBy('start_date')
            ->get();
    }

    public function releaseFromToday(Vehicle $vehicle): int
    {
        $today = Carbon::today();
        $yesterday = $today->copy()->subDay()->toDateString();
        $released = 0;

        foreach ($this->coveringTodayOrLater($vehicle) as $assignment) {
            $startsTodayOrLater = $assignment->start_date === null
                || $assignment->start_date->copy()->startOfDay()->gte($today);

            if ($startsTodayOrLater) {
                $assignment->delete();
            } else {
                $assignment->update(['end_date' => $yesterday]);
            }

            $released++;
        }

        return $released;
    }
}
