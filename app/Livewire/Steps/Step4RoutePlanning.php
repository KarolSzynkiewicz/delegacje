<?php

namespace App\Livewire\Steps;

use App\Enums\Currency;
use App\Models\Accommodation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Vehicle;
use App\Services\GeocodingService;
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
        $this->initializeWaypoints();
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
            if (! empty($assignment['accommodation_id'])) {
                $accommodationIds[] = (int) $assignment['accommodation_id'];
            }
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

    protected function initializeWaypoints(): void
    {
        $this->routeWaypoints = $this->accommodationIds;

        if (! empty($this->routeWaypoints)) {
            $this->planRoute();
        }
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

    public function getWaypointAccommodationsProperty(): array
    {
        $accommodationsData = $this->accommodationsData;
        $result = [];
        foreach ($this->routeWaypoints as $accommodationId) {
            if (! isset($accommodationsData[$accommodationId])) {
                continue;
            }
            $employees = $this->getEmployeesForAccommodation($accommodationId);
            $result[] = [
                'id' => $accommodationId,
                'accommodation' => $accommodationsData[$accommodationId],
                'employees' => $employees->map(fn ($e) => [
                    'id' => $e->id,
                    'full_name' => $e->full_name,
                ])->values()->toArray(),
            ];
        }

        return $result;
    }

    public function getAvailableVehiclesProperty()
    {
        return Vehicle::where('type', 'company_vehicle')->orderBy('registration_number')->get();
    }

    public function getAvailableEmployeesProperty()
    {
        return Employee::orderBy('last_name')->orderBy('first_name')->get();
    }

    public function getAvailableLocationsProperty()
    {
        return Location::orderBy('name')->get();
    }

    public function getCurrencyCasesProperty(): array
    {
        return Currency::cases();
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
        $this->planRoute();
    }

    public function moveDown(int $index): void
    {
        if ($index < 0 || $index >= count($this->routeWaypoints) - 1) {
            return;
        }
        $waypoints = $this->routeWaypoints;
        [$waypoints[$index], $waypoints[$index + 1]] = [$waypoints[$index + 1], $waypoints[$index]];
        $this->routeWaypoints = array_values($waypoints);
        $this->planRoute();
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
        $this->planRoute();
        $this->dispatchTransferConfig();
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
        $this->dispatch('route-planned', [
            'route_distance' => (float) $km,
            'route_duration' => $durationSeconds,
            'route_waypoints' => $this->routeWaypoints,
        ]);
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

        $accommodations = Accommodation::whereIn('id', $this->routeWaypoints)->get()->keyBy('id');

        $missingCoords = [];
        foreach ($this->routeWaypoints as $accId) {
            $acc = $accommodations->get($accId);
            if (! $acc || ! $acc->hasCoordinates()) {
                $missingCoords[] = $acc ? $acc->name : "ID:{$accId}";
            }
        }
        if (! empty($missingCoords)) {
            $this->routeError = 'Brak współrzędnych dla: '.implode(', ', $missingCoords).'. Edytuj akomodację i użyj wyszukiwania miejsca.';

            return;
        }

        if ($this->isPublicTransport) {
            $this->planTransferRoute($accommodations);
        } else {
            $this->planCarRoute($accommodations);
        }
    }

    protected function planCarRoute($accommodations): void
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

        try {
            $waypointList = [];
            foreach ($this->routeWaypoints as $accId) {
                $waypointList[] = $accommodations->get($accId);
            }

            $lastWaypoint = array_pop($waypointList);
            $intermediateWaypoints = $waypointList;

            $route = $this->routePlanningService->planRouteWithWaypoints($base, $lastWaypoint, $intermediateWaypoints, []);

            if ($route) {
                $this->routeData = ['distance' => $route['distance'], 'duration' => $route['duration']];
                $this->routeError = null;
                $this->dispatch('route-planned', [
                    'route_distance' => $route['distance'],
                    'route_duration' => $route['duration'],
                    'route_waypoints' => $this->routeWaypoints,
                ]);
                $this->dispatch('route-updated',
                    baseLocationData: $this->baseLocationData,
                    waypointAccommodations: $this->waypointAccommodations,
                    routeData: $this->routeData,
                );
            } else {
                $this->routeError = 'Nie udało się zaplanować trasy.';
                $this->routeData = null;
            }
        } catch (\Exception $e) {
            Log::error('Route planning exception (car)', ['message' => $e->getMessage()]);
            $this->routeError = 'Błąd podczas planowania trasy: '.$e->getMessage();
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

    protected function planTransferRoute($accommodations): void
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

        // Remaining accommodations: all except last become intermediates
        $waypointList = [];
        foreach ($this->routeWaypoints as $accId) {
            $waypointList[] = $accommodations->get($accId);
        }
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
                $this->routeError = null;
                // Also dispatch to parent
                $this->dispatch('route-planned', [
                    'route_distance' => $route['distance'],
                    'route_duration' => $route['duration'],
                    'route_waypoints' => $this->routeWaypoints,
                ]);
                $this->dispatchTransferConfig();
            } else {
                $this->routeError = 'Nie udało się zaplanować trasy transferu. Najczęściej powód to brak współrzędnych lub błąd w usłudze wyznaczania trasy. Sprawdź lotnisko docelowe, miejsce startowe transferu oraz domy.';
                $this->routeData = null;
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
        $accommodationIds = array_unique(array_filter(array_column($this->accommodationAssignments, 'accommodation_id')));
        $projectIds = array_unique(array_filter(array_column($this->assignmentRanges, 'project_id')));
        $vehicleIds = array_unique(array_filter(array_column($this->vehicleAssignments, 'vehicle_id')));

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
            $employeeId = (int) $range['employee_id'];
            $employeeToProject[$employeeId] = $range['project_id'] ?? null;
        }

        foreach ($this->accommodationAssignments as $employeeId => $accommodationAssignment) {
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

            $vehicleId = $this->vehicleAssignments[$employeeId]['vehicle_id'] ?? null;
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

        $sortedPlan = [];
        foreach ($this->routeWaypoints as $accommodationId) {
            if (isset($plan[$accommodationId])) {
                $sortedPlan[] = $plan[$accommodationId];
            }
        }

        return $sortedPlan;
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    protected function getEmployeesForAccommodation($accommodationId): Collection
    {
        $employeeIds = [];
        foreach ($this->accommodationAssignments as $employeeId => $assignment) {
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
        return view('livewire.steps.step4-route-planning', [
            'baseLocationData' => $this->baseLocationData,
            'startAirportData' => $this->startAirportData,
            'endAirportData' => $this->endAirportData,
            'pickupLocationData' => $this->pickupLocationData,
            // Back-compat: some Blade fragments may still reference $waypointStops.
            'waypointStops' => $this->waypointAccommodations,
            'waypointAccommodations' => $this->waypointAccommodations,
            'tripPlan' => $this->tripPlan,
            'isPublicTransport' => $this->isPublicTransport,
            'currencyCases' => $this->currencyCases,
            'availableVehicles' => $this->availableVehicles,
            'availableEmployees' => $this->availableEmployees,
            'availableLocations' => $this->availableLocations,
        ]);
    }
}
