<?php

namespace App\Services;

use App\Models\Rotation;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class RotationService
{
    /**
     * Create a new rotation with business logic validation.
     */
    public function createRotation(
        Employee $employee,
        Carbon $startDate,
        Carbon $endDate,
        ?string $notes = null
    ): Rotation {
        $this->validateNoOverlappingRotations(
            $employee->id,
            $startDate,
            $endDate
        );

        return $employee->rotations()->create([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => $notes,
        ]);
    }

    /**
     * Update an existing rotation with business logic validation.
     */
    public function updateRotation(
        Rotation $rotation,
        Carbon $startDate,
        Carbon $endDate,
        ?string $notes = null
    ): bool {
        $this->validateNoOverlappingRotations(
            $rotation->employee_id,
            $startDate,
            $endDate,
            $rotation->id
        );

        return $rotation->update([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => $notes,
        ]);
    }

    /**
     * Validate that rotation does not overlap with existing rotations.
     *
     * @throws ValidationException
     */
    protected function validateNoOverlappingRotations(
        int $employeeId,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeRotationId = null
    ): void {
        if (Rotation::hasOverlappingRotations($employeeId, $startDate, $endDate, $excludeRotationId)) {
            throw ValidationException::withMessages([
                'end_date' => 'Rotacja nakłada się z istniejącą aktywną rotacją tego pracownika.'
            ]);
        }
    }
}
