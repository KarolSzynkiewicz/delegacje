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
        ?string $notes = null,
        ?int $logisticsEventId = null,
        ?Carbon $arrivalDate = null
    ): AccommodationAssignment {
        $endDate = $endDate ?? DateRangeService::getDefaultEndDate();

        // Validate start date is not before arrival (if provided)
        if ($arrivalDate) {
            $this->validateStartDateAfterArrival($startDate, $arrivalDate);
        }

        // Validate lease covers the assignment period (for rented accommodations)
        $this->validateLeaseCoversRange($accommodation, $startDate, $endDate);

        // Validate employee doesn't have overlapping assignment to the same accommodation
        $this->validateNoOverlappingAssignment($employee, $accommodation, $startDate, $endDate);

        $this->validateAccommodationCapacity($accommodation, $startDate, $endDate);

        return AccommodationAssignment::create([
            'employee_id' => $employee->id,
            'accommodation_id' => $accommodation->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => $notes,
            'logistics_event_id' => $logisticsEventId,
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
        $endDate = $endDate ?? DateRangeService::getDefaultEndDate();

        // Validate lease covers the assignment period (for rented accommodations)
        $this->validateLeaseCoversRange($accommodation, $startDate, $endDate);

        // Validate employee doesn't have overlapping assignment to the same accommodation (excluding current)
        $this->validateNoOverlappingAssignment($assignment->employee, $accommodation, $startDate, $endDate, $assignment->id);

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
     * Validate that employee doesn't have overlapping assignment to the same accommodation.
     *
     * @throws ValidationException
     */
    protected function validateNoOverlappingAssignment(
        Employee $employee,
        Accommodation $accommodation,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeAssignmentId = null
    ): void {
        $query = $employee->accommodationAssignments()
            ->where('accommodation_id', $accommodation->id);

        DateRangeService::validateNoOverlappingAssignments(
            $query,
            $startDate,
            $endDate,
            $excludeAssignmentId,
            'accommodation_id',
            'Pracownik ma już przypisanie do tego mieszkania w tym okresie. Nie można tworzyć nakładających się przypisań.'
        );
    }

    /**
     * Validate accommodation capacity in date range.
     *
     * @throws ValidationException
     */
    protected function validateAccommodationCapacity(Accommodation $accommodation, Carbon $startDate, Carbon $endDate, ?int $excludeAssignmentId = null): void
    {
        if (! $accommodation->hasAvailableSpace($startDate, $endDate, $excludeAssignmentId)) {
            throw ValidationException::withMessages([
                'accommodation_id' => 'Brak wolnych miejsc w tym mieszkaniu w wybranym okresie.',
            ]);
        }
    }

    /**
     * Validate that a rented accommodation's active lease covers the assignment range.
     * Own accommodations (no active lease or type !== 'wynajmowany') are always allowed.
     *
     * @throws ValidationException
     */
    protected function validateLeaseCoversRange(Accommodation $accommodation, Carbon $startDate, Carbon $endDate): void
    {
        $accommodation->loadMissing('activeLease');
        $lease = $accommodation->activeLease;

        // Own accommodations have no lease restriction
        if (! $lease || $lease->type !== 'wynajmowany') {
            return;
        }

        $leaseEnd = $lease->end_date ? Carbon::parse($lease->end_date) : null;
        $leaseStart = $lease->start_date ? Carbon::parse($lease->start_date) : null;

        if ($leaseStart && $startDate->lt($leaseStart)) {
            throw ValidationException::withMessages([
                'start_date' => 'Data rozpoczęcia przypisania ('.$startDate->format('d.m.Y').') jest przed początkiem najmu mieszkania ('.$leaseStart->format('d.m.Y').').',
            ]);
        }

        if ($leaseEnd && $endDate->gt($leaseEnd)) {
            throw ValidationException::withMessages([
                'end_date' => 'Data zakończenia przypisania ('.$endDate->format('d.m.Y').') wykracza poza datę końca najmu mieszkania ('.$leaseEnd->format('d.m.Y').'). Przedłuż najem lub skróć przypisanie.',
            ]);
        }
    }

    /**
     * Validate that assignment start date is not before the logistics event arrival date.
     *
     * @throws ValidationException
     */
    protected function validateStartDateAfterArrival(Carbon $startDate, Carbon $arrivalDate): void
    {
        if ($startDate->lt($arrivalDate)) {
            throw ValidationException::withMessages([
                'start_date' => 'Data rozpoczęcia przypisania ('.$startDate->format('d.m.Y').') nie może być wcześniejsza niż data przybycia ('.$arrivalDate->format('d.m.Y').').',
            ]);
        }
    }
}
