<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Illuminate\Validation\ValidationException;

class VehicleAssignmentService
{
    /**
     * Create a vehicle assignment with availability validation.
     *
     * @throws ValidationException
     */
    public function createAssignment(int $employeeId, array $data): VehicleAssignment
    {
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        $endDate = $data['end_date'] ?? now()->addYears(10)->format('Y-m-d');

        $this->validateVehicleAvailability($vehicle, $data['start_date'], $endDate);

        return VehicleAssignment::create([
            'employee_id' => $employeeId,
            'vehicle_id' => $data['vehicle_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Validate vehicle availability in date range.
     *
     * @throws ValidationException
     */
    protected function validateVehicleAvailability(Vehicle $vehicle, string $startDate, string $endDate): void
    {
        if (!$vehicle->isAvailableInDateRange($startDate, $endDate)) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'Pojazd jest już przypisany w tym okresie.'
            ]);
        }
    }
}
