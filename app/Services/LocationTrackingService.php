<?php

namespace App\Services;

use App\Enums\EmployeeLocationState;
use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Enums\VehiclePosition;
use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\ProjectAssignment;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LocationTrackingService
{
    /**
     * Czy pracownik może być uczestnikiem transferu (poza lokalizacją bazy lub w podróży).
     */
    public function isEmployeeEligibleForTransfer(Employee $employee, Carbon $date): bool
    {
        $status = $this->getLocationStatus($employee, $date);

        return $status['state'] !== EmployeeLocationState::IN_BASE;
    }

    public function getLocationStatus(Employee $employee, Carbon $date): array
    {
        $dateDay = $date->copy()->startOfDay();

        // Jedno źródło prawdy jak scope inTransitOn(): tylko PLANNED/COMPLETED, bez anulowanych i bez „legacy” IN_PROGRESS
        if (LogisticsEvent::isEmployeeInTransit($employee, $dateDay)) {
            $state = EmployeeLocationState::IN_TRANSIT;
        } else {
            $lastEvent = $this->findLastEvent($employee, $dateDay);
            $state = $this->deriveStateFromEvent($lastEvent, $dateDay);
        }

        $projectAssignments = $this->findActiveProjectAssignments($employee, $dateDay);
        if ($projectAssignments->isNotEmpty() && $state === EmployeeLocationState::IN_BASE) {
            $state = EmployeeLocationState::OUTSIDE_BASE;
        }

        $accommodationAssignments = $this->findActiveAccommodationAssignments($employee, $dateDay);
        $vehicleAssignments = $this->findActiveVehicleAssignments($employee, $dateDay);

        $projectNames = $projectAssignments->map(function (ProjectAssignment $pa) {
            $n = $pa->project?->name;
            if (! $n) {
                return null;
            }
            $roleName = $pa->relationLoaded('role') ? $pa->role?->name : null;

            return $roleName ? $n.' ('.$roleName.')' : $n;
        })->filter()->values()->all();

        $accommodationNames = $accommodationAssignments->map(fn (AccommodationAssignment $a) => $a->accommodation?->name)
            ->filter()
            ->values()
            ->all();

        $vehicleLabels = $vehicleAssignments->map(fn (VehicleAssignment $va) => $va->vehicle?->registration_number)
            ->filter()
            ->values()
            ->all();

        $overlap = $projectAssignments->count() > 1
            || $accommodationAssignments->count() > 1
            || $vehicleAssignments->count() > 1;

        return [
            'state' => $state,
            'project_name' => $projectAssignments->first()?->project?->name,
            'accommodation_name' => $accommodationAssignments->first()?->accommodation?->name,
            'project_names' => $projectNames,
            'accommodation_names' => $accommodationNames,
            'vehicle_labels' => $vehicleLabels,
            'has_assignment_overlap' => $overlap,
        ];
    }

    public function getEmployeeLocation(Employee $employee): ?Location
    {
        $status = $this->getLocationStatus($employee, now());

        if ($status['state'] === EmployeeLocationState::IN_TRANSIT) {
            return null;
        }

        $projectAssignment = $this->findActiveProjectAssignment($employee, now());
        if ($projectAssignment?->project?->location) {
            return $projectAssignment->project->location;
        }

        if ($status['state'] === EmployeeLocationState::IN_BASE) {
            return Location::getBase();
        }

        return null;
    }

    public function getEmployeeLocationOnDate(Employee $employee, Carbon $date): Location|string|null
    {
        $status = $this->getLocationStatus($employee, $date);

        if ($status['state'] === EmployeeLocationState::IN_TRANSIT) {
            return 'W PODRÓŻY';
        }

        $projectAssignment = $this->findActiveProjectAssignment($employee, $date);
        if ($projectAssignment?->project?->location) {
            return $projectAssignment->project->location;
        }

        if ($status['state'] === EmployeeLocationState::IN_BASE) {
            return Location::getBase();
        }

        return null;
    }

    public function getVehicleLocation(Vehicle $vehicle): ?Location
    {
        if ($vehicle->current_location_id) {
            return $vehicle->currentLocation;
        }

        $activeAssignment = $vehicle->assignments()
            ->active()
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
            ->with('employee')
            ->first();

        if ($activeAssignment) {
            return $this->getEmployeeLocation($activeAssignment->employee);
        }

        return Location::getBase();
    }

    /**
     * Get the location of a vehicle on a specific date.
     *
     * @return Location|string|null Returns Location, "W PODRÓŻY" string, or null
     */
    public function getVehicleLocationOnDate(Vehicle $vehicle, Carbon $date): Location|string|null
    {
        $status = $this->getVehicleLocationStatus($vehicle, $date);

        if ($status['in_transit']) {
            return 'W PODRÓŻY';
        }

        // Zwróć nazwę stacjonowania (zakwaterowanie kierowcy lub baza)
        // Jeśli to string, zwróć go, jeśli null, zwróć Location::getBase() dla kompatybilności
        if ($status['stationing_location']) {
            return $status['stationing_location'];
        }

        return Location::getBase();
    }

    /**
     * Get comprehensive vehicle location status on a specific date.
     *
     * Returns:
     * - in_transit: bool - czy pojazd jest w podróży (podczas eventu logistycznego)
     * - outside_base: bool - czy pojazd był w wyjeździe (analogicznie do outside_base dla pracownika)
     * - project_names: Collection<string> - unikatowe nazwy projektów przypisanych osobom w pojeździe
     * - accommodation_names: Collection<string> - unikatowe nazwy zakwaterowań (domów) przypisanych osobom
     * - driver_accommodation: string|null - nazwa zakwaterowania kierowcy
     * - driver_project: string|null - nazwa projektu kierowcy
     * - stationing_location: string|null - gdzie stacjonuje (nazwa zakwaterowania kierowcy lub baza)
     * - occupancy: int - liczba przypisanych osób na daną datę
     * - capacity: int|null - pojemność pojazdu
     * - occupancy_percentage: float|null - procent zapełnienia (0-100)
     */
    public function getVehicleLocationStatus(Vehicle $vehicle, Carbon $date): array
    {
        // 1. Sprawdź czy pojazd jest w podróży (podczas eventu logistycznego)
        $inTransitEvent = $this->findVehicleInTransitEvent($vehicle, $date);
        $inTransit = $inTransitEvent !== null;

        // 2. Synchronizuj flagę outside_base (lazy evaluation)
        $this->syncVehicleOutsideBaseFlag($vehicle, $date);
        $outsideBase = $vehicle->outside_base;

        // 3. Jeśli w podróży, zwróć podstawowe informacje
        if ($inTransit) {
            return [
                'in_transit' => true,
                'outside_base' => $outsideBase,
                'project_names' => collect(),
                'accommodation_names' => collect(),
                'driver_accommodation' => null,
                'driver_project' => null,
                'stationing_location' => null,
                'occupancy' => 0,
                'capacity' => $vehicle->capacity,
                'occupancy_percentage' => null,
            ];
        }

        // 4. Znajdź wszystkie aktywne przypisania pojazdu na daną datę
        $activeAssignments = VehicleAssignment::where('vehicle_id', $vehicle->id)
            ->where('start_date', '<=', $date)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date))
            ->with(['employee'])
            ->get();

        // 5. Oblicz occupancy (liczba unikalnych pracowników)
        $uniqueEmployeeIds = $activeAssignments->pluck('employee_id')->unique();
        $occupancy = $uniqueEmployeeIds->count();

        // 6. Zbierz unikatowe nazwy lokalizacji projektów i domów
        $projectNames = collect();
        $accommodationNames = collect();
        $driverAccommodationName = null;
        $driverProjectName = null;

        foreach ($activeAssignments as $assignment) {
            $employee = $assignment->employee;

            if (! $employee) {
                continue;
            }

            // Projekty — wszystkie aktywne tego dnia (nazwa projektu, nie lokalizacji)
            foreach ($this->findActiveProjectAssignments($employee, $date) as $projectAssignment) {
                if ($projectAssignment->project?->name) {
                    $projectNames->push($projectAssignment->project->name);
                }
            }

            // Zakwaterowania (nazwa domu, nie lokalizacji)
            foreach ($this->findActiveAccommodationAssignments($employee, $date) as $accommodationAssignment) {
                if ($accommodationAssignment->accommodation?->name) {
                    $accommodationNames->push($accommodationAssignment->accommodation->name);
                }
            }

            // Jeśli to kierowca - zapisz pierwsze przypisanie z ustalonej kolejności
            if ($assignment->position === VehiclePosition::DRIVER) {
                $driverPa = $this->findActiveProjectAssignments($employee, $date)->first();
                $driverAa = $this->findActiveAccommodationAssignments($employee, $date)->first();
                if ($driverAa?->accommodation?->name) {
                    $driverAccommodationName = $driverAa->accommodation->name;
                }
                if ($driverPa?->project?->name) {
                    $driverProjectName = $driverPa->project->name;
                }
            }
        }

        // 7. Usuń duplikaty
        $projectNames = $projectNames->unique()->values();
        $accommodationNames = $accommodationNames->unique()->values();

        // 8. Określ lokalizację stacjonowania (dom kierowcy lub baza)
        $stationingLocation = null;
        if ($driverAccommodationName) {
            $stationingLocation = $driverAccommodationName;
        } elseif (! $outsideBase) {
            $stationingLocation = Location::getBase()?->name;
        }

        // 9. Oblicz procent zapełnienia
        $occupancyPercentage = null;
        if ($vehicle->capacity && $vehicle->capacity > 0) {
            $occupancyPercentage = round(($occupancy / $vehicle->capacity) * 100, 1);
        }

        return [
            'in_transit' => false,
            'outside_base' => $outsideBase,
            'project_names' => $projectNames,
            'accommodation_names' => $accommodationNames,
            'driver_accommodation' => $driverAccommodationName,
            'driver_project' => $driverProjectName,
            'stationing_location' => $stationingLocation,
            'occupancy' => $occupancy,
            'capacity' => $vehicle->capacity,
            'occupancy_percentage' => $occupancyPercentage,
        ];
    }

    /**
     * Find if vehicle is in transit on a specific date.
     */
    protected function findVehicleInTransitEvent(Vehicle $vehicle, Carbon $date): ?LogisticsEvent
    {
        return LogisticsEvent::forLocationTracking()
            ->where('vehicle_id', $vehicle->id)
            ->whereIn('type', [LogisticsEventType::DEPARTURE, LogisticsEventType::RETURN, LogisticsEventType::TRANSFER])
            ->where('status', '!=', LogisticsEventStatus::CANCELLED)
            ->where('event_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>', $date);
            })
            ->orderBy('event_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Find last logistics event for vehicle on a specific date.
     */
    protected function findLastVehicleEvent(Vehicle $vehicle, Carbon $date): ?LogisticsEvent
    {
        return LogisticsEvent::forLocationTracking()
            ->where('vehicle_id', $vehicle->id)
            ->whereIn('type', [LogisticsEventType::DEPARTURE, LogisticsEventType::RETURN, LogisticsEventType::TRANSFER])
            ->where('status', '!=', LogisticsEventStatus::CANCELLED)
            ->where('event_date', '<=', $date)
            ->orderBy('event_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Synchronize outside_base flag for vehicle (lazy evaluation).
     */
    protected function syncVehicleOutsideBaseFlag(Vehicle $vehicle, Carbon $date): void
    {
        $lastEvent = $this->findLastVehicleEvent($vehicle, $date);

        $shouldBeOutside = false;
        $lastDepartureId = null;

        if ($lastEvent) {
            if ($lastEvent->type === LogisticsEventType::DEPARTURE) {
                $shouldBeOutside = true;
                $lastDepartureId = $lastEvent->id;
            } elseif ($lastEvent->type === LogisticsEventType::RETURN) {
                if ($lastEvent->end_date && $lastEvent->end_date <= $date) {
                    $shouldBeOutside = false;
                } else {
                    $shouldBeOutside = true;
                }
            } elseif ($lastEvent->type === LogisticsEventType::TRANSFER) {
                $shouldBeOutside = true;
            }
        }

        // Jeśli brak eventu, sprawdź aktywne przypisania
        if (! $lastEvent) {
            $hasActiveAssignments = VehicleAssignment::where('vehicle_id', $vehicle->id)
                ->where('start_date', '<=', $date)
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date))
                ->exists();

            $shouldBeOutside = $hasActiveAssignments;
        }

        // Aktualizuj flagę tylko jeśli się zmieniła
        if ($vehicle->outside_base !== $shouldBeOutside || $vehicle->last_departure_id !== $lastDepartureId) {
            $vehicle->update([
                'outside_base' => $shouldBeOutside,
                'last_departure_id' => $lastDepartureId,
            ]);
        }
    }

    /**
     * Ostatni wyjazd lub powrót (zjazd) — bez transferów. Transfer nie ustala „baza / teren / w podróży”
     * (np. przejazd lotnisko→baza po powrocie nie powinien nadpisywać stanu po zjeździe).
     */
    protected function findLastEvent(Employee $employee, Carbon $date): ?LogisticsEvent
    {
        return LogisticsEvent::query()
            ->whereHas('participants',
                fn ($q) => $q->where('employee_id', $employee->id)
            )
            ->whereIn('type', [LogisticsEventType::DEPARTURE, LogisticsEventType::RETURN])
            ->whereIn('status', [
                LogisticsEventStatus::PLANNED,
                LogisticsEventStatus::COMPLETED,
            ])
            ->where('event_date', '<=', $date)
            ->orderBy('event_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Stan „baza / poza bazą” z ostatniego zakończonego odcinka — bez „w podróży”
     * (to wyłącznie przez {@see LogisticsEvent::isEmployeeInTransit} w {@see getLocationStatus}).
     */
    protected function deriveStateFromEvent(?LogisticsEvent $event, Carbon $date): EmployeeLocationState
    {
        if (! $event) {
            return EmployeeLocationState::IN_BASE;
        }

        $dateDay = $date->copy()->startOfDay();

        if ($event->type === LogisticsEventType::DEPARTURE) {
            return EmployeeLocationState::OUTSIDE_BASE;
        }

        if ($event->type === LogisticsEventType::RETURN) {
            if ($event->end_date && $event->end_date->copy()->startOfDay()->lte($dateDay)) {
                return EmployeeLocationState::IN_BASE;
            }

            return EmployeeLocationState::OUTSIDE_BASE;
        }

        return EmployeeLocationState::IN_BASE;
    }

    protected function findActiveProjectAssignment(Employee $employee, Carbon $date): ?ProjectAssignment
    {
        return $this->findActiveProjectAssignments($employee, $date)->first();
    }

    /**
     * Wszystkie aktywne przypisania projektowe w danym dniu (posortowane: nowszy start, wyższe id).
     *
     * @return Collection<int, ProjectAssignment>
     */
    protected function findActiveProjectAssignments(Employee $employee, Carbon $date): Collection
    {
        $inRange = fn (ProjectAssignment $a) => $a->start_date <= $date
            && ($a->end_date === null || $a->end_date >= $date);

        if ($employee->relationLoaded('assignments')) {
            $assignments = $employee->assignments
                ->filter($inRange)
                ->sort($this->sortAssignmentsByStartThenId(...))
                ->values();

            $assignments->loadMissing(['project', 'role']);

            return $assignments;
        }

        return $employee->assignments()
            ->where('start_date', '<=', $date)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date))
            ->orderBy('start_date', 'desc')
            ->orderBy('id', 'desc')
            ->with(['project', 'role'])
            ->get();
    }

    protected function findActiveAccommodationAssignment(Employee $employee, Carbon $date): ?AccommodationAssignment
    {
        return $this->findActiveAccommodationAssignments($employee, $date)->first();
    }

    /**
     * @return Collection<int, AccommodationAssignment>
     */
    protected function findActiveAccommodationAssignments(Employee $employee, Carbon $date): Collection
    {
        $inRange = fn (AccommodationAssignment $a) => $a->start_date <= $date
            && ($a->end_date === null || $a->end_date >= $date);

        if ($employee->relationLoaded('accommodationAssignments')) {
            $assignments = $employee->accommodationAssignments
                ->filter($inRange)
                ->sort($this->sortAssignmentsByStartThenId(...))
                ->values();

            $assignments->loadMissing('accommodation');

            return $assignments;
        }

        return $employee->accommodationAssignments()
            ->where('start_date', '<=', $date)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date))
            ->orderBy('start_date', 'desc')
            ->orderBy('id', 'desc')
            ->with('accommodation')
            ->get();
    }

    /**
     * Aktywne przypisania pojazdu (bez „nóg” zjazdowych).
     *
     * @return Collection<int, VehicleAssignment>
     */
    protected function findActiveVehicleAssignments(Employee $employee, Carbon $date): Collection
    {
        if ($employee->relationLoaded('vehicleAssignments')) {
            $filtered = $employee->vehicleAssignments
                ->filter(fn (VehicleAssignment $a) => ! $a->is_return_trip
                    && $a->start_date <= $date
                    && ($a->end_date === null || $a->end_date >= $date))
                ->sort($this->sortAssignmentsByStartThenId(...))
                ->values();
            foreach ($filtered as $va) {
                $va->loadMissing('vehicle');
            }

            return $filtered;
        }

        return $employee->vehicleAssignments()
            ->where('is_return_trip', false)
            ->where('start_date', '<=', $date)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date))
            ->orderBy('start_date', 'desc')
            ->orderBy('id', 'desc')
            ->with('vehicle')
            ->get();
    }

    /**
     * @param  ProjectAssignment|AccommodationAssignment|VehicleAssignment  $a
     * @param  ProjectAssignment|AccommodationAssignment|VehicleAssignment  $b
     */
    protected function sortAssignmentsByStartThenId($a, $b): int
    {
        $c = $b->start_date <=> $a->start_date;

        return $c !== 0 ? $c : $b->id <=> $a->id;
    }
}
