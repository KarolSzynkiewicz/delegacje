<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Models\Employee;
use App\Enums\VehiclePosition;
use App\Services\DateRangeService;
use App\Services\LogisticsEventService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class VehicleAssignmentService
{
    public function __construct(
        protected VehicleValidationService $vehicleValidationService
    ) {}
    /**
     * Create a vehicle assignment with availability validation.
     * Multiple people can be assigned to the same vehicle, but only one driver per period.
     *
     * @throws ValidationException
     */
    public function createAssignment(
        Employee $employee,
        Vehicle $vehicle,
        VehiclePosition $position,
        Carbon $startDate,
        ?Carbon $endDate = null,
        ?string $notes = null,
        ?int $logisticsEventId = null
    ): VehicleAssignment {
        $endDate = $endDate ?? DateRangeService::getDefaultEndDate();

        // Use centralized validation service
        $this->vehicleValidationService->validateForProjectAssignmentOrFail(
            $vehicle,
            $employee,
            $position,
            $startDate,
            $endDate
        );

        return VehicleAssignment::create([
            'employee_id' => $employee->id,
            'vehicle_id' => $vehicle->id,
            'position' => $position,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => $notes,
            'is_return_trip' => false, // Always false for manual assignments
            'logistics_event_id' => $logisticsEventId,
        ]);
    }

    /**
     * Update a vehicle assignment with availability validation.
     *
     * @throws ValidationException
     */
    public function updateAssignment(
        VehicleAssignment $assignment,
        Vehicle $vehicle,
        VehiclePosition $position,
        Carbon $startDate,
        ?Carbon $endDate = null,
        ?string $notes = null
    ): VehicleAssignment {
        $endDate = $endDate ?? DateRangeService::getDefaultEndDate();

        // Use centralized validation service (exclude current assignment)
        $this->vehicleValidationService->validateForProjectAssignmentOrFail(
            $vehicle,
            $assignment->employee,
            $position,
            $startDate,
            $endDate,
            $assignment->id
        );

        $assignment->update([
            'vehicle_id' => $vehicle->id,
            'position' => $position,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => $notes,
            'is_return_trip' => false, // Always false for manual updates (only zjazd sets it to true)
        ]);

        return $assignment;
    }

}
