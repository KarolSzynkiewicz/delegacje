<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Vehicle;
use App\Models\Location;
use App\Models\ProjectAssignment;
use App\Models\AccommodationAssignment;
use App\Models\VehicleAssignment;
use App\Models\LogisticsEvent;
use App\Enums\LogisticsEventType;
use App\Enums\LogisticsEventStatus;
use App\Enums\EmployeeLocationState;
use App\Enums\VehiclePosition;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class LocationTrackingService
{
    public function getLocationStatus(Employee $employee, Carbon $date): array
    {
        $lastEvent = $this->findLastEvent($employee, $date);
        $state = $this->deriveStateFromEvent($lastEvent, $date);
        
        $projectAssignment = $this->findActiveProjectAssignment($employee, $date);
        if ($projectAssignment && $state === EmployeeLocationState::IN_BASE) {
            $state = EmployeeLocationState::OUTSIDE_BASE;
        }
        
        $accommodationAssignment = $this->findActiveAccommodationAssignment($employee, $date);
        
        return [
            'state' => $state,
            'project_name' => $projectAssignment?->project?->name,
            'accommodation_name' => $accommodationAssignment?->accommodation?->name,
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
            return "W PODRÓŻY";
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
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
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
     * @param Vehicle $vehicle
     * @param Carbon $date
     * @return Location|string|null Returns Location, "W PODRÓŻY" string, or null
     */
    public function getVehicleLocationOnDate(Vehicle $vehicle, Carbon $date): Location|string|null
    {
        $status = $this->getVehicleLocationStatus($vehicle, $date);
        
        if ($status['in_transit']) {
            return "W PODRÓŻY";
        }
        
        // Zwróć nazwę lokalizacji stacjonowania (dom kierowcy lub baza)
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
     * - project_locations: Collection<string> - unikatowe nazwy lokalizacji projektów przypisanych osób
     * - accommodation_locations: Collection<string> - unikatowe nazwy lokalizacji domów przypisanych osób
     * - driver_accommodation: string|null - nazwa domu kierowcy
     * - driver_project: string|null - nazwa projektu kierowcy
     * - stationing_location: string|null - gdzie stacjonuje (nazwa domu kierowcy lub baza)
     * - occupancy: int - liczba przypisanych osób na daną datę
     * - capacity: int|null - pojemność pojazdu
     * - occupancy_percentage: float|null - procent zapełnienia (0-100)
     * 
     * @param Vehicle $vehicle
     * @param Carbon $date
     * @return array
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
                'project_locations' => collect(),
                'accommodation_locations' => collect(),
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
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date))
            ->with(['employee'])
            ->get();

        // 5. Oblicz occupancy (liczba unikalnych pracowników)
        $uniqueEmployeeIds = $activeAssignments->pluck('employee_id')->unique();
        $occupancy = $uniqueEmployeeIds->count();

        // 6. Zbierz unikatowe nazwy lokalizacji projektów i domów
        $projectLocationNames = collect();
        $accommodationLocationNames = collect();
        $driverAccommodationName = null;
        $driverProjectName = null;

        foreach ($activeAssignments as $assignment) {
            $employee = $assignment->employee;
            
            if (!$employee) {
                continue;
            }

            // Projekty (gdzie pracują)
            $projectAssignment = $this->findActiveProjectAssignment($employee, $date);
            if ($projectAssignment?->project?->location?->name) {
                $projectLocationNames->push($projectAssignment->project->location->name);
            }

            // Zakwaterowania (gdzie mieszkają)
            $accommodationAssignment = $this->findActiveAccommodationAssignment($employee, $date);
            if ($accommodationAssignment?->accommodation?->location?->name) {
                $accommodationLocationNames->push($accommodationAssignment->accommodation->location->name);
            }

            // Jeśli to kierowca - zapisz jego lokalizacje
            if ($assignment->position === VehiclePosition::DRIVER) {
                if ($accommodationAssignment?->accommodation?->location?->name) {
                    $driverAccommodationName = $accommodationAssignment->accommodation->location->name;
                }
                if ($projectAssignment?->project?->location?->name) {
                    $driverProjectName = $projectAssignment->project->location->name;
                }
            }
        }

        // 7. Usuń duplikaty z nazw lokalizacji
        $projectLocationNames = $projectLocationNames->unique()->values();
        $accommodationLocationNames = $accommodationLocationNames->unique()->values();

        // 8. Określ lokalizację stacjonowania (dom kierowcy lub baza)
        $stationingLocation = null;
        if ($driverAccommodationName) {
            $stationingLocation = $driverAccommodationName;
        } elseif (!$outsideBase) {
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
            'project_locations' => $projectLocationNames,
            'accommodation_locations' => $accommodationLocationNames,
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
     * 
     * @param Vehicle $vehicle
     * @param Carbon $date
     * @return LogisticsEvent|null
     */
    protected function findVehicleInTransitEvent(Vehicle $vehicle, Carbon $date): ?LogisticsEvent
    {
        return LogisticsEvent::where('vehicle_id', $vehicle->id)
            ->whereIn('type', [LogisticsEventType::DEPARTURE, LogisticsEventType::RETURN])
            ->where('status', '!=', LogisticsEventStatus::CANCELLED)
            ->where('event_date', '<=', $date)
            ->where(function($q) use ($date) {
                // Pojazd jest w podróży jeśli data jest między event_date a end_date
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>', $date);
            })
            ->orderBy('event_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Find last logistics event for vehicle on a specific date.
     * 
     * @param Vehicle $vehicle
     * @param Carbon $date
     * @return LogisticsEvent|null
     */
    protected function findLastVehicleEvent(Vehicle $vehicle, Carbon $date): ?LogisticsEvent
    {
        return LogisticsEvent::where('vehicle_id', $vehicle->id)
            ->whereIn('type', [LogisticsEventType::DEPARTURE, LogisticsEventType::RETURN])
            ->where('status', '!=', LogisticsEventStatus::CANCELLED)
            ->where('event_date', '<=', $date)
            ->orderBy('event_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Synchronize outside_base flag for vehicle (lazy evaluation).
     * 
     * @param Vehicle $vehicle
     * @param Carbon $date
     * @return void
     */
    protected function syncVehicleOutsideBaseFlag(Vehicle $vehicle, Carbon $date): void
    {
        $lastEvent = $this->findLastVehicleEvent($vehicle, $date);
        
        $shouldBeOutside = false;
        $lastDepartureId = null;

        if ($lastEvent) {
            if ($lastEvent->type === LogisticsEventType::DEPARTURE) {
                // Wyjazd - pojazd jest poza bazą
                $shouldBeOutside = true;
                $lastDepartureId = $lastEvent->id;
            } elseif ($lastEvent->type === LogisticsEventType::RETURN) {
                // Powrót - sprawdź czy już zakończony
                if ($lastEvent->end_date && $lastEvent->end_date <= $date) {
                    $shouldBeOutside = false; // Wrócił do bazy
                } else {
                    $shouldBeOutside = true; // Wciąż w podróży powrotnej
                }
            }
        }

        // Jeśli brak eventu, sprawdź aktywne przypisania
        if (!$lastEvent) {
            $hasActiveAssignments = VehicleAssignment::where('vehicle_id', $vehicle->id)
                ->where('start_date', '<=', $date)
                ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date))
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

    protected function findLastEvent(Employee $employee, Carbon $date): ?LogisticsEvent
    {
        return LogisticsEvent::whereHas('participants', 
            fn($q) => $q->where('employee_id', $employee->id)
        )
        ->whereIn('type', [LogisticsEventType::DEPARTURE, LogisticsEventType::RETURN])
        ->where('status', '!=', LogisticsEventStatus::CANCELLED)
        ->where('event_date', '<=', $date)
        ->orderBy('event_date', 'desc')
        ->orderBy('id', 'desc')
        ->first();
    }

    protected function deriveStateFromEvent(?LogisticsEvent $event, Carbon $date): EmployeeLocationState
    {
        if (!$event) {
            return EmployeeLocationState::IN_BASE;
        }

        if ($event->type === LogisticsEventType::DEPARTURE) {
            if ($event->end_date && $event->event_date <= $date && $date < $event->end_date) {
                return EmployeeLocationState::IN_TRANSIT;
            }
            return EmployeeLocationState::OUTSIDE_BASE;
        }

        if ($event->type === LogisticsEventType::RETURN) {
            if ($event->end_date && $event->end_date <= $date) {
                return EmployeeLocationState::IN_BASE;
            }
            return EmployeeLocationState::IN_TRANSIT;
        }

        return EmployeeLocationState::IN_BASE;
    }

    protected function findActiveProjectAssignment(Employee $employee, Carbon $date): ?ProjectAssignment
    {
        if ($employee->relationLoaded('assignments')) {
            $assignment = $employee->assignments
                ->filter(fn($a) => $a->start_date <= $date
                    && ($a->end_date === null || $a->end_date >= $date))
                ->sortByDesc('start_date')
                ->sortByDesc('id')
                ->first();
            
            if ($assignment && !$assignment->relationLoaded('project')) {
                $assignment->load('project');
            }
            
            return $assignment;
        }
        
        return $employee->assignments()
            ->where('start_date', '<=', $date)
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date))
            ->orderBy('start_date', 'desc')
            ->orderBy('id', 'desc')
            ->with('project')
            ->first();
    }

    protected function findActiveAccommodationAssignment(Employee $employee, Carbon $date): ?AccommodationAssignment
    {
        if ($employee->relationLoaded('accommodationAssignments')) {
            $assignment = $employee->accommodationAssignments
                ->filter(fn($a) => $a->start_date <= $date
                    && ($a->end_date === null || $a->end_date >= $date))
                ->sortByDesc('start_date')
                ->sortByDesc('id')
                ->first();
            
            if ($assignment && !$assignment->relationLoaded('accommodation')) {
                $assignment->load('accommodation');
            }
            
            return $assignment;
        }
        
        return $employee->accommodationAssignments()
            ->where('start_date', '<=', $date)
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date))
            ->orderBy('start_date', 'desc')
            ->orderBy('id', 'desc')
            ->with('accommodation')
            ->first();
    }
}
