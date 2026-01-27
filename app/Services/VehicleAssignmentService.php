<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Models\Employee;
use App\Enums\VehiclePosition;
use App\Services\DateRangeService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class VehicleAssignmentService
{
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
        ?string $notes = null
    ): VehicleAssignment {
        $endDate = $endDate ?? DateRangeService::getDefaultEndDate();

        // Validate driver availability (only one driver per vehicle per period)
        if ($position === VehiclePosition::DRIVER) {
            $this->validateDriverAvailability($vehicle, $startDate, $endDate);
        }

        // Validate employee doesn't have overlapping assignment to the same vehicle
        $this->validateNoOverlappingAssignment($employee, $vehicle, $startDate, $endDate);

        return VehicleAssignment::create([
            'employee_id' => $employee->id,
            'vehicle_id' => $vehicle->id,
            'position' => $position,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => $notes,
            'is_return_trip' => false, // Always false for manual assignments
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

        // Validate driver availability (only one driver per vehicle per period)
        // Exclude current assignment if it's being updated
        if ($position === VehiclePosition::DRIVER) {
            $this->validateDriverAvailability($vehicle, $startDate, $endDate, $assignment->id);
        }

        // Validate employee doesn't have overlapping assignment to the same vehicle (excluding current)
        $this->validateNoOverlappingAssignment($assignment->employee, $vehicle, $startDate, $endDate, $assignment->id);

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

    /**
     * Validate that employee doesn't have overlapping assignment to the same vehicle.
     *
     * @throws ValidationException
     */
    protected function validateNoOverlappingAssignment(
        Employee $employee,
        Vehicle $vehicle,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeAssignmentId = null
    ): void {
        $query = $employee->vehicleAssignments()
            ->where('vehicle_id', $vehicle->id)
            ->where('is_return_trip', false); // Exclude return trip assignments

        DateRangeService::validateNoOverlappingAssignments(
            $query,
            $startDate,
            $endDate,
            $excludeAssignmentId,
            'vehicle_id',
            'Pracownik ma już przypisanie do tego pojazdu w tym okresie. Nie można tworzyć nakładających się przypisań.'
        );
    }

    /**
     * Validate that there's only one driver per vehicle in date range.
     *
     * @throws ValidationException
     */
    protected function validateDriverAvailability(Vehicle $vehicle, Carbon $startDate, Carbon $endDate, ?int $excludeAssignmentId = null): void
    {
        $query = $vehicle->assignments()
            ->where('position', VehiclePosition::DRIVER->value)
            ->where('is_return_trip', false) // Exclude return trip assignments
            ->overlappingWith($startDate, $endDate);

        if ($excludeAssignmentId) {
            $query->where('id', '!=', $excludeAssignmentId);
        }

        $hasDriver = $query->exists();

        if ($hasDriver) {
            throw ValidationException::withMessages([
                'position' => 'Pojazd ma już przypisanego kierowcę w tym okresie.'
            ]);
        }
    }
}
