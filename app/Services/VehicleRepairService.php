<?php

namespace App\Services;

use App\Enums\LocationPurposeType;
use App\Models\Vehicle;
use App\Models\VehicleRepair;
use App\Models\VehicleAssignment;
use App\Models\Location;
use App\Models\FixedCostEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VehicleRepairService
{
    /**
     * Create a new repair, cutting active assignments and switching vehicle to workshop.
     */
    public function startRepair(Vehicle $vehicle, array $data): VehicleRepair
    {
        return DB::transaction(function () use ($vehicle, $data) {
            $workshopLocationId = $this->resolveWorkshopLocation($data);

            $repair = VehicleRepair::create([
                'vehicle_id'                   => $vehicle->id,
                'location_id'                  => $workshopLocationId,
                'action_type'                  => $data['action_type'],
                'start_date'                   => $data['start_date'],
                'end_date'                     => $data['end_date'] ?? null,
                'price'                        => $data['price'] ?? null,
                'currency'                     => $data['currency'] ?? null,
                'notes'                        => $data['notes'] ?? null,
                'previous_technical_condition' => $vehicle->technical_condition,
            ]);

            $this->cutActiveAssignments($vehicle, Carbon::parse($data['start_date']));

            $vehicle->update(['technical_condition' => 'workshop']);

            if (!empty($data['price']) && !empty($data['end_date'])) {
                $this->createOrUpdateFixedCostEntry($repair);
            }

            return $repair->fresh();
        });
    }

    /**
     * Complete a repair: set end_date + price, create/update cost entry, update vehicle condition.
     */
    public function completeRepair(VehicleRepair $repair, array $data): VehicleRepair
    {
        return DB::transaction(function () use ($repair, $data) {
            $repair->update([
                'end_date'  => $data['end_date'],
                'price'     => $data['price'],
                'currency'  => $data['currency'],
            ]);

            $this->createOrUpdateFixedCostEntry($repair);

            if (!empty($data['new_technical_condition'])) {
                $repair->vehicle->update(['technical_condition' => $data['new_technical_condition']]);
            }

            return $repair->fresh();
        });
    }

    /**
     * Update an existing repair, keeping FixedCostEntry in sync.
     */
    public function updateRepair(VehicleRepair $repair, array $data): VehicleRepair
    {
        return DB::transaction(function () use ($repair, $data) {
            $workshopLocationId = $this->resolveWorkshopLocation($data);

            $repair->update([
                'location_id' => $workshopLocationId,
                'action_type' => $data['action_type'],
                'start_date'  => $data['start_date'],
                'end_date'    => $data['end_date'] ?? null,
                'price'       => $data['price'] ?? null,
                'currency'    => $data['currency'] ?? null,
                'notes'       => $data['notes'] ?? null,
            ]);

            $repair->refresh();

            if (!empty($repair->price) && !empty($repair->end_date)) {
                $this->createOrUpdateFixedCostEntry($repair);
            } elseif ($repair->fixed_cost_entry_id) {
                $entry = $repair->fixedCostEntry;
                $repair->update(['fixed_cost_entry_id' => null]);
                $entry?->delete();
            }

            return $repair->fresh();
        });
    }

    /**
     * Delete a repair and its associated cost entry, restore vehicle condition if still workshop.
     */
    public function deleteRepair(VehicleRepair $repair): void
    {
        DB::transaction(function () use ($repair) {
            if ($repair->fixed_cost_entry_id) {
                $entry = $repair->fixedCostEntry;
                $repair->update(['fixed_cost_entry_id' => null]);
                $entry?->delete();
            }

            if (
                $repair->previous_technical_condition
                && $repair->vehicle->technical_condition === 'workshop'
            ) {
                $repair->vehicle->update([
                    'technical_condition' => $repair->previous_technical_condition,
                ]);
            }

            $repair->delete();
        });
    }

    /**
     * Lista przypisań pojazdu, które zostaną zmienione przy oddaniu do serwisu (podgląd przed zapisem).
     *
     * @return Collection<int, object{assignment: VehicleAssignment, action: string, new_end_date: ?Carbon}>
     */
    public function previewAssignmentChanges(Vehicle $vehicle, Carbon $repairStart): Collection
    {
        $cutDate = $repairStart->clone()->subDay();

        return $this->getAssignmentsActiveOnRepairHandover($vehicle, $repairStart)
            ->map(function (VehicleAssignment $assignment) use ($repairStart, $cutDate) {
                $assignmentStart = Carbon::parse($assignment->start_date);

                if ($assignmentStart->gte($repairStart)) {
                    return (object) [
                        'assignment'    => $assignment,
                        'action'        => 'delete',
                        'new_end_date'  => null,
                    ];
                }

                return (object) [
                    'assignment'    => $assignment,
                    'action'        => 'shorten',
                    'new_end_date'  => $cutDate->clone(),
                ];
            })
            ->values();
    }

    /**
     * Cut all VehicleAssignments for this vehicle that are active on the repair start date.
     * Sets their end_date to repair_start - 1 day.
     * If start_date >= repair_start (assignment starts same day), the assignment is deleted.
     */
    private function cutActiveAssignments(Vehicle $vehicle, Carbon $repairStart): void
    {
        $assignments = $this->getAssignmentsActiveOnRepairHandover($vehicle, $repairStart);

        foreach ($assignments as $assignment) {
            $assignmentStart = Carbon::parse($assignment->start_date);

            if ($assignmentStart->gte($repairStart)) {
                $assignment->delete();
            } else {
                $assignment->update(['end_date' => $repairStart->clone()->subDay()->toDateString()]);
            }
        }
    }

    /**
     * @return Collection<int, VehicleAssignment>
     */
    private function getAssignmentsActiveOnRepairHandover(Vehicle $vehicle, Carbon $repairStart): Collection
    {
        return VehicleAssignment::where('vehicle_id', $vehicle->id)
            ->where('start_date', '<=', $repairStart->toDateString())
            ->where(function ($q) use ($repairStart) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $repairStart->toDateString());
            })
            ->with('employee')
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Find or create the workshop Location from request data.
     * If location_id is provided, use it.
     * If new workshop fields are provided, firstOrCreate by name+address+city.
     */
    private function resolveWorkshopLocation(array $data): ?int
    {
        if (!empty($data['location_id'])) {
            return (int) $data['location_id'];
        }

        if (!empty($data['workshop_name']) && !empty($data['workshop_address'])) {
            $location = Location::firstOrCreate(
                [
                    'name'    => $data['workshop_name'],
                    'address' => $data['workshop_address'],
                    'city'    => $data['workshop_city'] ?? null,
                ],
                [
                    'postal_code' => $data['workshop_postal_code'] ?? null,
                    'country'     => $data['workshop_country'] ?? null,
                    'latitude'    => !empty($data['workshop_lat']) ? $data['workshop_lat'] : null,
                    'longitude'   => !empty($data['workshop_lng']) ? $data['workshop_lng'] : null,
                    'is_base'     => false,
                ]
            );

            $location->addPurposes([LocationPurposeType::WORKSHOP]);

            return $location->id;
        }

        return null;
    }

    /**
     * Create or update the FixedCostEntry linked to this repair.
     */
    private function createOrUpdateFixedCostEntry(VehicleRepair $repair): void
    {
        $entryData = [
            'name'            => 'Serwis #' . $repair->id . ' – ' . $repair->vehicle->registration_number,
            'amount'          => $repair->price,
            'currency'        => $repair->currency,
            'period_start'    => $repair->start_date->toDateString(),
            'period_end'      => $repair->end_date->toDateString(),
            'accounting_date' => $repair->end_date->toDateString(),
            'template_id'     => null,
            'notes'           => 'Koszt serwisu pojazdu ' . $repair->vehicle->registration_number,
        ];

        if ($repair->fixed_cost_entry_id && $repair->fixedCostEntry) {
            $repair->fixedCostEntry->update($entryData);
        } else {
            $entry = FixedCostEntry::create($entryData);
            $repair->update(['fixed_cost_entry_id' => $entry->id]);
        }
    }
}
