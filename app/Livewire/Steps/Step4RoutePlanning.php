<?php

namespace App\Livewire\Steps;

use App\Models\Location;
use App\Models\Accommodation;
use App\Models\Employee;
use App\Models\Vehicle;
use App\Services\GeocodingService;
use App\Services\RoutePlanningService;
use Livewire\Component;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class Step4RoutePlanning extends Component
{
    // Dane otrzymane z rodzica (read-only)
    public $departureDate;
    public $endDate;
    public $vehicleId;
    public $accommodationAssignments = []; // Read-only z rodzica
    public $assignmentRanges = []; // Read-only z rodzica
    public $vehicleAssignments = []; // Read-only z rodzica
    public $ticketCostsByEmployee = []; // Read-only z rodzica (for public transport tickets)

    // Dane trasy
    public $routeWaypoints = []; // Array of accommodation IDs in order
    public $routeData = null;    // Route data from API (plain array)
    public $isPlanningRoute = false;
    public $routeError = null;
    
    // Cache dla dystansów (optymalizacja API)
    protected $distanceCache = [];

    // IDs — Eloquent modele NIE są trzymane jako publiczne właściwości (Livewire nie może ich serializować)
    public $baseLocationId = null;
    public $accommodationIds = []; // [accommodation_id, ...]

    protected $geocodingService;
    protected $routePlanningService;

    public function boot(GeocodingService $geocodingService, RoutePlanningService $routePlanningService)
    {
        $this->geocodingService = $geocodingService;
        $this->routePlanningService = $routePlanningService;
    }

    public function mount($departureDate, $endDate, $vehicleId = null, $accommodationAssignments = [], $assignmentRanges = [], $vehicleAssignments = [], $ticketCostsByEmployee = [])
    {
        $this->departureDate = $departureDate;
        $this->endDate = $endDate;
        $this->vehicleId = $vehicleId;
        $this->accommodationAssignments = $accommodationAssignments;
        $this->assignmentRanges = $assignmentRanges;
        $this->vehicleAssignments = $vehicleAssignments;
        $this->ticketCostsByEmployee = $ticketCostsByEmployee;

        $this->loadLocations();
        $this->initializeWaypoints();
    }

    // Ładuje lokalizacje i geokoduje brakujące współrzędne; zapamiętuje tylko ID
    protected function loadLocations()
    {
        // Load base location
        $base = Location::getBase();
        if (!$base) {
            Log::error('No base location found.');
            return;
        }
        $this->baseLocationId = $base->id;

        if (!$base->hasCoordinates()) {
            $this->geocodingService->geocodeLocation($base);
            $base->refresh();
        }

        // Collect unique accommodation IDs
        $accommodationIds = [];
        foreach ($this->accommodationAssignments as $employeeId => $assignment) {
            if (!empty($assignment['accommodation_id'])) {
                $accommodationIds[] = (int)$assignment['accommodation_id'];
            }
        }
        $accommodationIds = array_values(array_unique($accommodationIds));

        $accommodations = Accommodation::whereIn('id', $accommodationIds)->get();

        foreach ($accommodations as $accommodation) {
            if (!$accommodation->hasCoordinates()) {
                $address = $accommodation->getFullAddress();
                if (!empty($address)) {
                    try {
                        $coordinates = $this->geocodingService->geocode($address);
                        if ($coordinates && isset($coordinates['latitude'], $coordinates['longitude'])) {
                            $accommodation->update([
                                'latitude'  => $coordinates['latitude'],
                                'longitude' => $coordinates['longitude'],
                            ]);
                        } else {
                            Log::warning('Geocoding failed for accommodation', ['id' => $accommodation->id, 'address' => $address]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Geocoding exception', ['id' => $accommodation->id, 'error' => $e->getMessage()]);
                    }
                }
            }
        }

        $this->accommodationIds = $accommodationIds;
    }

    protected function initializeWaypoints()
    {
        $this->routeWaypoints = $this->accommodationIds;

        if (!empty($this->routeWaypoints)) {
            $this->planRoute();
        }
    }

    // ─── Computed properties ───────────────────────────────────────────────────

    // Zwraca bazę jako tablicę (nie obiekt Eloquent)
    public function getBaseLocationDataProperty(): array
    {
        if (!$this->baseLocationId) {
            return [];
        }
        $loc = Location::find($this->baseLocationId);
        if (!$loc) return [];

        return [
            'id'        => $loc->id,
            'name'      => $loc->name,
            'address'   => $loc->address,
            'latitude'  => $loc->latitude  ? (float)$loc->latitude  : null,
            'longitude' => $loc->longitude ? (float)$loc->longitude : null,
        ];
    }

    // Zwraca mieszkania jako tablice
    public function getAccommodationsDataProperty(): array
    {
        if (empty($this->accommodationIds)) return [];
        $rows = Accommodation::whereIn('id', $this->accommodationIds)->get();
        $result = [];
        foreach ($rows as $row) {
            $result[$row->id] = [
                'id'        => $row->id,
                'name'      => $row->name,
                'address'   => $row->address,
                'city'      => $row->city,
                'latitude'  => $row->latitude  ? (float)$row->latitude  : null,
                'longitude' => $row->longitude ? (float)$row->longitude : null,
            ];
        }
        return $result;
    }

    // Zwraca listę przystanków dla widoku
    public function getWaypointAccommodationsProperty(): array
    {
        $accommodationsData = $this->accommodationsData;
        $result = [];

        foreach ($this->routeWaypoints as $accommodationId) {
            if (!isset($accommodationsData[$accommodationId])) continue;

            $employees = $this->getEmployeesForAccommodation($accommodationId);

            $result[] = [
                'id'            => $accommodationId,
                'accommodation' => $accommodationsData[$accommodationId],
                'employees'     => $employees->map(fn($e) => [
                    'id'        => $e->id,
                    'full_name' => $e->full_name,
                ])->values()->toArray(),
            ];
        }

        return $result;
    }

    // ─── Planowanie trasy ──────────────────────────────────────────────────────

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

    public function updateWaypointOrder($waypointIds)
    {
        $this->routeWaypoints = array_map('intval', $waypointIds);
        $this->planRoute();
        // Clear distance cache when order changes
        $this->distanceCache = [];
    }

    public function planRoute()
    {
        if (empty($this->routeWaypoints) || !$this->baseLocationId) {
            $this->routeData  = null;
            $this->routeError = 'Brak przystanków do zaplanowania trasy.';
            return;
        }

        // Pobierz świeże modele z DB (nie jako publiczne właściwości)
        $base = Location::find($this->baseLocationId);
        if (!$base) {
            $this->routeError = 'Nie znaleziono lokalizacji bazy.';
            return;
        }

        if (!$base->hasCoordinates()) {
            $this->routeError = 'Brak współrzędnych dla lokalizacji bazy: ' . $base->name . '. Edytuj lokalizację i użyj wyszukiwania miejsca.';
            return;
        }

        $accommodations = Accommodation::whereIn('id', $this->routeWaypoints)->get()->keyBy('id');

        $missingCoordinates = [];
        foreach ($this->routeWaypoints as $accId) {
            $acc = $accommodations->get($accId);
            if (!$acc || !$acc->hasCoordinates()) {
                $missingCoordinates[] = $acc ? $acc->name : "ID:{$accId}";
            }
        }

        if (!empty($missingCoordinates)) {
            $this->routeError = 'Brak współrzędnych dla: ' . implode(', ', $missingCoordinates) . '. Edytuj akomodację i użyj wyszukiwania miejsca.';
            return;
        }

        $this->isPlanningRoute = true;
        $this->routeError      = null;

        try {
            $waypointAccommodations = [];
            foreach ($this->routeWaypoints as $accId) {
                $waypointAccommodations[] = $accommodations->get($accId);
            }

            $lastWaypoint          = array_pop($waypointAccommodations);
            $intermediateWaypoints = $waypointAccommodations;

            $route = $this->routePlanningService->planRouteWithWaypoints(
                $base,
                $lastWaypoint,
                $intermediateWaypoints,
                []
            );

            if ($route) {
                $this->routeData  = [
                    'distance' => $route['distance'],
                    'duration' => $route['duration'],
                ];
                $this->routeError = null;

                // Dispatch event with complete route data including waypoint order
                $this->dispatch('route-planned', [
                    'route_distance'  => $route['distance'],
                    'route_duration'  => $route['duration'],
                    'route_waypoints' => $this->routeWaypoints, // Order of accommodation IDs
                ]);

                $this->dispatch('route-updated',
                    baseLocationData:       $this->baseLocationData,
                    waypointAccommodations: $this->waypointAccommodations,
                    routeData:              $this->routeData,
                );
            } else {
                $this->routeError = 'Nie udało się zaplanować trasy. Sprawdź logi serwera lub spróbuj ponownie.';
                $this->routeData  = null;
            }
        } catch (\Exception $e) {
            Log::error('Route planning exception in Step4RoutePlanning', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            $this->routeError = 'Błąd podczas planowania trasy: ' . $e->getMessage();
        } finally {
            $this->isPlanningRoute = false;
        }
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    protected function getEmployeesForAccommodation($accommodationId): Collection
    {
        $employeeIds = [];
        foreach ($this->accommodationAssignments as $employeeId => $assignment) {
            if (isset($assignment['accommodation_id']) && (int)$assignment['accommodation_id'] === (int)$accommodationId) {
                $employeeIds[] = $employeeId;
            }
        }

        if (empty($employeeIds)) {
            return collect();
        }

        return Employee::whereIn('id', $employeeIds)->get();
    }

    /**
     * Get trip plan data - who gets off where, their project, distance, and vehicle
     */
    public function getTripPlanProperty(): array
    {
        $plan = [];
        $isPublicTransport = empty($this->vehicleId);
        
        // Collect all IDs for eager loading
        $employeeIds = array_keys($this->accommodationAssignments);
        $accommodationIds = array_unique(array_filter(array_column($this->accommodationAssignments, 'accommodation_id')));
        $projectIds = array_unique(array_filter(array_column($this->assignmentRanges, 'project_id')));
        $vehicleIds = array_unique(array_filter(array_column($this->vehicleAssignments, 'vehicle_id')));
        
        // Eager load all data
        $employees = Employee::whereIn('id', $employeeIds)->get()->keyBy('id');
        $accommodations = Accommodation::whereIn('id', $accommodationIds)->get()->keyBy('id');
        $projects = \App\Models\Project::with('location')->whereIn('id', $projectIds)->get()->keyBy('id');
        $vehicles = Vehicle::whereIn('id', $vehicleIds)->get()->keyBy('id');

        $airportNames = collect();
        if ($isPublicTransport) {
            $airportIds = collect($this->ticketCostsByEmployee)
                ->flatMap(function ($row) {
                    $start = $row['start_airport_location_id'] ?? null;
                    $end = $row['end_airport_location_id'] ?? null;
                    return array_filter([(int) $start, (int) $end]);
                })
                ->unique()
                ->values();

            if ($airportIds->isNotEmpty()) {
                $airportNames = Location::whereIn('id', $airportIds)->pluck('name', 'id');
            }
        }
        
        // Build assignment maps for quick lookup
        $employeeToProject = [];
        foreach ($this->assignmentRanges as $key => $assignmentRange) {
            $employeeId = (int)$assignmentRange['employee_id'];
            $employeeToProject[$employeeId] = $assignmentRange['project_id'] ?? null;
        }
        
        // Group employees by accommodation
        foreach ($this->accommodationAssignments as $employeeId => $accommodationAssignment) {
            $accommodationId = $accommodationAssignment['accommodation_id'] ?? null;
            if (!$accommodationId) continue;
            
            $employee = $employees->get($employeeId);
            if (!$employee) continue;
            
            $accommodation = $accommodations->get($accommodationId);
            if (!$accommodation) continue;
            
            // Get project assignment
            $projectId = $employeeToProject[$employeeId] ?? null;
            $project = $projectId ? $projects->get($projectId) : null;
            $projectName = $project ? $project->name : null;
            
            // Get vehicle assignment
            $vehicleId = $this->vehicleAssignments[$employeeId]['vehicle_id'] ?? null;
            $vehicle = $vehicleId ? $vehicles->get($vehicleId) : null;
            $vehicleName = $vehicle ? ($vehicle->registration_number . ' - ' . $vehicle->brand . ' ' . $vehicle->model) : null;
            
            // Calculate distance from accommodation to project
            $distance = null;
            if (!$isPublicTransport && $project && $project->location && $accommodation->hasCoordinates() && $project->location->hasCoordinates()) {
                $distance = $this->getCachedDistance($accommodation, $project->location);
            }

            $ticket = null;
            if ($isPublicTransport) {
                $ticketData = $this->ticketCostsByEmployee[$employeeId] ?? [];
                $amount = $ticketData['amount'] ?? null;
                $currency = $ticketData['currency'] ?? null;
                $attachmentPath = $ticketData['attachment_path'] ?? null;
                $startAirportLocationId = $ticketData['start_airport_location_id'] ?? null;
                $endAirportLocationId = $ticketData['end_airport_location_id'] ?? null;
                $startAirportName = $startAirportLocationId ? ($airportNames[(int) $startAirportLocationId] ?? null) : null;
                $endAirportName = $endAirportLocationId ? ($airportNames[(int) $endAirportLocationId] ?? null) : null;

                if ($amount !== null || $currency !== null || $attachmentPath !== null) {
                    $ticket = [
                        'amount' => $amount,
                        'currency' => $currency,
                        'attachment_path' => $attachmentPath,
                        'start_airport_location_id' => $startAirportLocationId,
                        'end_airport_location_id' => $endAirportLocationId,
                        'start_airport_name' => $startAirportName,
                        'end_airport_name' => $endAirportName,
                    ];
                }
            }
            
            if (!isset($plan[$accommodationId])) {
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
                'project_id' => $projectId,
                'project_name' => $projectName,
                'distance' => $distance,
                'vehicle_id' => $vehicleId,
                'vehicle_name' => $vehicleName,
                'ticket' => $ticket,
            ];
        }
        
        // Sort by accommodation order in routeWaypoints
        $sortedPlan = [];
        foreach ($this->routeWaypoints as $accommodationId) {
            if (isset($plan[$accommodationId])) {
                $sortedPlan[] = $plan[$accommodationId];
            }
        }
        
        return $sortedPlan;
    }

    /**
     * Get cached distance between accommodation and project location
     */
    protected function getCachedDistance($accommodation, $location): ?float
    {
        $cacheKey = $accommodation->id . '_' . $location->id;
        
        if (isset($this->distanceCache[$cacheKey])) {
            return $this->distanceCache[$cacheKey];
        }
        
        // Use planRouteWithWaypoints which accepts objects with hasCoordinates() and getCoordinates()
        // Pass accommodation as start, location as end, with no intermediate waypoints
        try {
            $route = $this->routePlanningService->planRouteWithWaypoints(
                $accommodation,
                $location,
                [] // No intermediate waypoints
            );
            
            if ($route && isset($route['distance'])) {
                $distance = $route['distance'];
                $this->distanceCache[$cacheKey] = $distance;
                return $distance;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to calculate distance', [
                'accommodation_id' => $accommodation->id,
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);
        }
        
        return null;
    }

    /**
     * Prevents Livewire MethodNotFoundException when JavaScript serializes $wire.
     * This is called by JSON.stringify($wire) via JS Proxy.
     */
    public function toJSON(): array
    {
        return [];
    }

    public function render()
    {
        return view('livewire.steps.step4-route-planning', [
            'baseLocationData'       => $this->baseLocationData,
            'waypointAccommodations' => $this->waypointAccommodations,
            'tripPlan'               => $this->tripPlan,
        ]);
    }
}
