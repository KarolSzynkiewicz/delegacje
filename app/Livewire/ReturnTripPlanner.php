<?php

namespace App\Livewire;

use App\Enums\Currency;
use App\Enums\EmployeeLocationState;
use App\Enums\LocationPurposeType;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Vehicle;
use App\Services\LocationTrackingService;
use App\Services\ReturnTripService;
use App\Services\VehicleValidationService;
use App\Support\PublicTransportTicketCosts;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class ReturnTripPlanner extends Component
{
    use Concerns\InteractsWithLogisticsTransportMode;
    use WithFileUploads;

    public string $returnDate = '';

    public string $endDate = '';

    public $vehicleId = '';

    /** Jak w planerze wyjazdu: [idx => ['employee_id' => ?int, 'position' => string, 'external_driver' => bool]] */
    public array $vehicleSeats = [];

    /** @var 'public'|'own'|null */
    public ?string $transportMode = null;

    public bool $showTransportSwitchModal = false;

    /** @var 'public'|'own'|null */
    public ?string $pendingTransportMode = null;

    public array $selectedEmployeeIds = [];

    public string $employeeSearch = '';

    /** @var array<int, array{amount?: mixed, currency?: string, attachment?: mixed}> */
    public array $ticketCostsByEmployee = [];

    public $sharedStartAirportLocationId = null;

    public $sharedEndAirportLocationId = null;

    /** @var 'airport'|'station'|null Po wyborze typu pokazujemy listy; null = tylko przełącznik. */
    public ?string $publicTransportHubKind = null;

    public string $notes = '';

    public bool $showPreview = false;

    public array $previewData = [];

    /** Wymagane przy skróceniu przypisań innych osób do auta powrotnego */
    public bool $acceptReturnConsequences = false;

    public string $errorMessage = '';

    public function mount(): void
    {
        $this->returnDate = '';
        $this->endDate = '';
        $this->transportMode = null;
    }

    // ─── Computed properties ───────────────────────────────────────────────────

    public function getEmployeesListProperty(): array
    {
        if (empty($this->returnDate)) {
            return [];
        }

        $locationTrackingService = app(LocationTrackingService::class);
        $date = Carbon::parse($this->returnDate);
        $search = mb_strtolower(trim($this->employeeSearch));

        return Employee::with(['roles', 'assignments.project', 'accommodationAssignments.accommodation'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->filter(function (Employee $employee) use ($locationTrackingService, $date) {
                $status = $locationTrackingService->getLocationStatus($employee, $date);

                return $status['state'] === EmployeeLocationState::OUTSIDE_BASE;
            })
            ->filter(function (Employee $employee) use ($search) {
                if ($search === '') {
                    return true;
                }

                return str_contains(mb_strtolower($employee->full_name), $search);
            })
            ->values()
            ->map(fn (Employee $e) => [
                'id' => $e->id,
                'full_name' => $e->full_name,
                'first_name' => $e->first_name,
                'last_name' => $e->last_name,
                'image_url' => $e->image_url,
                'roles' => $e->roles->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])->toArray(),
            ])
            ->toArray();
    }

    public function getAvailableVehiclesProperty()
    {
        if (empty($this->returnDate)) {
            return collect();
        }

        $returnDate = Carbon::parse($this->returnDate);
        $effectiveEndDate = $this->endDate ? Carbon::parse($this->endDate) : $returnDate;
        $locationTrackingService = app(LocationTrackingService::class);
        $vehicleValidationService = app(VehicleValidationService::class);

        return Vehicle::where('type', 'company_vehicle')
            ->orderBy('registration_number')
            ->get()
            ->filter(function (Vehicle $vehicle) use ($returnDate, $effectiveEndDate, $locationTrackingService, $vehicleValidationService) {
                $status = $locationTrackingService->getVehicleLocationStatus($vehicle, $returnDate);
                if (! $status['outside_base']) {
                    return false;
                }

                $result = $vehicleValidationService->validateForLogisticsEvent(
                    $vehicle,
                    $returnDate,
                    $effectiveEndDate,
                    null,
                    true
                );

                return $result['valid'];
            });
    }

    /**
     * Liczba zajętych miejsc w zaplanowanym zjeździe: uczestnicy + 1 gdy kierowca zewnętrzny (fotel kierowcy).
     * Spójne z badge „X/Y zajęte” i siatką miejsc jak w planerze wyjazdu.
     */
    public function getVehicleOccupantCountForCapacity(): int
    {
        if ($this->transportMode !== 'own' || empty($this->vehicleId)) {
            return count($this->selectedEmployeeIds);
        }
        if (empty($this->vehicleSeats)) {
            return count($this->selectedEmployeeIds);
        }
        $external = (bool) ($this->vehicleSeats[0]['external_driver'] ?? true);

        return $external ? count($this->selectedEmployeeIds) + 1 : count($this->selectedEmployeeIds);
    }

    /**
     * Blokada „Podgląd zjazdu” — przepełnienie lub brak kierowcy przy kierowcy wewnętrznym.
     */
    public function getReturnTripPrepareBlockedProperty(): bool
    {
        if ($this->transportMode !== 'own' || empty($this->vehicleId) || empty($this->vehicleSeats)) {
            return false;
        }
        $vehicle = Vehicle::find($this->vehicleId);
        $capacity = (int) ($vehicle?->capacity ?? 0);
        $occ = $this->getVehicleOccupantCountForCapacity();
        if ($capacity > 0 && $occ > $capacity) {
            return true;
        }
        $driverSeat = $this->vehicleSeats[0] ?? null;
        $isExternal = (bool) ($driverSeat['external_driver'] ?? true);
        $driverId = (int) ($driverSeat['employee_id'] ?? 0);

        return ! $isExternal && $driverId === 0;
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
        if ($this->publicTransportHubKind === null) {
            return;
        }

        $ids = $this->availablePublicTransportHubs->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (! empty($this->sharedStartAirportLocationId) && ! in_array((int) $this->sharedStartAirportLocationId, $ids, true)) {
            $this->sharedStartAirportLocationId = null;
        }
        if (! empty($this->sharedEndAirportLocationId) && ! in_array((int) $this->sharedEndAirportLocationId, $ids, true)) {
            $this->sharedEndAirportLocationId = null;
        }
    }

    public function resetPublicTransportHubSelection(): void
    {
        $this->publicTransportHubKind = null;
        $this->sharedStartAirportLocationId = null;
        $this->sharedEndAirportLocationId = null;
    }

    public function getSelectedEmployeesProperty(): \Illuminate\Support\Collection
    {
        if (empty($this->selectedEmployeeIds)) {
            return collect();
        }

        return Employee::with('roles')
            ->whereIn('id', $this->selectedEmployeeIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public function getIsPublicTransportProperty(): bool
    {
        return $this->transportMode === 'public';
    }

    /** Tytuł sekcji biletów: lotnisko vs dworzec. */
    public function getPublicTransportTicketsSectionTitleProperty(): string
    {
        return $this->publicTransportHubKind === 'airport'
            ? 'Bilety lotnicze'
            : 'Bilety';
    }

    /** Kwota, waluta i załącznik dla każdej osoby (jak przy wyjeździe). */
    public function getHeaderTicketsIncompleteProperty(): bool
    {
        if (! $this->isPublicTransport) {
            return false;
        }

        return PublicTransportTicketCosts::areIncompleteForEmployees(
            $this->selectedEmployeeIds,
            $this->ticketCostsByEmployee,
            true
        );
    }

    public function getCurrencyCasesProperty(): array
    {
        return Currency::cases();
    }

    // ─── Miejsca w aucie (jak DeparturePlannerV2) ───────────────────────────────

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
        if (! $this->vehicleId) {
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

        $selectedIds = array_map(fn ($id) => (int) $id, $this->selectedEmployeeIds);

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
            $alreadyIn = collect($this->vehicleSeats)->contains(fn ($s) => (int) ($s['employee_id'] ?? 0) === $empId);
            if (! $alreadyIn && $seatIndex < $capacity) {
                $this->vehicleSeats[$seatIndex]['employee_id'] = $empId;
                $seatIndex++;
            }
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
        if (! $current === false) {
            $this->vehicleSeats[0]['employee_id'] = null;
        }
    }

    protected function addEmployeeToReturnVehicleSeats(int $employeeId): void
    {
        if (empty($this->vehicleId) || $this->transportMode !== 'own') {
            return;
        }
        if (empty($this->vehicleSeats)) {
            $this->initVehicleSeats();
        }
        $alreadyIn = collect($this->vehicleSeats)->contains(
            fn ($s) => (int) ($s['employee_id'] ?? 0) === $employeeId
        );
        if ($alreadyIn) {
            return;
        }
        for ($i = 1; $i < count($this->vehicleSeats); $i++) {
            if (empty($this->vehicleSeats[$i]['employee_id'])) {
                $this->vehicleSeats[$i]['employee_id'] = $employeeId;

                return;
            }
        }
    }

    protected function removeEmployeeFromReturnVehicleSeats(int $employeeId): void
    {
        if (empty($this->vehicleId) || empty($this->vehicleSeats)) {
            return;
        }
        if ((int) ($this->vehicleSeats[0]['employee_id'] ?? 0) === $employeeId) {
            $this->vehicleSeats[0]['employee_id'] = null;
            $this->vehicleSeats[0]['external_driver'] = true;
        }
        for ($i = 1; $i < count($this->vehicleSeats); $i++) {
            if ((int) ($this->vehicleSeats[$i]['employee_id'] ?? 0) === $employeeId) {
                $this->vehicleSeats[$i]['employee_id'] = null;
            }
        }
        $this->compactPassengerSeats();
    }

    // ─── Actions ───────────────────────────────────────────────────────────────

    public function toggleEmployee(int $employeeId): void
    {
        $employeeId = (int) $employeeId;
        if (in_array($employeeId, $this->selectedEmployeeIds, true)) {
            $this->selectedEmployeeIds = array_values(array_filter(
                $this->selectedEmployeeIds,
                fn ($id) => (int) $id !== $employeeId
            ));
            $this->removeEmployeeFromReturnVehicleSeats($employeeId);
        } else {
            $this->selectedEmployeeIds[] = $employeeId;
            $this->addEmployeeToReturnVehicleSeats($employeeId);
        }
        $this->showPreview = false;
        $this->previewData = [];
        $this->acceptReturnConsequences = false;
    }

    public function updatedEndDate(): void
    {
        $this->showPreview = false;
        $this->previewData = [];
        $this->acceptReturnConsequences = false;
    }

    public function updatedReturnDate(): void
    {
        $this->showPreview = false;
        $this->previewData = [];
        $this->acceptReturnConsequences = false;
        // Reset endDate if before returnDate
        if (! empty($this->returnDate) && ! empty($this->endDate) && $this->endDate < $this->returnDate) {
            $this->endDate = $this->returnDate;
        }
    }

    public function updatedVehicleId($value): void
    {
        $this->showPreview = false;
        $this->previewData = [];
        $this->acceptReturnConsequences = false;

        if (! empty($value)) {
            $this->transportMode = 'own';
            $this->sharedStartAirportLocationId = null;
            $this->sharedEndAirportLocationId = null;
            $this->ticketCostsByEmployee = [];
            $this->initVehicleSeats();
        } else {
            $this->transportMode = 'public';
            $this->vehicleSeats = [];
            $this->publicTransportHubKind = null;
            $this->sharedStartAirportLocationId = null;
            $this->sharedEndAirportLocationId = null;
        }
    }

    protected function onSwitchingToPublicTransportMode(): void
    {
        $this->vehicleId = '';
        $this->vehicleSeats = [];
        $this->sharedStartAirportLocationId = null;
        $this->sharedEndAirportLocationId = null;
        $this->publicTransportHubKind = null;
        $this->ticketCostsByEmployee = [];
    }

    protected function onSwitchingToOwnTransportMode(): void
    {
        $this->sharedStartAirportLocationId = null;
        $this->sharedEndAirportLocationId = null;
        $this->publicTransportHubKind = null;
        $this->ticketCostsByEmployee = [];
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

    protected function afterTransportModeChanged(string $mode): void
    {
        $this->showPreview = false;
        $this->previewData = [];
        $this->acceptReturnConsequences = false;
    }

    public function prepareReturn(): void
    {
        $this->errorMessage = '';
        $this->acceptReturnConsequences = false;

        if (empty($this->returnDate)) {
            $this->addError('returnDate', 'Wybierz datę zjazdu.');

            return;
        }

        if (empty($this->endDate)) {
            $this->addError('endDate', 'Wybierz datę zakończenia zjazdu.');

            return;
        }

        if ($this->transportMode === null) {
            $this->addError('transportMode', 'Wybierz sposób transportu (Publiczny / Własny).');

            return;
        }

        if (empty($this->selectedEmployeeIds)) {
            $this->addError('selectedEmployeeIds', 'Wybierz co najmniej jednego pracownika.');

            return;
        }

        try {
            $service = app(ReturnTripService::class);
            $vehicle = $this->vehicleId ? Vehicle::find($this->vehicleId) : null;

            $occupantCount = $this->getVehicleOccupantCountForCapacity();

            $preparation = $service->prepareZjazd(
                $this->selectedEmployeeIds,
                Carbon::parse($this->returnDate),
                $vehicle,
                Carbon::parse($this->endDate),
                null,
                $occupantCount,
            );

            $ui = $service->buildReturnTripPreviewUi($preparation);

            $vehicleFill = null;
            if ($vehicle) {
                $cap = (int) ($vehicle->capacity ?? 0);
                $vehicleFill = [
                    'capacity' => $cap,
                    'occupied' => $occupantCount,
                    'over_capacity' => $cap > 0 && $occupantCount > $cap,
                ];
            }

            $this->previewData = [
                'is_valid' => $preparation->isValid,
                'conflicts' => $preparation->conflicts->map(fn ($c) => [
                    'message' => $c->message,
                    'is_blocking' => $c->isBlocking,
                ])->toArray(),
                'employees_count' => count($this->selectedEmployeeIds),
                'return_date' => Carbon::parse($this->returnDate)->format('d.m.Y'),
                'return_date_iso' => $this->returnDate,
                'end_date' => $this->endDate ? Carbon::parse($this->endDate)->format('d.m.Y') : null,
                'vehicle' => $vehicle ? $vehicle->registration_number.' – '.$vehicle->brand.' '.$vehicle->model : null,
                'vehicle_fill' => $vehicleFill,
                'participant_rows' => $ui['participant_rows'],
                'displaced_without_vehicle' => $ui['displaced_without_vehicle'],
                'requires_consequences_confirm' => $ui['requires_consequences_confirm'],
            ];

            $this->showPreview = true;
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first() ?? 'Błąd walidacji.';
        } catch (\Exception $e) {
            $this->errorMessage = 'Błąd: '.$e->getMessage();
        }
    }

    public function saveReturn(): void
    {
        $this->errorMessage = '';

        if (empty($this->returnDate)) {
            $this->addError('returnDate', 'Wybierz datę zjazdu.');

            return;
        }

        if (empty($this->endDate)) {
            $this->addError('endDate', 'Wybierz datę zakończenia zjazdu.');

            return;
        }

        if ($this->transportMode === null) {
            $this->addError('transportMode', 'Wybierz sposób transportu (Publiczny / Własny).');

            return;
        }

        if (empty($this->selectedEmployeeIds)) {
            $this->addError('selectedEmployeeIds', 'Wybierz co najmniej jednego pracownika.');

            return;
        }

        if (! $this->showPreview || empty($this->previewData)) {
            $this->addError('preview', 'Najpierw wygeneruj podgląd zjazdu (przycisk „Podgląd zjazdu”).');

            return;
        }

        if (! empty($this->previewData['requires_consequences_confirm']) && ! $this->acceptReturnConsequences) {
            $this->addError('acceptReturnConsequences', 'Zaznacz potwierdzenie, że akceptujesz skrócenie przypisań do auta powrotnego dla osób spoza tego zjazdu.');

            return;
        }

        // Validate ticket costs for public transport
        if ($this->isPublicTransport) {
            if ($this->publicTransportHubKind === null) {
                $this->addError('publicTransportHubKind', 'Wybierz typ punktu: lotnisko lub dworzec.');

                return;
            }

            $hubPurpose = $this->publicTransportHubKind === 'station'
                ? LocationPurposeType::STATION
                : LocationPurposeType::AIRPORT;

            if (
                empty($this->sharedStartAirportLocationId)
                || ! Location::matchesPurpose((int) $this->sharedStartAirportLocationId, $hubPurpose)
            ) {
                $this->addError(
                    'sharedStartAirportLocationId',
                    $hubPurpose === LocationPurposeType::STATION
                        ? 'Wybierz dworzec startowy z listy.'
                        : 'Wybierz lotnisko startowe z listy.'
                );

                return;
            }

            if (
                empty($this->sharedEndAirportLocationId)
                || ! Location::matchesPurpose((int) $this->sharedEndAirportLocationId, $hubPurpose)
            ) {
                $this->addError(
                    'sharedEndAirportLocationId',
                    $hubPurpose === LocationPurposeType::STATION
                        ? 'Wybierz dworzec docelowy z listy.'
                        : 'Wybierz lotnisko docelowe z listy.'
                );

                return;
            }

            foreach ($this->selectedEmployeeIds as $empId) {
                $cost = $this->ticketCostsByEmployee[$empId] ?? [];
                $amount = $cost['amount'] ?? null;
                if ($amount === null || $amount === '' || ! is_numeric($amount) || (float) $amount <= 0) {
                    $this->addError('ticketCostsByEmployee.'.$empId.'.amount', 'Uzupełnij koszt biletu.');

                    return;
                }
                $currency = strtoupper(trim((string) ($cost['currency'] ?? 'PLN')));
                if (strlen($currency) !== 3) {
                    $this->addError('ticketCostsByEmployee.'.$empId.'.currency', 'Waluta musi mieć dokładnie 3 znaki (np. PLN, EUR).');

                    return;
                }

                $attachment = $cost['attachment'] ?? null;
                if (empty($attachment)) {
                    $this->addError('ticketCostsByEmployee.'.$empId.'.attachment', 'Dodaj załącznik biletu.');

                    return;
                }

                $attachmentValidator = Validator::make(
                    ['attachment' => $attachment],
                    ['attachment' => 'file|max:10240'],
                    [
                        'attachment.file' => 'Załącznik musi być poprawnym plikiem.',
                        'attachment.max' => 'Załącznik może mieć maksymalnie 10 MB.',
                    ]
                );
                if ($attachmentValidator->fails()) {
                    foreach ($attachmentValidator->errors()->all() as $message) {
                        $this->addError('ticketCostsByEmployee.'.$empId.'.attachment', $message);
                    }

                    return;
                }
            }
        }

        $ticketCostsToSave = [];
        if ($this->isPublicTransport) {
            foreach ($this->selectedEmployeeIds as $empId) {
                $cost = $this->ticketCostsByEmployee[$empId] ?? [];
                $amount = $cost['amount'] ?? null;
                $currency = strtoupper(trim((string) ($cost['currency'] ?? 'PLN')));
                $attachment = $cost['attachment'] ?? null;
                $attachmentPath = null;
                if ($attachment) {
                    $attachmentPath = $attachment->store('transport_costs', 'public');
                }
                $ticketCostsToSave[$empId] = [
                    'amount' => $amount !== null && $amount !== '' ? (float) $amount : null,
                    'currency' => $currency,
                    'attachment_path' => $attachmentPath,
                ];
            }
        }

        // Store data in session for the controller to pick up
        Session::put('return_trip_v2_data', [
            'return_date' => $this->returnDate,
            'end_date' => $this->endDate ?: $this->returnDate,
            'vehicle_id' => $this->vehicleId ?: null,
            'employee_ids' => $this->selectedEmployeeIds,
            'notes' => $this->notes,
            'is_public_transport' => $this->isPublicTransport,
            'ticket_costs_per_employee' => $ticketCostsToSave,
            'start_airport_location_id' => $this->sharedStartAirportLocationId,
            'end_airport_location_id' => $this->sharedEndAirportLocationId,
            'vehicle_occupant_count' => $this->getVehicleOccupantCountForCapacity(),
        ]);

        $this->redirect(route('return-trips.store-v2'), navigate: false);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.return-trip-planner');
    }
}
