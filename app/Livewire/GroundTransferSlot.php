<?php

namespace App\Livewire;

use App\Data\TransferGroundConfig;
use App\Enums\Currency;
use App\Enums\LocationPurposeType;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Vehicle;
use App\Services\RoutePlanningService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class GroundTransferSlot extends Component
{
    // --- Props (set by parent) ---
    public string $slotKey = 'default';

    public string $contextLabel = 'Transfer';

    #[Reactive]
    public string $dateFrom = '';

    #[Reactive]
    public string $dateTo = '';

    #[Reactive]
    public array $selectedEmployeeIds = [];

    public array $initialConfig = [];

    public ?int $baseLocationId = null;

    public ?int $fixedEndLocationId = null;

    /** Gdy ustawione (np. z TransferCreateBoard), nadpisuje pojazd w slocie przy zapisie konfiguracji. */
    #[Reactive]
    public ?int $syncVehicleId = null;

    /**
     * Kierowca z karty „Miejsca w pojeździe” (rodzic) — tylko do podglądu obok przycisków,
     * zgodnie z faktycznym zapisem transferu (save bierze kierowcę z siatki, uznanie z konfiguracji).
     */
    #[Reactive]
    public ?int $panelDriverEmployeeId = null;

    #[Reactive]
    public bool $panelDriverIsExternal = false;

    #[Reactive]
    public ?string $externalLegKind = null;

    // --- Internal state ---
    public ?string $legKind = null;

    public ?string $groundMode = null;

    public ?int $vehicleId = null;

    public ?int $driverEmployeeId = null;

    public ?float $driverPaymentAmount = null;

    public string $driverPaymentCurrency = 'PLN';

    public ?int $driverPayrollId = null;

    public array $routeWaypoints = [];

    public array $locationStopNotes = [];

    public ?float $routeDistance = null;

    public ?int $routeDuration = null;

    public bool $routeDistanceIsManual = false;

    public array $publicTicketCostsByEmployee = [];

    // --- Seat grid state ---
    public bool $isExternalDriver = false;

    public ?int $seatDriverEmployeeId = null;

    // --- Modal visibility ---
    public bool $showConfigModal = false;

    public bool $showRouteModal = false;

    // --- Ephemeral modal inputs ---
    public ?string $pendingLegKind = null;

    public ?string $pendingGroundMode = null;

    public ?float $manualDistanceKm = null;

    public ?int $manualDurationMin = null;

    public ?int $pendingWaypointLocationId = null;

    /** Komunikat błędu po ostatnim wywołaniu ORS (OpenRouteService). */
    public ?string $routeOrsError = null;

    protected RoutePlanningService $routePlanningService;

    public function boot(RoutePlanningService $routePlanningService): void
    {
        $this->routePlanningService = $routePlanningService;
    }

    public function mount(): void
    {
        if (! empty($this->initialConfig)) {
            $this->hydrateFromConfig(TransferGroundConfig::fromArray($this->initialConfig));
        }

        if ($this->externalLegKind !== null && $this->legKind === null) {
            $this->legKind = $this->externalLegKind;
            if ($this->externalLegKind === 'own' && $this->groundMode === null) {
                $this->groundMode = 'car';
            }
        }

        if ($this->syncVehicleId !== null && $this->syncVehicleId > 0) {
            $this->vehicleId = $this->syncVehicleId;
            $this->emitUpdated();
        }
    }

    public function updatedExternalLegKind(): void
    {
        if ($this->externalLegKind !== null) {
            $this->legKind = $this->externalLegKind;
            if ($this->externalLegKind === 'own' && $this->groundMode === null) {
                $this->groundMode = 'car';
            }
            $this->emitUpdated();
        }
    }

    public function updatedSyncVehicleId(?int $value): void
    {
        if ($value !== null && $value > 0) {
            $this->vehicleId = $value;
            $this->emitUpdated();
        }
    }

    /**
     * Notatki przy przystankach muszą trafić do rodzica (groundTransferConfig) przed „Zapisz transfer”.
     * Sam wire:model aktualizuje tylko ten komponent — bez emitu rodzic ma stare location_stop_notes.
     */
    public function updatedLocationStopNotes(): void
    {
        $this->emitUpdated();
    }

    // -------------------------------------------------------------------------
    // Config modal
    // -------------------------------------------------------------------------

    public function openConfigModal(): void
    {
        if ($this->syncVehicleId !== null && $this->syncVehicleId > 0) {
            $this->vehicleId = $this->syncVehicleId;
        }
        if ($this->panelDriverIsExternal) {
            $this->isExternalDriver = true;
            $this->seatDriverEmployeeId = null;
            $this->driverEmployeeId = null;
        } elseif ($this->panelDriverEmployeeId !== null && $this->panelDriverEmployeeId > 0) {
            $this->isExternalDriver = false;
            $this->seatDriverEmployeeId = $this->panelDriverEmployeeId;
            $this->driverEmployeeId = $this->panelDriverEmployeeId;
        }

        $this->pendingLegKind = $this->externalLegKind ?? $this->legKind;
        $this->pendingGroundMode = $this->groundMode ?? ($this->externalLegKind === 'own' ? 'car' : null);
        $this->showConfigModal = true;
    }

    public function selectLegKind(string $kind): void
    {
        if (! in_array($kind, ['own', 'public'], true)) {
            return;
        }
        $this->pendingLegKind = $kind;
        if ($kind === 'public') {
            $this->pendingGroundMode = null;
        }
    }

    public function selectGroundMode(string $mode): void
    {
        if (! in_array($mode, ['car', 'other'], true)) {
            return;
        }
        $this->pendingGroundMode = $mode;
    }

    public function confirmConfigModal(): void
    {
        $this->legKind = $this->pendingLegKind;
        $this->groundMode = $this->pendingLegKind === 'own' ? $this->pendingGroundMode : null;

        if ($this->legKind !== 'own') {
            $this->vehicleId = null;
            $this->driverEmployeeId = null;
            $this->driverPaymentAmount = null;
            $this->driverPayrollId = null;
            $this->seatDriverEmployeeId = null;
            $this->isExternalDriver = false;
        }

        $this->showConfigModal = false;
        $this->emitUpdated();
    }

    public function closeConfigModal(): void
    {
        $this->pendingLegKind = null;
        $this->pendingGroundMode = null;
        $this->showConfigModal = false;
    }

    // -------------------------------------------------------------------------
    // Route modal
    // -------------------------------------------------------------------------

    public function openRouteModal(): void
    {
        // If the route is empty, parent may decide to seed default waypoints.
        if ($this->routeWaypoints === []) {
            $this->dispatch('ground-transfer-slot-request-default-waypoints', slotKey: $this->slotKey);
        }

        $this->routeOrsError = null;
        $this->manualDistanceKm = $this->routeDistance !== null
            ? round($this->routeDistance / 1000, 1)
            : null;
        $this->manualDurationMin = $this->routeDuration !== null
            ? (int) round($this->routeDuration / 60)
            : null;
        $this->showRouteModal = true;
    }

    #[\Livewire\Attributes\On('ground-transfer-slot-apply-default-waypoints')]
    public function applyDefaultWaypoints(string $slotKey, array $waypoints, array $locationStopNotes = []): void
    {
        if ($slotKey !== $this->slotKey) {
            return;
        }
        if ($this->routeWaypoints !== []) {
            return;
        }

        $filtered = array_values(array_filter(
            array_map('strval', $waypoints),
            fn (string $k) => str_starts_with($k, 'loc:') && (int) substr($k, 4) > 0
        ));
        if ($filtered === []) {
            return;
        }

        $this->routeWaypoints = $filtered;
        $this->routeWaypoints = array_values($this->routeWaypoints);

        // Apply notes only for route locations and only if empty/not set.
        if (is_array($locationStopNotes) && $locationStopNotes !== []) {
            foreach ($locationStopNotes as $locId => $note) {
                $lid = (int) $locId;
                if ($lid <= 0) {
                    continue;
                }
                $key = (string) $lid;
                $existing = $this->locationStopNotes[$key] ?? '';
                if (trim((string) $existing) !== '') {
                    continue;
                }
                $n = is_string($note) ? trim($note) : '';
                if ($n === '') {
                    continue;
                }
                $this->locationStopNotes[$key] = $n;
            }
        }

        $this->invalidateRouteMetrics();
    }

    public function applyManualRoute(): void
    {
        $this->syncManualRouteFromInputs();
        $this->emitUpdated();
    }

    public function updatedManualDistanceKm(): void
    {
        $this->syncManualRouteFromInputs();
        $this->emitUpdated();
    }

    public function updatedManualDurationMin(): void
    {
        $this->syncManualRouteFromInputs();
        $this->emitUpdated();
    }

    /** Zapisuje km/min z pól do routeDistance (m) / routeDuration (s); oznacza trasę jako ręczną (edycja w modalu). */
    private function syncManualRouteFromInputs(): void
    {
        $this->routeOrsError = null;
        $km = $this->manualDistanceKm;
        $min = $this->manualDurationMin;
        if ($km !== null && $km !== '') {
            $this->routeDistance = round((float) $km * 1000, 1);
        }
        if ($min !== null && $min !== '') {
            $this->routeDuration = (int) round((float) $min * 60);
        }
        if (($km !== null && $km !== '') || ($min !== null && $min !== '')) {
            $this->routeDistanceIsManual = true;
        }
    }

    /** Jak syncManualRouteFromInputs, ale bez zmiany routeDistanceIsManual (np. „Zapisz i zamknij” po ORS). */
    private function applyRouteInputsToMetricsWithoutChangingManualFlag(): void
    {
        $this->routeOrsError = null;
        $km = $this->manualDistanceKm;
        $min = $this->manualDurationMin;
        if ($km !== null && $km !== '') {
            $this->routeDistance = round((float) $km * 1000, 1);
        }
        if ($min !== null && $min !== '') {
            $this->routeDuration = (int) round((float) $min * 60);
        }
    }

    /**
     * Wyznacza dystans i czas przez OpenRouteService w tej samej kolejności co routeWaypoints.
     *
     * Zgodnie z TransferCreateBoard::saveSimpleTransfer: przy ≥2 przystankach from = pierwszy loc,
     * to = ostatni loc — ORS musi iść po kolei przez listę użytkownika, bez doklejania bazy na start
     * (to dodawało np. „baza → … → baza” i zawyżało km). Przy 1 przystanku: from = ten loc, to = baza → trasa loc → baza.
     */
    public function recalculateRouteWithOrs(): void
    {
        $this->routeOrsError = null;

        if ($this->legKind !== 'own' || $this->groundMode !== 'car') {
            return;
        }

        $base = $this->baseLocationId
            ? Location::find($this->baseLocationId)
            : Location::getBase();

        if (! $base instanceof Location || ! $base->hasCoordinates()) {
            $this->routeOrsError = 'Wpisz dystans i czas ręcznie.';

            return;
        }

        $locIds = [];
        foreach ($this->routeWaypoints as $key) {
            $p = $this->parseWaypointKey((string) $key);
            if ($p['type'] === 'loc' && $p['id'] > 0) {
                $locIds[] = $p['id'];
            }
        }

        if ($locIds === []) {
            $this->routeOrsError = 'Wpisz dystans i czas ręcznie.';

            return;
        }

        $locations = Location::whereIn('id', array_values(array_unique($locIds)))->get()->keyBy('id');

        $chain = [];
        foreach ($locIds as $id) {
            $loc = $locations->get($id);
            if (! $loc instanceof Location) {
                $this->routeOrsError = 'Wpisz dystans i czas ręcznie.';

                return;
            }
            if (! $loc->hasCoordinates()) {
                $this->routeOrsError = 'Wpisz dystans i czas ręcznie.';

                return;
            }
            if ($chain !== [] && (int) $chain[count($chain) - 1]->id === (int) $loc->id) {
                continue;
            }
            $chain[] = $loc;
        }

        if ($chain === []) {
            $this->routeOrsError = 'Wpisz dystans i czas ręcznie.';

            return;
        }

        if (count($chain) === 1) {
            $first = $chain[0];
            if ((int) $first->id === (int) $base->id) {
                $this->routeOrsError = 'Wpisz dystans i czas ręcznie.';

                return;
            }
            $chain[] = $base;
        }

        if (count($chain) < 2) {
            $this->routeOrsError = 'Wpisz dystans i czas ręcznie.';

            return;
        }

        $route = $this->routePlanningService->planRouteAlongOrderedLocations($chain);

        if ($route === null) {
            $this->routeOrsError = 'Wpisz dystans i czas ręcznie.';

            return;
        }

        $this->routeDistance = round((float) $route['distance'] * 1000, 1);
        $this->routeDuration = (int) $route['duration'];
        $this->routeDistanceIsManual = false;
        $this->manualDistanceKm = round($this->routeDistance / 1000, 1);
        $this->manualDurationMin = (int) round($this->routeDuration / 60);
        $this->emitUpdated();
    }

    public function confirmRouteModal(): void
    {
        $this->applyRouteInputsToMetricsWithoutChangingManualFlag();
        $this->showRouteModal = false;
        $this->emitUpdated();
    }

    public function closeRouteModal(): void
    {
        $this->manualDistanceKm = null;
        $this->manualDurationMin = null;
        $this->showRouteModal = false;
    }

    // -------------------------------------------------------------------------
    // Waypoint management
    // -------------------------------------------------------------------------

    public function addWaypoint(): void
    {
        $id = $this->pendingWaypointLocationId;
        if (! $id) {
            return;
        }

        $key = 'loc:'.$id;
        if (! in_array($key, $this->routeWaypoints, true)) {
            $this->routeWaypoints[] = $key;
            $this->routeWaypoints = array_values($this->routeWaypoints);
            $this->locationStopNotes[(string) $id] ??= '';
            $this->invalidateRouteMetrics();
        }

        $this->pendingWaypointLocationId = null;
    }

    public function removeWaypoint(int $index): void
    {
        if ($index < 0 || $index >= count($this->routeWaypoints)) {
            return;
        }
        array_splice($this->routeWaypoints, $index, 1);
        $this->routeWaypoints = array_values($this->routeWaypoints);
        $this->pruneLocationStopNotes();
        $this->invalidateRouteMetrics();
    }

    public function moveWaypointUp(int $index): void
    {
        if ($index <= 0 || $index >= count($this->routeWaypoints)) {
            return;
        }
        [$this->routeWaypoints[$index - 1], $this->routeWaypoints[$index]] =
            [$this->routeWaypoints[$index], $this->routeWaypoints[$index - 1]];
        $this->routeWaypoints = array_values($this->routeWaypoints);
        $this->invalidateRouteMetrics();
    }

    public function moveWaypointDown(int $index): void
    {
        if ($index < 0 || $index >= count($this->routeWaypoints) - 1) {
            return;
        }
        [$this->routeWaypoints[$index], $this->routeWaypoints[$index + 1]] =
            [$this->routeWaypoints[$index + 1], $this->routeWaypoints[$index]];
        $this->routeWaypoints = array_values($this->routeWaypoints);
        $this->invalidateRouteMetrics();
    }

    // -------------------------------------------------------------------------
    // Seat grid
    // -------------------------------------------------------------------------

    public function assignDriverSeatEmployee(?int $id): void
    {
        $this->seatDriverEmployeeId = $id;
        if ($id !== null) {
            $this->isExternalDriver = false;
        }
        $this->driverEmployeeId = $id;
        $this->emitUpdated();
    }

    public function toggleExternalDriver(): void
    {
        $this->isExternalDriver = ! $this->isExternalDriver;
        if ($this->isExternalDriver) {
            $this->seatDriverEmployeeId = null;
            $this->driverEmployeeId = null;
        }
        $this->emitUpdated();
    }

    // -------------------------------------------------------------------------
    // Ticket costs
    // -------------------------------------------------------------------------

    public function updateTicketCost(int|string $employeeId, string $field, mixed $value): void
    {
        $row = $this->publicTicketCostsByEmployee[$employeeId] ?? [];
        $row[$field] = $value;
        $this->publicTicketCostsByEmployee[$employeeId] = $row;
        $this->emitUpdated();
    }

    // -------------------------------------------------------------------------
    // Computed
    // -------------------------------------------------------------------------

    #[Computed]
    public function availableVehicles()
    {
        return Vehicle::orderBy('registration_number')->get();
    }

    #[Computed]
    public function availableEmployees()
    {
        return Employee::orderBy('last_name')->get();
    }

    /**
     * Modal „Konfiguruj transfer”: gdy pojazd jest już wybrany u rodzica (nagłówek), tylko ten wpis — bez rozjazdu z górą.
     */
    #[Computed]
    public function configModalVehicleOptions(): Collection
    {
        $vid = $this->syncVehicleId;
        if ($vid !== null && $vid > 0) {
            $v = $this->availableVehicles->firstWhere('id', $vid);

            return $v ? collect([$v]) : $this->availableVehicles;
        }

        return $this->availableVehicles;
    }

    /**
     * Modal: gdy kierowca siedzi już na fotelu (siatka u rodzica), tylko ta osoba; zewnętrzny / brak — pełna lista.
     */
    #[Computed]
    public function configModalDriverOptions(): Collection
    {
        if ($this->panelDriverIsExternal) {
            return $this->availableEmployees;
        }
        $pid = $this->panelDriverEmployeeId;
        if ($pid !== null && $pid > 0) {
            $emp = $this->selectedEmployees->firstWhere('id', $pid) ?? Employee::query()->find($pid);

            return $emp ? collect([$emp]) : $this->availableEmployees;
        }

        return $this->availableEmployees;
    }

    #[Computed]
    public function currencyCases(): array
    {
        return Currency::cases();
    }

    #[Computed]
    public function selectedEmployees()
    {
        if ($this->selectedEmployeeIds === []) {
            return collect();
        }
        $ids = array_values(array_filter(
            array_map('intval', \Illuminate\Support\Arr::flatten($this->selectedEmployeeIds)),
            fn ($id) => $id > 0
        ));
        if ($ids === []) {
            return collect();
        }

        return Employee::whereIn('id', $ids)
            ->orderBy('last_name')
            ->get();
    }

    #[Computed]
    public function driverEmployee()
    {
        if ($this->isExternalDriver || ! $this->seatDriverEmployeeId) {
            return null;
        }

        return $this->selectedEmployees->firstWhere('id', $this->seatDriverEmployeeId);
    }

    #[Computed]
    public function driverCandidates()
    {
        return $this->selectedEmployees;
    }

    #[Computed]
    public function passengerSlots()
    {
        $driverId = $this->isExternalDriver ? null : $this->seatDriverEmployeeId;

        return $this->selectedEmployees
            ->filter(fn ($e) => $e->id !== $driverId)
            ->values()
            ->all();
    }

    #[Computed]
    public function vehicleCapacity(): int
    {
        if (! $this->vehicleId) {
            return 0;
        }
        $v = $this->availableVehicles->firstWhere('id', $this->vehicleId);

        return $v?->capacity ?? 0;
    }

    #[Computed]
    public function selectedVehicle(): ?Vehicle
    {
        if (! $this->vehicleId) {
            return null;
        }

        return $this->availableVehicles->firstWhere('id', $this->vehicleId);
    }

    /**
     * Struktura zgodna z `x-logistics.vehicle-seat-grid`:
     * - index 0: kierowca (position=driver) + external_driver
     * - reszta: pasażerowie (position=passenger)
     */
    #[Computed]
    public function vehicleSeats(): array
    {
        $capacity = $this->vehicleCapacity;
        if ($capacity <= 0) {
            return [];
        }

        $seats = [];

        $driverEmployeeId = (! $this->isExternalDriver && $this->seatDriverEmployeeId)
            ? (int) $this->seatDriverEmployeeId
            : null;

        $seats[] = [
            'employee_id' => $driverEmployeeId,
            'position' => 'driver',
            'external_driver' => (bool) $this->isExternalDriver,
        ];

        $passengers = collect($this->selectedEmployees)
            ->filter(fn ($e) => $driverEmployeeId === null || (int) $e->id !== $driverEmployeeId)
            ->values()
            ->all();

        for ($i = 1; $i < $capacity; $i++) {
            $emp = $passengers[$i - 1] ?? null;
            $seats[] = [
                'employee_id' => $emp ? (int) $emp->id : null,
                'position' => 'passenger',
            ];
        }

        return $seats;
    }

    #[Computed]
    public function totalTripPeople(): int
    {
        return count($this->selectedEmployeeIds);
    }

    #[Computed]
    public function isMissingDriver(): bool
    {
        return ! $this->isExternalDriver && ! $this->seatDriverEmployeeId;
    }

    #[Computed]
    public function isOverCapacity(): bool
    {
        return $this->vehicleCapacity > 0 && $this->totalTripPeople > $this->vehicleCapacity;
    }

    #[Computed]
    public function isConfigured(): bool
    {
        return ! $this->toConfig()->isEmpty();
    }

    #[Computed]
    public function routeTiles(): array
    {
        $locIds = [];
        foreach ($this->routeWaypoints as $key) {
            $p = $this->parseWaypointKey((string) $key);
            if ($p['type'] === 'loc' && $p['id'] > 0) {
                $locIds[] = $p['id'];
            }
        }

        $locations = $locIds
            ? Location::whereIn('id', array_unique($locIds))->withCount('accommodations')->get()->keyBy('id')
            : collect();

        $n = count($this->routeWaypoints);
        $tiles = [];

        foreach ($this->routeWaypoints as $index => $key) {
            $p = $this->parseWaypointKey((string) $key);
            if ($p['type'] !== 'loc') {
                continue;
            }
            $loc = $locations->get($p['id']);
            $typeLabel = null;
            if ($loc instanceof Location) {
                if ($loc->is_base || ($this->baseLocationId && (int) $loc->id === (int) $this->baseLocationId)) {
                    $typeLabel = 'Baza';
                } elseif ($loc->hasPurpose(LocationPurposeType::AIRPORT)) {
                    $typeLabel = 'Lotnisko';
                } elseif ($loc->hasPurpose(LocationPurposeType::STATION)) {
                    $typeLabel = 'Dworzec';
                } elseif (((int) ($loc->accommodations_count ?? 0)) > 0) {
                    $typeLabel = 'Dom';
                } else {
                    $typeLabel = 'Lokalizacja';
                }
            }
            $tiles[] = [
                'index' => $index,
                'key' => $key,
                'id' => $p['id'],
                'type_label' => $typeLabel,
                'name' => $loc?->name ?? '—',
                'city' => $loc?->city ?? null,
                'address' => $loc?->address ?? null,
                'can_move_up' => $index > 0,
                'can_move_down' => $index < $n - 1,
                'can_remove' => true,
            ];
        }

        return $tiles;
    }

    #[Computed]
    public function availableLocations()
    {
        return Location::orderBy('name')->withCount('accommodations')->get();
    }

    #[Computed]
    public function panelDriverLabel(): string
    {
        if ($this->panelDriverIsExternal) {
            return 'Kierowca zewnętrzny';
        }
        if (! $this->panelDriverEmployeeId) {
            return '— wybierz kierowcę —';
        }
        $id = $this->panelDriverEmployeeId;
        $emp = $this->selectedEmployees->firstWhere('id', $id);
        if (! $emp) {
            $emp = Employee::find($id);
        }

        return $emp?->full_name ?? ('#'.$id);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function toConfig(): TransferGroundConfig
    {
        return TransferGroundConfig::fromArray($this->currentState());
    }

    private function currentState(): array
    {
        return [
            'leg_kind' => $this->legKind,
            'ground_mode' => $this->groundMode,
            'vehicle_id' => $this->vehicleId,
            'driver_employee_id' => $this->driverEmployeeId,
            'driver_payment_amount' => $this->driverPaymentAmount,
            'driver_payment_currency' => $this->driverPaymentCurrency,
            'driver_payroll_id' => $this->driverPayrollId,
            'route_waypoints' => $this->routeWaypoints,
            'location_stop_notes' => $this->locationStopNotes,
            'route_distance' => $this->routeDistance,
            'route_duration' => $this->routeDuration,
            'route_distance_is_manual' => $this->routeDistanceIsManual,
            'public_ticket_costs_by_employee' => $this->publicTicketCostsByEmployee,
        ];
    }

    private function hydrateFromConfig(TransferGroundConfig $config): void
    {
        $this->legKind = $config->legKind;
        $this->groundMode = $config->groundMode;
        $this->vehicleId = $config->vehicleId;
        $this->driverEmployeeId = $config->driverEmployeeId;
        $this->driverPaymentAmount = $config->driverPaymentAmount;
        $this->driverPaymentCurrency = $config->driverPaymentCurrency;
        $this->driverPayrollId = $config->driverPayrollId;
        $this->routeWaypoints = $config->routeWaypoints;
        $this->locationStopNotes = $config->locationStopNotes;
        $this->routeDistance = $config->routeDistance;
        $this->routeDuration = $config->routeDuration;
        $this->routeDistanceIsManual = $config->routeDistanceIsManual;
        $this->publicTicketCostsByEmployee = $config->publicTicketCostsByEmployee;
    }

    private function parseWaypointKey(string $key): array
    {
        if (str_starts_with($key, 'loc:')) {
            return ['type' => 'loc', 'id' => (int) substr($key, 4)];
        }

        return ['type' => 'unknown', 'id' => 0];
    }

    private function pruneLocationStopNotes(): void
    {
        $allowed = [];
        foreach ($this->routeWaypoints as $key) {
            $p = $this->parseWaypointKey((string) $key);
            if ($p['type'] === 'loc' && $p['id'] > 0) {
                $allowed[(string) $p['id']] = true;
            }
        }
        $this->locationStopNotes = array_filter(
            $this->locationStopNotes,
            fn ($k) => isset($allowed[$k]),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function invalidateRouteMetrics(): void
    {
        $this->routeDistance = null;
        $this->routeDuration = null;
        $this->routeDistanceIsManual = false;
        $this->routeOrsError = null;
        $this->emitUpdated();
    }

    private function emitUpdated(): void
    {
        $this->dispatch(
            'ground-transfer-slot-updated',
            slotKey: $this->slotKey,
            config: $this->toConfig()->toArray(),
        );
    }

    public function render()
    {
        return view('livewire.ground-transfer-slot', [
            'availableVehicles' => $this->availableVehicles,
            'availableEmployees' => $this->availableEmployees,
            'configModalVehicleOptions' => $this->configModalVehicleOptions,
            'configModalDriverOptions' => $this->configModalDriverOptions,
            'currencyCases' => $this->currencyCases,
            'selectedEmployees' => $this->selectedEmployees,
            'selectedVehicle' => $this->selectedVehicle,
            'vehicleSeats' => $this->vehicleSeats,
            'isConfigured' => $this->isConfigured,
            'routeTiles' => $this->routeTiles,
            'availableLocations' => $this->availableLocations,
            'driverEmployee' => $this->driverEmployee,
            'driverCandidates' => $this->driverCandidates,
            'passengerSlots' => $this->passengerSlots,
            'vehicleCapacity' => $this->vehicleCapacity,
            'totalTripPeople' => $this->totalTripPeople,
            'isMissingDriver' => $this->isMissingDriver,
            'isOverCapacity' => $this->isOverCapacity,
            'isExternalDriver' => $this->isExternalDriver,
            'seatDriverEmployeeId' => $this->seatDriverEmployeeId,
        ]);
    }
}
