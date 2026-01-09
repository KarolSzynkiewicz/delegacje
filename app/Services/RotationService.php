<?php

namespace App\Services;

use App\Models\Rotation;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Validation\ValidationException;

class RotationService
{
    public function __construct(
        protected EmployeeRepositoryInterface $employeeRepository
    ) {}

    /**
     * Create a new rotation with business logic validation.
     */
    public function createRotation(int $employeeId, array $data): Rotation
    {
        $employee = $this->employeeRepository->findOrFail($employeeId);
        
        $this->validateNoOverlappingRotations(
            $employeeId,
            $data['start_date'],
            $data['end_date']
        );

        return $employee->rotations()->create($data);
    }

    /**
     * Update an existing rotation with business logic validation.
     */
    public function updateRotation(Rotation $rotation, array $data): bool
    {
        $this->validateNoOverlappingRotations(
            $rotation->employee_id,
            $data['start_date'],
            $data['end_date'],
            $rotation->id
        );

        return $rotation->update($data);
    }

    /**
     * Validate that rotation does not overlap with existing rotations.
     *
     * @throws ValidationException
     */
    protected function validateNoOverlappingRotations(
        int $employeeId,
        string $startDate,
        string $endDate,
        ?int $excludeRotationId = null
    ): void {
        if (Rotation::hasOverlappingRotations($employeeId, $startDate, $endDate, $excludeRotationId)) {
            throw ValidationException::withMessages([
                'end_date' => 'Rotacja nakłada się z istniejącą aktywną rotacją tego pracownika.'
            ]);
        }
    }
}
