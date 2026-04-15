<?php

namespace App\Livewire\Steps;

use App\Enums\Currency;
use App\Models\Accommodation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Vehicle;
use App\Services\GeocodingService;
use App\Services\LocationTrackingService;
use App\Services\RoutePlanningService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Step4RoutePlanning extends Component
{
    // Dane otrzymane z rodzica (read-only)
    public $departureDate;

    public $endDate;

    public $vehicleId;

    public $accommodationAssignments = [];

    public $assignmentRanges = [];

    public $vehicleAssignments = [];

    public $ticketCostsByEmployee = [];

    // Shared airports (for public transport)
    public $sharedStartAirportLocationId = null;

    public $sharedEndAirportLocationId = null;

    // Dane trasy
    public $routeWaypoints = []; // Array of accommodation IDs in order

    public $routeData = null;

    public $isPlanningRoute = false;

    public $routeError = null;

    // Transfer config (only for public transport)
    public $transferVehicleId = null;

    public $transferDriverEmployeeId = null;

    public $transferDriverBonusAmount = null;

    public $transferDriverBonusCurrency = 'PLN';

    public $transferPickupLocationId = null; // optional: where transfer car departs from before airport

    // Optional manual extra stop (user-added)
    public $extraStopLocationId = null;

    /** Notatki do przystanków ręcznych (loc) — klucz: id lokalizacji jako string */
    public array $locationStopNotes = [];

    // Manual distance fallback (mainly for transfer when ORS cannot route)
    public $manualRouteDistanceKm = null;

    public $manualRouteDurationMinutes = null;

    public $isManualRouteDistance = false;

    public $manualRouteHint = null; // e.g. failing location name from ORS

    // Internal IDs (no Eloquent objects as public props)
    public $baseLocationId = null;

    public $accommodationIds = [];

    protected $geocodingService;

    protected $routePlanningService;

    public function boot(GeocodingService $geocodingService, RoutePlanningService $routePlanningService)
    {
        $this->geocodingService = $geocodingService;
        $this->routePlanningService = $routePlanningService;
    }

    public function mount(
        $departureDate,
        $endDate,
        $vehicleId = null,
        $accommodationAssignments = [],
        $assignmentRanges = [],
        $vehicleAssignments = [],
        $ticketCostsByEmployee = [],
        $sharedStartAirportLocationId = null,
        $sharedEndAirportLocationId = null,
        $initialRouteWaypoints = [],
        $initialLocationStopNotes = [],
        $initialRouteDistance = null,
        $initialRouteDuration = null,
        $initialRouteManual = false,
        $initialTransferConfig = [],
    ) {
        $this->departureDate = $departureDate;
        $this->endDate = $endDate;
        $this->vehicleId = $vehicleId;
        $this->accommodationAssignments = $accommodationAssignments;
        $this->assignmentRanges = $assignmentRanges;
        $this->vehicleAssignments = $vehicleAssignments;
        $this->ticketCostsByEmployee = $ticketCostsByEmployee;
        $this->sharedStartAirportLocationId = $sharedStartAirportLocationId;
        $this->sharedEndAirportLocationId = $sharedEndAirportLocationId;

        $this->loadLocations();

        $initialRouteWaypoints = is_array($initialRouteWaypoints) ? $initialRouteWaypoints : [];
        $this->locationStopNotes = is_array($initialLocationStopNotes) ? $initialLocationStopNotes : [];
        $initialTransferConfig = is_array($initialTransferConfig) ? $initialTransferConfig : [];

        $this->hydrateTransferFieldsFromParent($initialTransferConfig);

        if (! empty($initialRouteWaypoints)) {
            $this->routeWaypoints = array_values($initialRouteWaypoints);
        } else {
            $this->initializeWaypoints();
        }

        $restored = $this->hydrateRouteMetricsFromParent($initialRouteDistance, $initialRouteDuration, (bool) $initialRouteManual);

        // Bez automatycznego wywołania API przy wejściu — użytkownik klika „Przelicz trasę”.
        if ($restored || ! empty($this->routeWaypoints)) {
            $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
        }

        $this->dispatchTransferConfig();
    }

    /**
     * Po zmianie przystanków / pickupu: kasuj stare km/czas i zsynchronizuj kolejność do rodzica (bez API).
     */
    protected function invalidateRouteMetricsAndSyncToParent(): void
    {
        $this->routeData = null;
        $this->isManualRouteDistance = false;
        $this->manualRouteDistanceKm = null;
        $this->manualRouteDurationMinutes = null;
        $this->routeError = null;
        $this->manualRouteHint = null;
        $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
        $this->dispatchTransferConfig();
    }

    protected function hydrateTransferFieldsFromParent(array $tc): void
    {
        if (! empty($this->vehicleId)) {
            return;
        }

        if (array_key_exists('vehicle_id', $tc) && $tc['vehicle_id'] !== null && $tc['vehicle_id'] !== '') {
            $this->transferVehicleId = (int) $tc['vehicle_id'];
        }
        if (array_key_exists('driver_employee_id', $tc) && $tc['driver_employee_id'] !== null && $tc['driver_employee_id'] !== '') {
            $this->transferDriverEmployeeId = (int) $tc['driver_employee_id'];
        }
        if (array_key_exists('bonus_amount', $tc) && $tc['bonus_amount'] !== null && $tc['bonus_amount'] !== '') {
            $this->transferDriverBonusAmount = $tc['bonus_amount'];
        }
        if (! empty($tc['bonus_currency'])) {
            $this->transferDriverBonusCurrency = (string) $tc['bonus_currency'];
        }
        if (array_key_exists('pickup_location_id', $tc) && $tc['pickup_location_id'] !== null && $tc['pickup_location_id'] !== '') {
            $this->transferPickupLocationId = (int) $tc['pickup_location_id'];
        }
    }

    /**
     * Odtwarzanie dystansu/czasu z rodzica (po powrocie z innego kroku — bez wymuszania nowego planowania API).
     */
    protected function hydrateRouteMetricsFromParent($distance, $duration, bool $manual): bool
    {
        if ($distance === null || $distance === '' || $duration === null || $duration === '') {
            return false;
        }
        if (! is_numeric($distance) || (float) $distance <= 0) {
            return false;
        }
        if (! is_numeric($duration) || (int) $duration <= 0) {
            return false;
        }

        $this->routeData = [
            'distance' => (float) $distance,
            'duration' => (int) $duration,
        ];
        $this->isManualRouteDistance = $manual;
        $this->syncManualFieldsFromRouteData();

        return true;
    }

    protected function buildRoutePlannedPayload(): array
    {
        return [
            'route_distance' => $this->routeData['distance'] ?? null,
            'route_duration' => $this->routeData['duration'] ?? null,
            'route_waypoints' => $this->routeWaypoints,
            'location_stop_notes' => $this->getLocationStopNotesPayload(),
            'route_distance_is_manual' => (bool) $this->isManualRouteDistance,
        ];
    }

    protected function syncManualFieldsFromRouteData(): void
    {
        if (empty($this->routeData) || ! isset($this->routeData['distance'], $this->routeData['duration'])) {
            return;
        }
        $this->manualRouteDistanceKm = round((float) $this->routeData['distance'], 3);
        $secs = (int) $this->routeData['duration'];
        $this->manualRouteDurationMinutes = max(1, (int) round($secs / 60));
    }

    // ─── Location loading ──────────────────────────────────────────────────────

    protected function loadLocations(): void
    {
        // Load base location
        $base = Location::getBase();
        if ($base) {
            $this->baseLocationId = $base->id;
            if (! $base->hasCoordinates()) {
                $this->geocodingService->geocodeLocation($base);
            }
        }

        // Geocode shared airports if needed
        foreach ([$this->sharedStartAirportLocationId, $this->sharedEndAirportLocationId] as $airportId) {
            if ($airportId) {
                $airport = Location::find($airportId);
                if ($airport && ! $airport->hasCoordinates()) {
                    $this->geocodingService->geocodeLocation($airport);
                }
            }
        }

        // Geocode pickup location if set
        if ($this->transferPickupLocationId) {
            $pickup = Location::find($this->transferPickupLocationId);
            if ($pickup && ! $pickup->hasCoordinates()) {
                $this->geocodingService->geocodeLocation($pickup);
            }
        }

        // Collect unique accommodation IDs
        $accommodationIds = [];
        foreach ($this->accommodationAssignments as $assignment) {
            if (! is_array($assignment) || empty($assignment['accommodation_id'])) {
                continue;
            }
            $accommodationIds[] = (int) $assignment['accommodation_id'];
        }
        $accommodationIds = array_values(array_unique($accommodationIds));

        // Geocode accommodations that are missing coordinates
        $accommodations = Accommodation::whereIn('id', $accommodationIds)->get();
        foreach ($accommodations as $accommodation) {
            if (! $accommodation->hasCoordinates()) {
                $address = $accommodation->getFullAddress();
                if (! empty($address)) {
                    try {
                        $coordinates = $this->geocodingService->geocode($address);
                        if ($coordinates && isset($coordinates['latitude'], $coordinates['longitude'])) {
                            $accommodation->update([
                                'latitude' => $coordinates['latitude'],
                                'longitude' => $coordinates['longitude'],
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Geocoding exception', ['id' => $accommodation->id, 'error' => $e->getMessage()]);
                    }
                }
            }
        }

        $this->accommodationIds = $accommodationIds;
    }

    /** Notatki przy przystankach loc — wysyłane do rodzica razem z trasą */
    protected function getLocationStopNotesPayload(): array
    {
        $out = [];
        foreach ($this->routeWaypoints as $key) {
            $p = $this->parseWaypointKey($key);
            if ($p['type'] === 'loc') {
                $id = (string) $p['id'];
                $out[$id] = trim((string) ($this->locationStopNotes[$id] ?? ''));
            }
        }

        return $out;
    }

    public function saveLocationStopNotesToParent(): void
    {
        if (empty($this->routeData) || ! isset($this->routeData['distance'], $this->routeData['duration'])) {
            return;
        }
        $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
    }

    public function updatedLocationStopNotes(): void
    {
        $this->saveLocationStopNotesToParent();
    }

    protected function pruneLocationStopNotes(): void
    {
        $allowed = [];
        foreach ($this->routeWaypoints as $key) {
            $p = $this->parseWaypointKey($key);
            if ($p['type'] === 'loc') {
                $allowed[] = (string) $p['id'];
            }
        }
        $this->locationStopNotes = array_intersect_key($this->locationStopNotes, array_flip($allowed));
    }

    protected function initializeWaypoints(): void
    {
        $this->routeWaypoints = array_values(array_map(fn ($id) => 'acc:'.((int) $id), $this->accommodationIds));
    }

    // ─── Computed properties ───────────────────────────────────────────────────

    public function getIsPublicTransportProperty(): bool
    {
        return empty($this->vehicleId);
    }

    public function getBaseLocationDataProperty(): array
    {
        if (! $this->baseLocationId) {
            return [];
        }
        $loc = Location::find($this->baseLocationId);
        if (! $loc) {
            return [];
        }

        return [
            'id' => $loc->id,
            'name' => $loc->name,
            'address' => $loc->address,
            'latitude' => $loc->latitude ? (float) $loc->latitude : null,
            'longitude' => $loc->longitude ? (float) $loc->longitude : null,
        ];
    }

    public function getStartAirportDataProperty(): array
    {
        if (! $this->sharedStartAirportLocationId) {
            return [];
        }
        $loc = Location::find($this->sharedStartAirportLocationId);
        if (! $loc) {
            return [];
        }

        return [
            'id' => $loc->id,
            'name' => $loc->name,
            'address' => $loc->address ?? '',
            'latitude' => $loc->latitude ? (float) $loc->latitude : null,
            'longitude' => $loc->longitude ? (float) $loc->longitude : null,
        ];
    }

    public function getEndAirportDataProperty(): array
    {
        if (! $this->sharedEndAirportLocationId) {
            return [];
        }
        $loc = Location::find($this->sharedEndAirportLocationId);
        if (! $loc) {
            return [];
        }

        return [
            'id' => $loc->id,
            'name' => $loc->name,
            'address' => $loc->address ?? '',
            'latitude' => $loc->latitude ? (float) $loc->latitude : null,
            'longitude' => $loc->longitude ? (float) $loc->longitude : null,
        ];
    }

    public function getPickupLocationDataProperty(): array
    {
        if (! $this->transferPickupLocationId) {
            return [];
        }
        $loc = Location::find($this->transferPickupLocationId);
        if (! $loc) {
            return [];
        }

        return [
            'id' => $loc->id,
            'name' => $loc->name,
            'address' => $loc->address ?? '',
            'latitude' => $loc->latitude ? (float) $loc->latitude : null,
            'longitude' => $loc->longitude ? (float) $loc->longitude : null,
        ];
    }

    public function getAccommodationsDataProperty(): array
    {
        if (empty($this->accommodationIds)) {
            return [];
        }
        $rows = Accommodation::whereIn('id', $this->accommodationIds)->get();
        $result = [];
        foreach ($rows as $row) {
            $result[$row->id] = [
                'id' => $row->id,
                'name' => $row->name,
                'address' => $row->address,
                'city' => $row->city,
                'latitude' => $row->latitude ? (float) $row->latitude : null,
                'longitude' => $row->longitude ? (float) $row->longitude : null,
            ];
        }

        return $result;
    }

    protected function parseWaypointKey(string|int $key): array
    {
        if (is_int($key) || ctype_digit((string) $key)) {
            return ['type' => 'acc', 'id' => (int) $key];
        }

        $key = (string) $key;
        if (str_starts_with($key, 'acc:')) {
            return ['type' => 'acc', 'id' => (int) substr($key, 4)];
        }
        if (str_starts_with($key, 'loc:')) {
            return ['type' => 'loc', 'id' => (int) substr($key, 4)];
        }

        return ['type' => 'acc', 'id' => (int) $key];
    }

    protected function getWaypointAccommodationIds(): array
    {
        return collect($this->routeWaypoints)
            ->map(fn ($k) => $this->parseWaypointKey($k))
            ->filter(fn ($p) => $p['type'] === 'acc' && $p['id'] > 0)
            ->map(fn ($p) => (int) $p['id'])
            ->values()
            ->all();
    }

    protected function getWaypointLocationIds(): array
    {
        return collect($this->routeWaypoints)
            ->map(fn ($k) => $this->parseWaypointKey($k))
            ->filter(fn ($p) => $p['type'] === 'loc' && $p['id'] > 0)
            ->map(fn ($p) => (int) $p['id'])
            ->values()
            ->all();
    }

    /**
     * Waypoints for UI: accommodations + extra locations in the chosen order.
     */
    public function getWaypointStopsProperty(): array
    {
        try {
            $accommodationsData = $this->accommodationsData;
            $locationIds = $this->getWaypointLocationIds();
            $locations = $locationIds ? Location::whereIn('id', $locationIds)->get()->keyBy('id') : collect();
            $result = [];
            foreach ((array) $this->routeWaypoints as $key) {
                $parsed = $this->parseWaypointKey($key);
                if ($parsed['type'] === 'acc') {
                    $accId = (int) $parsed['id'];
                    if (! isset($accommodationsData[$accId])) {
                        continue;
                    }
                    $employees = $this->getEmployeesForAccommodation($accId);
                    $result[] = [
                        'key' => 'acc:'.$accId,
                        'type' => 'acc',
                        'id' => $accId,
                        'label' => $accommodationsData[$accId]['name'],
                        'accommodation' => $accommodationsData[$accId],
                        'location' => null,
                        'employees' => $employees->map(fn ($e) => [
                            'id' => $e->id,
                            'full_name' => $e->full_name,
                        ])->values()->toArray(),
                    ];
                } elseif ($parsed['type'] === 'loc') {
                    $loc = $locations->get((int) $parsed['id']);
                    if (! $loc) {
                        continue;
                    }
                    $result[] = [
                        'key' => 'loc:'.$loc->id,
                        'type' => 'loc',
                        'id' => $loc->id,
                        'label' => $loc->name,
                        'accommodation' => null,
                        'location' => [
                            'id' => $loc->id,
                            'name' => $loc->name,
                            'address' => $loc->address,
                            'city' => $loc->city,
                            'latitude' => $loc->latitude ? (float) $loc->latitude : null,
                            'longitude' => $loc->longitude ? (float) $loc->longitude : null,
                        ],
                        'employees' => [],
                    ];
                }
            }

            return $result;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('getWaypointStopsProperty failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    // Back-compat alias
    public function getWaypointAccommodationsProperty(): array
    {
        return $this->waypointStops;
    }

    public function addExtraStop(): void
    {
        // Odczytaj ID po synchronizacji (np. po submit formularza — pewniejsze niż samo kliknięcie przy wire:model)
        $raw = $this->extraStopLocationId;
        $id = is_numeric($raw) ? (int) $raw : 0;
        if ($id <= 0) {
            return;
        }

        $key = 'loc:'.$id;
        if (in_array($key, $this->routeWaypoints, true)) {
            $this->extraStopLocationId = null;

            return;
        }

        $loc = Location::find($id);
        if ($loc && ! $loc->hasCoordinates()) {
            $this->geocodingService->geocodeLocation($loc);
        }

        $this->routeWaypoints[] = $key;
        $this->routeWaypoints = array_values($this->routeWaypoints);
        $this->extraStopLocationId = null;
        $this->locationStopNotes[(string) $id] = $this->locationStopNotes[(string) $id] ?? '';
        $this->invalidateRouteMetricsAndSyncToParent();
    }

    public function removeWaypoint(int $index): void
    {
        if ($index < 0 || $index >= count($this->routeWaypoints)) {
            return;
        }
        $waypoints = $this->routeWaypoints;
        array_splice($waypoints, $index, 1);
        $this->routeWaypoints = array_values($waypoints);
        $this->pruneLocationStopNotes();
        $this->invalidateRouteMetricsAndSyncToParent();
    }

    public function getAvailableVehiclesProperty()
    {
        $arrivalDate = $this->endDate ? \Carbon\Carbon::parse($this->endDate) : now();
        $locationTrackingService = app(LocationTrackingService::class);

        return Vehicle::where('type', 'company_vehicle')
            ->orderBy('registration_number')
            ->get()
            ->filter(function (Vehicle $vehicle) use ($arrivalDate, $locationTrackingService) {
                $status = $locationTrackingService->getVehicleLocationStatus($vehicle, $arrivalDate);

                // Transfer vehicle should be outside base on arrival date
                return ! $status['in_transit'] && $status['outside_base'];
            });
    }

    public function getAvailableEmployeesProperty()
    {
        $arrivalDate = $this->endDate ? \Carbon\Carbon::parse($this->endDate) : now();
        $locationTrackingService = app(LocationTrackingService::class);

        return Employee::orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->filter(function (Employee $employee) use ($arrivalDate, $locationTrackingService) {
                $status = $locationTrackingService->getLocationStatus($employee, $arrivalDate);

                return $status['state'] === \App\Enums\EmployeeLocationState::OUTSIDE_BASE;
            });
    }

    public function getAvailableLocationsProperty()
    {
        return Location::orderBy('name')->get();
    }

    public function getCurrencyCasesProperty(): array
    {
        return Currency::cases();
    }

    /** Czy brak zapisanego dystansu/czasu trasy (tak samo jak w DeparturePlannerV2::getStep4TabIncompleteProperty). */
    protected function routeMetricsComplete(): bool
    {
        $rd = $this->routeData;
        if (! is_array($rd)) {
            return false;
        }
        $dist = data_get($rd, 'route_distance', data_get($rd, 'distance'));
        $dur = data_get($rd, 'route_duration', data_get($rd, 'duration'));

        return $dist !== null && $dist !== '' && is_numeric($dist) && (float) $dist > 0
            && $dur !== null && $dur !== '' && (int) $dur > 0;
    }

    public function getRouteBlockIncompleteProperty(): bool
    {
        return ! $this->routeMetricsComplete();
    }

    public function getPickupIncompleteProperty(): bool
    {
        if (! $this->isPublicTransport) {
            return false;
        }

        return empty($this->transferPickupLocationId);
    }

    public function getTransferVehicleIncompleteProperty(): bool
    {
        return $this->isPublicTransport && empty($this->transferVehicleId);
    }

    public function getTransferDriverIncompleteProperty(): bool
    {
        return $this->isPublicTransport && empty($this->transferDriverEmployeeId);
    }

    public function getTransferBonusIncompleteProperty(): bool
    {
        if (! $this->isPublicTransport || empty($this->transferDriverEmployeeId)) {
            return false;
        }
        $bonus = $this->transferDriverBonusAmount;
        if ($bonus === null || $bonus === '' || ! is_numeric($bonus) || (float) $bonus <= 0) {
            return true;
        }
        $cur = strtoupper(trim((string) ($this->transferDriverBonusCurrency ?? 'PLN')));

        return strlen($cur) !== 3;
    }

    // ─── Route planning ────────────────────────────────────────────────────────

    public function moveUp(int $index): void
    {
        if ($index <= 0 || $index >= count($this->routeWaypoints)) {
            return;
        }
        $waypoints = $this->routeWaypoints;
        [$waypoints[$index - 1], $waypoints[$index]] = [$waypoints[$index], $waypoints[$index - 1]];
        $this->routeWaypoints = array_values($waypoints);
        $this->invalidateRouteMetricsAndSyncToParent();
    }

    public function moveDown(int $index): void
    {
        if ($index < 0 || $index >= count($this->routeWaypoints) - 1) {
            return;
        }
        $waypoints = $this->routeWaypoints;
        [$waypoints[$index], $waypoints[$index + 1]] = [$waypoints[$index + 1], $waypoints[$index]];
        $this->routeWaypoints = array_values($waypoints);
        $this->invalidateRouteMetricsAndSyncToParent();
    }

    public function updatedTransferPickupLocationId(): void
    {
        // Geocode new pickup location if needed
        if ($this->transferPickupLocationId) {
            $pickup = Location::find($this->transferPickupLocationId);
            if ($pickup && ! $pickup->hasCoordinates()) {
                $this->geocodingService->geocodeLocation($pickup);
            }
        }
        $this->invalidateRouteMetricsAndSyncToParent();
    }

    public function updatedTransferVehicleId(): void
    {
        $this->dispatchTransferConfig();
    }

    public function updatedTransferDriverEmployeeId(): void
    {
        $this->dispatchTransferConfig();
    }

    public function updatedTransferDriverBonusAmount(): void
    {
        $this->dispatchTransferConfig();
    }

    public function updatedTransferDriverBonusCurrency(): void
    {
        $this->dispatchTransferConfig();
    }

    public function updatedManualRouteDistanceKm(): void
    {
        // Do not auto-apply; user must confirm with applyManualRouteDistance()
    }

    public function applyManualRouteDistance(): void
    {
        $km = $this->manualRouteDistanceKm;
        if ($km === null || $km === '' || ! is_numeric($km) || (float) $km <= 0) {
            $this->routeError = 'Podaj poprawną liczbę kilometrów (większą od 0), aby ustawić dystans ręcznie.';

            return;
        }

        $minutes = $this->manualRouteDurationMinutes;
        if ($minutes === null || $minutes === '' || ! is_numeric($minutes) || (float) $minutes <= 0) {
            $this->routeError = 'Podaj szacowany czas przejazdu w minutach (większy od 0).';

            return;
        }

        // ORS zwraca duration w sekundach — utrzymujemy ten sam format w DB i transfer_config
        $durationSeconds = (int) round((float) $minutes * 60);

        $this->isManualRouteDistance = true;
        $this->manualRouteHint = null;
        $this->routeData = [
            'distance' => (float) $km,
            'duration' => $durationSeconds,
        ];
        // Keep routeError empty so UI does not look broken after manual fallback
        $this->routeError = null;

        // Dispatch to parent so it can be saved
        $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
        $this->dispatchTransferConfig();
    }

    protected function dispatchTransferConfig(): void
    {
        $this->dispatch('transfer-config-updated', [
            'vehicle_id' => $this->transferVehicleId,
            'driver_employee_id' => $this->transferDriverEmployeeId,
            'bonus_amount' => $this->transferDriverBonusAmount,
            'bonus_currency' => $this->transferDriverBonusCurrency,
            'pickup_location_id' => $this->transferPickupLocationId,
            'route_distance' => $this->routeData['distance'] ?? null,
            'route_duration' => $this->routeData['duration'] ?? null,
            'route_waypoints' => $this->routeWaypoints,
            'location_stop_notes' => $this->getLocationStopNotesPayload(),
            'end_airport_location_id' => $this->sharedEndAirportLocationId,
            'route_distance_is_manual' => (bool) $this->isManualRouteDistance,
        ]);
    }

    public function planRoute(): void
    {
        if (empty($this->routeWaypoints)) {
            $this->routeData = null;
            $this->routeError = 'Brak przystanków do zaplanowania trasy.';

            return;
        }

        $accIds = $this->getWaypointAccommodationIds();
        $locIds = $this->getWaypointLocationIds();
        $accommodations = Accommodation::whereIn('id', $accIds)->get()->keyBy('id');
        $locations = Location::whereIn('id', $locIds)->get()->keyBy('id');

        $missingCoords = [];
        foreach ($this->routeWaypoints as $key) {
            $parsed = $this->parseWaypointKey($key);
            if ($parsed['type'] === 'acc') {
                $acc = $accommodations->get((int) $parsed['id']);
                if (! $acc || ! $acc->hasCoordinates()) {
                    $missingCoords[] = $acc ? $acc->name : "Dom ID:{$parsed['id']}";
                }
            } elseif ($parsed['type'] === 'loc') {
                $loc = $locations->get((int) $parsed['id']);
                if (! $loc || ! $loc->hasCoordinates()) {
                    $missingCoords[] = $loc ? $loc->name : "Lokacja ID:{$parsed['id']}";
                }
            }
        }
        if (! empty($missingCoords)) {
            $this->routeError = 'Brak współrzędnych dla: '.implode(', ', $missingCoords).'. Edytuj akomodację i użyj wyszukiwania miejsca.';

            return;
        }

        if ($this->isPublicTransport) {
            $this->planTransferRoute($accommodations, $locations);
        } else {
            $this->planCarRoute($accommodations, $locations);
        }
    }

    protected function resolveWaypointObjects($accommodations, $locations): array
    {
        $list = [];
        foreach ($this->routeWaypoints as $key) {
            $parsed = $this->parseWaypointKey($key);
            if ($parsed['type'] === 'acc') {
                $obj = $accommodations->get((int) $parsed['id']);
                if ($obj) {
                    $list[] = $obj;
                }
            } elseif ($parsed['type'] === 'loc') {
                $obj = $locations->get((int) $parsed['id']);
                if ($obj) {
                    $list[] = $obj;
                }
            }
        }

        return $list;
    }

    protected function planCarRoute($accommodations, $locations): void
    {
        if (! $this->baseLocationId) {
            $this->routeError = 'Brak lokalizacji bazy.';

            return;
        }
        $base = Location::find($this->baseLocationId);
        if (! $base || ! $base->hasCoordinates()) {
            $this->routeError = 'Brak współrzędnych dla lokalizacji bazy.';

            return;
        }

        $this->isPlanningRoute = true;
        $this->routeError = null;
        $previousRouteData = $this->routeData;

        try {
            $waypointList = $this->resolveWaypointObjects($accommodations, $locations);

            $lastWaypoint = array_pop($waypointList);
            $intermediateWaypoints = $waypointList;

            $route = $this->routePlanningService->planRouteWithWaypoints($base, $lastWaypoint, $intermediateWaypoints, []);

            if ($route) {
                $this->routeData = ['distance' => $route['distance'], 'duration' => $route['duration']];
                $this->isManualRouteDistance = false;
                $this->syncManualFieldsFromRouteData();
                $this->routeError = null;
                $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
                $this->dispatchTransferConfig();
                $this->dispatch('route-updated',
                    baseLocationData: $this->baseLocationData,
                    waypointAccommodations: $this->waypointStops,
                    routeData: $this->routeData,
                );
            } else {
                $this->routeError = 'Nie udało się zaplanować trasy.';
                $this->routeData = $previousRouteData;
            }
        } catch (\Exception $e) {
            Log::error('Route planning exception (car)', ['message' => $e->getMessage()]);
            $this->routeError = 'Błąd podczas planowania trasy: '.$e->getMessage();
            $this->routeData = $previousRouteData;
        } finally {
            $this->isPlanningRoute = false;
        }
    }

    public function getTransferGoogleMapsUrlProperty(): ?string
    {
        if (! $this->isPublicTransport) {
            return null;
        }

        $coordsList = [];

        // Start: pickup if set, otherwise end airport
        if ($this->transferPickupLocationId) {
            $pickup = Location::find($this->transferPickupLocationId);
            if ($pickup && $pickup->hasCoordinates()) {
                $coordsList[] = $pickup->getCoordinates(); // [lat, lng]
            }
        }

        $endAirport = $this->sharedEndAirportLocationId ? Location::find($this->sharedEndAirportLocationId) : null;
        if ($endAirport && $endAirport->hasCoordinates()) {
            $coordsList[] = $endAirport->getCoordinates();
        }

        // Homes (in current order)
        $accommodations = Accommodation::whereIn('id', $this->routeWaypoints)->get()->keyBy('id');
        foreach ($this->routeWaypoints as $accId) {
            $acc = $accommodations->get($accId);
            if ($acc && $acc->hasCoordinates()) {
                $coordsList[] = $acc->getCoordinates();
            }
        }

        if (count($coordsList) < 2) {
            return null;
        }

        $origin = $coordsList[0];
        $destination = $coordsList[count($coordsList) - 1];
        $waypoints = array_slice($coordsList, 1, -1);

        $originStr = $origin[0].','.$origin[1];
        $destinationStr = $destination[0].','.$destination[1];
        $waypointsStr = implode('|', array_map(fn ($c) => ($c[0].','.$c[1]), $waypoints));

        $params = [
            'api' => '1',
            'travelmode' => 'driving',
            'origin' => $originStr,
            'destination' => $destinationStr,
        ];
        if (! empty($waypointsStr)) {
            $params['waypoints'] = $waypointsStr;
        }

        return 'https://www.google.com/maps/dir/?'.http_build_query($params);
    }

    protected function planTransferRoute($accommodations, $locations): void
    {
        // Transfer route: [optional pickup] → end airport → accommodations
        $endAirport = $this->sharedEndAirportLocationId
            ? Location::find($this->sharedEndAirportLocationId)
            : null;

        if (! $endAirport) {
            $this->routeError = 'Wybierz lotnisko docelowe, aby zaplanować trasę transferu.';

            return;
        }

        if (! $endAirport->hasCoordinates()) {
            $this->routeError = 'Brak współrzędnych dla lotniska docelowego: '.$endAirport->name.'. Edytuj lokalizację.';

            return;
        }

        // Determine start: pickup location or end airport itself
        $startPoint = $endAirport;
        $intermediateWaypoints = [];

        if ($this->transferPickupLocationId) {
            $pickup = Location::find($this->transferPickupLocationId);
            if (! $pickup) {
                $this->routeError = 'Wybrane miejsce startowe transferu nie istnieje (odśwież stronę i wybierz ponownie).';

                return;
            }
            if (! $pickup->hasCoordinates()) {
                $this->routeError = 'Brak współrzędnych dla miejsca startowego transferu: '.$pickup->name.'. Edytuj lokalizację i uzupełnij współrzędne.';

                return;
            }

            $startPoint = $pickup;
            // airport becomes intermediate between pickup and first accommodation
            $intermediateWaypoints[] = $endAirport;
        }

        // Remaining waypoints: all except last become intermediates
        $waypointList = $this->resolveWaypointObjects($accommodations, $locations);
        $lastWaypoint = array_pop($waypointList);
        if (! $lastWaypoint) {
            $this->routeError = 'Brak domów do zaplanowania transferu. Wróć do kroku 2 i przypisz mieszkania.';

            return;
        }
        foreach ($waypointList as $w) {
            $intermediateWaypoints[] = $w;
        }

        $this->isPlanningRoute = true;
        $this->routeError = null;
        $previousRouteData = $this->routeData;

        try {
            // Build a debug map of coordinate index -> point (matches RoutePlanningService coordinate order)
            $debugPoints = [];
            $debugPoints[] = [
                'label' => 'Start',
                'type' => get_class($startPoint),
                'id' => $startPoint->id ?? null,
                'name' => $startPoint->name ?? '—',
                'coords' => $startPoint->getCoordinates(),
            ];
            foreach ($intermediateWaypoints as $wp) {
                $debugPoints[] = [
                    'label' => 'Waypoint',
                    'type' => get_class($wp),
                    'id' => $wp->id ?? null,
                    'name' => $wp->name ?? '—',
                    'coords' => $wp->getCoordinates(),
                ];
            }
            $debugPoints[] = [
                'label' => 'End',
                'type' => get_class($lastWaypoint),
                'id' => $lastWaypoint->id ?? null,
                'name' => $lastWaypoint->name ?? '—',
                'coords' => $lastWaypoint->getCoordinates(),
            ];

            $route = $this->routePlanningService->planRouteWithWaypoints($startPoint, $lastWaypoint, $intermediateWaypoints, []);

            if ($route) {
                $this->isManualRouteDistance = false;
                $this->manualRouteHint = null;
                $this->routeData = ['distance' => $route['distance'], 'duration' => $route['duration']];
                $this->syncManualFieldsFromRouteData();
                $this->routeError = null;
                // Also dispatch to parent
                $this->dispatch('route-planned', $this->buildRoutePlannedPayload());
                $this->dispatchTransferConfig();
            } else {
                $this->routeError = 'Nie udało się zaplanować trasy transferu. Najczęściej powód to brak współrzędnych lub błąd w usłudze wyznaczania trasy. Sprawdź lotnisko docelowe, miejsce startowe transferu oraz domy.';
                $this->routeData = $previousRouteData;
            }
        } catch (\Exception $e) {
            $extraHint = null;
            if (preg_match('/specified coordinate\\s+(\\d+):\\s+([0-9.\\-]+)\\s+([0-9.\\-]+)/i', $e->getMessage(), $m)) {
                $coordIndex = (int) $m[1];
                $failed = $debugPoints[$coordIndex] ?? null;
                if ($failed) {
                    $coords = $failed['coords'];
                    $coordText = is_array($coords) ? ($coords[0].', '.$coords[1]) : '—';
                    $extraHint = ' Problem dotyczy punktu #'.$coordIndex.': '.($failed['name'] ?? '—').' ('.($failed['label'] ?? 'punkt').', '.$coordText.').';
                    $this->manualRouteHint = ($failed['name'] ?? null) ?: null;
                } else {
                    $extraHint = ' Problem dotyczy punktu #'.$coordIndex.' (nie udało się go zmapować na nazwę).';
                    $this->manualRouteHint = null;
                }
            }

            Log::error('Route planning exception (transfer)', [
                'message' => $e->getMessage(),
                'pickup_location_id' => $this->transferPickupLocationId,
                'end_airport_location_id' => $this->sharedEndAirportLocationId,
                'route_waypoints' => $this->routeWaypoints,
                'debug_points' => $debugPoints ?? null,
            ]);
            $this->routeError = 'Błąd podczas planowania trasy transferu: '.$e->getMessage().($extraHint ?? '');
            $this->routeData = $previousRouteData;
        } finally {
            $this->isPlanningRoute = false;
        }
    }

    // ─── Trip plan ─────────────────────────────────────────────────────────────

    public function getTripPlanProperty(): array
    {
        $plan = [];
        $isPublicTransport = $this->isPublicTransport;

        $employeeIds = array_keys($this->accommodationAssignments);
        $accommodationRows = array_values(array_filter($this->accommodationAssignments, 'is_array'));
        $rangeRows = array_values(array_filter($this->assignmentRanges, 'is_array'));
        $vehicleRows = array_values(array_filter($this->vehicleAssignments, 'is_array'));
        $accommodationIds = array_unique(array_filter(array_column($accommodationRows, 'accommodation_id')));
        $projectIds = array_unique(array_filter(array_column($rangeRows, 'project_id')));
        $vehicleIds = array_unique(array_filter(array_column($vehicleRows, 'vehicle_id')));

        $employees = Employee::whereIn('id', $employeeIds)->get()->keyBy('id');
        $accommodations = Accommodation::whereIn('id', $accommodationIds)->get()->keyBy('id');
        $projects = \App\Models\Project::with('location')->whereIn('id', $projectIds)->get()->keyBy('id');
        $vehicles = Vehicle::whereIn('id', $vehicleIds)->get()->keyBy('id');

        $airportNames = collect();
        if ($isPublicTransport) {
            $airportIds = collect([$this->sharedStartAirportLocationId, $this->sharedEndAirportLocationId])
                ->filter()
                ->map('intval')
                ->unique();
            if ($airportIds->isNotEmpty()) {
                $airportNames = Location::whereIn('id', $airportIds)->pluck('name', 'id');
            }
        }

        $employeeToProject = [];
        foreach ($this->assignmentRanges as $range) {
            if (! is_array($range)) {
                continue;
            }
            $employeeId = (int) ($range['employee_id'] ?? 0);
            if ($employeeId <= 0) {
                continue;
            }
            $employeeToProject[$employeeId] = $range['project_id'] ?? null;
        }

        foreach ($this->accommodationAssignments as $employeeId => $accommodationAssignment) {
            if (! is_array($accommodationAssignment)) {
                continue;
            }
            $accommodationId = $accommodationAssignment['accommodation_id'] ?? null;
            if (! $accommodationId) {
                continue;
            }

            $employee = $employees->get($employeeId);
            $accommodation = $accommodations->get($accommodationId);
            if (! $employee || ! $accommodation) {
                continue;
            }

            $projectId = $employeeToProject[$employeeId] ?? null;
            $project = $projectId ? $projects->get($projectId) : null;
            $projectName = $project ? $project->name : null;

            $vehicleId = data_get($this->vehicleAssignments, $employeeId.'.vehicle_id');
            $vehicle = $vehicleId ? $vehicles->get($vehicleId) : null;
            $vehicleName = $vehicle ? ($vehicle->registration_number.' - '.$vehicle->brand.' '.$vehicle->model) : null;

            $distance = null;
            if (! $isPublicTransport && $project && $project->location && $accommodation->hasCoordinates() && $project->location->hasCoordinates()) {
                $distance = $this->getCachedDistance($accommodation, $project->location);
            }

            $ticket = null;
            if ($isPublicTransport) {
                $ticketData = $this->ticketCostsByEmployee[$employeeId] ?? [];
                $amount = $ticketData['amount'] ?? null;
                $currency = $ticketData['currency'] ?? null;
                if ($amount !== null || $currency !== null) {
                    $ticket = [
                        'amount' => $amount,
                        'currency' => $currency,
                        'start_airport_name' => $airportNames[$this->sharedStartAirportLocationId] ?? null,
                        'end_airport_name' => $airportNames[$this->sharedEndAirportLocationId] ?? null,
                    ];
                }
            }

            if (! isset($plan[$accommodationId])) {
                $plan[$accommodationId] = [
                    'accommodation' => [
                        'id' => $accommodation->id,
                        'name' => $accommodation->name,
                        'address' => $accommodation->getFullAddress(),
                    ],
                    'employees' => [],
                ];
            }

            $plan[$accommodationId]['employees'][] = [
                'id' => $employee->id,
                'full_name' => $employee->full_name,
                'project_name' => $projectName,
                'distance' => $distance,
                'vehicle_name' => $vehicleName,
                'ticket' => $ticket,
            ];
        }

        // Sort plan by routeWaypoints order, using parsed IDs (waypoints are 'acc:ID' strings)
        $sortedPlan = [];
        foreach ($this->routeWaypoints as $routeIdx => $waypointKey) {
            $parsed = $this->parseWaypointKey($waypointKey);
            if ($parsed['type'] === 'acc' && isset($plan[$parsed['id']])) {
                $stop = $plan[$parsed['id']];
                $stop['route_index'] = $routeIdx; // actual index in routeWaypoints for moveUp/moveDown
                $sortedPlan[] = $stop;
            }
        }

        return $sortedPlan;
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    protected function getEmployeesForAccommodation($accommodationId): Collection
    {
        $employeeIds = [];
        foreach ($this->accommodationAssignments as $employeeId => $assignment) {
            if (! is_array($assignment)) {
                continue;
            }
            if (isset($assignment['accommodation_id']) && (int) $assignment['accommodation_id'] === (int) $accommodationId) {
                $employeeIds[] = $employeeId;
            }
        }
        if (empty($employeeIds)) {
            return collect();
        }

        return Employee::whereIn('id', $employeeIds)->get();
    }

    protected function getCachedDistance($accommodation, $location): ?float
    {
        try {
            $route = $this->routePlanningService->planRouteWithWaypoints($accommodation, $location, []);

            return $route['distance'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function toJSON(): array
    {
        return [];
    }

    public function render()
    {
        $waypointStops = $this->waypointStops;

        return view('livewire.steps.step4-route-planning', [
            'baseLocationData' => $this->baseLocationData,
            'startAirportData' => $this->startAirportData,
            'endAirportData' => $this->endAirportData,
            'pickupLocationData' => $this->pickupLocationData,
            'waypointAccommodations' => $this->waypointAccommodations,
            'waypointStops' => $waypointStops,
            'routeWaypoints' => $this->routeWaypoints,
            'extraStopLocationId' => $this->extraStopLocationId,
            'tripPlan' => $this->tripPlan,
            'isPublicTransport' => $this->isPublicTransport,
            'currencyCases' => $this->currencyCases,
            'availableVehicles' => $this->availableVehicles,
            'availableEmployees' => $this->availableEmployees,
            'availableLocations' => $this->availableLocations,
        ]);
    }
}
