<?php

namespace App\Services;

use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use Illuminate\Validation\ValidationException;

class AccommodationAssignmentService
{
    /**
     * Create an accommodation assignment with capacity validation.
     *
     * @throws ValidationException
     */
    public function createAssignment(int $employeeId, array $data): AccommodationAssignment
    {
        $accommodation = Accommodation::findOrFail($data['accommodation_id']);
        $endDate = $data['end_date'] ?? now()->addYears(10)->format('Y-m-d');

        $this->validateAccommodationCapacity($accommodation, $data['start_date'], $endDate);

        return AccommodationAssignment::create([
            'employee_id' => $employeeId,
            'accommodation_id' => $data['accommodation_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'] ?? \App\Enums\AssignmentStatus::ACTIVE,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Update an accommodation assignment with capacity validation.
     *
     * @throws ValidationException
     */
    public function updateAssignment(AccommodationAssignment $assignment, array $data): AccommodationAssignment
    {
        $accommodation = Accommodation::findOrFail($data['accommodation_id']);
        $endDate = $data['end_date'] ?? now()->addYears(10)->format('Y-m-d');

        // Validate capacity excluding current assignment
        $this->validateAccommodationCapacity($accommodation, $data['start_date'], $endDate, $assignment->id);

        $assignment->update($data);

        return $assignment;
    }

    /**
     * Validate accommodation capacity in date range.
     *
     * @throws ValidationException
     */
    protected function validateAccommodationCapacity(Accommodation $accommodation, string $startDate, string $endDate, ?int $excludeAssignmentId = null): void
    {
        if (!$accommodation->hasAvailableSpace($startDate, $endDate, $excludeAssignmentId)) {
            throw ValidationException::withMessages([
                'accommodation_id' => 'Brak wolnych miejsc w tym mieszkaniu w wybranym okresie.'
            ]);
        }
    }
}
