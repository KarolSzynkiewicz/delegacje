<?php

namespace App\Livewire;

use App\Enums\Currency;
use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Project;
use App\Models\Vehicle;
use App\Services\GeocodingService;
use App\Services\LocationTrackingService;
use App\Services\RoutePlanningService;
use App\Services\TransferService;
use App\Support\VehicleDocumentExpiry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class TransferPlanner extends Component
{
    use WithPagination;

    // Basic
    public string $transferDate = '';

    public ?int $vehicleId = null;

    public string $notes = '';

    // Route: ordered list of location IDs [from, ...waypoints, to]
    public array $waypointLocationIds = [];

    // Route result
    public ?array $routeData = null;

    public bool $isPlanningRoute = false;

    public ?string $routeError = null;

    // Participants
    public array $selectedEmployeeIds = [];

    // Driver payment
    public ?int $driverEmployeeId = null;

    public string $driverPaymentAmount = '';

    public string $driverPaymentCurrency = 'PLN';

    public ?int $driverPayrollId = null;

    // Search helpers
    public string $employeeSearch = '';

    public string $projectSearch = '';

    public string $accommodationSearch = '';

    public string $vehicleSearch = '';

    public string $locationSearch = '';

    // Filters for participant picker (at transfer date)
    public ?int $filterProjectId = null;

    public ?int $filterAccommodationId = null;

    // UI: which location slot to add next
    public ?int $addLocationId = null;

    // Reassignment mode
    public bool $hasReassignment = false;

    // Per-employee reassignment data: [employee_id => ['project_id' => ..., 'accommodation_id' => ..., ...]]
    public array $reassignments = [];

    // Cached “home” info for participants (at transfer date): [employee_id => ['accommodation_name'=>?, 'location_id'=>?, ...] | null]
    public array $participantHomeLocations = [];

    /** Kreator przypisań (jak wyjazd kroki 1–3), gdy włączone przeniesienie */
    public int $reassignStep = 1;

    public array $assignments = [];

    public array $assignmentRanges = [];

    /** Miejsca w pojeździe transferu — synchronizowane z krokiem 1 (jak w wyjeździe) */
    public array $vehicleSeats = [];

    public array $accommodationAssignments = [];

    public array $vehicleAssignments = [];

    protected $listeners = [
        'assignment-added' => 'handleAssignmentAdded',
        'assignment-removed' => 'handleAssignmentRemoved',
        'assignment-range-added' => 'handleAssignmentRangeAdded',
        'assignment-range-removed' => 'handleAssignmentRangeRemoved',
        'vehicle-seat-updated' => 'handleVehicleSeatUpdated',
        'accommodation-assigned' => 'handleAccommodationAssigned',
        'accommodation-removed' => 'handleAccommodationRemoved',
        'vehicle-assigned' => 'handleVehicleAssigned',
        'vehicle-assignment-removed' => 'handleVehicleAssignmentRemoved',
        'go-to-step' => 'handleWizardGoToStep',
    ];

    protected RoutePlanningService $routePlanningService;

    protected GeocodingService $geocodingService;

    protected TransferService $transferService;

    protected LocationTrackingService $locationTracking;

    public function boot(
        RoutePlanningService $routePlanningService,
        GeocodingService $geocodingService,
        TransferService $transferService,
        LocationTrackingService $locationTracking
    ): void {
        $this->routePlanningService = $routePlanningService;
        $this->geocodingService = $geocodingService;
        $this->transferService = $transferService;
        $this->locationTracking = $locationTracking;
    }

    public function mount(): void
    {
        $this->transferDate = now()->format('Y-m-d\TH:i');
        $this->driverPaymentCurrency = Currency::PLN->value;
    }

    // ─── Computed properties ────────────────────────────────────────────────────

    public function updatingEmployeeSearch(): void
    {
        $this->resetPage();
    }

    public function updatingProjectSearch(): void
    {
        $this->resetPage();
    }

    public function updatingAccommodationSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterProjectId(): void
    {
        $this->resetPage();
    }

    public function updatingFilterAccommodationId(): void
    {
        $this->resetPage();
    }

    public function paginationView()
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function getEmployeesQueryProperty()
    {
        $q = Employee::query()->orderBy('last_name')->orderBy('first_name');

        $date = $this->transferDate
            ? Carbon::parse($this->transferDate)
            : now();

        if ($this->employeeSearch !== '') {
            $search = mb_strtolower(trim($this->employeeSearch));
            $q->where(function ($inner) use ($search) {
                $inner->whereRaw('LOWER(CONCAT(first_name, \" \", last_name)) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(CONCAT(last_name, \" \", first_name)) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(first_name) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(phone) LIKE ?', ['%'.$search.'%']);
            });
        }

        if ($this->filterProjectId) {
            $projectId = (int) $this->filterProjectId;
            $q->whereHas('assignments', function ($aq) use ($date, $projectId) {
                $aq->activeAtDate($date)->where('project_id', $projectId);
            });
        }

        if ($this->filterAccommodationId) {
            $accId = (int) $this->filterAccommodationId;
            $q->whereHas('accommodationAssignments', function ($aq) use ($date, $accId) {
                $aq->activeAtDate($date)->where('accommodation_id', $accId);
            });
        }

        return $q;
    }

    public function getEmployeesPageProperty()
    {
        $date = $this->transferDate
            ? Carbon::parse($this->transferDate)
            : now();

        $ids = (clone $this->employeesQuery)->pluck('id');
        if ($ids->isEmpty()) {
            return Employee::query()->whereRaw('0 = 1')->paginate(12);
        }

        $employees = Employee::whereIn('id', $ids)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $eligibleIds = $this->hasReassignment
            ? $employees
                ->filter(fn (Employee $e) => $this->locationTracking->isEmployeeEligibleForTransfer($e, $date))
                ->pluck('id')
                ->values()
            : $employees->pluck('id')->values();

        if ($eligibleIds->isEmpty()) {
            return Employee::query()->whereRaw('0 = 1')->paginate(12);
        }

        return Employee::query()
            ->whereIn('id', $eligibleIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(12);
    }

    public function getSelectedEmployeesProperty()
    {
        if (empty($this->selectedEmployeeIds)) {
            return collect();
        }

        return Employee::whereIn('id', $this->selectedEmployeeIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public function getLocationsProperty()
    {
        return Location::orderBy('name')->get();
    }

    public function getFilteredLocationsForPickerProperty()
    {
        return $this->locations->filter(function (Location $loc) {
            if (! $this->locationSearch) {
                return true;
            }
            $q = mb_strtolower($this->locationSearch);

            return str_contains(mb_strtolower($loc->name), $q)
                || str_contains(mb_strtolower($loc->city ?? ''), $q);
        })->whereNotIn('id', $this->waypointLocationIds);
    }

    public function getWaypointLocationsProperty(): array
    {
        if (empty($this->waypointLocationIds)) {
            return [];
        }
        $locations = Location::whereIn('id', $this->waypointLocationIds)->get()->keyBy('id');
        $result = [];
        foreach ($this->waypointLocationIds as $id) {
            if ($loc = $locations->get($id)) {
                $result[] = $loc;
            }
        }

        return $result;
    }

    public function getVehiclesProperty()
    {
        return Vehicle::where('type', 'company_vehicle')
            ->orderBy('registration_number')
            ->get()
            ->filter(function (Vehicle $v) {
                if (! $this->vehicleSearch) {
                    return true;
                }
                $q = mb_strtolower($this->vehicleSearch);

                return str_contains(mb_strtolower($v->registration_number), $q)
                    || str_contains(mb_strtolower($v->brand ?? ''), $q)
                    || str_contains(mb_strtolower($v->model ?? ''), $q);
            });
    }

    public function getDriverPayrollsProperty()
    {
        if (! $this->driverEmployeeId) {
            return collect();
        }

        return \App\Models\Payroll::with('employee')
            ->where('employee_id', $this->driverEmployeeId)
            ->orderBy('period_start', 'desc')
            ->get();
    }

    public function getCurrenciesProperty(): array
    {
        return Currency::cases();
    }

    // ─── Waypoint management ────────────────────────────────────────────────────

    public function addWaypoint(): void
    {
        if (! $this->addLocationId) {
            return;
        }
        $id = (int) $this->addLocationId;
        if (! in_array($id, $this->waypointLocationIds)) {
            $this->waypointLocationIds[] = $id;
        }
        $this->addLocationId = null;
        $this->locationSearch = '';

        $baseEndpointMsg = $this->baseAsRouteEndpointMessage();
        if ($baseEndpointMsg !== null) {
            array_pop($this->waypointLocationIds);
            $this->waypointLocationIds = array_values($this->waypointLocationIds);
            $this->routeError = $baseEndpointMsg;
            $this->routeData = null;

            return;
        }

        $this->planRoute();
    }

    public function removeWaypoint(int $index): void
    {
        array_splice($this->waypointLocationIds, $index, 1);
        $this->waypointLocationIds = array_values($this->waypointLocationIds);
        $this->planRoute();
    }

    public function moveWaypointUp(int $index): void
    {
        if ($index <= 0) {
            return;
        }
        [$this->waypointLocationIds[$index - 1], $this->waypointLocationIds[$index]] =
            [$this->waypointLocationIds[$index], $this->waypointLocationIds[$index - 1]];

        $baseEndpointMsg = $this->baseAsRouteEndpointMessage();
        if ($baseEndpointMsg !== null) {
            [$this->waypointLocationIds[$index - 1], $this->waypointLocationIds[$index]] =
                [$this->waypointLocationIds[$index], $this->waypointLocationIds[$index - 1]];
            $this->routeError = $baseEndpointMsg;

            return;
        }

        $this->planRoute();
    }

    public function moveWaypointDown(int $index): void
    {
        if ($index >= count($this->waypointLocationIds) - 1) {
            return;
        }
        [$this->waypointLocationIds[$index], $this->waypointLocationIds[$index + 1]] =
            [$this->waypointLocationIds[$index + 1], $this->waypointLocationIds[$index]];

        $baseEndpointMsg = $this->baseAsRouteEndpointMessage();
        if ($baseEndpointMsg !== null) {
            [$this->waypointLocationIds[$index], $this->waypointLocationIds[$index + 1]] =
                [$this->waypointLocationIds[$index + 1], $this->waypointLocationIds[$index]];
            $this->routeError = $baseEndpointMsg;

            return;
        }

        $this->planRoute();
    }

    /**
     * Baza nie może być pierwszym ani ostatnim punktem trasy transferu (do tego służą zjazd/wyjazd).
     */
    private function baseAsRouteEndpointMessage(): ?string
    {
        if (count($this->waypointLocationIds) < 2) {
            return null;
        }

        $firstId = $this->waypointLocationIds[0];
        $lastId = $this->waypointLocationIds[array_key_last($this->waypointLocationIds)];
        $locs = Location::whereIn('id', [$firstId, $lastId])->get()->keyBy('id');
        $first = $locs->get($firstId);
        $last = $locs->get($lastId);

        $msg = 'Lokalizacja oznaczona jako baza nie może być pierwszym ani ostatnim punktem trasy. W tym celu ustaw zjazd lub wyjazd przy planowaniu delegacji.';

        if ($first && $first->is_base) {
            return $msg;
        }
        if ($last && $last->is_base) {
            return $msg;
        }

        return null;
    }

    // ─── Route planning ─────────────────────────────────────────────────────────

    public function planRoute(): void
    {
        if (count($this->waypointLocationIds) < 2) {
            $this->routeData = null;
            $this->routeError = null;

            return;
        }

        $baseMsg = $this->baseAsRouteEndpointMessage();
        if ($baseMsg !== null) {
            $this->routeError = $baseMsg;
            $this->routeData = null;

            return;
        }

        $locations = Location::whereIn('id', $this->waypointLocationIds)->get()->keyBy('id');

        // Geocode any locations missing coordinates
        foreach ($this->waypointLocationIds as $locId) {
            $loc = $locations->get($locId);
            if (! $loc) {
                continue;
            }
            if (! $loc->hasCoordinates()) {
                $address = $loc->getFullAddress();
                if ($address) {
                    try {
                        $coords = $this->geocodingService->geocode($address);
                        if ($coords && isset($coords['latitude'], $coords['longitude'])) {
                            $loc->update(['latitude' => $coords['latitude'], 'longitude' => $coords['longitude']]);
                            $loc->refresh();
                            $locations->put($loc->id, $loc);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Geocoding failed for location', ['id' => $loc->id]);
                    }
                }
            }
        }

        $missingCoords = [];
        foreach ($this->waypointLocationIds as $locId) {
            $loc = $locations->get($locId);
            if (! $loc || ! $loc->hasCoordinates()) {
                $missingCoords[] = $loc ? $loc->name : "ID:{$locId}";
            }
        }

        if (! empty($missingCoords)) {
            $this->routeError = 'Brak współrzędnych dla: '.implode(', ', $missingCoords).'. Edytuj lokalizację i uzupełnij adres.';
            $this->routeData = null;

            return;
        }

        $this->isPlanningRoute = true;
        $this->routeError = null;

        try {
            $ordered = array_map(fn ($id) => $locations->get($id), $this->waypointLocationIds);
            $start = array_shift($ordered);
            $end = array_pop($ordered);
            $intermediates = $ordered;

            $route = $this->routePlanningService->planRouteWithWaypoints($start, $end, $intermediates);

            if ($route) {
                $this->routeData = [
                    'distance' => $route['distance'],
                    'duration' => $route['duration'],
                ];
                $this->routeError = null;
            } else {
                $this->routeError = 'Nie udało się zaplanować trasy. Sprawdź współrzędne lokalizacji.';
                $this->routeData = null;
            }
        } catch (\Exception $e) {
            Log::error('Transfer route planning failed', ['message' => $e->getMessage()]);
            $this->routeError = 'Błąd podczas planowania trasy: '.$e->getMessage();
            $this->routeData = null;
        } finally {
            $this->isPlanningRoute = false;
        }
    }

    // ─── Participants ────────────────────────────────────────────────────────────

    public function toggleEmployee(int $employeeId): void
    {
        if (in_array($employeeId, $this->selectedEmployeeIds)) {
            $this->selectedEmployeeIds = array_values(
                array_filter($this->selectedEmployeeIds, fn ($id) => $id !== $employeeId)
            );
            if ($this->driverEmployeeId === $employeeId) {
                $this->driverEmployeeId = null;
                $this->driverPayrollId = null;
            }
        } else {
            $this->selectedEmployeeIds[] = $employeeId;
        }

        $this->syncParticipantHomeLocationsAndMaybeRouteStart();
        if ($this->hasReassignment) {
            $this->ensureReassignmentPayloadsForParticipants();
            $this->pruneReassignmentWizardForDeselectedParticipants();
        }
    }

    public function updatedTransferDate(): void
    {
        $this->pruneSelectedEmployeesIfIneligible();
        // Transfer date affects “where people live now”
        $this->syncParticipantHomeLocationsAndMaybeRouteStart();
    }

    protected function pruneSelectedEmployeesIfIneligible(): void
    {
        if ($this->selectedEmployeeIds === []) {
            return;
        }

        $date = $this->transferDate
            ? Carbon::parse($this->transferDate)
            : now();

        $this->selectedEmployeeIds = array_values(array_filter(
            $this->selectedEmployeeIds,
            function (int $id) use ($date) {
                $employee = Employee::find($id);
                if (! $employee) {
                    return false;
                }

                return $this->locationTracking->isEmployeeEligibleForTransfer($employee, $date);
            }
        ));

        if ($this->driverEmployeeId && ! in_array($this->driverEmployeeId, $this->selectedEmployeeIds, true)) {
            $this->driverEmployeeId = null;
            $this->driverPayrollId = null;
        }
    }

    protected function syncParticipantHomeLocationsAndMaybeRouteStart(): void
    {
        if (empty($this->selectedEmployeeIds)) {
            $this->participantHomeLocations = [];
            $this->waypointLocationIds = [];
            $this->routeData = null;
            $this->routeError = null;

            return;
        }

        $date = $this->transferDate ? Carbon::parse($this->transferDate) : now();

        $assignments = AccommodationAssignment::whereIn('employee_id', $this->selectedEmployeeIds)
            ->activeAtDate($date)
            ->with(['accommodation.location'])
            ->get()
            ->groupBy('employee_id');

        $homeByEmployee = [];
        foreach ($this->selectedEmployeeIds as $empId) {
            $a = $assignments->get($empId)?->sortByDesc('start_date')->first();
            $acc = $a?->accommodation;
            $loc = $acc?->location;

            if (! $a || ! $acc) {
                $homeByEmployee[$empId] = null;

                continue;
            }

            $homeByEmployee[$empId] = [
                'accommodation_id' => $acc->id,
                'accommodation_name' => $acc->name,
                'location_id' => $loc?->id,
                'location_name' => $loc?->name,
                'city' => $loc?->city ?? $acc->city,
                'is_base' => (bool) ($loc?->is_base ?? false),
            ];
        }

        $this->participantHomeLocations = $homeByEmployee;

        $this->syncWaypointLocationIdsFromHomes();
    }

    /**
     * Trasa: unikalne lokalizacje domów — najpierw obecne (na dzień transferu), potem z kreatora przeniesienia.
     */
    protected function collectOrderedHomeLocationIds(): array
    {
        $ordered = [];
        $seen = [];

        foreach ($this->selectedEmployeeIds as $empId) {
            $home = $this->participantHomeLocations[$empId] ?? null;
            $lid = $home['location_id'] ?? null;
            if ($lid && empty($seen[(int) $lid])) {
                $seen[(int) $lid] = true;
                $ordered[] = (int) $lid;
            }
        }

        if ($this->hasReassignment && ! empty($this->accommodationAssignments)) {
            $accIds = [];
            foreach ($this->selectedEmployeeIds as $empId) {
                $aid = (int) ($this->accommodationAssignments[$empId]['accommodation_id'] ?? 0);
                if ($aid > 0) {
                    $accIds[] = $aid;
                }
            }
            $accIds = array_values(array_unique($accIds));

            if ($accIds !== []) {
                $byId = Accommodation::query()->whereIn('id', $accIds)->get()->keyBy('id');

                foreach ($this->selectedEmployeeIds as $empId) {
                    $aid = (int) ($this->accommodationAssignments[$empId]['accommodation_id'] ?? 0);
                    if ($aid <= 0) {
                        continue;
                    }
                    $acc = $byId->get($aid);
                    $lid = $acc?->location_id;
                    if ($lid && empty($seen[(int) $lid])) {
                        $seen[(int) $lid] = true;
                        $ordered[] = (int) $lid;
                    }
                }
            }
        }

        return $ordered;
    }

    protected function syncWaypointLocationIdsFromHomes(): void
    {
        if ($this->selectedEmployeeIds === []) {
            return;
        }

        $homeIds = $this->collectOrderedHomeLocationIds();
        if ($homeIds === []) {
            return;
        }

        // Zachowaj kolejność ręcznych punktów, dopisz brakujące domy (stare + nowe).
        $merged = [];
        $seen = [];
        foreach ($this->waypointLocationIds as $wid) {
            $wid = (int) $wid;
            if ($wid > 0 && empty($seen[$wid])) {
                $seen[$wid] = true;
                $merged[] = $wid;
            }
        }
        foreach ($homeIds as $hid) {
            if (empty($seen[$hid])) {
                $seen[$hid] = true;
                $merged[] = $hid;
            }
        }

        $this->waypointLocationIds = $merged;
        $this->planRoute();
    }

    public function updatedDriverEmployeeId(): void
    {
        $this->driverPayrollId = null;
    }

    // ─── Reassignment ────────────────────────────────────────────────────────────

    public function updatedHasReassignment(): void
    {
        if ($this->hasReassignment) {
            $this->reassignStep = 1;
            $this->resetReassignmentWizardState();
            $this->initWizardVehicleSeatsIfNeeded();
            $this->ensureReassignmentPayloadsForParticipants();
        } else {
            $this->resetReassignmentWizardState();
            $this->reassignStep = 1;
        }

        $this->syncWaypointLocationIdsFromHomes();
    }

    public function updatedVehicleId(): void
    {
        if (! $this->hasReassignment) {
            return;
        }
        $this->vehicleSeats = [];
        if ($this->vehicleId) {
            $this->initWizardVehicleSeatsIfNeeded();
        }
        $this->dispatch('vehicle-seats-updated', vehicleSeats: $this->vehicleSeats);
    }

    /** Inicjalizuje wpisy reassignments dla wybranych uczestników (payload dla TransferService). */
    public function ensureReassignmentPayloadsForParticipants(): void
    {
        if (empty($this->selectedEmployeeIds)) {
            return;
        }

        $date = $this->transferDate
            ? \Carbon\Carbon::parse($this->transferDate)
            : now();

        foreach ($this->selectedEmployeeIds as $empId) {
            if (! isset($this->reassignments[$empId])) {
                $this->reassignments[$empId] = [
                    'project_id' => '',
                    'role_id' => '',
                    'accommodation_id' => '',
                    'vehicle_id' => '',
                    'vehicle_position' => \App\Enums\VehiclePosition::PASSENGER->value,
                    'start_date' => $date->format('Y-m-d'),
                    'end_date' => '',
                    'keep_current' => false,
                ];
            } else {
                if (! array_key_exists('role_id', $this->reassignments[$empId])) {
                    $this->reassignments[$empId]['role_id'] = '';
                }
                $this->reassignments[$empId]['keep_current'] = false;
            }
        }
    }

    protected function resetReassignmentWizardState(): void
    {
        $this->assignments = [];
        $this->assignmentRanges = [];
        $this->accommodationAssignments = [];
        $this->vehicleAssignments = [];
        $this->vehicleSeats = [];
    }

    protected function pruneReassignmentWizardForDeselectedParticipants(): void
    {
        $allowed = array_flip($this->selectedEmployeeIds);

        foreach ($this->assignmentRanges as $key => $range) {
            $eid = (int) ($range['employee_id'] ?? 0);
            if (! isset($allowed[$eid])) {
                unset($this->assignmentRanges[$key]);
            }
        }

        foreach ($this->accommodationAssignments as $eid => $_) {
            if (! isset($allowed[(int) $eid])) {
                unset($this->accommodationAssignments[$eid]);
            }
        }

        foreach ($this->vehicleAssignments as $eid => $_) {
            if (! isset($allowed[(int) $eid])) {
                unset($this->vehicleAssignments[$eid]);
            }
        }

        if (! empty($this->vehicleSeats)) {
            foreach ($this->vehicleSeats as $i => $seat) {
                $eid = (int) ($seat['employee_id'] ?? 0);
                if ($eid && ! isset($allowed[$eid])) {
                    $this->vehicleSeats[$i] = [
                        'employee_id' => null,
                        'position' => $seat['position'] ?? 'passenger',
                    ];
                }
            }
        }

        foreach (array_keys($this->reassignments) as $eid) {
            if (! isset($allowed[(int) $eid])) {
                unset($this->reassignments[$eid]);
            }
        }

        $this->dispatch('vehicle-seats-updated', vehicleSeats: $this->vehicleSeats);
        $this->dispatch('refresh-assignments');
    }

    protected function initWizardVehicleSeatsIfNeeded(): void
    {
        if (! $this->vehicleId) {
            return;
        }

        $vehicle = Vehicle::find($this->vehicleId);
        if (! $vehicle || ! $vehicle->capacity) {
            return;
        }

        if (count($this->vehicleSeats) !== (int) $vehicle->capacity) {
            $this->vehicleSeats = [];
            for ($i = 0; $i < $vehicle->capacity; $i++) {
                $this->vehicleSeats[$i] = [
                    'employee_id' => null,
                    'position' => 'passenger',
                ];
            }
        }
    }

    public function handleWizardGoToStep(mixed $step = null): void
    {
        if (! $this->hasReassignment) {
            return;
        }

        if (is_array($step)) {
            $step = (int) ($step['step'] ?? 0);
        } else {
            $step = (int) $step;
        }

        if ($step < 1 || $step > 3) {
            return;
        }

        $this->goToReassignStep($step);
    }

    public function goToReassignStep(int $step): void
    {
        $step = (int) $step;

        $this->resetErrorBag('reassignWizard');

        if ($step === 2) {
            $hasAssignments = ! empty($this->assignments) || ! empty($this->assignmentRanges);
            if (! $hasAssignments) {
                $this->addError(
                    'reassignWizard',
                    'Musisz przypisać przynajmniej jednego pracownika do projektu przed przejściem dalej.'
                );

                return;
            }
        }

        $this->reassignStep = $step;
    }

    public function handleAssignmentAdded($data): void
    {
        if (! $this->hasReassignment) {
            return;
        }

        $day = $data['day'];
        $projectId = $data['project_id'];
        $roleId = $data['role_id'];
        $employeeId = $data['employee_id'];

        if (! isset($this->assignments[$day])) {
            $this->assignments[$day] = [];
        }
        if (! isset($this->assignments[$day][$projectId])) {
            $this->assignments[$day][$projectId] = [];
        }
        if (! isset($this->assignments[$day][$projectId][$roleId])) {
            $this->assignments[$day][$projectId][$roleId] = [];
        }

        if (! in_array($employeeId, $this->assignments[$day][$projectId][$roleId])) {
            $this->assignments[$day][$projectId][$roleId][] = $employeeId;
        }
    }

    public function handleAssignmentRemoved($data = []): void
    {
        if (! $this->hasReassignment) {
            return;
        }

        if (empty($data) || ! is_array($data)) {
            return;
        }

        $day = $data['day'] ?? null;
        $projectId = $data['project_id'] ?? null;
        $roleId = $data['role_id'] ?? null;
        $employeeId = $data['employee_id'] ?? null;

        if (! $day || ! $projectId || ! $roleId || ! $employeeId) {
            return;
        }

        if (isset($this->assignments[$day][$projectId][$roleId])) {
            $this->assignments[$day][$projectId][$roleId] = array_values(
                array_filter($this->assignments[$day][$projectId][$roleId], fn ($id) => $id != $employeeId)
            );

            if (empty($this->assignments[$day][$projectId][$roleId])) {
                unset($this->assignments[$day][$projectId][$roleId]);
            }
            if (empty($this->assignments[$day][$projectId])) {
                unset($this->assignments[$day][$projectId]);
            }
            if (empty($this->assignments[$day])) {
                unset($this->assignments[$day]);
            }
        }
    }

    public function handleAssignmentRangeAdded($data): void
    {
        if (! $this->hasReassignment) {
            return;
        }

        $employeeId = (int) ($data['employee_id'] ?? 0);
        foreach ($this->assignmentRanges as $key => $range) {
            if ((int) ($range['employee_id'] ?? 0) === $employeeId) {
                unset($this->assignmentRanges[$key]);
            }
        }

        $key = $data['employee_id'].'_'.$data['project_id'].'_'.$data['role_id'];
        $this->assignmentRanges[$key] = [
            'employee_id' => $data['employee_id'],
            'project_id' => $data['project_id'],
            'role_id' => $data['role_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ];

        if ($this->vehicleId) {
            $alreadyInVehicle = false;

            if (empty($this->vehicleSeats)) {
                $this->initWizardVehicleSeatsIfNeeded();
            }

            foreach ($this->vehicleSeats as $seat) {
                if (! empty($seat['employee_id']) && (int) $seat['employee_id'] === $employeeId) {
                    $alreadyInVehicle = true;
                    break;
                }
            }

            if (! $alreadyInVehicle) {
                foreach ($this->vehicleSeats as $index => $seat) {
                    if (empty($seat['employee_id'])) {
                        $this->vehicleSeats[$index] = [
                            'employee_id' => $employeeId,
                            'position' => 'passenger',
                        ];
                        break;
                    }
                }
            }
        }

        $this->dispatch('refresh-assignments');
        $this->dispatch('vehicle-seats-updated', vehicleSeats: $this->vehicleSeats);
    }

    public function handleAssignmentRangeRemoved($data = []): void
    {
        if (! $this->hasReassignment) {
            return;
        }

        if (empty($data) || ! is_array($data)) {
            return;
        }

        $employeeId = $data['employee_id'] ?? null;
        $projectId = $data['project_id'] ?? null;
        $roleId = $data['role_id'] ?? null;

        if (! $employeeId || ! $projectId || ! $roleId) {
            return;
        }

        $key = $employeeId.'_'.$projectId.'_'.$roleId;
        unset($this->assignmentRanges[$key]);

        if ($this->vehicleId && ! empty($this->vehicleSeats)) {
            foreach ($this->vehicleSeats as $index => $seat) {
                if (! empty($seat['employee_id']) && (int) $seat['employee_id'] === (int) $employeeId) {
                    $this->vehicleSeats[$index] = [
                        'employee_id' => null,
                        'position' => 'passenger',
                    ];
                    break;
                }
            }
            $this->dispatch('vehicle-seats-updated', vehicleSeats: $this->vehicleSeats);
        }

        $this->dispatch('refresh-assignments');
    }

    public function handleVehicleSeatUpdated($data): void
    {
        if (! $this->hasReassignment) {
            return;
        }

        $seatIndex = $data['seat_index'];
        $this->vehicleSeats[$seatIndex] = [
            'employee_id' => $data['employee_id'] ?? null,
            'position' => $data['position'] ?? 'passenger',
        ];

        $this->dispatch('vehicle-seats-updated', vehicleSeats: $this->vehicleSeats);
    }

    public function handleAccommodationAssigned($data): void
    {
        if (! $this->hasReassignment) {
            return;
        }

        $this->accommodationAssignments[$data['employee_id']] = [
            'accommodation_id' => $data['accommodation_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ];

        $this->syncWaypointLocationIdsFromHomes();
    }

    public function handleAccommodationRemoved($data): void
    {
        if (! $this->hasReassignment) {
            return;
        }

        unset($this->accommodationAssignments[$data['employee_id']]);

        $this->syncWaypointLocationIdsFromHomes();
    }

    public function handleVehicleAssigned($data): void
    {
        if (! $this->hasReassignment) {
            return;
        }

        $this->vehicleAssignments[$data['employee_id']] = [
            'vehicle_id' => $data['vehicle_id'],
            'position' => $data['position'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ];
    }

    public function handleVehicleAssignmentRemoved($data): void
    {
        if (! $this->hasReassignment) {
            return;
        }

        unset($this->vehicleAssignments[$data['employee_id']]);
    }

    protected function syncReassignmentsFromWizard(): void
    {
        if (! $this->hasReassignment) {
            return;
        }

        $transferDate = $this->transferDate ? Carbon::parse($this->transferDate) : now();

        foreach ($this->selectedEmployeeIds as $empId) {
            if (! isset($this->reassignments[$empId])) {
                continue;
            }

            $this->reassignments[$empId]['keep_current'] = false;

            $range = collect($this->assignmentRanges)->first(
                fn ($r) => (int) ($r['employee_id'] ?? 0) === (int) $empId
            );

            $acc = $this->accommodationAssignments[$empId] ?? null;
            $veh = $this->vehicleAssignments[$empId] ?? null;

            $start = $range['start_date'] ?? ($acc['start_date'] ?? null) ?? ($veh['start_date'] ?? null) ?? $transferDate->format('Y-m-d');
            $end = $range['end_date'] ?? ($acc['end_date'] ?? null) ?? ($veh['end_date'] ?? null) ?? '';

            $this->reassignments[$empId]['project_id'] = $range ? (string) $range['project_id'] : '';
            $this->reassignments[$empId]['role_id'] = $range ? (string) $range['role_id'] : '';
            $this->reassignments[$empId]['accommodation_id'] = $acc ? (string) $acc['accommodation_id'] : '';
            $this->reassignments[$empId]['vehicle_id'] = $veh ? (string) $veh['vehicle_id'] : '';
            $this->reassignments[$empId]['vehicle_position'] = $veh['position'] ?? \App\Enums\VehiclePosition::PASSENGER->value;
            $this->reassignments[$empId]['start_date'] = $start;
            $this->reassignments[$empId]['end_date'] = $end !== '' ? $end : '';
        }
    }

    public function getProjectsListProperty()
    {
        return Project::with('location')
            ->where('status', \App\Enums\ProjectStatus::ACTIVE)
            ->orderBy('name')
            ->get();
    }

    public function getAccommodationsListProperty()
    {
        return Accommodation::with('location')
            ->orderBy('name')
            ->get();
    }

    public function getFilteredProjectsForParticipantFilterProperty()
    {
        $q = mb_strtolower(trim($this->projectSearch));

        return $this->projectsList->filter(function (Project $p) use ($q) {
            if ($q === '') {
                return true;
            }

            $name = mb_strtolower($p->name ?? '');
            $loc = mb_strtolower($p->location?->name ?? '');

            return str_contains($name, $q) || str_contains($loc, $q);
        })->values();
    }

    public function getFilteredAccommodationsForParticipantFilterProperty()
    {
        $q = mb_strtolower(trim($this->accommodationSearch));

        return $this->accommodationsList->filter(function (Accommodation $a) use ($q) {
            if ($q === '') {
                return true;
            }

            $name = mb_strtolower($a->name ?? '');
            $loc = mb_strtolower($a->location?->name ?? '');
            $city = mb_strtolower($a->city ?? '');

            return str_contains($name, $q) || str_contains($loc, $q) || str_contains($city, $q);
        })->values();
    }

    // ─── Save ────────────────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->validate([
            'transferDate' => ['required', 'date'],
            'waypointLocationIds' => ['required', 'array', 'min:2'],
            'vehicleId' => ['nullable', 'exists:vehicles,id'],
            'selectedEmployeeIds' => ['required', 'array', 'min:1'],
            'selectedEmployeeIds.*' => ['exists:employees,id'],
            'driverEmployeeId' => ['nullable', 'in:'.implode(',', $this->selectedEmployeeIds ?: [0])],
            'driverPaymentAmount' => ['nullable', 'numeric', 'min:0'],
            'driverPaymentCurrency' => ['required_with:driverPaymentAmount', 'in:'.implode(',', Currency::values())],
            'driverPayrollId' => [
                'nullable',
                'exists:payrolls,id',
                function ($attribute, $value, $fail) {
                    if ($value && $this->driverEmployeeId) {
                        $payroll = \App\Models\Payroll::find($value);
                        if ($payroll && $payroll->employee_id !== (int) $this->driverEmployeeId) {
                            $fail('Wybrany payroll nie należy do kierowcy.');
                        }
                    }
                },
            ],
        ], [
            'transferDate.required' => 'Data transferu jest wymagana.',
            'waypointLocationIds.required' => 'Dodaj co najmniej 2 lokalizacje (start i cel).',
            'waypointLocationIds.min' => 'Transfer musi mieć co najmniej lokalizację startową i docelową.',
            'selectedEmployeeIds.required' => 'Dodaj co najmniej jednego uczestnika.',
            'selectedEmployeeIds.min' => 'Dodaj co najmniej jednego uczestnika.',
            'driverPaymentCurrency.in' => 'Wybierz walutę z listy.',
        ]);

        $baseEndpointMsg = $this->baseAsRouteEndpointMessage();
        if ($baseEndpointMsg !== null) {
            $this->addError('waypointLocationIds', $baseEndpointMsg);

            return;
        }

        if ($this->hasReassignment) {
            $this->syncReassignmentsFromWizard();
        }

        $fromLocationId = $this->waypointLocationIds[0];
        $toLocationId = $this->waypointLocationIds[count($this->waypointLocationIds) - 1];

        try {
            $this->transferService->commitTransfer([
                'employee_ids' => $this->selectedEmployeeIds,
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocationId,
                'transfer_date' => \Carbon\Carbon::parse($this->transferDate),
                'vehicle_id' => $this->vehicleId ?: null,
                'notes' => $this->notes ?: null,
                'route_distance' => $this->routeData['distance'] ?? null,
                'route_duration' => $this->routeData ? (int) $this->routeData['duration'] : null,
                'route_waypoints' => count($this->waypointLocationIds) > 2
                    ? array_slice($this->waypointLocationIds, 1, -1)
                    : null,
                'has_reassignment' => $this->hasReassignment,
                'reassignments' => $this->reassignments,
                'driver_employee_id' => $this->driverEmployeeId,
                'driver_payment_amount' => $this->driverPaymentAmount !== '' ? (float) $this->driverPaymentAmount : null,
                'driver_payment_currency' => $this->driverPaymentCurrency,
                'driver_payroll_id' => $this->driverPayrollId,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('TransferPlanner::save commitTransfer failed', [
                'exception' => $e,
            ]);
            $this->addError('save', $e->getMessage());

            return;
        }

        session()->flash('success', $this->hasReassignment
            ? 'Transfer z przeniesieniem został zapisany.'
            : 'Transfer został zapisany.');
        $this->redirect(route('transfers.index'));
    }

    public function render()
    {
        $vehicleDocConfirmPayload = VehicleDocumentExpiry::confirmPayload(
            Vehicle::where('type', 'company_vehicle')->orderBy('registration_number')->get()
        );

        return view('livewire.transfer-planner', compact('vehicleDocConfirmPayload'));
    }
}
