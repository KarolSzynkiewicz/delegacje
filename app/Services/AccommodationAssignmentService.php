<?php

namespace App\Services;

use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AccommodationAssignmentService
{
    /**
     * Create an accommodation assignment with capacity validation.
     *
     * @throws ValidationException
     */
    public function createAssignment(
        Employee $employee,
        Accommodation $accommodation,
        Carbon $startDate,
        ?Carbon $endDate = null,
        ?string $notes = null
    ): AccommodationAssignment {
        $endDate = $endDate ?? now()->addYears(10);

        $this->validateAccommodationCapacity($accommodation, $startDate, $endDate);

        return AccommodationAssignment::create([
            'employee_id' => $employee->id,
            'accommodation_id' => $accommodation->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => $notes,
        ]);
    }

    /**
     * Update an accommodation assignment with capacity validation.
     *
     * @throws ValidationException
     */
    public function updateAssignment(
        AccommodationAssignment $assignment,
        Accommodation $accommodation,
        Carbon $startDate,
        ?Carbon $endDate = null,
        ?string $notes = null
    ): AccommodationAssignment {
        $endDate = $endDate ?? now()->addYears(10);

        // Validate capacity excluding current assignment
        $this->validateAccommodationCapacity($accommodation, $startDate, $endDate, $assignment->id);

        $assignment->update([
            'accommodation_id' => $accommodation->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => $notes,
        ]);

        return $assignment;
    }

    /**
     * Validate accommodation capacity in date range.
     *
     * @throws ValidationException
     */
    protected function validateAccommodationCapacity(Accommodation $accommodation, Carbon $startDate, Carbon $endDate, ?int $excludeAssignmentId = null): void
    {
        if (!$accommodation->hasAvailableSpace($startDate, $endDate, $excludeAssignmentId)) {
            throw ValidationException::withMessages([
                'accommodation_id' => 'Brak wolnych miejsc w tym mieszkaniu w wybranym okresie.'
            ]);
        }
    }
}
