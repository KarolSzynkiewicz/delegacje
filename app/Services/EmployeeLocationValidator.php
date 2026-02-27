<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Location;
use App\Models\LogisticsEvent;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Service for validating employee location constraints for project assignments.
 * 
 * Extracted from ProjectAssignmentService for better separation of concerns.
 */
class EmployeeLocationValidator
{
    public function __construct(
        protected LocationTrackingService $locationTracker
    ) {}

    /**
     * Validate employee location and logistics for project assignment.
     * 
     * Rules:
     * 1. Block if employee is in transit
     * 2. Allow if already in project location
     * 3. Allow if local project (both at base)
     * 4. Require departure if from base to field
     * 5. Allow migration between field locations
     *
     * @param int|null $currentDepartureId If provided, this departure is being created and should be considered
     * @throws ValidationException
     */
    public function validateForAssignment(
        Employee $employee,
        Project $project,
        Carbon $startDate,
        ?int $currentDepartureId = null
    ): void {
        // Skip if project has no location
        if (!$project->location_id) {
            return;
        }

        $employeeLocation = $this->locationTracker->getEmployeeLocationOnDate($employee, $startDate);
        $projectLocation = $project->location;
        
        // 1. Block if in transit
        $this->blockIfInTransit($employee, $employeeLocation, $startDate);
        
        // 2. Allow if already in location
        if ($this->isEmployeeInLocation($employeeLocation, $projectLocation)) {
            return;
        }
        
        // 3. Allow if local project
        if ($this->isLocalProject($employeeLocation, $projectLocation)) {
            return;
        }
        
        // 4. Require departure if from base to field
        $this->requireDepartureIfNeeded($employee, $employeeLocation, $projectLocation, $startDate, $currentDepartureId);
        
        // 5. Allow migration between field locations (implicit - no exception thrown)
    }

    /**
     * Block assignment if employee is in transit.
     */
    protected function blockIfInTransit($employee, $location, Carbon $date): void
    {
        if ($location === "W PODRÓŻY") {
            throw ValidationException::withMessages([
                'employee_id' => "Pracownik {$employee->full_name} jest w trakcie podróży w dniu {$date->format('Y-m-d')}. " .
                    "Nie można przypisać pracownika do projektu podczas trwania wyjazdu/powrotu. " .
                    "Przypisanie może rozpocząć się w dniu przybycia (end_date wyjazdu) lub później."
            ]);
        }
    }

    /**
     * Check if employee is already in project location.
     */
    protected function isEmployeeInLocation($employeeLocation, Location $projectLocation): bool
    {
        return $employeeLocation instanceof Location && 
               $employeeLocation->id === $projectLocation->id;
    }

    /**
     * Check if this is a local project (both employee and project at base).
     */
    protected function isLocalProject($employeeLocation, Location $projectLocation): bool
    {
        $base = Location::getBase();
        
        return $employeeLocation instanceof Location &&
               $employeeLocation->id === $base->id && 
               $projectLocation->id === $base->id;
    }

    /**
     * Require departure if employee is at base and project is elsewhere.
     * 
     * @param int|null $currentDepartureId If provided, check this departure as well (for bulk creation)
     */
    protected function requireDepartureIfNeeded(
        Employee $employee,
        $employeeLocation,
        Location $projectLocation,
        Carbon $startDate,
        ?int $currentDepartureId = null
    ): void {
        $base = Location::getBase();
        
        // Not at base? Allow (migration between field locations)
        if (!$employeeLocation instanceof Location || $employeeLocation->id !== $base->id) {
            return;
        }
        
        // At base, project elsewhere - need departure
        // First check if current departure being created matches
        if ($currentDepartureId) {
            $currentDeparture = LogisticsEvent::find($currentDepartureId);
            if ($currentDeparture && 
                $currentDeparture->to_location_id === $projectLocation->id &&
                $currentDeparture->participants()->where('employee_id', $employee->id)->exists()) {
                // Current departure matches - validate date
                if ($currentDeparture->end_date && 
                    $startDate->copy()->startOfDay()->lt($currentDeparture->end_date->copy()->startOfDay())) {
                    throw ValidationException::withMessages([
                        'start_date' => "Data przypisania ({$startDate->format('Y-m-d')}) jest przed datą przybycia pracownika " .
                            "({$currentDeparture->end_date->format('Y-m-d')}). Pracownik może rozpocząć pracę w dniu przybycia lub później. " .
                            "Zmień datę przypisania na {$currentDeparture->end_date->format('Y-m-d')} lub późniejszą."
                    ]);
                }
                return; // Current departure is valid
            }
        }
        
        // Check for existing planned departures
        $departure = LogisticsEvent::plannedDeparturesTo($employee, $projectLocation->id)->first();
        
        if (!$departure) {
            throw $this->buildNoDepartureException($employee, $employeeLocation, $projectLocation);
        }
        
        // Only validate date if assignment is AFTER departure starts
        // If assignment starts before departure, it's OK (employee will be there when departure arrives)
        if ($startDate->copy()->startOfDay()->gte($departure->event_date->copy()->startOfDay())) {
            $this->validateAssignmentDate($departure, $startDate);
        }
    }

    /**
     * Build exception for missing departure.
     */
    protected function buildNoDepartureException(
        Employee $employee, 
        Location $fromLocation, 
        Location $toLocation
    ): ValidationException {
        $debugInfo = $this->getDebugDeparturesInfo($employee, $toLocation);
        
        return ValidationException::withMessages([
            'employee_id' => "Pracownik {$employee->full_name} jest w bazie ({$fromLocation->name}), " .
                "a projekt znajduje się w innej lokalizacji ({$toLocation->name}, ID: {$toLocation->id}). " .
                "Najpierw utwórz wyjazd do lokalizacji {$toLocation->name}, który oczekuje na przypisanie (status: Oczekuje na przypisanie)." .
                $debugInfo
        ]);
    }

    /**
     * Get debug info about employee's planned departures.
     */
    protected function getDebugDeparturesInfo(Employee $employee, Location $targetLocation): string
    {
        $anyDepartures = LogisticsEvent::where('type', \App\Enums\LogisticsEventType::DEPARTURE)
            ->where('status', \App\Enums\LogisticsEventStatus::PLANNED)
            ->whereHas('participants', fn($q) => $q->where('employee_id', $employee->id))
            ->with('toLocation')
            ->get();
        
        if ($anyDepartures->isEmpty()) {
            return '';
        }
        
        return ' Znalezione wyjazdy PLANNED dla pracownika: ' . 
            $anyDepartures->map(fn($d) => "#{$d->id} do {$d->toLocation->name} (przybycie: {$d->end_date->format('Y-m-d')})")->join(', ') . 
            ". Sprawdź czy lokalizacja ({$targetLocation->name}, ID: {$targetLocation->id}) się zgadza z lokalizacją docelową wyjazdu.";
    }

    /**
     * Validate that assignment date is on or after departure arrival date.
     */
    protected function validateAssignmentDate(LogisticsEvent $departure, Carbon $startDate): void
    {
        if (!$departure->end_date) {
            return;
        }
        
        if ($startDate->copy()->startOfDay()->lt($departure->end_date->copy()->startOfDay())) {
            throw ValidationException::withMessages([
                'start_date' => "Data przypisania ({$startDate->format('Y-m-d')}) jest przed datą przybycia pracownika " .
                    "({$departure->end_date->format('Y-m-d')}). Pracownik może rozpocząć pracę w dniu przybycia lub później. " .
                    "Zmień datę przypisania na {$departure->end_date->format('Y-m-d')} lub późniejszą."
            ]);
        }
    }
}
