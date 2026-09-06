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

        $this->assertCanAssign($employee, $accommodation, $startDate, $endDate, null, $arrivalDate);

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

        $this->assertCanAssign(
            $assignment->employee,
            $accommodation,
            $startDate,
            $endDate,
            $assignment->id
        );

        $assignment->update([
            'accommodation_id' => $accommodation->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => $notes,
        ]);

        return $assignment;
    }

    /**
     * Ta sama walidacja co przy zapisie — do UI (kalendarz / potwierdzenie zakresu).
     *
     * @throws ValidationException
     */
    public function assertCanAssign(
        Employee $employee,
        Accommodation $accommodation,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeAssignmentId = null,
        ?Carbon $arrivalDate = null
    ): void {
        if ($arrivalDate) {
            $this->validateStartDateAfterArrival($startDate, $arrivalDate);
        }

        $this->validateLeaseCoversRange($accommodation, $startDate, $endDate);
        $this->validateNoOverlappingAssignment($employee, $accommodation, $startDate, $endDate, $excludeAssignmentId);
        $this->validateAccommodationCapacity($accommodation, $startDate, $endDate, $excludeAssignmentId);
    }

    /**
     * Jedna osoba = jedno mieszkanie w danym okresie (nie tylko to samo mieszkanie).
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
        $query = $employee->accommodationAssignments()->with('accommodation');
        if ($excludeAssignmentId) {
            $query->where('id', '!=', $excludeAssignmentId);
        }

        $overlapping = $query->overlappingWith($startDate, $endDate)->first();
        if (! $overlapping) {
            return;
        }

        $houseName = $overlapping->accommodation?->name ?? 'inne mieszkanie';
        $from = $overlapping->start_date?->format('d.m.Y') ?? '—';
        $to = $overlapping->end_date?->format('d.m.Y') ?? 'bezterminowo';

        throw ValidationException::withMessages([
            'accommodation_id' => "Pracownik {$employee->full_name} ma już przypisanie do mieszkania {$houseName} ({$from} – {$to}). ".
                'Jedna osoba może mieć tylko jedno zakwaterowanie w danym okresie.',
        ]);
    }

    /**
     * @throws ValidationException
     */
    protected function validateAccommodationCapacity(
        Accommodation $accommodation,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeAssignmentId = null
    ): void {
        if ($accommodation->hasAvailableSpace($startDate, $endDate, $excludeAssignmentId)) {
            return;
        }

        $fullOn = $accommodation->firstDateAtCapacity($startDate, $endDate, $excludeAssignmentId);
        $when = $fullOn ? $fullOn->format('d.m.Y') : $startDate->format('d.m.Y');
        $capacity = (int) $accommodation->capacity;

        throw ValidationException::withMessages([
            'accommodation_id' => "Brak wolnych miejsc w mieszkaniu {$accommodation->name} w dniu {$when} ".
                "(pojemność {$capacity}). Kalendarz pokazuje obłożenie dzień po dniu — zakres musi mieć wolne łóżko przez wszystkie wybrane dni, nie tylko na początku i końcu.",
        ]);
    }

    /**
     * Najem liczony od dat przypisania, nie od „aktywnego teraz” (activeLease).
     *
     * @throws ValidationException
     */
    protected function validateLeaseCoversRange(Accommodation $accommodation, Carbon $startDate, Carbon $endDate): void
    {
        $covering = $accommodation->leaseCoveringRange($startDate, $endDate);
        if ($covering) {
            return;
        }

        $start = $startDate->toDateString();
        $end = $endDate->toDateString();

        $overlappingRentals = $accommodation->leases()
            ->where('type', 'wynajmowany')
            ->where(function ($q) use ($end) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $end);
            })
            ->where(function ($q) use ($start) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $start);
            })
            ->orderBy('start_date')
            ->get();

        if ($overlappingRentals->isEmpty()) {
            return;
        }

        $lease = $overlappingRentals->first();
        $leaseStart = $lease->start_date ? Carbon::parse($lease->start_date) : null;
        $leaseEnd = $lease->end_date ? Carbon::parse($lease->end_date) : null;

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

        throw ValidationException::withMessages([
            'accommodation_id' => 'Najem mieszkania '.$accommodation->name.' nie pokrywa całego okresu przypisania ('.
                $startDate->format('d.m.Y').' – '.$endDate->format('d.m.Y').').',
        ]);
    }

    /**
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
