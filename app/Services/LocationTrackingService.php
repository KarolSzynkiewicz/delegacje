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
use Illuminate\Support\Facades\DB;

class LocationTrackingService
{
    /** @var array<string, array{0: list<int>, 1: list<int>, 2: list<int>}> */
    private array $employeeLocationCohorts = [];

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

    /**
     * ID pracowników, którzy w danym dniu NIE są w bazie (w podróży albo poza bazą).
     * Do list wyjazdu — zostają tylko osoby w bazie.
     *
     * @return list<int>
     */
    public function employeeIdsNotInBaseOn(Carbon $date): array
    {
        [$withProject, $inTransit, $outsideByLastEvent] = $this->employeeLocationCohortsOn($date);

        return array_values(array_unique(array_merge($withProject, $inTransit, $outsideByLastEvent)));
    }

    /**
     * ID pracowników poza bazą, ale nie w trakcie podróży.
     * Zgodne z getLocationStatus() === OUTSIDE_BASE, bez zapytania na osobę.
     *
     * @return list<int>
     */
    public function employeeIdsOutsideBaseOn(Carbon $date): array
    {
        [$withProject, $inTransit, $outsideByLastEvent] = $this->employeeLocationCohortsOn($date);

        $inTransitSet = array_fill_keys($inTransit, true);
        $outside = [];
        foreach (array_merge($withProject, $outsideByLastEvent) as $id) {
            if (! isset($inTransitSet[$id])) {
                $outside[$id] = true;
            }
        }

        return array_map('intval', array_keys($outside));
    }

    /**
     * ID pracowników poza bazą albo w podróży — wyłącznie po logistyce (wyjazd/zjazd/transfer),
     * bez heurystyki „ma projekt = poza bazą”.
     *
     * @return list<int>
     */
    public function employeeIdsInFieldByTripOn(Carbon $date): array
    {
        [, $inTransit, $outsideByLastEvent] = $this->employeeLocationCohortsOn($date);

        return array_values(array_unique(array_merge($inTransit, $outsideByLastEvent)));
    }

