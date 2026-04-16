<?php

namespace App\Livewire;

use App\Enums\LocationPurposeType;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Vehicle;
use App\Services\LocationTrackingService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;

class DeparturePlannerV2 extends Component
{
    use WithFileUploads;

    // Podstawowe dane formularza
    public $departureDate;

    public $endDate;

    public $vehicleId;

    /** @var 'public'|'own'|null */
    public ?string $transportMode = null;

    public $currentStep = 1;

    // Dane z wyborów użytkownika (przechowywane w głównym komponencie)
    public $assignments = []; // [day_1 => [project_id => [role_id => [employee_id => ...]]]]

    public $assignmentRanges = []; // [employee_id_projectId_roleId => ['start_date' => ..., 'end_date' => ..., 'employee_id' => ..., 'project_id' => ..., 'role_id' => ...]]

    public $vehicleSeats = []; // [seat_index => ['employee_id' => ..., 'position' => 'driver'|'passenger']]

    public $accommodationAssignments = []; // [employee_id => ['accommodation_id' => ..., 'start_date' => ..., 'end_date' => ...]]

    public $vehicleAssignments = []; // [employee_id => ['vehicle_id' => ..., 'position' => ..., 'start_date' => ..., 'end_date' => ...]]

    public $routeData = null; // Route planning data

    public $ticketCostsByEmployee = []; // [employee_id => ['amount' => ..., 'currency' => 'PLN', 'attachment' => file]]

    // Shared airport selection (for public transport - same for all employees)
    public $sharedStartAirportLocationId = null;

    public $sharedEndAirportLocationId = null;

    // Transfer config (created alongside departure for public transport)
    public $transferConfig = []; // [vehicle_id, driver_employee_id, bonus_amount, bonus_currency, pickup_location_id, route_distance, route_duration, route_waypoints]

    /** Modal: zapis mimo brakujących danych */
    public bool $showIncompleteSaveModal = false;

    /** @var array<int, string> */
    public array $incompleteSaveMessages = [];

    /** Modal: zmiana trybu transportu (zamiast natywnego confirm — wyśrodkowany) */
    public bool $showTransportSwitchModal = false;

    /** @var 'public'|'own'|null */
    public ?string $pendingTransportMode = null;

    /** Modal: zmiana zakresu dat (zeruje przypisania — nowe okno czasowe / dostępności) */
    public bool $showDateChangeModal = false;

    public ?string $pendingDepartureDate = null;

    public ?string $pendingEndDate = null;

    /** Ostatnio zatwierdzone daty — porównanie przed ostrzeżeniem */
    public string $committedDepartureDate = '';

    public string $committedEndDate = '';

    // Listenery na eventy z podkomponentów
    protected $listeners = [
        // Step 1 - Project Assignments
        'assignment-added' => 'handleAssignmentAdded',
        'assignment-removed' => 'handleAssignmentRemoved',
        'assignment-range-added' => 'handleAssignmentRangeAdded',
        'assignment-range-removed' => 'handleAssignmentRangeRemoved',
        'vehicle-seat-updated' => 'handleVehicleSeatUpdated',

        // Step 2 - Accommodation Assignments
        'accommodation-assigned' => 'handleAccommodationAssigned',
        'accommodation-removed' => 'handleAccommodationRemoved',

        // Step 3 - Vehicle Assignments
        'vehicle-assigned' => 'handleVehicleAssigned',
        'vehicle-assignment-removed' => 'handleVehicleAssignmentRemoved',

        // Navigation / zapis
        'go-to-step' => 'goToStep',
        'save-departure' => 'requestSaveDeparture',

        // Step 4 - Route Planning
        'route-planned' => 'handleRoutePlanned',
        'transfer-config-updated' => 'handleTransferConfigUpdated',
    ];

    public function mount($departureDate = null, $endDate = null, $vehicleId = null)
    {
        $this->departureDate = ($departureDate !== null && $departureDate !== '') ? $departureDate : '';
        $this->endDate = ($endDate !== null && $endDate !== '') ? $endDate : '';
        $this->vehicleId = $vehicleId;
        $this->committedDepartureDate = (string) $this->departureDate;
        $this->committedEndDate = (string) $this->endDate;
    }

    public function updatedDepartureDate(): void
    {
        $this->handlePossibleDateChange();
    }

    public function updatedEndDate(): void
    {
        $this->handlePossibleDateChange();
    }

