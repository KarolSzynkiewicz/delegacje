<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Illuminate\Validation\ValidationException;

class VehicleAssignmentService
{
    /**
     * Create a vehicle assignment with availability validation.
     * Multiple people can be assigned to the same vehicle, but only one driver per period.
     *
     * @throws ValidationException
     */
    public function createAssignment(int $employeeId, array $data): VehicleAssignment
    {
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        $endDate = $data['end_date'] ?? now()->addYears(10)->format('Y-m-d');
        
        // Convert position string to enum if needed
        $position = $data['position'] instanceof \App\Enums\VehiclePosition 
            ? $data['position'] 
            : \App\Enums\VehiclePosition::from($data['position'] ?? 'passenger');

        // Validate driver availability (only one driver per vehicle per period)
        if ($position === \App\Enums\VehiclePosition::DRIVER) {
            $this->validateDriverAvailability($vehicle, $data['start_date'], $endDate);
        }

        return VehicleAssignment::create([
            'employee_id' => $employeeId,
            'vehicle_id' => $data['vehicle_id'],
            'position' => $position,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_return_trip' => false, // Always false for manual assignments
        ]);
    }

    /**
     * Update a vehicle assignment with availability validation.
     *
     * @throws ValidationException
     */
    public function updateAssignment(\App\Contracts\HasEmployee&\App\Contracts\HasDateRange $assignment, array $data): VehicleAssignment
    {
        if (!$assignment instanceof VehicleAssignment) {
            throw new \InvalidArgumentException('Assignment must be a VehicleAssignment instance.');
        }

        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        $endDate = $data['end_date'] ?? now()->addYears(10)->format('Y-m-d');
        
        // Convert position string to enum if needed
        $position = $data['position'] instanceof \App\Enums\VehiclePosition 
            ? $data['position'] 
            : \App\Enums\VehiclePosition::from($data['position'] ?? 'passenger');

        // Validate driver availability (only one driver per vehicle per period)
        // Exclude current assignment if it's being updated
        if ($position === \App\Enums\VehiclePosition::DRIVER) {
            $this->validateDriverAvailability($vehicle, $data['start_date'], $endDate, $assignment->id);
        }

        // Ensure position is converted to enum value for database
        $data['position'] = $position;
        
        // Always keep is_return_trip as false for manual updates (only zjazd sets it to true)
        $data['is_return_trip'] = false;

        $assignment->update($data);

        return $assignment;
    }

    /**
     * Validate that there's only one driver per vehicle in date range.
     *
     * @throws ValidationException
     */
    protected function validateDriverAvailability(Vehicle $vehicle, string $startDate, string $endDate, ?int $excludeAssignmentId = null): void
    {
        $query = $vehicle->assignments()
            ->where('position', \App\Enums\VehiclePosition::DRIVER->value)
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
