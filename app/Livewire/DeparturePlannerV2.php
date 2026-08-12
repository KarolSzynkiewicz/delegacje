<?php

namespace App\Livewire;

use App\Enums\LocationPurposeType;
use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Models\Employee;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\Vehicle;
use App\Services\LocationTrackingService;
use App\Support\DepartureRoutePlan;
use App\Support\PublicTransportTicketCosts;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;

class DeparturePlannerV2 extends Component
{
    use Concerns\InteractsWithLogisticsTransportMode;
    use Concerns\ManagesVehicleSeats;
    use Concerns\ValidatesPublicTransportTicketUploads;
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

    /** @var 'airport'|'station'|null Po wyborze typu pokazujemy listy; null = tylko przełącznik. */
    public ?string $publicTransportHubKind = null;

    // Transfer config (created alongside departure for public transport)
    public $transferConfig = []; // [vehicle_id, driver_employee_id, bonus_amount, bonus_currency, pickup_location_id, route_distance, route_duration, route_waypoints]

    /** Segmenty trasy (transport publiczny): loty + transfer(y) ziemne — kolejność ma znaczenie. */
    public array $routeSegments = [];

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
        'sync-route-segments' => 'handleSyncRouteSegments',
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
            || ! empty($this->transferConfig) || ! empty($this->routeSegments)) {
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
        $this->routeSegments = [];

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

    // ─── Transport mode toggle (public vs own vehicle) — trait + hooki poniżej ──

    protected function onSwitchingToPublicTransportMode(): void
    {
        // Z poprzedniego trybu (własny pojazd): czyść auto, miejsca, dojazdy w kroku 3, trasę
        $this->vehicleId = null;
        $this->vehicleSeats = [];
        $this->vehicleAssignments = [];
        $this->routeData = null;
        $this->transferConfig = [];
        $this->ticketCostsByEmployee = [];
        $this->sharedStartAirportLocationId = null;
        $this->sharedEndAirportLocationId = null;
        $this->publicTransportHubKind = null;
        $this->routeSegments = [];
    }

    protected function onSwitchingToOwnTransportMode(): void
    {
        // Z transportu publicznego: czyść lotniska, bilety, transfer
        $this->sharedStartAirportLocationId = null;
        $this->sharedEndAirportLocationId = null;
        $this->publicTransportHubKind = null;
        $this->ticketCostsByEmployee = [];
        $this->transferConfig = [];
        $this->routeData = null;
        $this->routeSegments = [];
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
        $this->vehicleSeats[0] = $this->buildSeatRow(
            0,
            $driverIsEmployee ? $previousDriverId : null,
            'driver',
            ! $driverIsEmployee
        );

        for ($i = 1; $i < $capacity; $i++) {
            $this->vehicleSeats[$i] = $this->buildSeatRow($i, null, 'passenger', false);
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
        // Jak TransferCreateBoard: włączenie „Zewnętrzny” zdejmuje pracownika z fotela (nie mieszaj zewnętrznego z konkretną osobą).
        if (! $current) {
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

        $this->vehicleSeats[0] = $this->buildSeatRow(0, $employeeId, 'driver', $employeeId === null);
        $this->dispatch('vehicle-seats-updated', vehicleSeats: $this->vehicleSeats);
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
        if (! is_array($data)) {
            return;
        }

        $this->applyVehicleSeatUpdateFromChild($data);

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

        if ($step === 4 && $this->transportMode === 'public' && empty($this->vehicleId)) {
            $this->ensureRouteSegmentsForPublicStep();
        }
    }

    /**
     * Domyślny plan 2 segmentów (lot + transfer) — zgodny ze starym kreatorem.
     */
    public function ensureRouteSegmentsForPublicStep(): void
    {
        if ($this->transportMode !== 'public' || ! empty($this->vehicleId) || ! empty($this->routeSegments)) {
            return;
        }

        $this->routeSegments = DepartureRoutePlan::defaultTwoSegmentPlan(
            $this->publicTransportHubKind,
            $this->sharedStartAirportLocationId,
            $this->sharedEndAirportLocationId,
            is_array($this->ticketCostsByEmployee) ? $this->ticketCostsByEmployee : [],
            is_array($this->transferConfig) ? $this->transferConfig : [],
            data_get($this->routeData, 'route_waypoints', []) ?: [],
            data_get($this->routeData, 'location_stop_notes', []) ?: [],
        );
    }

    public function handleSyncRouteSegments(array $segments): void
    {
        $this->routeSegments = $segments;
        $this->deriveLegacyFieldsFromRouteSegments();
    }

    /**
     * Utrzymuje zgodność z polami używanymi w nagłówku i walidacji (pierwszy segment publiczny, ostatni własny).
     *
     * UWAGA: nie czytamy z segmentów `ticket_costs_by_employee` do nagłówka. Bilety (kwota/waluta/załącznik)
     * są własnością rodzica — plik (TemporaryUploadedFile) w nagłówku żyje tu, w rodzicu, przez `wire:model`.
     * Eventy Livewire (`$this->dispatch(..., route_segments: ...)`) są kodowane do JSON po stronie JS; obiekty
     * `TemporaryUploadedFile` nie przechodzą zdrową formą przez event payload (Synth dehydratuje tylko
     * właściwości komponentu). Przyjęcie biletów z segmentów zwrotnych z Step4 niszczyło nagłówkowe uploady
     * (UI: „bilety się odpinają”, walidacja: „Załącznik musi być poprawnym plikiem”). Zawsze wymuszamy
     * kierunek nagłówek → segment.
     */
    protected function deriveLegacyFieldsFromRouteSegments(): void
    {
        if (empty($this->routeSegments)) {
            return;
        }

        foreach ($this->routeSegments as $seg) {
            if (($seg['mode'] ?? '') === 'public') {
                if (array_key_exists('hub_kind', $seg)) {
                    $this->publicTransportHubKind = $seg['hub_kind'];
                }
                $this->sharedStartAirportLocationId = $seg['start_location_id'] ?? $this->sharedStartAirportLocationId;
                $this->sharedEndAirportLocationId = $seg['end_location_id'] ?? $this->sharedEndAirportLocationId;

                break;
            }
        }

        // Zawsze nadpisz bilety w pierwszym segmencie publicznym autorytatywnym stanem nagłówka rodzica
        // — niezależnie od tego, co przyszło zwrotnie z eventu (tam pliki są już uszkodzone).
        $this->pushTicketsToFirstPublicSegment();

        $ground = DepartureRoutePlan::primaryPostAirportOwnSegment($this->routeSegments);
        if ($ground !== null) {
            $this->transferConfig = is_array($ground['transfer_config'] ?? null) ? $ground['transfer_config'] : [];
            $rd = is_array($this->routeData) ? $this->routeData : [];
            $this->routeData = array_merge($rd, [
                'route_waypoints' => $ground['route_waypoints'] ?? ($rd['route_waypoints'] ?? []),
                'location_stop_notes' => $ground['location_stop_notes'] ?? ($rd['location_stop_notes'] ?? []),
            ]);
        }
    }

    protected function pushTicketsToFirstPublicSegment(): void
    {
        if (empty($this->routeSegments)) {
            return;
        }
        foreach ($this->routeSegments as $i => $seg) {
            if (($seg['mode'] ?? '') === 'public') {
                $this->routeSegments[$i]['ticket_costs_by_employee'] = PublicTransportTicketCosts::ensureCurrencies(
                    is_array($this->ticketCostsByEmployee) ? $this->ticketCostsByEmployee : []
                );

                return;
            }
        }
    }

    protected function pushAirportsToFirstPublicSegment(): void
    {
        if (empty($this->routeSegments)) {
            return;
        }
        foreach ($this->routeSegments as $i => $seg) {
            if (($seg['mode'] ?? '') === 'public') {
                $this->routeSegments[$i]['hub_kind'] = $this->publicTransportHubKind;
                $this->routeSegments[$i]['start_location_id'] = $this->sharedStartAirportLocationId;
                $this->routeSegments[$i]['end_location_id'] = $this->sharedEndAirportLocationId;

                return;
            }
        }
    }

    public function updatedTicketCostsByEmployee(): void
    {
        // Select w UI pokazuje PLN wizualnie przy pustym modelu — dopisz domyślną walutę do stanu Livewire.
        $this->ticketCostsByEmployee = PublicTransportTicketCosts::ensureCurrencies(
            is_array($this->ticketCostsByEmployee) ? $this->ticketCostsByEmployee : []
        );
        $this->pushTicketsToFirstPublicSegment();
    }

    public function updatedSharedStartAirportLocationId(): void
    {
        $this->pushAirportsToFirstPublicSegment();
    }

    public function updatedSharedEndAirportLocationId(): void
    {
        $this->pushAirportsToFirstPublicSegment();
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

        if (! empty($data['route_segments']) && is_array($data['route_segments'])) {
            $this->routeSegments = $data['route_segments'];
        } elseif (! empty($this->routeSegments) && $this->transportMode === 'public' && empty($this->vehicleId)) {
            $idx = DepartureRoutePlan::primaryPostAirportOwnSegmentIndex($this->routeSegments);
            if ($idx !== null) {
                $tc = is_array($this->routeSegments[$idx]['transfer_config'] ?? null)
                    ? $this->routeSegments[$idx]['transfer_config']
                    : [];
                $this->routeSegments[$idx]['transfer_config'] = array_merge($tc, [
                    'route_distance' => $this->routeData['route_distance'] ?? null,
                    'route_duration' => $this->routeData['route_duration'] ?? null,
                    'route_waypoints' => $this->routeData['route_waypoints'] ?? [],
                    'location_stop_notes' => $this->routeData['location_stop_notes'] ?? [],
                ]);
                $this->routeSegments[$idx]['route_waypoints'] = $this->routeData['route_waypoints'] ?? [];
                $this->routeSegments[$idx]['location_stop_notes'] = $this->routeData['location_stop_notes'] ?? [];
            }
        }

        if (! empty($this->routeSegments)) {
            $this->deriveLegacyFieldsFromRouteSegments();
        }
    }

    public function handleTransferConfigUpdated($data)
    {
        $this->transferConfig = $data;
        if (empty($this->routeSegments) || $this->transportMode !== 'public' || ! empty($this->vehicleId)) {
            return;
        }
        $idx = DepartureRoutePlan::primaryPostAirportOwnSegmentIndex($this->routeSegments);
        if ($idx !== null) {
            $this->routeSegments[$idx]['transfer_config'] = $data;
        }
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
            if ($this->publicTransportHubKind === null) {
                $out[] = 'Wybierz typ punktu (lotnisko lub dworzec).';
            } elseif (empty($this->sharedStartAirportLocationId) || empty($this->sharedEndAirportLocationId)) {
                $out[] = 'Nie wybrano punktu startowego i/lub docelowego.';
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

    protected function uploadTicketAttachmentsToRouteSegments(): void
    {
        foreach ($this->routeSegments as $si => $seg) {
            if (($seg['mode'] ?? '') === 'public') {
                $tickets = $seg['ticket_costs_by_employee'] ?? [];
                foreach ($tickets as $eid => $cost) {
                    if (! is_array($cost)) {
                        continue;
                    }
                    $att = $cost['attachment'] ?? null;
                    if ($this->isTicketFileUpload($att)) {
                        $path = $att->store('transport_costs', 'public');
                        $eidKey = is_numeric($eid) ? (int) $eid : $eid;
                        $this->routeSegments[$si]['ticket_costs_by_employee'][$eidKey]['attachment_path'] = $path;
                        unset($this->routeSegments[$si]['ticket_costs_by_employee'][$eidKey]['attachment']);
                    }
                }

                continue;
            }

            // Odcinki ziemne: bilety w public_leg_ticket_costs_by_employee (transport publiczny lub „inny transport”)
            $lk = $seg['leg_kind'] ?? '';
            $gm = $seg['ground_mode'] ?? 'car';
            $groundTickets = ($lk === 'public') || ($lk === 'own' && $gm === 'other');
            if (($seg['mode'] ?? '') === 'own' && $groundTickets) {
                $tickets = $seg['public_leg_ticket_costs_by_employee'] ?? [];
                foreach ($tickets as $eid => $cost) {
                    if (! is_array($cost)) {
                        continue;
                    }
                    $att = $cost['attachment'] ?? null;
                    if ($this->isTicketFileUpload($att)) {
                        $path = $att->store('transport_costs', 'public');
                        $eidKey = is_numeric($eid) ? (int) $eid : $eid;
                        $this->routeSegments[$si]['public_leg_ticket_costs_by_employee'][$eidKey]['attachment_path'] = $path;
                        unset($this->routeSegments[$si]['public_leg_ticket_costs_by_employee'][$eidKey]['attachment']);
                    }
                }
            }
        }
    }

    /**
     * @param  array<int>  $employeeIds
     * @param  list<array<string, mixed>>  $ticketCostsLineItems
     * @param  list<array<string, mixed>>  $transferConfigsList
     */
    protected function validateAndBuildTicketsForRouteSegments(array $employeeIds, array &$ticketCostsLineItems, array &$transferConfigsList): void
    {
        $transferConfigsList = [];
        foreach ($this->routeSegments as $seg) {
            if (($seg['mode'] ?? '') === 'own') {
                $tc = $seg['transfer_config'] ?? [];
                if (is_array($tc) && $tc !== []) {
                    $transferConfigsList[] = $tc;
                }
            }
        }

        foreach ($this->routeSegments as $segIndex => $seg) {
            if (($seg['mode'] ?? '') !== 'public') {
                continue;
            }
            $hk = $seg['hub_kind'] ?? $this->publicTransportHubKind;
            if ($hk === null) {
                $this->addError('publicTransportHubKind', 'Wybierz typ punktu (lotnisko / dworzec) dla odcinka lotu #'.($segIndex + 1).'.');

                continue;
            }
            $purpose = $hk === 'station' ? LocationPurposeType::STATION : LocationPurposeType::AIRPORT;
            $s = $seg['start_location_id'] ?? null;
            $e = $seg['end_location_id'] ?? null;
            if (empty($s) || ! Location::matchesPurpose((int) $s, $purpose)) {
                $this->addError('routeSegments', 'Nieprawidłowy punkt startowy odcinka lotu #'.($segIndex + 1).'.');
            }
            if (empty($e) || ! Location::matchesPurpose((int) $e, $purpose)) {
                $this->addError('routeSegments', 'Nieprawidłowy punkt docelowy odcinka lotu #'.($segIndex + 1).'.');
            }
            if (! empty($s) && ! empty($e) && (int) $s === (int) $e) {
                $this->addError('routeSegments', 'Punkty start i meta odcinka #'.($segIndex + 1).' nie mogą być identyczne.');
            }

            $tickets = $seg['ticket_costs_by_employee'] ?? [];
            foreach ($employeeIds as $employeeId) {
                $employeeCost = $tickets[$employeeId] ?? $tickets[(string) $employeeId] ?? [];
                $amount = $employeeCost['amount'] ?? null;
                $currency = PublicTransportTicketCosts::normalizeCurrency($employeeCost['currency'] ?? null);

                if ($amount === null || $amount === '' || ! is_numeric($amount) || (float) $amount <= 0) {
                    $this->addError("ticketCostsByEmployee.{$employeeId}.amount", 'Podaj poprawny koszt biletu (odcinek #'.($segIndex + 1).').');
                }

                if (strlen($currency) !== 3) {
                    $this->addError("ticketCostsByEmployee.{$employeeId}.currency", 'Waluta musi mieć 3 znaki (odcinek #'.($segIndex + 1).').');
                }

                $this->validateTicketAttachmentUpload(
                    $employeeCost,
                    "ticketCostsByEmployee.{$employeeId}.attachment"
                );
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $this->uploadTicketAttachmentsToRouteSegments();
        $ticketCostsLineItems = DepartureRoutePlan::buildTicketLineItems($this->routeSegments, $employeeIds);
        $publicCount = count(array_filter($this->routeSegments, fn ($s) => ($s['mode'] ?? '') === 'public'));
        $need = count($employeeIds) * max(1, $publicCount);
        if (count($ticketCostsLineItems) < $need) {
            $this->addError('routeSegments', 'Uzupełnij bilety dla każdej osoby i każdego odcinka lotu.');
        }
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
        $ticketCostsLineItems = [];
        $transferConfigsList = [];

        if (empty($this->vehicleId)) {
            $employeeIds = $this->getSelectedEmployeeIds();

            if (! empty($this->routeSegments)) {
                $this->pushTicketsToFirstPublicSegment();
                $this->pushAirportsToFirstPublicSegment();
                $this->validateAndBuildTicketsForRouteSegments($employeeIds, $ticketCostsLineItems, $transferConfigsList);
            } else {
                $startAirportLocationId = $this->sharedStartAirportLocationId;
                $endAirportLocationId = $this->sharedEndAirportLocationId;

                if ($this->publicTransportHubKind === null) {
                    $this->addError('publicTransportHubKind', 'Wybierz typ punktu: lotnisko lub dworzec.');
                }

                $hubPurpose = $this->publicTransportHubKind === 'station'
                    ? LocationPurposeType::STATION
                    : LocationPurposeType::AIRPORT;

                if ($this->publicTransportHubKind !== null && (empty($startAirportLocationId) || ! Location::matchesPurpose((int) $startAirportLocationId, $hubPurpose))) {
                    $this->addError(
                        'sharedStartAirportLocationId',
                        $hubPurpose === LocationPurposeType::STATION
                            ? 'Wybierz dworzec startowy z listy.'
                            : 'Wybierz lotnisko startowe z listy.'
                    );
                }

                if ($this->publicTransportHubKind !== null && (empty($endAirportLocationId) || ! Location::matchesPurpose((int) $endAirportLocationId, $hubPurpose))) {
                    $this->addError(
                        'sharedEndAirportLocationId',
                        $hubPurpose === LocationPurposeType::STATION
                            ? 'Wybierz dworzec docelowy z listy.'
                            : 'Wybierz lotnisko docelowe z listy.'
                    );
                }

                if (! empty($startAirportLocationId) && ! empty($endAirportLocationId) && (int) $startAirportLocationId === (int) $endAirportLocationId) {
                    $this->addError('sharedStartAirportLocationId', 'Punkt startowy i docelowy nie mogą być takie same.');
                    $this->addError('sharedEndAirportLocationId', 'Punkt startowy i docelowy nie mogą być takie same.');
                }

                foreach ($employeeIds as $employeeId) {
                    $employeeCost = $this->ticketCostsByEmployee[$employeeId] ?? [];
                    $amount = $employeeCost['amount'] ?? null;
                    $currency = PublicTransportTicketCosts::normalizeCurrency($employeeCost['currency'] ?? null);

                    if ($amount === null || $amount === '' || ! is_numeric($amount) || (float) $amount <= 0) {
                        $this->addError("ticketCostsByEmployee.{$employeeId}.amount", 'Podaj poprawny koszt biletu dla tego pracownika.');
                    }

                    if (strlen($currency) !== 3) {
                        $this->addError("ticketCostsByEmployee.{$employeeId}.currency", 'Waluta musi mieć dokładnie 3 znaki (np. PLN, EUR).');
                    }

                    $this->validateTicketAttachmentUpload(
                        $employeeCost,
                        "ticketCostsByEmployee.{$employeeId}.attachment"
                    );
                }

                if ($this->getErrorBag()->isNotEmpty()) {
                    return;
                }

                foreach ($employeeIds as $employeeId) {
                    $employeeCost = $this->ticketCostsByEmployee[$employeeId] ?? [];
                    $amount = $employeeCost['amount'] ?? null;
                    $currency = PublicTransportTicketCosts::normalizeCurrency($employeeCost['currency'] ?? null);
                    $attachment = $employeeCost['attachment'] ?? null;

                    $attachmentPath = $employeeCost['attachment_path'] ?? null;
                    if ($this->isTicketFileUpload($attachment)) {
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

            if ($this->getErrorBag()->isNotEmpty()) {
                return;
            }
        }

        $routeDataForSession = is_array($this->routeData) ? $this->routeData : [];
        if (! empty($this->routeSegments)) {
            $routeDataForSession['route_segments'] = $this->routeSegments;
            $routeDataForSession['merged_own_route_waypoints'] = DepartureRoutePlan::mergeOwnSegmentWaypoints($this->routeSegments);
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
                'vehicle_assignments' => $this->vehicleAssignments,
                'route_data' => $routeDataForSession,
                'ticket_costs_per_employee' => $ticketCostsPerEmployee,
                'ticket_costs_line_items' => $ticketCostsLineItems,
                // Zawsze przekazuj transfer_config z kroku 4 (kierowca, uznanie, waypoints) — przy vehicle_id w nagłówku
                // i tak tworzymy jeden LogisticsEvent wyjazdu; wcześniej zerowanie tu blokowało zapis Adjustment (bonus).
                'transfer_config' => is_array($this->transferConfig) ? $this->transferConfig : [],
                'transfer_configs_list' => $transferConfigsList,
            ],
        ]);

        // Przekieruj do kontrolera, który zapisze dane
        return redirect()->route('departures.store-v2');
    }

    /**
     * Get vehicles that are in base on departure date.
     *
     * Uses a single batch query to find in-transit vehicle IDs instead of
     * calling getVehicleLocationStatus() per vehicle (N+1).
     */
    public function getAvailableVehiclesProperty()
    {
        if (! $this->departureDate) {
            return collect();
        }

        $departureDate = Carbon::parse($this->departureDate);

        $vehicles = Vehicle::where('type', 'company_vehicle')
            ->orderBy('registration_number')
            ->get();

        // Jeden batch query zamiast N×getVehicleLocationStatus()
        $inTransitVehicleIds = LogisticsEvent::forLocationTracking()
            ->whereIn('vehicle_id', $vehicles->pluck('id'))
            ->whereIn('type', [LogisticsEventType::DEPARTURE, LogisticsEventType::RETURN, LogisticsEventType::TRANSFER])
            ->where('status', '!=', LogisticsEventStatus::CANCELLED)
            ->where('event_date', '<=', $departureDate)
            ->where(function ($q) use ($departureDate) {
                $q->whereNull('end_date')->orWhere('end_date', '>', $departureDate);
            })
            ->pluck('vehicle_id')
            ->flip()
            ->toArray();

        return $vehicles->filter(
            fn ($v) => ! isset($inTransitVehicleIds[$v->id]) && ! $v->outside_base
        );
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

    /** Dla kroku 4 (np. bilety na odcinki ziemne transportu publicznego). */
    public function getSelectedEmployeeIdsProperty(): array
    {
        return $this->getSelectedEmployeeIds();
    }

    public function getAvailableLocationsProperty()
    {
        return Location::orderBy('name')->get();
    }

    public function getAvailablePublicTransportHubsProperty()
    {
        if ($this->publicTransportHubKind === null) {
            return collect();
        }

        $purpose = $this->publicTransportHubKind === 'station'
            ? LocationPurposeType::STATION
            : LocationPurposeType::AIRPORT;

        return Location::query()
            ->whereHas('purposes', fn ($q) => $q->where('purpose', $purpose))
            ->orderBy('name')
            ->get();
    }

    public function updatedPublicTransportHubKind(): void
    {
        if ($this->publicTransportHubKind !== null) {
            $ids = $this->availablePublicTransportHubs->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (! empty($this->sharedStartAirportLocationId) && ! in_array((int) $this->sharedStartAirportLocationId, $ids, true)) {
                $this->sharedStartAirportLocationId = null;
            }
            if (! empty($this->sharedEndAirportLocationId) && ! in_array((int) $this->sharedEndAirportLocationId, $ids, true)) {
                $this->sharedEndAirportLocationId = null;
            }
        }

        $this->pushAirportsToFirstPublicSegment();
    }

    /** Wraca do wyboru typu punktu (lotnisko/dworzec); czyści wybrane lokalizacje. */
    public function resetPublicTransportHubSelection(): void
    {
        $this->publicTransportHubKind = null;
        $this->sharedStartAirportLocationId = null;
        $this->sharedEndAirportLocationId = null;
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

        $fromSeg = DepartureRoutePlan::primaryPostAirportOwnSegment($this->routeSegments);
        if ($fromSeg === null) {
            return false;
        }

        if (! $routeOk) {
            return true;
        }

        if (($fromSeg['leg_kind'] ?? 'own') === 'public') {
            return false;
        }

        $tc = $this->transferConfig;
        if (! is_array($tc) || empty($tc['vehicle_id']) || empty($tc['driver_employee_id'])) {
            return true;
        }

        $bonus = $tc['bonus_amount'] ?? null;
        if ($bonus === null || $bonus === '' || ! is_numeric($bonus) || (float) $bonus <= 0) {
            return true;
        }

        $cur = strtoupper(trim((string) ($tc['bonus_currency'] ?? 'PLN')));

        return strlen($cur) !== 3;
    }

    /** Tytuł sekcji biletów: lotnisko vs dworzec. */
    public function getPublicTransportTicketsSectionTitleProperty(): string
    {
        return $this->publicTransportHubKind === 'airport'
            ? 'Bilety lotnicze'
            : 'Bilety';
    }

    /** Bilety w nagłówku: kwota, waluta, załącznik dla każdej osoby. */
    public function getHeaderTicketsIncompleteProperty(): bool
    {
        if ($this->transportMode !== 'public') {
            return false;
        }

        if (! empty($this->routeSegments)) {
            foreach ($this->routeSegments as $seg) {
                if (($seg['mode'] ?? '') !== 'public') {
                    continue;
                }
                $tickets = $seg['ticket_costs_by_employee'] ?? [];
                if (PublicTransportTicketCosts::areIncompleteForEmployees(
                    $this->getSelectedEmployeeIds(),
                    $tickets,
                    true
                )) {
                    return true;
                }
            }

            return false;
        }

        return PublicTransportTicketCosts::areIncompleteForEmployees(
            $this->getSelectedEmployeeIds(),
            $this->ticketCostsByEmployee,
            true
        );
    }

    public function render()
    {
        return view('livewire.departure-planner-v2');
    }
}