    /**
     * Zmiana dat z istniejącymi przypisaniami wymaga potwierdzenia (jak zmiana trybu transportu).
     */
    protected function handlePossibleDateChange(): void
    {
        if ($this->showDateChangeModal) {
            return;
        }

        $newDep = (string) ($this->departureDate ?? '');
        $newEnd = (string) ($this->endDate ?? '');
        $comDep = $this->committedDepartureDate;
        $comEnd = $this->committedEndDate;

        if ($newDep === $comDep && $newEnd === $comEnd) {
            return;
        }

        if (! $this->tripHasDateScopedData()) {
            $this->committedDepartureDate = $newDep;
            $this->committedEndDate = $newEnd;

            return;
        }

        $this->pendingDepartureDate = $newDep;
        $this->pendingEndDate = $newEnd;
        $this->departureDate = $comDep;
        $this->endDate = $comEnd;
        $this->showDateChangeModal = true;
    }

    protected function tripHasDateScopedData(): bool
    {
        if (! empty($this->assignments) || ! empty($this->assignmentRanges) || ! empty($this->accommodationAssignments)
            || ! empty($this->vehicleAssignments) || ! empty($this->routeData) || ! empty($this->ticketCostsByEmployee)
            || ! empty($this->transferConfig)) {
            return true;
        }

        foreach ($this->vehicleSeats as $seat) {
            if (! empty($seat['employee_id'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Po zmianie dat: czyść przypisania i dane zależne od zakresu / tras.
     */
    protected function resetTripDataAfterDateChange(): void
    {
        $this->assignments = [];
        $this->assignmentRanges = [];
        $this->accommodationAssignments = [];
        $this->vehicleAssignments = [];
        $this->routeData = null;
        $this->ticketCostsByEmployee = [];
        $this->transferConfig = [];

        if ($this->transportMode === 'own' && ! empty($this->vehicleId)) {
            $this->initVehicleSeats();
        } else {
            $this->vehicleSeats = [];
        }

        $this->currentStep = 1;
        $this->dispatch('vehicle-seats-updated', vehicleSeats: $this->vehicleSeats);
    }

    public function confirmDateChange(): void
    {
        if ($this->pendingDepartureDate === null || $this->pendingEndDate === null) {
            $this->cancelDateChange();

            return;
        }

        $dep = trim((string) $this->pendingDepartureDate);
        $end = trim((string) $this->pendingEndDate);

        if ($dep === '' || $end === '') {
            $this->addError('departureDate', 'Ustal datę wyjazdu i datę zakończenia.');

            return;
        }

        try {
            $d1 = Carbon::parse($dep)->startOfDay();
            $d2 = Carbon::parse($end)->startOfDay();
            if ($d2->lt($d1)) {
                $this->addError('endDate', 'Data zakończenia nie może być wcześniejsza niż data wyjazdu.');

                return;
            }
        } catch (\Throwable) {
            $this->addError('departureDate', 'Nieprawidłowy format daty.');

            return;
        }

        $this->resetErrorBag();
        $this->departureDate = $dep;
        $this->endDate = $end;
        $this->committedDepartureDate = $dep;
        $this->committedEndDate = $end;
        $this->resetTripDataAfterDateChange();
        $this->showDateChangeModal = false;
        $this->pendingDepartureDate = null;
        $this->pendingEndDate = null;
    }

    public function cancelDateChange(): void
    {
        $this->showDateChangeModal = false;
        $this->pendingDepartureDate = null;
        $this->pendingEndDate = null;
    }

    // ─── Transport mode toggle (public vs own vehicle) ─────────────────────────

    public function requestSetTransportMode(string $mode): void
    {
        $mode = $mode === 'own' ? 'own' : 'public';
        if ($mode === $this->transportMode) {
            return;
        }
        if ($this->transportMode === null) {
            $this->setTransportMode($mode);

            return;
        }
        $this->pendingTransportMode = $mode;
        $this->showTransportSwitchModal = true;
    }

    public function confirmTransportModeSwitch(): void
    {
        if ($this->pendingTransportMode === null) {
            return;
        }
        $mode = $this->pendingTransportMode;
        $this->showTransportSwitchModal = false;
        $this->pendingTransportMode = null;
        $this->setTransportMode($mode);
    }

    public function cancelTransportModeSwitch(): void
    {
        $this->showTransportSwitchModal = false;
        $this->pendingTransportMode = null;
    }

    public function setTransportMode(string $mode): void
    {
        $mode = $mode === 'own' ? 'own' : 'public';
        if ($mode === $this->transportMode) {
            return;
        }

        if ($mode === 'public') {
            // Z poprzedniego trybu (własny pojazd): czyść auto, miejsca, dojazdy w kroku 3, trasę
            $this->vehicleId = null;
            $this->vehicleSeats = [];
            $this->vehicleAssignments = [];
            $this->routeData = null;
            $this->transferConfig = [];
            $this->ticketCostsByEmployee = [];
        } else {
            // Z transportu publicznego: czyść lotniska, bilety, transfer
            $this->sharedStartAirportLocationId = null;
            $this->sharedEndAirportLocationId = null;
            $this->ticketCostsByEmployee = [];
            $this->transferConfig = [];
            $this->routeData = null;
            if (empty($this->vehicleId)) {
                $first = $this->availableVehicles->first();
                if ($first) {
                    $this->vehicleId = $first->id;
                }
            }
            if ($this->vehicleId) {
                $this->initVehicleSeats();
            }
        }

        $this->transportMode = $mode;
    }

    /**
     * Initialize vehicleSeats for the currently selected vehicle.
     * Called both from updatedVehicleId() and from setTransportMode('own')
     * when vehicle was already chosen.
     */
    protected function initVehicleSeats(): void
    {
        // Preserve driver employee before reinitialising (e.g. when vehicle is swapped)
        $previousDriverId = null;
        if (
            ! empty($this->vehicleSeats[0])
            && ! ($this->vehicleSeats[0]['external_driver'] ?? true)
            && ! empty($this->vehicleSeats[0]['employee_id'])
        ) {
            $previousDriverId = (int) $this->vehicleSeats[0]['employee_id'];
        }

        $this->vehicleSeats = [];
        if (! $this->vehicleId) {
            return;
        }
        $vehicle = \App\Models\Vehicle::find($this->vehicleId);
        if (! $vehicle) {
            return;
        }
        $capacity = $vehicle->capacity ?? 0;
        if ($capacity < 1) {
            $capacity = 1;
        }

        $selectedIds = $this->getSelectedEmployeeIds();

        // Restore driver if still part of the trip, otherwise default to external
        $driverIsEmployee = $previousDriverId && in_array($previousDriverId, $selectedIds);
        $this->vehicleSeats[0] = [
            'employee_id' => $driverIsEmployee ? $previousDriverId : null,
            'position' => 'driver',
            'external_driver' => ! $driverIsEmployee,
        ];

        for ($i = 1; $i < $capacity; $i++) {
            $this->vehicleSeats[$i] = [
                'employee_id' => null,
                'position' => 'passenger',
                'external_driver' => false,
            ];
        }

        // Fill passenger seats from existing assignmentRanges (skip driver)
        $seatIndex = 1;
        foreach ($this->assignmentRanges as $range) {
            $empId = (int) $range['employee_id'];
            if ($empId === $previousDriverId && $driverIsEmployee) {
                continue; // already in driver seat
            }
            $alreadyIn = collect($this->vehicleSeats)->contains(fn ($s) => (int) ($s['employee_id'] ?? 0) === $empId);
            if (! $alreadyIn && $seatIndex < $capacity) {
                $this->vehicleSeats[$seatIndex]['employee_id'] = $empId;
                $seatIndex++;
            }
        }
    }

    // ─── Vehicle seat management from header ────────────────────────────────────

    public function updateVehicleSeatFromHeader(int $index, string $key, $value): void
    {
        if (isset($this->vehicleSeats[$index])) {
            $this->vehicleSeats[$index][$key] = $value;
        }
    }

    public function updateVehicleSeatPositionFromHeader(int $index, string $position): void
    {
        if (isset($this->vehicleSeats[$index])) {
            $this->vehicleSeats[$index]['position'] = $position;
        }
    }

    public function removeVehicleSeatFromHeader(int $index): void
    {
        if (isset($this->vehicleSeats[$index])) {
            $this->vehicleSeats[$index]['employee_id'] = null;
            // If clearing driver seat, restore external_driver default
            if ($index === 0) {
                $this->vehicleSeats[0]['external_driver'] = true;
                $this->vehicleSeats[0]['position'] = 'driver';
            }
        }
    }

    /**
     * Toggle external driver for driver seat.
     * When enabled: seat 0 is occupied by external driver (no project assignment).
     * When disabled: seat 0 is free for an employee to be assigned.
     */
    public function toggleExternalDriver(): void
    {
        if (! isset($this->vehicleSeats[0])) {
            return;
        }
        $current = (bool) ($this->vehicleSeats[0]['external_driver'] ?? true);
        $this->vehicleSeats[0]['external_driver'] = ! $current;
        if (! $current === false) {
            // Turning off external driver — clear employee too
            $this->vehicleSeats[0]['employee_id'] = null;
        }
        $this->dispatch('vehicle-seats-updated', vehicleSeats: $this->vehicleSeats);
    }

    /**
     * Assign an employee to the driver seat (seat 0), disabling external driver flag.
     * Passing null clears the driver seat (returns employee to passenger list automatically).
     */
    public function assignDriverSeatEmployee(?int $employeeId): void
    {
        if (! isset($this->vehicleSeats[0])) {
            return;
        }

        // Clear the employee from any existing passenger seat to prevent duplicates / gaps
        if ($employeeId) {
            foreach ($this->vehicleSeats as $i => $seat) {
                if ($i > 0 && (int) ($seat['employee_id'] ?? 0) === $employeeId) {
                    $this->vehicleSeats[$i]['employee_id'] = null;
                }
            }
            // Compact passenger seats: bubble occupied seats to the front, empty to back
            $this->compactPassengerSeats();
        }

        $this->vehicleSeats[0]['employee_id'] = $employeeId;
        $this->vehicleSeats[0]['external_driver'] = $employeeId === null; // null → back to external
        $this->vehicleSeats[0]['position'] = 'driver';
        $this->dispatch('vehicle-seats-updated', vehicleSeats: $this->vehicleSeats);
    }

    /**
     * Compact passenger seats (indices 1..n) so occupied slots come first,
     * empty slots last — no gaps.
     */
    protected function compactPassengerSeats(): void
    {
        $capacity = count($this->vehicleSeats);
        if ($capacity <= 1) {
            return;
        }
        // Collect passenger employee IDs (non-null first)
        $occupied = [];
        for ($i = 1; $i < $capacity; $i++) {
            $eid = $this->vehicleSeats[$i]['employee_id'] ?? null;
            if ($eid) {
                $occupied[] = $eid;
            }
        }
        // Rewrite seats 1..n in order: occupied first, then nulls
        $idx = 0;
        for ($i = 1; $i < $capacity; $i++) {
            $this->vehicleSeats[$i] = [
                'employee_id' => $occupied[$idx] ?? null,
                'position' => 'passenger',
                'external_driver' => false,
            ];
            $idx++;
        }
    }

    public function updatedTransportMode(): void
    {
        // Pełne czyszczenie jest w setTransportMode(); tu tylko spójność przy ewentualnym wire:model
        if ($this->transportMode === 'public') {
            $this->vehicleId = null;
        }
    }

    // Automatyczne odświeżanie po zmianie pojazdu
    public function updatedVehicleId()
    {
        if (! empty($this->vehicleId)) {
            $this->ticketCostsByEmployee = [];
            $this->sharedStartAirportLocationId = null;
            $this->sharedEndAirportLocationId = null;
            $this->transferConfig = [];
            $this->routeData = null;
        } else {
            $this->vehicleSeats = [];
            $this->routeData = null;
        }

        $this->initVehicleSeats();

        // Dispatch event z aktualnymi vehicleSeats do komponentu Step1
        $this->dispatch('vehicle-seats-updated', vehicleSeats: $this->vehicleSeats);
    }

    // Automatyczne odświeżanie po zmianie miejsc w aucie
    public function updatedVehicleSeats()
    {
        // Dispatch event z aktualnymi vehicleSeats do komponentu Step1
        $this->dispatch('vehicle-seats-updated', vehicleSeats: $this->vehicleSeats);
    }

    // ============================================
    // Step 1 - Project Assignment Handlers
    // ============================================

    public function handleAssignmentAdded($data)
    {
        // $data: ['day' => 'day_1', 'project_id' => 1, 'role_id' => 2, 'employee_id' => 3]
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

    public function handleAssignmentRemoved($data = [])
    {
        // Jeśli $data jest puste lub nie jest tablicą, nie rób nic
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

            // Clean up empty arrays
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

    public function handleAssignmentRangeAdded($data)
    {
        // $data: ['employee_id' => 1, 'project_id' => 2, 'role_id' => 3, 'start_date' => '2024-01-01', 'end_date' => '2024-01-10']
        $key = $data['employee_id'].'_'.$data['project_id'].'_'.$data['role_id'];
        $this->assignmentRanges[$key] = [
            'employee_id' => $data['employee_id'],
            'project_id' => $data['project_id'],
            'role_id' => $data['role_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ];

        // Automatycznie przypisz pracownika do pierwszego wolnego miejsca w aucie
        if ($this->vehicleId) {
            $employeeId = (int) $data['employee_id'];

            // Inicjalizuj vehicleSeats jeśli są puste
            if (empty($this->vehicleSeats)) {
                $this->initVehicleSeats();
            }

            // Sprawdź czy pracownik już jest w aucie
            $alreadyInVehicle = collect($this->vehicleSeats)->contains(
                fn ($s) => (int) ($s['employee_id'] ?? 0) === $employeeId
            );

            // Przypisz do pierwszego wolnego fotela pasażera (nie na miejsce kierowcy)
            if (! $alreadyInVehicle) {
                for ($i = 1; $i < count($this->vehicleSeats); $i++) {
                    if (empty($this->vehicleSeats[$i]['employee_id'])) {
                        $this->vehicleSeats[$i]['employee_id'] = $employeeId;
                        break;
                    }
                }
                // If no free seat found: the blade will show overflow warning — don't hard-block
            }
        }

        // Dispatch event to refresh child component
        $this->dispatch('refresh-assignments');
        $this->dispatch('vehicle-seats-updated', vehicleSeats: $this->vehicleSeats);
    }

    public function handleAssignmentRangeRemoved($data = [])
    {
        // Jeśli $data jest puste lub nie jest tablicą, nie rób nic
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

        // Usuń pracownika z vehicleSeats i skompaktuj miejsca pasażerów
        if ($this->vehicleId && ! empty($this->vehicleSeats)) {
            // If removed employee was the driver, reset to external
            if ((int) ($this->vehicleSeats[0]['employee_id'] ?? 0) === (int) $employeeId) {
                $this->vehicleSeats[0]['employee_id'] = null;
                $this->vehicleSeats[0]['external_driver'] = true;
            }
            // Clear from any passenger seat
            for ($i = 1; $i < count($this->vehicleSeats); $i++) {
                if ((int) ($this->vehicleSeats[$i]['employee_id'] ?? 0) === (int) $employeeId) {
                    $this->vehicleSeats[$i]['employee_id'] = null;
                }
            }
            $this->compactPassengerSeats();
            $this->dispatch('vehicle-seats-updated', vehicleSeats: $this->vehicleSeats);
        }

        // Dispatch event to refresh child component
        $this->dispatch('refresh-assignments');
    }

    public function handleVehicleSeatUpdated($data)
    {
        // $data: ['seat_index' => 0, 'employee_id' => 1, 'position' => 'driver']
        $seatIndex = $data['seat_index'];
        $this->vehicleSeats[$seatIndex] = [
            'employee_id' => $data['employee_id'] ?? null,
            'position' => $data['position'] ?? 'passenger',
        ];

        // Dispatch event z aktualnymi vehicleSeats do komponentu Step1
        $this->dispatch('vehicle-seats-updated', vehicleSeats: $this->vehicleSeats);
    }

    // ============================================
    // Step 2 - Accommodation Assignment Handlers
    // ============================================

    public function handleAccommodationAssigned($data)
    {
        // $data: ['employee_id' => 1, 'accommodation_id' => 2, 'start_date' => '2024-01-01', 'end_date' => '2024-01-10']
        $this->accommodationAssignments[$data['employee_id']] = [
            'accommodation_id' => $data['accommodation_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ];
    }

    public function handleAccommodationRemoved($data)
    {
        // $data: ['employee_id' => 1]
        unset($this->accommodationAssignments[$data['employee_id']]);
    }

    // ============================================
    // Step 3 - Vehicle Assignment Handlers
    // ============================================

    public function handleVehicleAssigned($data)
    {
        // $data: ['employee_id' => 1, 'vehicle_id' => 2, 'position' => 'driver', 'start_date' => '2024-01-01', 'end_date' => '2024-01-10']
        $this->vehicleAssignments[$data['employee_id']] = [
            'vehicle_id' => $data['vehicle_id'],
            'position' => $data['position'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ];
    }

    public function handleVehicleAssignmentRemoved($data)
    {
        // $data: ['employee_id' => 1]
        unset($this->vehicleAssignments[$data['employee_id']]);
    }

    // ============================================
    // Navigation
    // ============================================

    public function goToStep($step)
    {
        $step = (int) $step;

        if ($step >= 2 && (empty($this->departureDate) || empty($this->endDate))) {
            $this->dispatch('error', message: 'Wybierz datę wyjazdu i datę zakończenia w sekcji „Szczegóły wyjazdu”.');

            return;
        }

        if ($step >= 2 && $this->transportMode === null) {
            $this->dispatch('error', message: 'Wybierz sposób transportu (Publiczny / Własny).');

            return;
        }

        // Walidacja przed przejściem do następnego kroku
        if ($step === 2) {
            $hasAssignments = ! empty($this->assignments) || ! empty($this->assignmentRanges);
            if (! $hasAssignments) {
                $this->dispatch('error', message: 'Musisz przypisać przynajmniej jednego pracownika do projektu przed przejściem dalej.');

                return;
            }
        }

        $this->currentStep = $step;
    }

    public function handleRoutePlanned($data)
    {
        $prev = is_array($this->routeData) ? $this->routeData : [];
        $this->routeData = [
            'route_distance' => $data['route_distance'] ?? $prev['route_distance'] ?? null,
            'route_duration' => $data['route_duration'] ?? $prev['route_duration'] ?? null,
            'route_waypoints' => array_key_exists('route_waypoints', $data)
                ? $data['route_waypoints']
                : ($prev['route_waypoints'] ?? []),
            'location_stop_notes' => array_key_exists('location_stop_notes', $data)
                ? $data['location_stop_notes']
                : ($prev['location_stop_notes'] ?? []),
            'route_distance_is_manual' => array_key_exists('route_distance_is_manual', $data)
                ? (bool) $data['route_distance_is_manual']
                : (bool) ($prev['route_distance_is_manual'] ?? false),
        ];
    }

    public function handleTransferConfigUpdated($data)
    {
        $this->transferConfig = $data;
    }

    public function requestSaveDeparture(): void
    {
        $this->incompleteSaveMessages = $this->collectIncompleteDepartureMessages();
        if (count($this->incompleteSaveMessages) > 0) {
            $this->showIncompleteSaveModal = true;

            return;
        }
        $this->saveDeparture();
    }

    public function confirmSaveDespiteIncompleteGaps(): void
    {
        $this->showIncompleteSaveModal = false;
        $this->saveDeparture();
    }

    public function cancelIncompleteSaveModal(): void
    {
        $this->showIncompleteSaveModal = false;
    }

    /**
     * @return array<int, string>
     */
    protected function collectIncompleteDepartureMessages(): array
    {
        $out = [];

        if (empty($this->departureDate) || empty($this->endDate)) {
            $out[] = 'Ustal zakres dat przejazdu (data wyjazdu i data zakończenia).';
        }

        if ($this->transportMode === null) {
            $out[] = 'Wybierz sposób transportu (Publiczny / Własny).';
        }

        if ($this->step2TabIncomplete) {
            $out[] = 'Krok 2: część osób nie ma przypisanego mieszkania (domu).';
        }
        if ($this->step3TabIncomplete) {
            $out[] = 'Krok 3: część osób nie ma przypisanego pojazdu dojazdowego.';
        }
        if ($this->step4TabIncomplete) {
            $out[] = 'Krok 4: niekompletna trasa (dystans/czas) lub konfiguracja transferu.';
        }

        if ($this->transportMode === 'public') {
            if (empty($this->sharedStartAirportLocationId) || empty($this->sharedEndAirportLocationId)) {
                $out[] = 'Nie wybrano lotniska startowego i/lub docelowego.';
            }
            if ($this->headerTicketsIncomplete) {
                $out[] = 'Bilety lotnicze: dla części osób brakuje kwoty, waluty lub załącznika.';
            }
        } elseif ($this->transportMode === 'own') {
            if (empty($this->vehicleId)) {
                $out[] = 'Nie wybrano pojazdu wyjazdu (sekcja „Czym” / własny transport).';
            }
        }

        return array_values(array_unique($out));
    }

    public function saveDeparture()
    {
        // Wyczyść błędy z poprzedniej próby zapisu
        $this->resetErrorBag();

        // Walidacja przed zapisem
        if (empty($this->departureDate) || empty($this->endDate)) {
            $this->addError('departureDate', 'Wybierz datę wyjazdu i datę zakończenia w sekcji „Szczegóły wyjazdu”.');
            $this->dispatch('error', message: 'Ustal zakres dat przejazdu przed zapisem wyjazdu.');

            return;
        }

        if ($this->transportMode === null) {
            $this->addError('transportMode', 'Wybierz sposób transportu (Publiczny / Własny).');
            $this->dispatch('error', message: 'Wybierz sposób transportu przed zapisem wyjazdu.');

            return;
        }

        if (empty($this->assignments) && empty($this->assignmentRanges)) {
            $this->dispatch('error', message: 'Musisz przypisać przynajmniej jednego pracownika do projektu.');

            return;
        }

        $ticketCostsPerEmployee = [];

        if (empty($this->vehicleId)) {
            $employeeIds = $this->getSelectedEmployeeIds();

            // Validate shared airports (once for all employees)
            $startAirportLocationId = $this->sharedStartAirportLocationId;
            $endAirportLocationId = $this->sharedEndAirportLocationId;

            if (empty($startAirportLocationId) || ! Location::whereKey($startAirportLocationId)->whereHas('purposes', fn ($q) => $q->where('purpose', LocationPurposeType::AIRPORT))->exists()) {
                $this->addError('sharedStartAirportLocationId', 'Wybierz lotnisko startowe dla całej grupy.');
            }

            if (empty($endAirportLocationId) || ! Location::whereKey($endAirportLocationId)->whereHas('purposes', fn ($q) => $q->where('purpose', LocationPurposeType::AIRPORT))->exists()) {
                $this->addError('sharedEndAirportLocationId', 'Wybierz lotnisko docelowe dla całej grupy.');
            }

            if (! empty($startAirportLocationId) && ! empty($endAirportLocationId) && (int) $startAirportLocationId === (int) $endAirportLocationId) {
                $this->addError('sharedStartAirportLocationId', 'Lotnisko startowe i docelowe nie mogą być takie same.');
                $this->addError('sharedEndAirportLocationId', 'Lotnisko startowe i docelowe nie mogą być takie same.');
            }

            // Krok 1: waliduj pola per-pracownik (bez uploadu pliku)
            foreach ($employeeIds as $employeeId) {
                $employeeCost = $this->ticketCostsByEmployee[$employeeId] ?? [];
                $amount = $employeeCost['amount'] ?? null;
                $currency = strtoupper(trim((string) ($employeeCost['currency'] ?? '')));
                $attachment = $employeeCost['attachment'] ?? null;

                if ($amount === null || $amount === '' || ! is_numeric($amount) || (float) $amount <= 0) {
                    $this->addError("ticketCostsByEmployee.{$employeeId}.amount", 'Podaj poprawny koszt biletu dla tego pracownika.');
                }

                if (strlen($currency) !== 3) {
                    $this->addError("ticketCostsByEmployee.{$employeeId}.currency", 'Waluta musi mieć dokładnie 3 znaki (np. PLN, EUR).');
                }

                if ($attachment) {
                    $attachmentValidator = \Illuminate\Support\Facades\Validator::make(
                        ['attachment' => $attachment],
                        ['attachment' => 'file|max:10240'],
                        [
                            'attachment.file' => 'Załącznik musi być poprawnym plikiem.',
                            'attachment.max' => 'Załącznik może mieć maksymalnie 10 MB.',
                        ]
                    );
                    if ($attachmentValidator->fails()) {
                        foreach ($attachmentValidator->errors()->all() as $message) {
                            $this->addError("ticketCostsByEmployee.{$employeeId}.attachment", $message);
                        }
                    }
                }
            }

            // Jeśli walidacja nie przeszła — zatrzymaj, pokaż błędy w UI
            if ($this->getErrorBag()->isNotEmpty()) {
                return;
            }

            // Krok 2: walidacja OK — uploaduj pliki i buduj tablicę kosztów
            // Shared airports are copied to every employee
            foreach ($employeeIds as $employeeId) {
                $employeeCost = $this->ticketCostsByEmployee[$employeeId] ?? [];
                $amount = $employeeCost['amount'] ?? null;
                $currency = strtoupper(trim((string) ($employeeCost['currency'] ?? '')));
                $attachment = $employeeCost['attachment'] ?? null;

                $attachmentPath = null;
                if ($attachment) {
                    $attachmentPath = $attachment->store('transport_costs', 'public');
                }

                $ticketCostsPerEmployee[$employeeId] = [
                    'amount' => (float) $amount,
                    'currency' => $currency,
                    'attachment_path' => $attachmentPath,
                    'start_airport_location_id' => (int) $startAirportLocationId,
                    'end_airport_location_id' => (int) $endAirportLocationId,
                ];
            }
        }

        // Zapisz dane w sesji (route_data może być duże, więc lepiej przez sesję)
        session([
            'departure_v2_data' => [
                'departure_date' => $this->departureDate,
                'end_date' => $this->endDate,
                'vehicle_id' => $this->vehicleId,
                'assignments' => $this->assignments,
                'assignment_ranges' => $this->assignmentRanges,
                'vehicle_seats' => ! empty($this->vehicleId) ? $this->vehicleSeats : [],
                'accommodation_assignments' => $this->accommodationAssignments,
                // vehicle_assignments (krok 3) są niezależne od sposobu wyjazdu (auto vs transport publiczny)
                'vehicle_assignments' => $this->vehicleAssignments,
                'route_data' => $this->routeData, // route_distance, route_duration, route_waypoints, location_stop_notes
                'ticket_costs_per_employee' => $ticketCostsPerEmployee ?? [],
                'transfer_config' => ! empty($this->vehicleId) ? [] : $this->transferConfig,
            ],
        ]);

        // Przekieruj do kontrolera, który zapisze dane
        return redirect()->route('departures.store-v2');
    }

    /**
     * Get vehicles that are in base on departure date
     */
    public function getAvailableVehiclesProperty()
    {
        if (! $this->departureDate) {
            return collect();
        }

        $departureDate = Carbon::parse($this->departureDate);
        $locationTrackingService = app(LocationTrackingService::class);

        // Pobierz wszystkie pojazdy firmowe
        $vehicles = Vehicle::where('type', 'company_vehicle')
            ->orderBy('registration_number')
            ->get();

        // Filtruj tylko te, które są w bazie na dzień wyjazdu
        $availableVehicles = $vehicles->filter(function ($vehicle) use ($departureDate, $locationTrackingService) {
            $status = $locationTrackingService->getVehicleLocationStatus($vehicle, $departureDate);

            // Pojazd jest dostępny jeśli nie jest w podróży i nie jest poza bazą
            return ! $status['in_transit'] && ! $status['outside_base'];
        });

        return $availableVehicles;
    }

    public function getSelectedEmployeesProperty()
    {
        $employeeIds = $this->getSelectedEmployeeIds();

        if (empty($employeeIds)) {
            return collect();
        }

        return Employee::whereIn('id', $employeeIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public function getAvailableLocationsProperty()
    {
        return Location::orderBy('name')->get();
    }

    public function getAvailableAirportsProperty()
    {
        return Location::query()
            ->whereHas('purposes', fn ($q) => $q->where('purpose', LocationPurposeType::AIRPORT))
            ->orderBy('name')
            ->get();
    }

    protected function getSelectedEmployeeIds(): array
    {
        $employeeIds = collect();

        foreach ($this->assignments as $projects) {
            if (! is_array($projects)) {
                continue;
            }

            foreach ($projects as $roles) {
                if (! is_array($roles)) {
                    continue;
                }

                foreach ($roles as $ids) {
                    if (! is_array($ids)) {
                        continue;
                    }
                    $employeeIds = $employeeIds->merge($ids);
                }
            }
        }

        foreach ($this->assignmentRanges as $range) {
            if (! empty($range['employee_id'])) {
                $employeeIds->push($range['employee_id']);
            }
        }

        return $employeeIds
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /** Krok 2: czerwona zakładka — brak mieszkania dla któregoś z wybranych pracowników. */
    public function getStep2TabIncompleteProperty(): bool
    {
        foreach ($this->getSelectedEmployeeIds() as $empId) {
            $row = $this->accommodationAssignments[$empId] ?? null;
            if (! is_array($row) || empty($row['accommodation_id'])) {
                return true;
            }
        }

        return false;
    }

    /** Krok 3: czerwona zakładka — brak przypisania pojazdu (dojazd) dla któregoś z pracowników. */
    public function getStep3TabIncompleteProperty(): bool
    {
        foreach ($this->getSelectedEmployeeIds() as $empId) {
            $row = $this->vehicleAssignments[$empId] ?? null;
            if (! is_array($row) || empty($row['vehicle_id'])) {
                return true;
            }
        }

        return false;
    }

    /** Krok 4: brak trasy (dystans/czas) lub przy locie — niewypełniona konfiguracja transferu. */
    public function getStep4TabIncompleteProperty(): bool
    {
        $rd = $this->routeData;
        $dist = data_get($rd, 'route_distance', data_get($rd, 'distance'));
        $dur = data_get($rd, 'route_duration', data_get($rd, 'duration'));
        $routeOk = is_array($rd)
            && $dist !== null && $dist !== '' && is_numeric($dist) && (float) $dist > 0
            && $dur !== null && $dur !== '' && (int) $dur > 0;

        if ($this->transportMode === 'own') {
            return ! $routeOk;
        }

        if (! $routeOk) {
            return true;
        }

        $tc = $this->transferConfig;
        if (! is_array($tc) || empty($tc['vehicle_id']) || empty($tc['driver_employee_id'])) {
            return true;
        }

        if (empty($tc['pickup_location_id'])) {
            return true;
        }

        $bonus = $tc['bonus_amount'] ?? null;
        if ($bonus === null || $bonus === '' || ! is_numeric($bonus) || (float) $bonus <= 0) {
            return true;
        }

        $cur = strtoupper(trim((string) ($tc['bonus_currency'] ?? 'PLN')));

        return strlen($cur) !== 3;
    }

    /** Bilety w nagłówku: kwota, waluta, załącznik dla każdej osoby. */
    public function getHeaderTicketsIncompleteProperty(): bool
    {
        if ($this->transportMode !== 'public') {
            return false;
        }

        foreach ($this->getSelectedEmployeeIds() as $empId) {
            $t = $this->ticketCostsByEmployee[$empId] ?? [];
            $amount = $t['amount'] ?? null;
            if ($amount === null || $amount === '' || ! is_numeric($amount) || (float) $amount <= 0) {
                return true;
            }
            $cur = strtoupper(trim((string) ($t['currency'] ?? 'PLN')));
            if (strlen($cur) !== 3) {
                return true;
            }
            if (empty($t['attachment'])) {
                return true;
            }
        }

        return false;
    }

    public function render()
    {
        return view('livewire.departure-planner-v2');
    }
}
