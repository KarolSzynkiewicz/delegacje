<?php

namespace App\Livewire;

use App\Data\TransferGroundConfig;
use App\Enums\Currency;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Vehicle;
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

    // -------------------------------------------------------------------------
    // Config modal
    // -------------------------------------------------------------------------

    public function openConfigModal(): void
    {
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
        $this->manualDistanceKm = $this->routeDistance !== null
            ? round($this->routeDistance / 1000, 1)
            : null;
        $this->manualDurationMin = $this->routeDuration !== null
            ? (int) round($this->routeDuration / 60)
            : null;
        $this->showRouteModal = true;
    }

    public function applyManualRoute(): void
    {
        if ($this->manualDistanceKm !== null) {
            $this->routeDistance = (float) $this->manualDistanceKm * 1000;
        }
        if ($this->manualDurationMin !== null) {
            $this->routeDuration = (int) ($this->manualDurationMin * 60);
        }
        $this->routeDistanceIsManual = true;
        $this->emitUpdated();
    }

    public function confirmRouteModal(): void
    {
        $this->applyManualRoute();
        $this->showRouteModal = false;
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
            ? Location::whereIn('id', array_unique($locIds))->get()->keyBy('id')
            : collect();

        $n = count($this->routeWaypoints);
        $tiles = [];

        foreach ($this->routeWaypoints as $index => $key) {
            $p = $this->parseWaypointKey((string) $key);
            if ($p['type'] !== 'loc') {
                continue;
            }
            $loc = $locations->get($p['id']);
            $tiles[] = [
                'index' => $index,
                'key' => $key,
                'id' => $p['id'],
                'name' => $loc?->name ?? '—',
                'city' => $loc?->city ?? null,
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
        return Location::orderBy('name')->get();
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
        $this->emitUpdated();
    }

    private function emitUpdated(): void
    {
        $this->dispatch('ground-transfer-slot-updated', [
            'slotKey' => $this->slotKey,
            'config' => $this->toConfig()->toArray(),
        ]);
    }

    public function render()
    {
        return view('livewire.ground-transfer-slot', [
            'availableVehicles' => $this->availableVehicles,
            'availableEmployees' => $this->availableEmployees,
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
