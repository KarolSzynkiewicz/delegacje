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
            'status' => $data['status'] ?? \App\Enums\AssignmentStatus::ACTIVE,
            'notes' => $data['notes'] ?? null,
            'is_return_trip' => false, // Always false for manual assignments
        ]);
    }

    /**
     * Update a vehicle assignment with availability validation.
     *
     * @throws ValidationException
     */
    public function updateAssignment(VehicleAssignment $assignment, array $data): VehicleAssignment
    {
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
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                          ->where(function ($q2) use ($endDate) {
                              $q2->where('end_date', '>=', $endDate)
                                 ->orWhereNull('end_date');
                          });
                    });
            })
            ->where('status', '!=', \App\Enums\AssignmentStatus::CANCELLED->value);

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