    /**
     * @return array{0: list<int>, 1: list<int>, 2: list<int>}
     */
    protected function employeeLocationCohortsOn(Carbon $date): array
    {
        $dateDay = $date->copy()->startOfDay();
        $cacheKey = $dateDay->toDateString();
        if (isset($this->employeeLocationCohorts[$cacheKey])) {
            return $this->employeeLocationCohorts[$cacheKey];
        }

        $withProject = ProjectAssignment::query()
            ->where('start_date', '<=', $dateDay)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $dateDay))
            ->pluck('employee_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $inTransit = DB::table('logistics_events as e')
            ->join('logistics_event_participants as p', 'p.logistics_event_id', '=', 'e.id')
            ->where(function ($q) {
                $q->where('e.type', '!=', LogisticsEventType::TRANSFER->value)
                    ->orWhere('e.has_reassignment', true);
            })
            ->whereIn('e.type', [
                LogisticsEventType::DEPARTURE->value,
                LogisticsEventType::RETURN->value,
                LogisticsEventType::TRANSFER->value,
            ])
            ->whereIn('e.status', [
                LogisticsEventStatus::PLANNED->value,
                LogisticsEventStatus::COMPLETED->value,
            ])
            ->where('e.event_date', '<=', $dateDay)
            ->where('e.end_date', '>', $dateDay)
            ->distinct()
            ->pluck('p.employee_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ranked = DB::table('logistics_event_participants as p')
            ->join('logistics_events as e', 'e.id', '=', 'p.logistics_event_id')
            ->whereIn('e.type', [
                LogisticsEventType::DEPARTURE->value,
                LogisticsEventType::RETURN->value,
            ])
            ->whereIn('e.status', [
                LogisticsEventStatus::PLANNED->value,
                LogisticsEventStatus::COMPLETED->value,
            ])
            ->where('e.event_date', '<=', $dateDay)
            ->select([
                'p.employee_id',
                'e.type',
                'e.end_date',
                'e.event_date',
            ])
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY p.employee_id ORDER BY e.event_date DESC, e.id DESC) as rn');

        $outsideByLastEvent = [];
        foreach (DB::query()->fromSub($ranked, 'ranked')->where('rn', 1)->get() as $row) {
            $event = new LogisticsEvent([
                'type' => $row->type,
                'end_date' => $row->end_date,
                'event_date' => $row->event_date,
            ]);
            if ($this->deriveStateFromEvent($event, $dateDay) !== EmployeeLocationState::IN_BASE) {
                $outsideByLastEvent[] = (int) $row->employee_id;
            }
        }

        return $this->employeeLocationCohorts[$cacheKey] = [$withProject, $inTransit, $outsideByLastEvent];
    }

    /**
     * Pojazdy poza bazą na dany dzień — ten sam werdykt co getVehicleLocationStatus()['outside_base'],
     * bez liczenia załogi i bez zapisu cache.
     *
     * @param  iterable<int>  $vehicleIds
     * @return list<int>
     */
    public function vehicleIdsOutsideBaseOn(iterable $vehicleIds, Carbon $date): array
    {
        $ids = collect($vehicleIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        $placementTypes = array_map(
            fn (LogisticsEventType $type) => $type->value,
            $this->vehiclePlacementEventTypes()
        );

        $ranked = DB::table('logistics_events as e')
            ->whereIn('e.vehicle_id', $ids)
            ->where('e.status', '!=', LogisticsEventStatus::CANCELLED->value)
            ->where('e.event_date', '<=', $date)
            ->whereIn('e.type', $placementTypes)
            ->where(function ($q) {
                $q->where('e.type', '!=', LogisticsEventType::TRANSFER->value)
                    ->orWhere('e.has_reassignment', true);
            })
            ->select([
                'e.vehicle_id',
                'e.id',
                'e.type',
                'e.end_date',
                'e.event_date',
                'e.sets_outside_base',
            ])
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY e.vehicle_id ORDER BY e.event_date DESC, e.id DESC) as rn');

        $lastByVehicle = [];
        foreach (DB::query()->fromSub($ranked, 'ranked')->where('rn', 1)->get() as $row) {
            $lastByVehicle[(int) $row->vehicle_id] = new LogisticsEvent([
                'type' => $row->type,
                'end_date' => $row->end_date,
                'event_date' => $row->event_date,
                'sets_outside_base' => (bool) $row->sets_outside_base,
            ]);
        }

        $outside = [];
        $noEvent = [];
        foreach ($ids as $vehicleId) {
            $lastEvent = $lastByVehicle[$vehicleId] ?? null;
            if ($lastEvent === null) {
                $noEvent[] = $vehicleId;

                continue;
            }

            $placed = $this->placedByEvent($lastEvent, $date);
            if ($placed !== null && $placed[0]) {
                $outside[] = $vehicleId;
            }
        }

        if ($noEvent !== []) {
            $withAssignment = VehicleAssignment::query()
                ->whereIn('vehicle_id', $noEvent)
                ->where('start_date', '<=', $date)
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date))
                ->distinct()
                ->pluck('vehicle_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $outside = array_merge($outside, $withAssignment);
        }

        return array_values(array_unique($outside));
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
        $lastEvent = $this->findLastVehicleEvent($vehicle, $date);
        [$outsideBase, $lastDepartureId] = $this->placementFromEvent($vehicle, $lastEvent, $date);
        $inTransit = $lastEvent !== null && $this->vehicleEventIsInTransit($lastEvent, $date);
        $this->persistVehiclePlacementCache($vehicle, $date, $outsideBase, $lastDepartureId);
        $source = $this->placementSource($lastEvent);

        // 3. Jeśli w podróży, zwróć podstawowe informacje
        if ($inTransit) {
            return [
                'in_transit' => true,
                'outside_base' => $outsideBase,
                'source_label' => $source['label'],
                'source_url' => $source['url'],
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
            'source_label' => $source['label'],
            'source_url' => $source['url'],
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
     * Vehicle ids whose latest placement event still covers $date as travel.
     * A later correction with no end date clears an older open departure.
     *
     * @param  iterable<int>  $vehicleIds
     * @return array<int, true>
     */
    public function inTransitVehicleIds(iterable $vehicleIds, Carbon $date): array
    {
        $ids = collect($vehicleIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        $events = LogisticsEvent::forLocationTracking()
            ->whereIn('vehicle_id', $ids)
            ->whereIn('type', $this->vehiclePlacementEventTypes())
            ->where('status', '!=', LogisticsEventStatus::CANCELLED)
            ->where('event_date', '<=', $date)
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->get(['id', 'vehicle_id', 'type', 'event_date', 'end_date']);

        $lastByVehicle = [];
        foreach ($events as $event) {
            $vehicleId = (int) $event->vehicle_id;
            if (! array_key_exists($vehicleId, $lastByVehicle)) {
                $lastByVehicle[$vehicleId] = $event;
            }
        }

        $inTransit = [];
        foreach ($lastByVehicle as $vehicleId => $event) {
            if ($this->vehicleEventIsInTransit($event, $date)) {
                $inTransit[$vehicleId] = true;
            }
        }

        return $inTransit;
    }

    /**
     * @return list<LogisticsEventType>
     */
    protected function vehiclePlacementEventTypes(): array
    {
        return [
            LogisticsEventType::DEPARTURE,
            LogisticsEventType::RETURN,
            LogisticsEventType::TRANSFER,
            LogisticsEventType::PLACEMENT_CORRECTION,
        ];
    }

    /**
     * Find last logistics event for vehicle on a specific date.
     */
    protected function findLastVehicleEvent(Vehicle $vehicle, Carbon $date): ?LogisticsEvent
    {
        return LogisticsEvent::forLocationTracking()
            ->where('vehicle_id', $vehicle->id)
            ->whereIn('type', $this->vehiclePlacementEventTypes())
            ->where('status', '!=', LogisticsEventStatus::CANCELLED)
            ->where('event_date', '<=', $date)
            ->orderBy('event_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * In transit only when the latest event itself still covers the day.
     * A correction has no travel window, so it ends an older open trip for this vehicle.
     */
    protected function vehicleEventIsInTransit(LogisticsEvent $event, Carbon $date): bool
    {
        if ($event->type === LogisticsEventType::PLACEMENT_CORRECTION) {
            return false;
        }

        if ($event->event_date !== null && $event->event_date->greaterThan($date)) {
            return false;
        }

        return $event->end_date === null || $event->end_date->greaterThan($date);
    }

    /**
     * @return array{0: bool, 1: ?int}|null
     */
    protected function placedByEvent(LogisticsEvent $event, Carbon $date): ?array
    {
        return match ($event->type) {
            LogisticsEventType::DEPARTURE => [true, $event->id],
            LogisticsEventType::RETURN => [! ($event->end_date && $event->end_date <= $date), null],
            LogisticsEventType::TRANSFER => [true, null],
            LogisticsEventType::PLACEMENT_CORRECTION => [(bool) $event->sets_outside_base, null],
            default => null,
        };
    }

    /**
     * @return array{0: bool, 1: ?int}
     */
    protected function placementFromEvent(Vehicle $vehicle, ?LogisticsEvent $lastEvent, Carbon $date): array
    {
        if ($lastEvent && ($placed = $this->placedByEvent($lastEvent, $date)) !== null) {
            return $placed;
        }

        $hasActiveAssignments = VehicleAssignment::where('vehicle_id', $vehicle->id)
            ->where('start_date', '<=', $date)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date))
            ->exists();

        return [$hasActiveAssignments, null];
    }

    /**
     * Cache „na dziś” dla planera. Podgląd innego dnia liczy stan, ale go nie zapisuje.
     */
    protected function persistVehiclePlacementCache(Vehicle $vehicle, Carbon $date, bool $shouldBeOutside, ?int $lastDepartureId): void
    {
        if (! $date->isSameDay(now())) {
            return;
        }

        if ($vehicle->outside_base === $shouldBeOutside && $vehicle->last_departure_id === $lastDepartureId) {
            return;
        }

        $vehicle->update([
            'outside_base' => $shouldBeOutside,
            'last_departure_id' => $lastDepartureId,
        ]);
    }

    /**
     * @return array{label: ?string, url: ?string}
     */
    protected function placementSource(?LogisticsEvent $event): array
    {
        if (! $event) {
            return ['label' => null, 'url' => null];
        }

        $label = $event->type->label().' '.$event->event_date->format('d.m.Y');

        $url = match ($event->type) {
            LogisticsEventType::DEPARTURE => route('departures.show', $event),
            LogisticsEventType::RETURN => route('return-trips.show', $event),
            LogisticsEventType::TRANSFER => route('transfers.show', $event),
            default => null,
        };

        return ['label' => $label, 'url' => $url];
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
