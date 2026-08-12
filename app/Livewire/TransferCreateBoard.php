<?php

namespace App\Livewire;

use App\Data\TransferGroundConfig;
use App\Enums\LocationPurposeType;
use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Enums\ProjectStatus;
use App\Enums\VehiclePosition;
use App\Livewire\Concerns\InteractsWithLogisticsTransportMode;
use App\Livewire\Concerns\ValidatesPublicTransportTicketUploads;
use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Role;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Services\AssignmentQueryService;
use App\Services\DateRangeService;
use App\Services\DefaultRouteWaypointsService;
use App\Services\DeparturePlannerService;
use App\Services\LocationTrackingService;
use App\Services\TransferService;
use App\Support\PublicTransportTicketCosts;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class TransferCreateBoard extends Component
{
    use InteractsWithLogisticsTransportMode;
    use ValidatesPublicTransportTicketUploads;
    use WithFileUploads;

    public string $transferDate = '';

    public string $departureDate = '';

    public string $endDate = '';

    /** @var 'public'|'own'|null */
    public ?string $transportMode = null;

    public ?int $vehicleId = null;

    public $sharedStartAirportLocationId = null;

    public $sharedEndAirportLocationId = null;

    /** @var 'airport'|'station'|null */
    public ?string $publicTransportHubKind = null;

    public bool $showTransportSwitchModal = false;

    /** @var 'public'|'own'|null */
    public ?string $pendingTransportMode = null;

    /** assignment | transport */
    public string $mode = 'assignment';

    /** FK do wyjazdu (DEPARTURE) — zapisywane na transferze jako related_departure_id */
    public ?int $relatedDepartureId = null;

    /** Remount EmployeePicker po powiązaniu z wyjazdem (żeby przejąć listę osób). */
    public int $employeePickerKey = 0;

    public ?string $successBanner = null;

    // --- Transport bez reassignment ---
    public array $selectedEmployeeIds = [];

    public array $groundTransferConfig = [];

    /** [seat_index => ['employee_id' => ?int, 'position' => 'driver'|'passenger', 'external_driver' => bool]] */
    public array $vehicleSeats = [];

    /** [employee_id => ['amount' => ?float, 'currency' => string, 'attachment' => TemporaryUploadedFile|null, 'attachment_path' => ?string]] */
    public array $ticketCostsByEmployee = [];

    /**
     * Płaska tablica uploadów: [employee_id => TemporaryUploadedFile].
     * Livewire niezawodnie wiąże pliki przy płytkim kluczu; po upload'ie przepisujemy
     * plik do $ticketCostsByEmployee[$empId]['attachment'] w hooku updatedTicketAttachmentUploads().
     */
    public array $ticketAttachmentUploads = [];

    // --- Kanban draft ---
    public array $draftProjectByAssignment = [];

    public array $draftAssignmentDetails = [];

    // --- Modal: braki ról ---
    public bool $showGapsModal = false;

    public ?int $pendingAssignmentId = null;

    public ?int $pendingTargetProjectId = null;

    public ?array $gapsModalProject = null;

    public array $gapsModalRoles = [];

    // --- Modal: kalendarz ---
    public bool $showCalendarModal = false;

    public ?int $pendingEmployeeId = null;

    public ?int $selectedRoleId = null;

    public array $employeeAvailability = [];

    public ?string $selectedStartDate = null;

    public ?string $selectedEndDate = null;

    public ?string $calendarMonthStart = null;

    public string $wizardPhase = 'board';

    public bool $assignNewAccommodation = false;

    public bool $assignNewVehicle = false;

    public array $assignmentRanges = [];

    public array $accommodationAssignments = [];

    public array $vehicleAssignments = [];

    protected DeparturePlannerService $departurePlannerService;

    protected TransferService $transferService;

    protected AssignmentQueryService $assignmentQueryService;

    protected DefaultRouteWaypointsService $defaultRouteWaypointsService;

    protected $listeners = [
        'accommodation-assigned' => 'handleAccommodationAssigned',
        'accommodation-removed' => 'handleAccommodationRemoved',
        'vehicle-assigned' => 'handleVehicleAssigned',
        'vehicle-assignment-removed' => 'handleVehicleAssignmentRemoved',
        'transfer-wizard-accommodation-done' => 'onTransferAccommodationStepDone',
        'transfer-wizard-vehicle-done' => 'onTransferVehicleStepDone',
        'transfer-wizard-back' => 'onTransferWizardBack',
    ];

    public function boot(
        DeparturePlannerService $departurePlannerService,
        TransferService $transferService,
        AssignmentQueryService $assignmentQueryService,
        DefaultRouteWaypointsService $defaultRouteWaypointsService
    ): void {
        $this->departurePlannerService = $departurePlannerService;
        $this->transferService = $transferService;
        $this->assignmentQueryService = $assignmentQueryService;
        $this->defaultRouteWaypointsService = $defaultRouteWaypointsService;
    }

    // -------------------------------------------------------------------------
    // Event listeners
    // -------------------------------------------------------------------------

    #[On('transfer-employees-updated')]
    public function onEmployeesUpdated(array $employeeIds): void
    {
        // Spłaszcz na wypadek gdyby dispatch przesłał ['employeeIds' => [...]]
        $flat = isset($employeeIds[0]) || empty($employeeIds)
            ? $employeeIds
            : ($employeeIds['employeeIds'] ?? array_values($employeeIds));
        $this->selectedEmployeeIds = array_values(array_map('intval', array_filter($flat, 'is_numeric')));

        // Przelicz siatkę miejsc (pasażerowie) dla wybranego pojazdu
        if ($this->transportMode === 'own' && ! empty($this->vehicleId)) {
            $this->initVehicleSeats();
        }

        // Wyczyść bilety dla osób, które nie są już uczestnikami
        if ($this->transportMode === 'public' && ! empty($this->ticketCostsByEmployee)) {
            $this->ticketCostsByEmployee = array_intersect_key(
                $this->ticketCostsByEmployee,
                array_flip($this->selectedEmployeeIds)
            );
            $this->ticketAttachmentUploads = array_intersect_key(
                $this->ticketAttachmentUploads,
                array_flip($this->selectedEmployeeIds)
            );
        }
    }

    #[On('ground-transfer-slot-updated')]
    public function onGroundTransferSlotUpdated(string $slotKey, array $config): void
    {
        $this->groundTransferConfig = $config;
    }

    public function updatedRelatedDepartureId(mixed $value): void
    {
        $id = $value === null || $value === '' ? null : (int) $value;
        $this->relatedDepartureId = $id > 0 ? $id : null;

        if ($this->relatedDepartureId === null) {
            return;
        }

        $departure = LogisticsEvent::query()
            ->whereKey($this->relatedDepartureId)
            ->where('type', LogisticsEventType::DEPARTURE)
            ->whereIn('status', [LogisticsEventStatus::PLANNED, LogisticsEventStatus::COMPLETED])
            ->with(['participants', 'fromLocation', 'toLocation'])
            ->first();

        if (! $departure) {
            $this->relatedDepartureId = null;
            session()->flash('warning', 'Nie znaleziono wyjazdu do powiązania.');

            return;
        }

        $employeeIds = $departure->participants
            ->pluck('employee_id')
            ->map(fn ($eid) => (int) $eid)
            ->filter(fn (int $eid) => $eid > 0)
            ->unique()
            ->values()
            ->all();

        $this->mode = 'transport';
        $this->selectedEmployeeIds = $employeeIds;
        $this->employeePickerKey++;

        if ($this->departureDate === '' && $departure->event_date) {
            $this->departureDate = $departure->event_date->format('Y-m-d');
        }
        if ($this->endDate === '' && ($departure->end_date || $departure->event_date)) {
            $this->endDate = ($departure->end_date ?? $departure->event_date)->format('Y-m-d');
        }

        if ($this->transportMode === 'own' && ! empty($this->vehicleId)) {
            $this->initVehicleSeats();
        }

        if ($this->transportMode === 'public') {
            $this->ticketCostsByEmployee = array_intersect_key(
                $this->ticketCostsByEmployee,
                array_flip($this->selectedEmployeeIds)
            );
            $this->ticketAttachmentUploads = array_intersect_key(
                $this->ticketAttachmentUploads,
                array_flip($this->selectedEmployeeIds)
            );
        }
    }

    /**
     * Wyjazdy do dropdownu „Powiąż z innym transportem”.
     *
     * @return \Illuminate\Support\Collection<int, array{id: int, label: string}>
     */
    public function getLinkableDeparturesProperty()
    {
        return LogisticsEvent::query()
            ->where('type', LogisticsEventType::DEPARTURE)
            ->whereIn('status', [LogisticsEventStatus::PLANNED, LogisticsEventStatus::COMPLETED])
            ->with(['participants', 'fromLocation', 'toLocation'])
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->limit(80)
            ->get()
            ->map(function (LogisticsEvent $event) {
                $people = $event->participants->count();
                $transport = $event->vehicle_id ? 'własny' : 'publiczny';
                $from = $event->fromLocation?->name ?? '?';
                $to = $event->toLocation?->name ?? '?';
                $date = $event->event_date?->format('Y-m-d') ?? '—';

                return [
                    'id' => (int) $event->id,
                    'label' => '#'.$event->id.' · '.$date.' · '.$transport.' · '.$people.' os. · '.$from.' → '.$to,
                ];
            })
            ->values();
    }

    #[On('ground-transfer-slot-request-default-waypoints')]
    public function onGroundTransferSlotRequestDefaultWaypoints(string $slotKey): void
    {
        // Only seed for own transport: public mode is from/to hubs.
        if ($this->transportMode !== 'own') {
            return;
        }

        $day = $this->departureDate !== '' ? Carbon::parse($this->departureDate)->startOfDay() : now()->startOfDay();

        $waypoints = [];
        $locationStopNotes = [];
        if ($this->mode === 'assignment' && ! empty($this->draftProjectByAssignment)) {
            $targetAccByEmp = null;
            if ($this->assignNewAccommodation && $this->accommodationAssignments !== []) {
                $accByEmp = $this->accommodationAssignmentsByEmployeeId();
                $targetAccByEmp = [];
                foreach ($this->draftEmployeeIds as $eid) {
                    $aid = (int) ($accByEmp[$eid]['accommodation_id'] ?? 0);
                    if ($aid > 0) {
                        $targetAccByEmp[$eid] = $aid;
                    }
                }
                if ($targetAccByEmp === []) {
                    $targetAccByEmp = null;
                }
            }

            $data = $this->defaultRouteWaypointsService->buildReassignmentTransferWaypoints(
                $this->draftProjectByAssignment,
                $day,
                $targetAccByEmp
            );
            $waypoints = $data['waypoints'] ?? [];
            $locationStopNotes = $data['location_stop_notes'] ?? [];
        } else {
            $data = $this->defaultRouteWaypointsService->buildSimpleTransferWaypoints(
                $this->effectiveEmployeeIds,
                $day
            );
            $waypoints = $data['waypoints'] ?? [];
            $locationStopNotes = $data['location_stop_notes'] ?? [];
        }

        if ($waypoints === []) {
            return;
        }

        $this->dispatch(
            'ground-transfer-slot-apply-default-waypoints',
            slotKey: $slotKey,
            waypoints: $waypoints,
            locationStopNotes: $locationStopNotes,
            force: $this->shouldForceRefreshDefaultRouteWaypointsFromSketch(),
        );
    }

    /**
     * Przy każdym „Konfiguruj trasę” na tablicy przeniesień nadpisujemy prefill z aktualnego szkicu (bez gubienia punktów po edycji).
     */
    protected function shouldForceRefreshDefaultRouteWaypointsFromSketch(): bool
    {
        return $this->mode === 'assignment'
            && $this->draftProjectByAssignment !== []
            && $this->transportMode === 'own';
    }

    #[On('error')]
    public function onPlannerError(mixed $message = null): void
    {
        if (is_array($message)) {
            $message = $message['message'] ?? null;
        }
        if (is_string($message) && $message !== '') {
            session()->flash('warning', $message);
        }
    }

    // -------------------------------------------------------------------------
    // Lifecycle
    // -------------------------------------------------------------------------

    public function mount(): void
    {
        $now = now();
        $this->departureDate = $now->format('Y-m-d');
        $this->endDate = $now->format('Y-m-d');
        $this->transferDate = $this->departureDate;
    }

    public function updatedDepartureDate(): void
    {
        $this->transferDate = $this->departureDate;
        $this->draftProjectByAssignment = [];
        $this->draftAssignmentDetails = [];
        $this->successBanner = null;
        $this->resetTransferWizardState();
        $this->closeAllModals();
    }

    // -------------------------------------------------------------------------
    // Transport mode hooks (from InteractsWithLogisticsTransportMode)
    // -------------------------------------------------------------------------

    protected function onSwitchingToPublicTransportMode(): void
    {
        $this->vehicleId = null;
        $this->vehicleSeats = [];
        $this->groundTransferConfig = [];
    }

    protected function onSwitchingToOwnTransportMode(): void
    {
        $this->publicTransportHubKind = null;
        $this->sharedStartAirportLocationId = null;
        $this->sharedEndAirportLocationId = null;
        $this->ticketCostsByEmployee = [];
        $this->ticketAttachmentUploads = [];
        if (empty($this->vehicleId)) {
            $first = $this->availableVehicles->first();
            if ($first) {
                $this->vehicleId = $first->id;
            }
        }
        if (! empty($this->vehicleId)) {
            $this->initVehicleSeats();
        }
    }

    public function updatedVehicleId(): void
    {
        if (! empty($this->vehicleId)) {
            $this->initVehicleSeats();
        } else {
            $this->vehicleSeats = [];
        }
    }

    public function resetPublicTransportHubSelection(): void
    {
        $this->publicTransportHubKind = null;
        $this->sharedStartAirportLocationId = null;
        $this->sharedEndAirportLocationId = null;
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
    }

    public function updatedMode(string $value): void
    {
        if ($this->transportMode !== 'own') {
            return;
        }

        $ids = $this->availableVehicles->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($this->vehicleId !== null && ($ids === [] || ! in_array((int) $this->vehicleId, $ids, true))) {
            $this->vehicleId = null;
            $this->vehicleSeats = [];
        }

        if (empty($this->vehicleId) && $this->availableVehicles->isNotEmpty()) {
            $this->vehicleId = $this->availableVehicles->first()->id;
            $this->initVehicleSeats();
        }
    }

    // -------------------------------------------------------------------------
    // Computed properties
    // -------------------------------------------------------------------------

    public function getAvailableVehiclesProperty()
    {
        if ($this->departureDate === '') {
            return collect();
        }

        $vehicles = Vehicle::where('type', 'company_vehicle')
            ->orderBy('registration_number')
            ->get();

        if ($this->mode === 'transport') {
            return $vehicles;
        }

        $departureDate = Carbon::parse($this->departureDate);
        $locationTrackingService = app(LocationTrackingService::class);

        return $vehicles->filter(function (Vehicle $vehicle) use ($departureDate, $locationTrackingService) {
            $status = $locationTrackingService->getVehicleLocationStatus($vehicle, $departureDate);

            return ! $status['in_transit'] && $status['outside_base'];
        });
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

    /**
     * Podsumowanie trasy z konfiguracji slotu (km, liczba przystanków loc:, start/koniec jak przy zapisie transferu).
     */
    public function getTransferBoardRouteSummaryProperty(): ?array
    {
        return TransferGroundConfig::fromArray($this->groundTransferConfig)->toRouteSummary();
    }

    /**
     * Informacje o zdarzeniu logistycznym aktualnie przypisanym do wybranego pojazdu na dzień transferu.
     * Zwraca null gdy brak pojazdu lub brak aktywnego zdarzenia.
     *
     * @return array{event_id: int, type_label: string, from: ?string, to: ?string, status_label: string}|null
     */
    public function getSelectedVehicleActiveEventInfoProperty(): ?array
    {
        if (! $this->vehicleId || $this->departureDate === '') {
            return null;
        }
        $vehicle = Vehicle::find((int) $this->vehicleId);
        if (! $vehicle) {
            return null;
        }
        $day = Carbon::parse($this->departureDate)->startOfDay();
        $event = LogisticsEvent::where('vehicle_id', $vehicle->id)
            ->whereIn('type', [
                LogisticsEventType::DEPARTURE,
                LogisticsEventType::RETURN,
                LogisticsEventType::TRANSFER,
            ])
            ->where('status', '!=', LogisticsEventStatus::CANCELLED)
            ->where('event_date', '<=', $day->copy()->endOfDay())
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $day))
            ->with(['fromLocation', 'toLocation'])
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->first();
        if (! $event) {
            return null;
        }
        $typeLabel = match ($event->type) {
            LogisticsEventType::DEPARTURE => 'Wyjazd',
            LogisticsEventType::RETURN => 'Zjazd',
            LogisticsEventType::TRANSFER => 'Transfer',
            default => 'Zdarzenie',
        };
        $statusLabel = match ($event->status) {
            LogisticsEventStatus::PLANNED => 'Planowany',
            LogisticsEventStatus::IN_PROGRESS => 'W trakcie',
            LogisticsEventStatus::COMPLETED => 'Zakończony',
            default => (string) $event->status->value,
        };

        return [
            'event_id' => $event->id,
            'type_label' => $typeLabel,
            'from' => $event->fromLocation?->name,
            'to' => $event->toLocation?->name,
            'status_label' => $statusLabel,
        ];
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

    /**
     * Wszyscy pracownicy ze szkicu (nawet bez wypełnionych szczegółów).
     * Używane do wstępnego wyświetlenia siatki miejsc / biletów w trybie „Przypisania".
     *
     * @return array<int>
     */
    public function getAllDraftEmployeeIdsProperty(): array
    {
        if (empty($this->draftProjectByAssignment)) {
            return [];
        }

        $assignmentIds = array_map('intval', array_keys($this->draftProjectByAssignment));

        return ProjectAssignment::whereIn('id', $assignmentIds)
            ->pluck('employee_id')
            ->map('intval')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Efektywna lista pracowników do wyświetlenia w siatce miejsc / biletach.
     * - tryb 'assignment' + szkic: pracownicy ze szkicu
     * - tryb 'transport': pracownicy z checkboxów
     */
    public function getEffectiveEmployeesProperty()
    {
        if ($this->mode === 'assignment' && ! empty($this->draftProjectByAssignment)) {
            $ids = $this->allDraftEmployeeIds;
            if (empty($ids)) {
                return collect();
            }

            return Employee::whereIn('id', $ids)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }

        return $this->selectedEmployees;
    }

    /**
     * Efektywne ID pracowników do siatki/biletów (płaska tablica int).
     *
     * @return array<int>
     */
    public function getEffectiveEmployeeIdsProperty(): array
    {
        if ($this->mode === 'assignment' && ! empty($this->draftProjectByAssignment)) {
            return $this->allDraftEmployeeIds;
        }

        return array_values(array_map('intval', $this->selectedEmployeeIds));
    }

    public function getPublicTransportTicketsSectionTitleProperty(): string
    {
        return $this->publicTransportHubKind === 'airport'
            ? 'Bilety lotnicze'
            : 'Bilety';
    }

    // -------------------------------------------------------------------------
    // Vehicle seats (siatka miejsc) — aligned with DeparturePlannerV2
    // -------------------------------------------------------------------------

    /**
     * Zainicjuj siatkę miejsc pojazdu dla aktualnie wybranego $vehicleId / $selectedEmployeeIds.
     * Zachowuje kierowcę pracownika, jeśli nadal jest uczestnikiem transferu.
     */
    protected function initVehicleSeats(): void
    {
        $previousDriverId = null;
        if (
            ! empty($this->vehicleSeats[0])
            && ! ($this->vehicleSeats[0]['external_driver'] ?? true)
            && ! empty($this->vehicleSeats[0]['employee_id'])
        ) {
            $previousDriverId = (int) $this->vehicleSeats[0]['employee_id'];
        }

        $this->vehicleSeats = [];
        if (empty($this->vehicleId)) {
            return;
        }

        $vehicle = Vehicle::find($this->vehicleId);
        if (! $vehicle) {
            return;
        }

        $capacity = (int) ($vehicle->capacity ?? 0);
        if ($capacity < 1) {
            $capacity = 1;
        }

        // Użyj efektywnych pracowników (szkic lub checkboxy) jako pasażerów
        $selectedIds = $this->effectiveEmployeeIds;
        $driverIsEmployee = $previousDriverId && in_array($previousDriverId, $selectedIds, true);

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

        $seatIndex = 1;
        foreach ($selectedIds as $empId) {
            if ($driverIsEmployee && $empId === $previousDriverId) {
                continue;
            }
            if ($seatIndex >= $capacity) {
                break;
            }
            $this->vehicleSeats[$seatIndex]['employee_id'] = $empId;
            $seatIndex++;
        }
    }

    public function assignDriverSeatEmployee(?int $employeeId): void
    {
        if (! isset($this->vehicleSeats[0])) {
            return;
        }

        if ($employeeId) {
            foreach ($this->vehicleSeats as $i => $seat) {
                if ($i > 0 && (int) ($seat['employee_id'] ?? 0) === $employeeId) {
                    $this->vehicleSeats[$i]['employee_id'] = null;
                }
            }
            $this->compactPassengerSeats();
        }

        $this->vehicleSeats[0]['employee_id'] = $employeeId;
        $this->vehicleSeats[0]['external_driver'] = $employeeId === null;
        $this->vehicleSeats[0]['position'] = 'driver';
    }

    public function toggleExternalDriver(): void
    {
        if (! isset($this->vehicleSeats[0])) {
            return;
        }
        $current = (bool) ($this->vehicleSeats[0]['external_driver'] ?? true);
        $this->vehicleSeats[0]['external_driver'] = ! $current;
        // Kiedy włączamy „Zewnętrzny” → czyścimy pracownika z fotela kierowcy.
        if (! $current) {
            $this->vehicleSeats[0]['employee_id'] = null;
        }
    }

    protected function compactPassengerSeats(): void
    {
        $capacity = count($this->vehicleSeats);
        if ($capacity <= 1) {
            return;
        }
        $occupied = [];
        for ($i = 1; $i < $capacity; $i++) {
            $eid = $this->vehicleSeats[$i]['employee_id'] ?? null;
            if ($eid) {
                $occupied[] = $eid;
            }
        }
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

    // -------------------------------------------------------------------------
    // Public transport tickets — hook na płaski upload → merge do struktury
    // -------------------------------------------------------------------------

    /**
     * Livewire niezawodnie wiąże płytki klucz (ticketAttachmentUploads.123).
     * Po przypięciu pliku przepisujemy go do odpowiedniego wiersza w ticketCostsByEmployee.
     */
    public function updatedTicketAttachmentUploads($value, ?string $key = null): void
    {
        if ($key === null) {
            return;
        }
        $empId = (int) $key;
        if ($empId <= 0) {
            return;
        }
        $row = $this->ticketCostsByEmployee[$empId] ?? ['amount' => null, 'currency' => 'PLN'];
        $row['attachment'] = $value;
        $this->ticketCostsByEmployee[$empId] = $row;
    }

    // -------------------------------------------------------------------------
    // Wizard — reassignment flow
    // -------------------------------------------------------------------------

    protected function resetTransferWizardState(): void
    {
        $this->wizardPhase = 'board';
        $this->assignmentRanges = [];
        $this->accommodationAssignments = [];
        $this->vehicleAssignments = [];
        $this->assignNewAccommodation = false;
        $this->assignNewVehicle = false;
    }

    public function proceedFromBoard(): void
    {
        if ($this->draftProjectByAssignment === []) {
            session()->flash('warning', 'Najpierw przygotuj szkic przeniesienia (przeciągnij osoby między projektami i dokończ rolę oraz daty).');

            return;
        }

        foreach (array_keys($this->draftProjectByAssignment) as $assignmentId) {
            if (empty($this->draftAssignmentDetails[$assignmentId])) {
                session()->flash('warning', 'Dokończ szkic dla wszystkich przeniesionych osób (rola i zakres dat w kalendarzu).');

                return;
            }
        }

        $this->rebuildTransferAssignmentRanges();
        $this->wizardPhase = 'followup';
    }

    public function proceedFromFollowup(): void
    {
        $this->rebuildTransferAssignmentRanges();

        if (! $this->assignNewAccommodation) {
            $this->accommodationAssignments = [];
        }
        if (! $this->assignNewVehicle) {
            $this->vehicleAssignments = [];
        }

        if ($this->assignNewAccommodation) {
            $this->wizardPhase = 'accommodation';

            return;
        }

        if ($this->assignNewVehicle) {
            $this->wizardPhase = 'vehicle';

            return;
        }

        $this->wizardPhase = 'done';
    }

    public function backToBoardFromFollowup(): void
    {
        $this->wizardPhase = 'board';
        $this->assignmentRanges = [];
        $this->accommodationAssignments = [];
        $this->vehicleAssignments = [];
    }

    public function onTransferAccommodationStepDone(): void
    {
        if ($this->assignNewVehicle) {
            $this->wizardPhase = 'vehicle';

            return;
        }
        $this->wizardPhase = 'done';
    }

    public function onTransferVehicleStepDone(): void
    {
        $this->wizardPhase = 'done';
    }

    public function onTransferWizardBack(?array $payload = null): void
    {
        $screen = is_array($payload) ? ($payload['screen'] ?? null) : null;

        if ($screen === 'accommodation') {
            $this->wizardPhase = 'followup';

            return;
        }

        if ($screen === 'vehicle') {
            $this->wizardPhase = $this->assignNewAccommodation ? 'accommodation' : 'followup';
        }
    }

    public function finishWizardBackToBoard(): void
    {
        $fromSummary = $this->wizardPhase === 'done';
        $this->rebuildTransferAssignmentRanges();
        $this->wizardPhase = 'board';
        if ($fromSummary) {
            $this->successBanner = 'Podsumowanie zamknięte. Szkic i szczegóły transportu zostają w kreatorze — bez zapisu w systemie.';
        }
    }

    protected function rebuildTransferAssignmentRanges(): void
    {
        $ranges = [];

        foreach ($this->draftProjectByAssignment as $assignmentId => $projectId) {
            $details = $this->draftAssignmentDetails[$assignmentId] ?? null;
            if (! $details) {
                continue;
            }
            $pa = ProjectAssignment::query()->find($assignmentId);
            if (! $pa) {
                continue;
            }
            $key = $pa->employee_id.'_'.$projectId.'_'.$details['role_id'];
            $ranges[$key] = [
                'employee_id' => (int) $pa->employee_id,
                'project_id' => (int) $projectId,
                'role_id' => (int) $details['role_id'],
                'start_date' => $details['start_date'],
                'end_date' => $details['end_date'],
            ];
        }

        $this->assignmentRanges = $ranges;
    }

    // -------------------------------------------------------------------------
    // Computed summary properties
    // -------------------------------------------------------------------------

    public function getDraftEmployeeIdsProperty(): array
    {
        $ids = [];
        foreach ($this->draftProjectByAssignment as $assignmentId => $_) {
            if (empty($this->draftAssignmentDetails[$assignmentId])) {
                continue;
            }
            $pa = ProjectAssignment::query()->find($assignmentId);
            if ($pa) {
                $ids[$pa->employee_id] = true;
            }
        }

        return array_map('intval', array_keys($ids));
    }

    /**
     * Uzupełnienie karty kanban „Plan po zatwierdzeniu”: docelowy projekt oraz (gdy wybrano) nowe mieszkanie i pojazd.
     *
     * @return array<int, array{project_name: ?string, accommodation_name: ?string, vehicle_label: ?string}>
     */
    public function getDraftKanbanPlanExtrasProperty(): array
    {
        if ($this->draftProjectByAssignment === []) {
            return [];
        }

        $assignmentIds = [];
        foreach ($this->draftProjectByAssignment as $assignmentId => $_) {
            if (! empty($this->draftAssignmentDetails[$assignmentId])) {
                $assignmentIds[] = (int) $assignmentId;
            }
        }
        if ($assignmentIds === []) {
            return [];
        }

        $pas = ProjectAssignment::query()->whereIn('id', $assignmentIds)->get()->keyBy('id');
        $projectIds = array_values(array_unique(array_map('intval', $this->draftProjectByAssignment)));
        $projects = Project::query()->whereIn('id', $projectIds)->get()->keyBy('id');

        $accById = collect();
        if ($this->assignNewAccommodation && $this->accommodationAssignments !== []) {
            $accIds = [];
            foreach ($this->accommodationAssignmentsByEmployeeId() as $row) {
                if (! empty($row['accommodation_id'])) {
                    $accIds[] = (int) $row['accommodation_id'];
                }
            }
            $accIds = array_values(array_unique(array_filter($accIds)));
            if ($accIds !== []) {
                $accById = Accommodation::query()->whereIn('id', $accIds)->get()->keyBy('id');
            }
        }

        $vehById = collect();
        if ($this->assignNewVehicle && $this->vehicleAssignments !== []) {
            $vehIds = [];
            foreach ($this->vehicleAssignmentsByEmployeeId() as $row) {
                if (! empty($row['vehicle_id'])) {
                    $vehIds[] = (int) $row['vehicle_id'];
                }
            }
            $vehIds = array_values(array_unique(array_filter($vehIds)));
            if ($vehIds !== []) {
                $vehById = Vehicle::query()->whereIn('id', $vehIds)->get()->keyBy('id');
            }
        }

        $accByEmp = $this->accommodationAssignmentsByEmployeeId();
        $vehByEmp = $this->vehicleAssignmentsByEmployeeId();

        $out = [];
        foreach ($assignmentIds as $aid) {
            $pid = (int) ($this->draftProjectByAssignment[$aid] ?? 0);
            $pa = $pas->get($aid);
            $employeeId = (int) ($pa?->employee_id ?? 0);

            $projectName = $pid > 0 ? ($projects->get($pid)?->name) : null;

            $accommodationName = null;
            if ($this->assignNewAccommodation && $employeeId > 0) {
                $accRow = $accByEmp[$employeeId] ?? null;
                $accId = (int) ($accRow['accommodation_id'] ?? 0);
                if ($accId > 0) {
                    $accommodationName = $accById->get($accId)?->name;
                }
            }

            $vehicleLabel = null;
            if ($this->assignNewVehicle && $employeeId > 0) {
                $vehRow = $vehByEmp[$employeeId] ?? null;
                $vehId = (int) ($vehRow['vehicle_id'] ?? 0);
                if ($vehId > 0) {
                    $vehicleLabel = $vehById->get($vehId)?->registration_number;
                }
            }

            $out[$aid] = [
                'project_name' => $projectName,
                'accommodation_name' => $accommodationName,
                'vehicle_label' => $vehicleLabel,
            ];
        }

        return $out;
    }

    /**
     * Aktualne mieszkanie i pojazd z bazy na dzień transferu — pod karty kanban po zapisie (bez szkicu).
     *
     * @return array<int, array{accommodation_name: ?string, vehicle_label: ?string}>
     */
    public function getKanbanLiveLogisticsByAssignmentIdProperty(): array
    {
        if ($this->mode !== 'assignment' || $this->transferDate === '') {
            return [];
        }

        $day = Carbon::parse($this->transferDate)->startOfDay();
        $employeeIds = [];
        $assignmentToEmployee = [];

        foreach ($this->columns as $col) {
            foreach ($col['assignments'] as $assignment) {
                $eid = (int) $assignment->employee_id;
                if ($eid <= 0) {
                    continue;
                }
                $employeeIds[$eid] = true;
                $assignmentToEmployee[(int) $assignment->id] = $eid;
            }
        }

        $employeeIds = array_keys($employeeIds);
        if ($employeeIds === []) {
            return [];
        }

        $accByEmp = [];
        $aaRows = AccommodationAssignment::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('start_date', '<=', $day)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $day))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->with('accommodation')
            ->get();
        foreach ($aaRows as $aa) {
            $eid = (int) $aa->employee_id;
            if (! isset($accByEmp[$eid]) && $aa->accommodation) {
                $accByEmp[$eid] = $aa->accommodation->name;
            }
        }

        $vehByEmp = [];
        $vaRows = VehicleAssignment::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('is_return_trip', false)
            ->where('start_date', '<=', $day)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $day))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->with('vehicle')
            ->get();
        foreach ($vaRows as $va) {
            $eid = (int) $va->employee_id;
            if (! isset($vehByEmp[$eid]) && $va->vehicle) {
                $vehByEmp[$eid] = $va->vehicle->registration_number;
            }
        }

        $out = [];
        foreach ($assignmentToEmployee as $aid => $eid) {
            $out[$aid] = [
                'accommodation_name' => $accByEmp[$eid] ?? null,
                'vehicle_label' => $vehByEmp[$eid] ?? null,
            ];
        }

        return $out;
    }

    public function getWizardSummarySketchRowsProperty(): array
    {
        $rows = [];
        foreach ($this->draftProjectByAssignment as $assignmentId => $projectId) {
            $details = $this->draftAssignmentDetails[$assignmentId] ?? null;
            if (! $details) {
                continue;
            }
            $pa = ProjectAssignment::query()->with('employee')->find($assignmentId);
            $project = Project::query()->find($projectId);
            $start = Carbon::parse($details['start_date'])->format('d.m.Y');
            $end = Carbon::parse($details['end_date'])->format('d.m.Y');
            $rows[] = [
                'employee_name' => $pa?->employee?->full_name ?? '?',
                'project_name' => $project?->name ?? '?',
                'role_name' => $details['role_name'] ?? '?',
                'date_label' => $details['start_date'] === $details['end_date'] ? $start : $start.' – '.$end,
            ];
        }

        return $rows;
    }

    public function getWizardSummaryAccommodationRowsProperty(): array
    {
        if ($this->accommodationAssignments === []) {
            return [];
        }
        $employeeIds = array_map('intval', array_keys($this->accommodationAssignments));
        $employees = Employee::query()->whereIn('id', $employeeIds)->get()->keyBy('id');
        $rows = [];
        foreach ($this->accommodationAssignments as $employeeId => $row) {
            $acc = Accommodation::query()->find((int) ($row['accommodation_id'] ?? 0));
            $emp = $employees->get((int) $employeeId);
            $rows[] = [
                'employee_name' => $emp?->full_name ?? '?',
                'label' => $acc?->name ?? ('#'.$row['accommodation_id']),
                'dates' => Carbon::parse($row['start_date'])->format('d.m.Y').' – '.Carbon::parse($row['end_date'])->format('d.m.Y'),
            ];
        }

        return $rows;
    }

    public function getWizardSummaryVehicleRowsProperty(): array
    {
        if ($this->vehicleAssignments === []) {
            return [];
        }
        $employeeIds = array_map('intval', array_keys($this->vehicleAssignments));
        $employees = Employee::query()->whereIn('id', $employeeIds)->get()->keyBy('id');
        $rows = [];
        foreach ($this->vehicleAssignments as $employeeId => $row) {
            $veh = Vehicle::query()->find((int) ($row['vehicle_id'] ?? 0));
            $emp = $employees->get((int) $employeeId);
            $rows[] = [
                'employee_name' => $emp?->full_name ?? '?',
                'label' => $veh?->registration_number ?? ('#'.$row['vehicle_id']),
                'dates' => Carbon::parse($row['start_date'])->format('d.m.Y').' – '.Carbon::parse($row['end_date'])->format('d.m.Y'),
                'position' => ($row['position'] ?? '') === 'driver' ? 'Kierowca' : 'Pasażer',
            ];
        }

        return $rows;
    }

    public function getWizardSummaryShortenedRowsProperty(): array
    {
        if ($this->wizardPhase !== 'done') {
            return [];
        }

        $transferDay = Carbon::parse($this->transferDate)->startOfDay();
        $dateLabel = $transferDay->format('d.m.Y');
        $dayBeforeLabel = $transferDay->copy()->subDay()->format('d.m.Y');
        $rows = [];

        foreach ($this->draftProjectByAssignment as $assignmentId => $_) {
            $details = $this->draftAssignmentDetails[$assignmentId] ?? null;
            if (! $details) {
                continue;
            }
            $pa = ProjectAssignment::query()->with(['project', 'employee'])->find($assignmentId);
            if (! $pa) {
                continue;
            }
            $endWas = $pa->end_date ? $pa->end_date->format('d.m.Y') : 'otwarte (brak końca)';
            $paStart = DateRangeService::normalizeDate($pa->start_date);
            $targetProjectId = (int) ($this->draftProjectByAssignment[$assignmentId] ?? 0);
            $targetProject = $targetProjectId > 0 ? Project::query()->find($targetProjectId) : null;
            $targetName = $targetProject?->name ?? '?';

            $detail = $paStart->gte($transferDay)
                ? 'Przypisanie do „'.($pa->project?->name ?? '?').'" zaczyna się w dniu transferu ('.$dateLabel.'), więc nie da się go skrócić do dnia wcześniejszego bez sprzeczności z datami. Zostanie usunięte i zastąpione nowym przypisaniem do „'.$targetName.'" od '.$dateLabel.'.'
                : 'Skrócenie końca przypisania do '.$dayBeforeLabel.' (wcześniej do: '.$endWas.').';

            $rows[] = [
                'kind_label' => 'Projekt',
                'employee_name' => $pa->employee?->full_name ?? '?',
                'item_label' => $pa->project?->name ?? '?',
                'detail' => $detail,
            ];
        }

        $newAccNameByEmployee = [];
        if ($this->assignNewAccommodation) {
            foreach ($this->accommodationAssignments as $eid => $row) {
                if (! empty($row['accommodation_id'])) {
                    $newAccNameByEmployee[(int) $eid] = Accommodation::query()->find((int) $row['accommodation_id'])?->name ?? '?';
                }
            }
        }
        $newVehLabelByEmployee = [];
        if ($this->assignNewVehicle) {
            foreach ($this->vehicleAssignments as $eid => $row) {
                if (! empty($row['vehicle_id'])) {
                    $newVehLabelByEmployee[(int) $eid] = Vehicle::query()->find((int) $row['vehicle_id'])?->registration_number ?? '?';
                }
            }
        }

        foreach ($this->draftEmployeeIds as $employeeId) {
            $employee = Employee::query()->find($employeeId);
            $name = $employee?->full_name ?? '?';

            if ($this->assignNewAccommodation) {
                $aa = AccommodationAssignment::query()
                    ->where('employee_id', $employeeId)
                    ->where('start_date', '<=', $transferDay)
                    ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $transferDay))
                    ->orderByDesc('start_date')->orderByDesc('id')
                    ->with('accommodation')
                    ->first();
                if ($aa) {
                    $endWas = $aa->end_date ? $aa->end_date->format('d.m.Y') : 'otwarte (brak końca)';
                    $aaStart = DateRangeService::normalizeDate($aa->start_date);
                    $newAccLabel = $newAccNameByEmployee[$employeeId] ?? null;
                    $newAccPhrase = $newAccLabel ? 'mieszkaniem „'.$newAccLabel.'"' : 'nowym mieszkaniem';
                    $detail = $aaStart->gte($transferDay)
                        ? 'Przypisanie do „'.($aa->accommodation?->name ?? '?').'" zaczyna się w dniu transferu — zostanie usunięte i zastąpione '.$newAccPhrase.' od '.$dateLabel.'.'
                        : 'Skrócenie końca przypisania do '.$dayBeforeLabel.' (wcześniej do: '.$endWas.').'
                            .($newAccLabel ? ' Nowe mieszkanie: „'.$newAccLabel.'” od '.$dateLabel.'.' : '');
                    $rows[] = [
                        'kind_label' => 'Mieszkanie',
                        'employee_name' => $name,
                        'item_label' => $aa->accommodation?->name ?? ('#'.$aa->accommodation_id),
                        'detail' => $detail,
                    ];
                }
            }

            if ($this->assignNewVehicle) {
                $va = VehicleAssignment::query()
                    ->where('employee_id', $employeeId)
                    ->where('is_return_trip', false)
                    ->where('start_date', '<=', $transferDay)
                    ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $transferDay))
                    ->orderByDesc('start_date')->orderByDesc('id')
                    ->with('vehicle')
                    ->first();
                if ($va && $va->vehicle) {
                    $endWas = $va->end_date ? $va->end_date->format('d.m.Y') : 'otwarte (brak końca)';
                    $vaStart = DateRangeService::normalizeDate($va->start_date);
                    $newVeh = $newVehLabelByEmployee[$employeeId] ?? null;
                    $newVehPhrase = $newVeh ? 'pojazdem '.$newVeh : 'nowym pojazdem';
                    $detail = $vaStart->gte($transferDay)
                        ? 'Przypisanie do pojazdu '.$va->vehicle->registration_number.' zaczyna się w dniu transferu — zostanie usunięte i zastąpione '.$newVehPhrase.' od '.$dateLabel.'.'
                        : 'Skrócenie końca przypisania do '.$dayBeforeLabel.' (wcześniej do: '.$endWas.').'
                            .($newVeh ? ' Nowy pojazd: '.$newVeh.' od '.$dateLabel.'.' : '');
                    $rows[] = [
                        'kind_label' => 'Pojazd',
                        'employee_name' => $name,
                        'item_label' => $va->vehicle->registration_number,
                        'detail' => $detail,
                    ];
                }
            }
        }

        return $rows;
    }

    // -------------------------------------------------------------------------
    // Save — reassignment transfer
    // -------------------------------------------------------------------------

    /**
     * Zatwierdza transfer (reassignment) w bazie — tylko z głównej tablicy, gdy wypełniono szkic i transport.
     */
    public function saveReassignmentTransferToSystem(): void
    {
        if ($this->mode !== 'assignment' || $this->wizardPhase !== 'board') {
            return;
        }

        $validation = $this->validateTransferBeforeCommit();
        if ($validation !== null) {
            session()->flash('warning', $validation);

            return;
        }

        try {
            $event = $this->transferService->commitTransfer($this->buildCommitTransferPayload());
        } catch (ValidationException $e) {
            session()->flash('warning', collect($e->errors())->flatten()->first() ?: $e->getMessage());

            return;
        } catch (\Throwable $e) {
            session()->flash('warning', $e->getMessage());

            return;
        }

        $this->draftProjectByAssignment = [];
        $this->draftAssignmentDetails = [];
        $this->successBanner = null;
        $this->resetTransferWizardState();

        session()->flash('success', 'Transfer został zapisany — wrócono do tablicy. Szczegóły zdarzenia: #'.$event->id.'.');
    }

    // -------------------------------------------------------------------------
    // Save — simple transfer (bez reassignment)
    // -------------------------------------------------------------------------

    public function saveSimpleTransfer(): void
    {
        if ($this->selectedEmployeeIds === []) {
            session()->flash('warning', 'Wybierz co najmniej jednego uczestnika.');

            return;
        }

        if ($this->transportMode === null) {
            session()->flash('warning', 'Wybierz sposób transportu.');

            return;
        }

        $config = TransferGroundConfig::fromArray($this->groundTransferConfig);
        $base = Location::getBase();

        if ($this->transportMode === 'own') {
            $simpleWaypoints = $config->routeWaypoints;
            if (count($simpleWaypoints) < 2) {
                session()->flash('warning', 'Skonfiguruj trasę — transport własny wymaga co najmniej 2 przystanków (start i cel).');

                return;
            }
            $simpleFirst = str_starts_with((string) ($simpleWaypoints[0] ?? ''), 'loc:')
                ? (int) substr($simpleWaypoints[0], 4) : 0;
            $simpleLast = str_starts_with((string) ($simpleWaypoints[count($simpleWaypoints) - 1] ?? ''), 'loc:')
                ? (int) substr($simpleWaypoints[count($simpleWaypoints) - 1], 4) : 0;
            if ($simpleFirst > 0 && $simpleFirst === $simpleLast) {
                session()->flash('warning', 'Start i cel trasy to ta sama lokalizacja — skonfiguruj trasę z różnym punktem startowym i docelowym.');

                return;
            }
        }

        $publicTicketLines = [];
        $locationStopNotes = null;
        $routeWaypoints = $config->routeWaypoints ?: null;
        $routeDistance = $config->routeDistance;
        $routeDuration = $config->routeDuration;

        if ($this->transportMode === 'public') {
            if (PublicTransportTicketCosts::areIncompleteForEmployees($this->selectedEmployeeIds, $this->ticketCostsByEmployee, true)) {
                session()->flash('warning', 'Uzupełnij kwoty, waluty i załączniki biletów dla wszystkich uczestników.');

                return;
            }
            foreach ($this->selectedEmployeeIds as $empId) {
                $this->validateTicketAttachmentUpload(
                    $this->ticketCostsByEmployee[$empId] ?? [],
                    'ticketCostsByEmployee.'.$empId.'.attachment'
                );
            }
            if ($this->getErrorBag()->isNotEmpty()) {
                session()->flash('warning', (string) $this->getErrorBag()->first());

                return;
            }

            $fromLocationId = (int) $this->sharedStartAirportLocationId;
            $toLocationId = (int) $this->sharedEndAirportLocationId;
            $routeWaypoints = null;
            $routeDistance = null;
            $routeDuration = null;

            $startLoc = Location::find($fromLocationId);
            $endLoc = Location::find($toLocationId);
            $hubLabel = $this->publicTransportHubKind === 'station' ? 'Dworzec' : 'Lotnisko';

            foreach ($this->selectedEmployeeIds as $empId) {
                $cost = $this->ticketCostsByEmployee[$empId] ?? [];
                $attachmentPath = $cost['attachment_path'] ?? null;
                if ($this->isTicketFileUpload($cost['attachment'] ?? null)) {
                    $attachmentPath = $cost['attachment']->store('transport_costs', 'public');
                }
                $emp = Employee::find($empId);
                $publicTicketLines[] = [
                    'employee_id' => (int) $empId,
                    'amount' => (float) ($cost['amount'] ?? 0),
                    'currency' => strtoupper(trim((string) ($cost['currency'] ?? 'PLN'))),
                    'attachment_path' => $attachmentPath,
                    'description' => 'Bilet — '.($emp?->full_name ?? '#'.$empId),
                    'notes' => trim($hubLabel.': '.($startLoc?->name ?? '').' → '.($endLoc?->name ?? '')),
                ];
            }
        } else {
            $waypoints = $config->routeWaypoints;
            $fromLocationId = $base->id;
            $toLocationId = $base->id;

            if (count($waypoints) >= 1) {
                $firstKey = (string) $waypoints[0];
                if (str_starts_with($firstKey, 'loc:')) {
                    $fromLocationId = (int) substr($firstKey, 4) ?: $base->id;
                }
            }
            if (count($waypoints) >= 2) {
                $lastKey = (string) $waypoints[count($waypoints) - 1];
                if (str_starts_with($lastKey, 'loc:')) {
                    $toLocationId = (int) substr($lastKey, 4) ?: $base->id;
                }
            }

            $normalizedWpKeys = LogisticsEvent::normalizeRouteWaypointsFromPayload($config->routeWaypoints);
            $locationStopNotes = LogisticsEvent::sanitizeLocationStopNotes($config->locationStopNotes, $normalizedWpKeys);
        }

        // Kierowca jest autorytatywnie zarządzany przez siatkę miejsc rodzica.
        // Dla trybu „public” pomijamy pojazd i kierowcę.
        $effectiveVehicleId = $this->transportMode === 'own' ? ($this->vehicleId ?: $config->vehicleId) : null;
        $effectiveDriverId = null;
        if ($this->transportMode === 'own' && isset($this->vehicleSeats[0])) {
            $seat0 = $this->vehicleSeats[0];
            if (empty($seat0['external_driver']) && ! empty($seat0['employee_id'])) {
                $effectiveDriverId = (int) $seat0['employee_id'];
            }
        }

        try {
            $event = $this->transferService->commitTransfer([
                'employee_ids' => $this->selectedEmployeeIds,
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocationId,
                'transfer_date' => $this->departureDate ?: now()->format('Y-m-d'),
                'has_reassignment' => false,
                'reassignments' => [],
                'notes' => null,
                'vehicle_id' => $effectiveVehicleId,
                'route_distance' => $routeDistance,
                'route_duration' => $routeDuration,
                'route_waypoints' => $routeWaypoints,
                'driver_employee_id' => $effectiveDriverId,
                'driver_payment_amount' => $config->driverPaymentAmount,
                'driver_payment_currency' => $config->driverPaymentCurrency,
                'driver_payroll_id' => $config->driverPayrollId,
                'location_stop_notes' => $locationStopNotes,
                'public_ticket_lines' => $publicTicketLines,
                'related_departure_id' => $this->relatedDepartureId,
            ]);
        } catch (ValidationException $e) {
            session()->flash('warning', collect($e->errors())->flatten()->first() ?: $e->getMessage());

            return;
        } catch (\Throwable $e) {
            session()->flash('warning', $e->getMessage());

            return;
        }

        $this->redirect(route('transfers.show', $event), navigate: true);
    }

    // -------------------------------------------------------------------------
    // Validation helpers
    // -------------------------------------------------------------------------

    protected function validateTransferBeforeCommit(): ?string
    {
        if ($this->draftProjectByAssignment === []) {
            return 'Brak szkicu przypisań do projektu.';
        }
        if ($this->transportMode === null) {
            return 'Wybierz sposób transportu (Publiczny / Własny) w sekcji „Szczegóły transferu".';
        }
        if ($this->departureDate === '' || $this->endDate === '') {
            return 'Uzupełnij datę początkową i datę zakończenia.';
        }
        if ($this->departureDate > $this->endDate) {
            return 'Data zakończenia nie może być wcześniejsza niż data początkowa.';
        }
        if ($this->transportMode === 'public') {
            if ($this->publicTransportHubKind === null) {
                return 'Wybierz typ punktu: lotnisko lub dworzec.';
            }
            $hubPurpose = $this->publicTransportHubKind === 'station'
                ? LocationPurposeType::STATION
                : LocationPurposeType::AIRPORT;
            if (empty($this->sharedStartAirportLocationId) || ! Location::matchesPurpose((int) $this->sharedStartAirportLocationId, $hubPurpose)) {
                return 'Wybierz prawidłowy punkt startowy (lotnisko / dworzec).';
            }
            if (empty($this->sharedEndAirportLocationId) || ! Location::matchesPurpose((int) $this->sharedEndAirportLocationId, $hubPurpose)) {
                return 'Wybierz prawidłowy punkt docelowy (lotnisko / dworzec).';
            }
            if ((int) $this->sharedStartAirportLocationId === (int) $this->sharedEndAirportLocationId) {
                return 'Punkt startowy i docelowy nie mogą być takie same.';
            }
        } elseif ($this->transportMode === 'own') {
            if (empty($this->vehicleId)) {
                return 'Wybierz pojazd służbowy (transport własny).';
            }
            $ownConfig = TransferGroundConfig::fromArray($this->groundTransferConfig);
            $ownWaypoints = $ownConfig->routeWaypoints;
            if (count($ownWaypoints) < 2) {
                return 'Skonfiguruj trasę — transport własny wymaga co najmniej 2 przystanków (start i cel).';
            }
            $firstLocId = str_starts_with((string) ($ownWaypoints[0] ?? ''), 'loc:')
                ? (int) substr($ownWaypoints[0], 4) : 0;
            $lastLocId = str_starts_with((string) ($ownWaypoints[count($ownWaypoints) - 1] ?? ''), 'loc:')
                ? (int) substr($ownWaypoints[count($ownWaypoints) - 1], 4) : 0;
            if ($firstLocId > 0 && $firstLocId === $lastLocId) {
                return 'Start i cel trasy to ta sama lokalizacja — skonfiguruj trasę z różnym punktem startowym i docelowym.';
            }
        }

        $seenEmployees = [];
        foreach ($this->draftProjectByAssignment as $assignmentId => $_) {
            if (empty($this->draftAssignmentDetails[$assignmentId])) {
                return 'Uzupełnij szkic (rola i daty) dla wszystkich wierszy.';
            }
            $pa = ProjectAssignment::query()->find($assignmentId);
            if (! $pa) {
                return 'Nie znaleziono przypisania projektowego #'.$assignmentId.'.';
            }
            $eid = (int) $pa->employee_id;
            if (isset($seenEmployees[$eid])) {
                return 'Ta sama osoba występuje w szkicu więcej niż raz.';
            }
            $seenEmployees[$eid] = true;
        }

        $accByEmp = $this->accommodationAssignmentsByEmployeeId();
        $vehByEmp = $this->vehicleAssignmentsByEmployeeId();
        foreach ($this->draftEmployeeIds as $employeeId) {
            if ($this->assignNewAccommodation && empty($accByEmp[$employeeId]['accommodation_id'] ?? null)) {
                return 'Brak przypisania mieszkania dla: '.(Employee::find($employeeId)?->full_name ?? 'ID '.$employeeId).'.';
            }
            if ($this->assignNewVehicle && empty($vehByEmp[$employeeId]['vehicle_id'] ?? null)) {
                return 'Brak przypisania pojazdu dla: '.(Employee::find($employeeId)?->full_name ?? 'ID '.$employeeId).'.';
            }
        }

        return null;
    }

    protected function buildCommitTransferPayload(): array
    {
        $transferMoment = Carbon::parse($this->departureDate);

        $firstAssignmentId = array_key_first($this->draftProjectByAssignment);
        $firstPa = ProjectAssignment::query()->with('project.location')->find((int) $firstAssignmentId);
        $firstTargetId = (int) ($this->draftProjectByAssignment[$firstAssignmentId] ?? 0);
        $targetProject = Project::query()->with('location')->find($firstTargetId);

        $base = Location::getBase();
        $fromLocationId = (int) ($firstPa?->project?->location_id ?? $base->id) ?: $base->id;
        $toLocationId = (int) ($targetProject?->location_id ?? $fromLocationId) ?: $base->id;

        $accByEmp = $this->accommodationAssignmentsByEmployeeId();
        $vehByEmp = $this->vehicleAssignmentsByEmployeeId();

        $reassignments = [];
        foreach ($this->draftProjectByAssignment as $assignmentId => $targetProjectId) {
            $details = $this->draftAssignmentDetails[$assignmentId] ?? null;
            if (! $details) {
                continue;
            }
            $pa = ProjectAssignment::query()->find($assignmentId);
            if (! $pa) {
                continue;
            }
            $employeeId = (int) $pa->employee_id;
            $accRow = $accByEmp[$employeeId] ?? null;
            $vehRow = $vehByEmp[$employeeId] ?? null;
            $accId = $this->assignNewAccommodation ? ((int) ($accRow['accommodation_id'] ?? 0)) : 0;
            $vehId = $this->assignNewVehicle ? ((int) ($vehRow['vehicle_id'] ?? 0)) : 0;

            $reassignments[$employeeId] = [
                'source_project_assignment_id' => (int) $assignmentId,
                'project_id' => (int) $targetProjectId,
                'role_id' => (int) $details['role_id'],
                'start_date' => $details['start_date'],
                'end_date' => $details['end_date'],
                'accommodation_id' => $accId > 0 ? $accId : null,
                'vehicle_id' => $vehId > 0 ? $vehId : null,
                'vehicle_position' => is_array($vehRow) && ! empty($vehRow['position'])
                    ? (string) $vehRow['position']
                    : VehiclePosition::PASSENGER->value,
                'skip_old_accommodation_shorten' => ! $this->assignNewAccommodation,
                'skip_old_vehicle_shorten' => ! $this->assignNewVehicle,
            ];
        }

        $employeeIds = array_keys($reassignments);
        sort($employeeIds);

        return [
            'employee_ids' => $employeeIds,
            'from_location_id' => $fromLocationId,
            'to_location_id' => $toLocationId,
            'transfer_date' => $transferMoment,
            'vehicle_id' => $this->transportMode === 'own' ? ($this->vehicleId ?: null) : null,
            'notes' => 'Transfer z tablicy (kreator)',
            'route_distance' => null,
            'route_duration' => null,
            'route_waypoints' => null,
            'has_reassignment' => true,
            'reassignments' => $reassignments,
            'driver_employee_id' => null,
            'driver_payment_amount' => null,
            'driver_payment_currency' => null,
            'driver_payroll_id' => null,
            'related_departure_id' => $this->relatedDepartureId,
        ];
    }

    // -------------------------------------------------------------------------
    // Accommodation / vehicle assignment handlers
    // -------------------------------------------------------------------------

    public function handleAccommodationAssigned(array $data): void
    {
        if (! in_array($this->wizardPhase, ['accommodation', 'vehicle', 'done'], true) || empty($data['employee_id'])) {
            return;
        }
        $this->accommodationAssignments[(int) $data['employee_id']] = [
            'accommodation_id' => (int) $data['accommodation_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ];
    }

    public function handleAccommodationRemoved(array $data): void
    {
        if (empty($data['employee_id'])) {
            return;
        }
        unset($this->accommodationAssignments[(int) $data['employee_id']]);
    }

    public function handleVehicleAssigned(array $data): void
    {
        if (! in_array($this->wizardPhase, ['vehicle', 'done'], true) || empty($data['employee_id'])) {
            return;
        }
        $this->vehicleAssignments[(int) $data['employee_id']] = [
            'vehicle_id' => (int) $data['vehicle_id'],
            'position' => (string) ($data['position'] ?? 'passenger'),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ];
    }

    public function handleVehicleAssignmentRemoved(array $data): void
    {
        if (empty($data['employee_id'])) {
            return;
        }
        unset($this->vehicleAssignments[(int) $data['employee_id']]);
    }

    /**
     * Livewire potrafi serializować klucze tablicy jako stringi — lookup po employee_id musi być stabilny.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function accommodationAssignmentsByEmployeeId(): array
    {
        $out = [];
        foreach ($this->accommodationAssignments as $eid => $row) {
            if (is_array($row)) {
                $out[(int) $eid] = $row;
            }
        }

        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function vehicleAssignmentsByEmployeeId(): array
    {
        $out = [];
        foreach ($this->vehicleAssignments as $eid => $row) {
            if (is_array($row)) {
                $out[(int) $eid] = $row;
            }
        }

        return $out;
    }

    // -------------------------------------------------------------------------
    // Kanban drag & drop
    // -------------------------------------------------------------------------

    public function startTransferDrop(int $assignmentId, int $targetProjectId): void
    {
        $date = Carbon::parse($this->transferDate)->startOfDay();

        $assignment = ProjectAssignment::query()
            ->activeAtDate($date)
            ->with(['employee.roles', 'project', 'role'])
            ->find($assignmentId);

        if (! $assignment || ! $assignment->employee) {
            return;
        }

        $project = Project::query()
            ->where('status', ProjectStatus::ACTIVE)
            ->activeAtDate($date)
            ->whereKey($targetProjectId)
            ->first();

        if (! $project) {
            return;
        }

        $effectiveFromId = (int) ($this->draftProjectByAssignment[$assignmentId] ?? $assignment->project_id);
        if ($effectiveFromId === $targetProjectId) {
            unset($this->draftProjectByAssignment[$assignmentId], $this->draftAssignmentDetails[$assignmentId]);

            return;
        }

        $this->pendingAssignmentId = $assignmentId;
        $this->pendingTargetProjectId = $targetProjectId;
        $this->pendingEmployeeId = $assignment->employee_id;

        $arrival = $date->copy();
        $gapsAll = $this->departurePlannerService->getProjectGapsForTwoWeeks($arrival);
        $slice = $gapsAll[$targetProjectId] ?? null;

        $project->loadMissing('location');
        $this->gapsModalProject = [
            'id' => $project->id,
            'name' => $project->name,
            'location' => $project->location?->name,
        ];

        $this->gapsModalRoles = ($slice && ! empty($slice['roles']))
            ? $slice['roles']
            : $this->fallbackRolesFromDemands($project, $arrival);

        if ($this->gapsModalRoles === []) {
            if ($assignment->role_id) {
                $this->openCalendarForRole((int) $assignment->role_id);

                return;
            }
            session()->flash('warning', 'Brak ról (zapotrzebowania) dla tego projektu w okresie 14 dni — ustaw zapotrzebowanie w projekcie.');
            $this->resetPendingDrop();

            return;
        }

        $this->showGapsModal = true;
    }

    public function employeeHasRole(int $roleId): bool
    {
        if (! $this->pendingEmployeeId) {
            return false;
        }

        return Employee::find($this->pendingEmployeeId)?->hasRole($roleId) ?? false;
    }

    protected function fallbackRolesFromDemands(Project $project, Carbon $arrival): array
    {
        $end = $arrival->copy()->addDays(13);
        $demands = $project->demands()
            ->overlappingWith($arrival, $end)
            ->with('role')
            ->get()
            ->unique('role_id');

        $out = [];
        foreach ($demands as $d) {
            if (! $d->role) {
                continue;
            }
            $out[$d->role_id] = [
                'id' => $d->role->id,
                'name' => $d->role->name,
                'min_gaps' => 0,
                'max_gaps' => 0,
            ];
        }

        return $out;
    }

    public function closeGapsModal(): void
    {
        $this->showGapsModal = false;
        $this->resetPendingDrop();
    }

    public function selectRoleForTransfer(int $roleId): void
    {
        $employee = $this->pendingEmployeeId ? Employee::find($this->pendingEmployeeId) : null;
        if (! $employee || ! $employee->hasRole($roleId)) {
            return;
        }
        $this->openCalendarForRole($roleId);
    }

    protected function openCalendarForRole(int $roleId): void
    {
        $this->selectedRoleId = $roleId;
        $arrival = Carbon::parse($this->transferDate)->startOfDay();
        $this->calendarMonthStart = $arrival->copy()->startOfMonth()->format('Y-m-d');
        $this->selectedStartDate = null;
        $this->selectedEndDate = null;
        $this->employeeAvailability = [];
        $this->loadEmployeeAvailabilityForMonth();
        $this->showGapsModal = false;
        $this->showCalendarModal = true;
    }

    public function closeCalendarModal(): void
    {
        $this->showCalendarModal = false;
        $this->employeeAvailability = [];
        $this->selectedStartDate = null;
        $this->selectedEndDate = null;
        $this->calendarMonthStart = null;
        $this->selectedRoleId = null;
        $this->resetPendingDrop();
    }

    public function backFromCalendarToGaps(): void
    {
        if (! $this->pendingAssignmentId || ! $this->pendingTargetProjectId) {
            return;
        }
        $this->showCalendarModal = false;
        $this->employeeAvailability = [];
        $this->selectedStartDate = null;
        $this->selectedEndDate = null;
        $this->calendarMonthStart = null;
        $this->selectedRoleId = null;
        $this->showGapsModal = true;
    }

    protected function loadEmployeeAvailabilityForMonth(): void
    {
        if (! $this->pendingEmployeeId || ! $this->selectedRoleId || ! $this->calendarMonthStart || ! $this->pendingTargetProjectId) {
            return;
        }

        $employee = Employee::find($this->pendingEmployeeId);
        $project = Project::find($this->pendingTargetProjectId);
        $role = Role::find($this->selectedRoleId);

        if (! $employee || ! $project || ! $role) {
            return;
        }

        $monthStart = Carbon::parse($this->calendarMonthStart)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $minDate = Carbon::parse($this->transferDate)->startOfDay();

        $newAvailability = $this->departurePlannerService->getEmployeeAvailabilityForMonthRange(
            $employee, $project, $role, $monthStart, $monthEnd, [], [], $minDate, true
        );

        $this->employeeAvailability = array_merge($this->employeeAvailability, $newAvailability);
    }

    public function previousMonth(): void
    {
        if ($this->calendarMonthStart) {
            $this->calendarMonthStart = Carbon::parse($this->calendarMonthStart)->subMonth()->startOfMonth()->format('Y-m-d');
            $this->loadEmployeeAvailabilityForMonth();
        }
    }

    public function nextMonth(): void
    {
        if ($this->calendarMonthStart) {
            $this->calendarMonthStart = Carbon::parse($this->calendarMonthStart)->addMonth()->startOfMonth()->format('Y-m-d');
            $this->loadEmployeeAvailabilityForMonth();
        }
    }

    public function selectDate(string $date): void
    {
        if (! $this->pendingEmployeeId || ! $this->pendingTargetProjectId || ! $this->selectedRoleId) {
            return;
        }

        $dateCarbon = Carbon::parse($date)->startOfDay();
        $transferDay = Carbon::parse($this->transferDate)->startOfDay();

        if ($dateCarbon->lt($transferDay)) {
            return;
        }
        if (! isset($this->employeeAvailability[$date]) || empty($this->employeeAvailability[$date]['can_assign'])) {
            return;
        }

        if (! $this->selectedStartDate) {
            $this->selectedStartDate = $date;
            $this->selectedEndDate = null;
        } else {
            $start = Carbon::parse($this->selectedStartDate);
            $end = Carbon::parse($date);
            if ($end->lt($start)) {
                $this->selectedStartDate = $date;
                $this->selectedEndDate = null;
            } else {
                $this->selectedEndDate = $date;
            }
        }
    }

    public function confirmTransferAssignment(): void
    {
        if (! $this->pendingAssignmentId || ! $this->pendingTargetProjectId) {
            $this->addError('confirmation', 'Brak danych przypisania.');

            return;
        }
        if (! $this->selectedStartDate) {
            $this->addError('confirmation', 'Wybierz datę rozpoczęcia w kalendarzu.');

            return;
        }
        if (! $this->selectedRoleId) {
            $this->addError('confirmation', 'Brak wybranej roli.');

            return;
        }

        $start = Carbon::parse($this->selectedStartDate);
        $end = $this->selectedEndDate ? Carbon::parse($this->selectedEndDate) : $start;

        $targetProject = Project::find($this->pendingTargetProjectId);
        if ($targetProject && $targetProject->end_date) {
            $projectEnd = Carbon::parse($targetProject->end_date)->endOfDay();
            if ($start->gt($projectEnd) || $end->gt($projectEnd)) {
                $this->addError('confirmation', 'Wybrane daty wykraczają poza koniec projektu.');

                return;
            }
        }

        $assignmentId = $this->pendingAssignmentId;
        $role = Role::find($this->selectedRoleId);

        $this->moveAssignment($assignmentId, $this->pendingTargetProjectId);

        $this->draftAssignmentDetails[$assignmentId] = [
            'role_id' => (int) $this->selectedRoleId,
            'role_name' => $role?->name ?? '?',
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
        ];

        $rangeText = $start->format('d.m.Y').($end->format('Y-m-d') !== $start->format('Y-m-d') ? ' – '.$end->format('d.m.Y') : '');
        $this->successBanner = 'Szkic przeniesienia zapisany: '.$role?->name.', '.$rangeText;

        // Odśwież siatkę miejsc — do szkicu dołączył nowy pracownik
        if ($this->transportMode === 'own' && ! empty($this->vehicleId)) {
            $this->initVehicleSeats();
        }
        // Wyczyść bilety osoby, która właśnie opuściła szkic (edge case: ponowny ruch)
        if ($this->transportMode === 'public') {
            $newIds = $this->allDraftEmployeeIds;
            $this->ticketCostsByEmployee = array_intersect_key(
                $this->ticketCostsByEmployee,
                array_flip($newIds)
            );
        }

        $this->showCalendarModal = false;
        $this->resetPendingDrop();
        $this->resetValidation(['confirmation']);
    }

    public function moveAssignment(int $assignmentId, int $toProjectId): void
    {
        $date = Carbon::parse($this->transferDate)->startOfDay();

        $assignment = ProjectAssignment::query()->activeAtDate($date)->find($assignmentId);
        if (! $assignment) {
            return;
        }

        $project = Project::query()
            ->where('status', ProjectStatus::ACTIVE)
            ->activeAtDate($date)
            ->whereKey($toProjectId)
            ->first();
        if (! $project) {
            return;
        }

        if ($assignment->project_id === $toProjectId) {
            unset($this->draftProjectByAssignment[$assignmentId], $this->draftAssignmentDetails[$assignmentId]);
        } else {
            $this->draftProjectByAssignment[$assignmentId] = $toProjectId;
        }

        // Synchronizuj siatkę miejsc po każdej zmianie szkicu
        if ($this->transportMode === 'own' && ! empty($this->vehicleId)) {
            $this->initVehicleSeats();
        }
    }

    protected function resetPendingDrop(): void
    {
        $this->pendingAssignmentId = null;
        $this->pendingTargetProjectId = null;
        $this->pendingEmployeeId = null;
        $this->gapsModalProject = null;
        $this->gapsModalRoles = [];
        $this->showGapsModal = false;
        $this->showCalendarModal = false;
        $this->employeeAvailability = [];
        $this->selectedStartDate = null;
        $this->selectedEndDate = null;
        $this->calendarMonthStart = null;
        $this->selectedRoleId = null;
    }

    protected function closeAllModals(): void
    {
        $this->resetPendingDrop();
    }

    public function clearDraft(): void
    {
        $this->draftProjectByAssignment = [];
        $this->draftAssignmentDetails = [];
        $this->successBanner = null;
        $this->vehicleSeats = [];
        $this->ticketCostsByEmployee = [];
        $this->ticketAttachmentUploads = [];
        $this->resetTransferWizardState();
    }

    // -------------------------------------------------------------------------
    // Kanban columns
    // -------------------------------------------------------------------------

    public function getColumnsProperty(): array
    {
        $date = Carbon::parse($this->transferDate)->startOfDay();

        $byProject = collect();

        foreach (
            Project::query()
                ->where('status', ProjectStatus::ACTIVE)
                ->activeAtDate($date)
                ->with('location')
                ->orderBy('name')
                ->get() as $project
        ) {
            $byProject->put($project->id, ['project' => $project, 'assignments' => collect()]);
        }

        $assignments = ProjectAssignment::query()
            ->activeAtDate($date)
            ->whereHas('project', fn ($q) => $q->where('status', ProjectStatus::ACTIVE))
            ->with(['project.location', 'employee', 'role'])
            ->orderBy('project_id')
            ->orderBy('employee_id')
            ->get();

        foreach ($assignments as $assignment) {
            $effectiveProjectId = (int) ($this->draftProjectByAssignment[$assignment->id] ?? $assignment->project_id);

            if (! $byProject->has($effectiveProjectId)) {
                $project = $effectiveProjectId === (int) $assignment->project_id
                    ? $assignment->project
                    : Project::query()->with('location')->find($effectiveProjectId);

                if (! $project || $project->status !== ProjectStatus::ACTIVE) {
                    continue;
                }

                $byProject->put($effectiveProjectId, ['project' => $project, 'assignments' => collect()]);
            }

            $byProject->get($effectiveProjectId)['assignments']->push($assignment);
        }

        return $byProject
            ->sortBy(fn (array $col) => mb_strtolower($col['project']->name))
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.transfer-create-board');
    }
}
