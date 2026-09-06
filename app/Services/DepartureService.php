<?php

namespace App\Services;

use App\Enums\EmployeeLocationState;
use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Enums\VehiclePosition;
use App\Models\Accommodation;
use App\Models\Adjustment;
use App\Models\Employee;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\LogisticsEventParticipant;
use App\Models\Project;
use App\Models\Role;
use App\Models\TimeLog;
use App\Models\TransportCost;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Service for handling departures (wyjazdy) - employees going from base to project location.
 *
 * This service implements the domain model where Departure is a domain event
 * that records employees leaving base for a project location.
 */
class DepartureService
{
    public function __construct(
        protected LogisticsEventService $logisticsEventService,
        protected ProjectAssignmentService $projectAssignmentService,
        protected AccommodationAssignmentService $accommodationAssignmentService,
        protected VehicleAssignmentService $vehicleAssignmentService,
        protected LocationTrackingService $locationTrackingService
    ) {}

    /**
     * Commit the departure (create the logistics event).
     *
     * Creates a LogisticsEvent of type DEPARTURE with participants.
     *
     * @param  Carbon  $departureDate  Start of the trip
     * @param  Carbon  $endDate  End of the trip (when employees arrive at destination) - REQUIRED
     * @param  LogisticsEvent|null  $existingEvent  If provided, updates existing event instead of creating new one
     * @param  LogisticsEventStatus|null  $status  If provided, sets this status (only for updates)
     */
    public function commitDeparture(
        array $employeeIds,
        Carbon $departureDate,
        Carbon $endDate,
        int $toLocationId,
        ?int $vehicleId = null,
        ?string $notes = null,
        ?LogisticsEvent $existingEvent = null,
        ?LogisticsEventStatus $status = null
    ): LogisticsEvent {
        $baseLocation = Location::getBase();

        // Validate vehicle availability if vehicle is provided
        if ($vehicleId) {
            $vehicle = Vehicle::find($vehicleId);
            $this->logisticsEventService->validateVehicleAvailability(
                $vehicle,
                $departureDate,
                $endDate,
                $existingEvent?->id
            );
        }

        return DB::transaction(function () use ($employeeIds, $departureDate, $endDate, $toLocationId, $vehicleId, $notes, $baseLocation, $existingEvent, $status) {
            // Create or update LogisticsEvent as domain fact
            if ($existingEvent) {
                // Update existing event
                $event = $existingEvent;
                $updateData = [
                    'event_date' => $departureDate,
                    'end_date' => $endDate,
                    'vehicle_id' => $vehicleId,
                    'from_location_id' => $baseLocation->id,
                    'to_location_id' => $toLocationId,
                    'notes' => $notes,
                ];

                // Update status if provided
                if ($status !== null) {
                    $updateData['status'] = $status;
                }

                $event->update($updateData);

                // Delete old participants
                $event->participants()->delete();
            } else {
                // Create new event
                $event = LogisticsEvent::create([
                    'type' => LogisticsEventType::DEPARTURE,
                    'event_date' => $departureDate,
                    'end_date' => $endDate,
                    'has_transport' => false,
                    'vehicle_id' => $vehicleId,
                    'transport_id' => null,
                    'from_location_id' => $baseLocation->id,
                    'to_location_id' => $toLocationId,
                    'status' => $status ?? LogisticsEventStatus::PLANNED,
                    'notes' => $notes,
                    'created_by' => auth()->id() ?? 1,
                ]);
            }

            // Create participants (no assignments for departures - they're going TO projects)
            foreach ($employeeIds as $employeeId) {
                LogisticsEventParticipant::create([
                    'logistics_event_id' => $event->id,
                    'employee_id' => $employeeId,
                    'assignment_type' => null,
                    'assignment_id' => null,
                    'status' => 'pending',
                ]);
            }

            // Update vehicle location if vehicle specified
            if ($vehicleId) {
                $vehicle = Vehicle::find($vehicleId);
                if ($vehicle) {
                    $toLocation = Location::find($toLocationId);
                    if ($toLocation) {
                        $vehicle->update([
                            'current_location_id' => $toLocation->id,
                        ]);
                    }
                }
            }

            return $event;
        });
    }

    /**
     * Reverse a departure - clean up before editing.
     *
     * @param  LogisticsEvent  $departure  The departure to reverse
     */
    public function reverseDeparture(LogisticsEvent $departure): void
    {
        if ($departure->type !== LogisticsEventType::DEPARTURE) {
            throw new \InvalidArgumentException('Can only reverse departures.');
        }

        DB::transaction(function () use ($departure) {
            // Delete all participants (they will be recreated with new data)
            $departure->participants()->delete();
        });
    }

    /**
     * Transfer „lotnisko → domy” tworzony przy wyjeździe V2 (transport zbiorowy).
     * Preferuje {@see LogisticsEvent::$related_departure_id}; dla starych rekordów — notatka z ID wyjazdu.
     */
    public function findLinkedAirportTransfer(LogisticsEvent $departure, bool $onlyActive = true): ?LogisticsEvent
    {
        if ($departure->type !== LogisticsEventType::DEPARTURE) {
            return null;
        }

        $scoped = LogisticsEvent::query()
            ->where('type', LogisticsEventType::TRANSFER)
            ->when($onlyActive, fn ($q) => $q->whereIn('status', [LogisticsEventStatus::PLANNED, LogisticsEventStatus::COMPLETED]));

        $byFk = (clone $scoped)
            ->where('related_departure_id', $departure->id)
            ->orderByDesc('id')
            ->first();

        if ($byFk) {
            return $byFk;
        }

        return (clone $scoped)
            ->where('notes', 'like', '%wyjazdu #'.$departure->id.'%')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Aktywne transfery powiązane z wyjazdem (FK), z jednym rekordem „legacy” gdy brak FK.
     *
     * @return EloquentCollection<int, LogisticsEvent>
     */
    public function activeTransfersLinkedToDeparture(LogisticsEvent $departure): EloquentCollection
    {
        if ($departure->type !== LogisticsEventType::DEPARTURE) {
            return new EloquentCollection;
        }

        $linked = LogisticsEvent::query()
            ->where('type', LogisticsEventType::TRANSFER)
            ->where('related_departure_id', $departure->id)
            ->whereIn('status', [LogisticsEventStatus::PLANNED, LogisticsEventStatus::COMPLETED])
            ->orderBy('id')
            ->get();

        if ($linked->isNotEmpty()) {
            return $linked;
        }

        $legacy = $this->findLinkedAirportTransfer($departure, true);

        return $legacy ? new EloquentCollection([$legacy]) : new EloquentCollection;
    }

    /**
     * Usuwa z ewidencji koszty zgodnie z zaznaczeniami użytkownika.
     * Zawsze anuluje powiązane transfery, usuwa ich uczestników oraz
     * usuwa korekty (uznania/obciążenia) powiązane z wyjazdem i transferami,
     * o ile nie są już w rozliczeniu płac (payroll_id).
     *
     * @param  array{
     *     remove_fuel?: bool,
     *     remove_other_costs?: bool,
     *     remove_ticket_ids?: array<int>,
     * }  $selection
     * @return array{
     *     transport_costs_deleted: int,
     *     transfer_cancelled: bool,
     *     cancelled_transfers_count: int,
     *     adjustments_deleted: int,
     *     adjustments_skipped_payroll: int,
     * }
     */
    public function cancelDepartureLinkedTransferAndCosts(LogisticsEvent $departure, array $selection): array
    {
        $removeFuel = ! empty($selection['remove_fuel']);
        $removeOther = ! empty($selection['remove_other_costs']);
        $removeTicketIds = array_map('intval', $selection['remove_ticket_ids'] ?? []);

        $result = [
            'transport_costs_deleted' => 0,
            'transfer_cancelled' => false,
            'cancelled_transfers_count' => 0,
            'adjustments_deleted' => 0,
            'adjustments_skipped_payroll' => 0,
        ];

        $transfers = $this->activeTransfersLinkedToDeparture($departure);
        $eventIds = collect([$departure->id])->merge($transfers->pluck('id'))->unique()->values()->all();

        $allowedTicketIds = TransportCost::query()
            ->whereIn('logistics_event_id', $eventIds)
            ->where('cost_type', 'ticket')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $removeTicketIds = array_values(array_intersect($removeTicketIds, $allowedTicketIds));

        foreach (TransportCost::query()->whereIn('logistics_event_id', $eventIds)->get() as $cost) {
            $delete = false;
            if ($cost->cost_type === 'ticket') {
                $delete = in_array((int) $cost->id, $removeTicketIds, true);
            } elseif ($cost->cost_type === 'fuel') {
                $delete = $removeFuel;
            } elseif (in_array($cost->cost_type, ['parking', 'toll', 'other'], true)) {
                $delete = $removeOther;
            }

            if ($delete) {
                $cost->delete();
                $result['transport_costs_deleted']++;
            }
        }

        foreach (Adjustment::query()->where('logistics_event_id', $departure->id)->get() as $adjustment) {
            if ($adjustment->payroll_id !== null) {
                $result['adjustments_skipped_payroll']++;

                continue;
            }
            $adjustment->delete();
            $result['adjustments_deleted']++;
        }

        foreach ($transfers as $transfer) {
            foreach (Adjustment::query()->where('logistics_event_id', $transfer->id)->get() as $adjustment) {
                if ($adjustment->payroll_id !== null) {
                    $result['adjustments_skipped_payroll']++;

                    continue;
                }
                $adjustment->delete();
                $result['adjustments_deleted']++;
            }

            $transfer->update(['status' => LogisticsEventStatus::CANCELLED]);
            $transfer->load('participants');
            $transfer->participants->each->delete();
            $result['cancelled_transfers_count']++;
            $result['transfer_cancelled'] = true;
        }

        return $result;
    }

    /**
     * Wypisuje jednego uczestnika z wyjazdu (bez anulowania całego wyjazdu).
     *
     * Blokada: gdy na przypisaniach projektu z tego wyjazdu są już time_logs.
     * Blokada: nie można wypisać ostatniego uczestnika.
     *
     * @return array{
     *     project_assignments_deleted: int,
     *     vehicle_assignments_deleted: int,
     *     accommodation_assignments_deleted: int,
     *     tickets_deleted: int,
     *     adjustments_deleted: int,
     *     transfer_participants_removed: int,
     *     transfers_cancelled_empty: int,
     * }
     */
    public function removeParticipant(LogisticsEvent $departure, int $employeeId): array
    {
        if ($departure->type !== LogisticsEventType::DEPARTURE) {
            throw new \InvalidArgumentException('Zdarzenie nie jest wyjazdem.');
        }

        if (! in_array($departure->status, [LogisticsEventStatus::PLANNED, LogisticsEventStatus::COMPLETED], true)) {
            throw ValidationException::withMessages([
                'employee_id' => 'Można wypisać uczestnika tylko z aktywnego wyjazdu.',
            ]);
        }

        $participant = $departure->participants()->where('employee_id', $employeeId)->first();
        if (! $participant) {
            throw ValidationException::withMessages([
                'employee_id' => 'Ta osoba nie jest uczestnikiem tego wyjazdu.',
            ]);
        }

        if ($departure->participants()->count() <= 1) {
            throw ValidationException::withMessages([
                'employee_id' => 'Nie można wypisać ostatniego uczestnika — anuluj cały wyjazd.',
            ]);
        }

        $projectAssignmentIds = $departure->projectAssignments()
            ->where('employee_id', $employeeId)
            ->pluck('id');

        if (
            $projectAssignmentIds->isNotEmpty()
            && \App\Models\TimeLog::query()->whereIn('project_assignment_id', $projectAssignmentIds)->exists()
        ) {
            throw ValidationException::withMessages([
                'employee_id' => 'Nie można wypisać uczestnika: są już zarejestrowane godziny pracy na przypisaniu projektu z tego wyjazdu.',
            ]);
        }

        return DB::transaction(function () use ($departure, $employeeId, $participant) {
            $result = [
                'project_assignments_deleted' => 0,
                'vehicle_assignments_deleted' => 0,
                'accommodation_assignments_deleted' => 0,
                'tickets_deleted' => 0,
                'adjustments_deleted' => 0,
                'transfer_participants_removed' => 0,
                'transfers_cancelled_empty' => 0,
            ];

            $employee = \App\Models\Employee::find($employeeId);
            $employeeName = $employee?->full_name;

            $result['project_assignments_deleted'] = $departure->projectAssignments()
                ->where('employee_id', $employeeId)
                ->get()
                ->each->delete()
                ->count();

            $result['vehicle_assignments_deleted'] = $departure->vehicleAssignments()
                ->where('employee_id', $employeeId)
                ->get()
                ->each->delete()
                ->count();

            $result['accommodation_assignments_deleted'] = $departure->accommodationAssignments()
                ->where('employee_id', $employeeId)
                ->get()
                ->each->delete()
                ->count();

            if ($employeeName) {
                $tickets = TransportCost::query()
                    ->where('logistics_event_id', $departure->id)
                    ->where('cost_type', 'ticket')
                    ->where('description', 'like', '%'.$employeeName.'%')
                    ->get();
                foreach ($tickets as $ticket) {
                    $ticket->delete();
                    $result['tickets_deleted']++;
                }
            }

            foreach (
                Adjustment::query()
                    ->where('logistics_event_id', $departure->id)
                    ->where('employee_id', $employeeId)
                    ->where('type', 'bonus')
                    ->get() as $adj
            ) {
                if ($adj->payroll_id !== null) {
                    continue;
                }
                $adj->delete();
                $result['adjustments_deleted']++;
            }

            $transfers = $this->activeTransfersLinkedToDeparture($departure);
            foreach ($transfers as $transfer) {
                $tp = $transfer->participants()->where('employee_id', $employeeId)->get();
                foreach ($tp as $row) {
                    $row->delete();
                    $result['transfer_participants_removed']++;
                }

                if ($transfer->participants()->count() === 0) {
                    foreach (
                        Adjustment::query()
                            ->where('logistics_event_id', $transfer->id)
                            ->whereNull('payroll_id')
                            ->get() as $adj
                    ) {
                        $adj->delete();
                        $result['adjustments_deleted']++;
                    }
                    $transfer->update(['status' => LogisticsEventStatus::CANCELLED]);
                    $result['transfers_cancelled_empty']++;
                }
            }

            $participant->delete();

            if ($employee) {
                $this->locationTrackingService->getLocationStatus($employee, now());
            }

            return $result;
        });
    }

    /**
     * Dopisuje osobę do istniejącego wyjazdu (projekt + opcjonalnie dom / auto / bilet).
     *
     * @param  array{
     *     employee_id: int,
     *     project_id: int,
     *     role_id: int,
     *     project_start_date: string,
     *     project_end_date: string,
     *     accommodation_id?: int|null,
     *     accommodation_start_date?: string|null,
     *     accommodation_end_date?: string|null,
     *     vehicle_id?: int|null,
     *     vehicle_position?: string|null,
     *     vehicle_start_date?: string|null,
     *     vehicle_end_date?: string|null,
     *     ticket_amount?: float|string|null,
     *     ticket_currency?: string|null,
     * }  $data
     */
    public function addParticipant(LogisticsEvent $departure, array $data): LogisticsEventParticipant
    {
        $this->assertDepartureMutable($departure);

        $employee = Employee::find((int) ($data['employee_id'] ?? 0));
        if (! $employee) {
            throw ValidationException::withMessages([
                'employee_id' => 'Nie znaleziono pracownika.',
            ]);
        }

        if ($departure->participants()->where('employee_id', $employee->id)->exists()) {
            throw ValidationException::withMessages([
                'employee_id' => 'Ta osoba jest już uczestnikiem tego wyjazdu.',
            ]);
        }

        $location = $this->locationTrackingService->getLocationStatus($employee, $departure->event_date->copy()->startOfDay());
        if (($location['state'] ?? null) !== EmployeeLocationState::IN_BASE) {
            $label = $location['state'] instanceof EmployeeLocationState
                ? $location['state']->label()
                : 'nieznany';
            throw ValidationException::withMessages([
                'employee_id' => "Pracownik {$employee->full_name} nie jest w bazie w dniu wyjazdu ({$label}). Nie można dopisać go do wyjazdu.",
            ]);
        }

        if (empty($departure->vehicle_id)) {
            $amount = $data['ticket_amount'] ?? null;
            $currency = strtoupper(trim((string) ($data['ticket_currency'] ?? '')));
            if ($amount === null || $amount === '' || ! is_numeric($amount) || (float) $amount <= 0 || strlen($currency) !== 3) {
                throw ValidationException::withMessages([
                    'ticket_amount' => 'Przy transporcie publicznym uzupełnij koszt biletu i walutę (3 znaki).',
                ]);
            }
        }

        return DB::transaction(function () use ($departure, $data, $employee) {
            $participant = LogisticsEventParticipant::create([
                'logistics_event_id' => $departure->id,
                'employee_id' => $employee->id,
                'status' => 'pending',
            ]);

            $this->syncParticipantAssignments($departure, $employee, $data, null);

            $this->locationTrackingService->getLocationStatus($employee, now());

            return $participant;
        });
    }

    /**
     * Zmienia przypisania istniejącego uczestnika (projekt / dom / auto / bilet).
     *
     * @param  array{
     *     project_id: int,
     *     role_id: int,
     *     project_start_date: string,
     *     project_end_date: string,
     *     accommodation_id?: int|null,
     *     accommodation_start_date?: string|null,
     *     accommodation_end_date?: string|null,
     *     vehicle_id?: int|null,
     *     vehicle_position?: string|null,
     *     vehicle_start_date?: string|null,
     *     vehicle_end_date?: string|null,
     *     ticket_amount?: float|string|null,
     *     ticket_currency?: string|null,
     * }  $data
     */
    public function updateParticipantAssignments(LogisticsEvent $departure, int $employeeId, array $data): void
    {
        $this->assertDepartureMutable($departure);

        $participant = $departure->participants()->where('employee_id', $employeeId)->first();
        if (! $participant) {
            throw ValidationException::withMessages([
                'employee_id' => 'Ta osoba nie jest uczestnikiem tego wyjazdu.',
            ]);
        }

        $employee = Employee::find($employeeId);
        if (! $employee) {
            throw ValidationException::withMessages([
                'employee_id' => 'Nie znaleziono pracownika.',
            ]);
        }

        $existingProject = $departure->projectAssignments()->where('employee_id', $employeeId)->first();
        $projectLocked = $existingProject
            && TimeLog::query()->where('project_assignment_id', $existingProject->id)->exists();

        if ($projectLocked && $existingProject) {
            if ((int) ($data['project_id'] ?? 0) !== (int) $existingProject->project_id) {
                throw ValidationException::withMessages([
                    'project_id' => 'Nie można zmienić projektu: są już zarejestrowane godziny pracy na tym przypisaniu.',
                ]);
            }
        }

        DB::transaction(function () use ($departure, $employee, $data, $existingProject, $projectLocked) {
            $this->syncParticipantAssignments($departure, $employee, $data, $existingProject, $projectLocked);
            $this->locationTrackingService->getLocationStatus($employee, now());
        });
    }

    /**
     * Zapis z planera uczestnika: te same kroki 1–3 co przy tworzeniu wyjazdu,
     * ale bez ruszania dat / auta / trasy nagłówka.
     *
     * @param  array{
     *     assignment_ranges: array<int|string, array<string, mixed>>,
     *     accommodation_assignments?: array<int|string, array<string, mixed>>,
     *     vehicle_assignments?: array<int|string, array<string, mixed>>,
     *     ticket_costs_by_employee?: array<int|string, array<string, mixed>>,
     *     driver_employee_id?: int|null,
     * }  $state
     * @param  int|null  $lockEmployeeId  Edycja jednej osoby — ignoruj innych z formularza
     */
    public function applyPlannerParticipants(
        LogisticsEvent $departure,
        array $state,
        ?int $lockEmployeeId = null
    ): void {
        $this->assertDepartureMutable($departure);

        $ranges = is_array($state['assignment_ranges'] ?? null) ? $state['assignment_ranges'] : [];
        $byEmployee = [];
        foreach ($ranges as $range) {
            if (! is_array($range) || empty($range['employee_id']) || empty($range['project_id']) || empty($range['role_id']) || empty($range['start_date'])) {
                continue;
            }
            $eid = (int) $range['employee_id'];
            if ($lockEmployeeId && $eid !== $lockEmployeeId) {
                continue;
            }
            $byEmployee[$eid][] = $range;
        }

        if ($lockEmployeeId && empty($byEmployee[$lockEmployeeId])) {
            throw ValidationException::withMessages([
                'assignment_ranges' => 'Przypisz tę osobę do projektu (krok 1), zanim zapiszesz.',
            ]);
        }

        if ($byEmployee === []) {
            throw ValidationException::withMessages([
                'assignment_ranges' => 'Przypisz przynajmniej jedną osobę do projektu.',
            ]);
        }

        $accByEmployee = is_array($state['accommodation_assignments'] ?? null) ? $state['accommodation_assignments'] : [];
        $vehByEmployee = is_array($state['vehicle_assignments'] ?? null) ? $state['vehicle_assignments'] : [];
        $tickets = is_array($state['ticket_costs_by_employee'] ?? null) ? $state['ticket_costs_by_employee'] : [];
        $syncDriver = array_key_exists('driver_employee_id', $state);
        $driverEmployeeId = $syncDriver && $state['driver_employee_id'] !== null && $state['driver_employee_id'] !== ''
            ? (int) $state['driver_employee_id']
            : null;

        DB::transaction(function () use ($departure, $byEmployee, $accByEmployee, $vehByEmployee, $tickets, $syncDriver, $driverEmployeeId) {
            foreach ($byEmployee as $employeeId => $employeeRanges) {
                $employee = Employee::find($employeeId);
                if (! $employee) {
                    throw ValidationException::withMessages([
                        'employee_id' => 'Nie znaleziono pracownika #'.$employeeId.'.',
                    ]);
                }

                $isNew = ! $departure->participants()->where('employee_id', $employeeId)->exists();
                if ($isNew) {
                    $location = $this->locationTrackingService->getLocationStatus(
                        $employee,
                        $departure->event_date->copy()->startOfDay()
                    );
                    if (($location['state'] ?? null) !== EmployeeLocationState::IN_BASE) {
                        $label = $location['state'] instanceof EmployeeLocationState
                            ? $location['state']->label()
                            : 'nieznany';
                        throw ValidationException::withMessages([
                            'employee_id' => "Pracownik {$employee->full_name} nie jest w bazie w dniu wyjazdu ({$label}).",
                        ]);
                    }

                    LogisticsEventParticipant::create([
                        'logistics_event_id' => $departure->id,
                        'employee_id' => $employee->id,
                        'status' => 'pending',
                    ]);
                }

                $primary = $employeeRanges[0];
                $acc = $accByEmployee[$employeeId] ?? $accByEmployee[(string) $employeeId] ?? [];
                $veh = $vehByEmployee[$employeeId] ?? $vehByEmployee[(string) $employeeId] ?? [];
                $ticket = $tickets[$employeeId] ?? $tickets[(string) $employeeId] ?? [];

                $existingProject = $departure->projectAssignments()->where('employee_id', $employeeId)->first();
                if ($existingProject) {
                    foreach ($employeeRanges as $range) {
                        if ((int) $range['project_id'] === (int) $existingProject->project_id
                            && (int) $range['role_id'] === (int) $existingProject->role_id) {
                            $primary = $range;
                            break;
                        }
                    }
                }

                $payload = [
                    'project_id' => (int) $primary['project_id'],
                    'role_id' => (int) $primary['role_id'],
                    'project_start_date' => $primary['start_date'],
                    'project_end_date' => $primary['end_date'] ?? $primary['start_date'],
                    'accommodation_id' => ! empty($acc['accommodation_id']) ? (int) $acc['accommodation_id'] : null,
                    'accommodation_start_date' => $acc['start_date'] ?? null,
                    'accommodation_end_date' => $acc['end_date'] ?? null,
                    'vehicle_id' => ! empty($veh['vehicle_id']) ? (int) $veh['vehicle_id'] : null,
                    'vehicle_position' => $veh['position'] ?? 'passenger',
                    'vehicle_start_date' => $veh['start_date'] ?? null,
                    'vehicle_end_date' => $veh['end_date'] ?? null,
                    'ticket_amount' => $ticket['amount'] ?? null,
                    'ticket_currency' => $ticket['currency'] ?? null,
                ];

                if ($isNew && empty($departure->vehicle_id)) {
                    $amount = $payload['ticket_amount'];
                    $currency = strtoupper(trim((string) ($payload['ticket_currency'] ?? '')));
                    if ($amount === null || $amount === '' || ! is_numeric($amount) || (float) $amount <= 0 || strlen($currency) !== 3) {
                        throw ValidationException::withMessages([
                            'ticket_amount' => 'Przy transporcie publicznym uzupełnij koszt biletu i walutę dla '.$employee->full_name.'.',
                        ]);
                    }
                }

                $projectLocked = $existingProject
                    && TimeLog::query()->where('project_assignment_id', $existingProject->id)->exists();

                if ($projectLocked && $existingProject && (int) $payload['project_id'] !== (int) $existingProject->project_id) {
                    throw ValidationException::withMessages([
                        'project_id' => 'Nie można zmienić projektu dla '.$employee->full_name.': są już zarejestrowane godziny pracy.',
                    ]);
                }

                $this->syncParticipantAssignments($departure, $employee, $payload, $existingProject, $projectLocked);
                $this->syncExtraProjectRanges($departure, $employee, $employeeRanges, $primary);
                $this->locationTrackingService->getLocationStatus($employee, now());
            }

            if ($syncDriver && ! empty($departure->vehicle_id)) {
                $this->syncDepartureVehicleDriver($departure, $driverEmployeeId);
            }
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $ranges
     * @param  array<string, mixed>  $primaryRange
     */
    protected function syncExtraProjectRanges(
        LogisticsEvent $departure,
        Employee $employee,
        array $ranges,
        array $primaryRange
    ): void {
        $primaryKey = (int) $primaryRange['project_id'].'_'.(int) $primaryRange['role_id'];
        $keepKeys = [];

        foreach ($ranges as $range) {
            $key = (int) $range['project_id'].'_'.(int) $range['role_id'];
            $keepKeys[$key] = true;
            if ($key === $primaryKey) {
                continue;
            }

            $existing = $departure->projectAssignments()
                ->where('employee_id', $employee->id)
                ->where('project_id', (int) $range['project_id'])
                ->where('role_id', (int) $range['role_id'])
                ->first();

            $start = Carbon::parse($range['start_date'])->startOfDay();
            $end = Carbon::parse($range['end_date'] ?? $range['start_date'])->startOfDay();
            $project = Project::find((int) $range['project_id']);
            $role = Role::find((int) $range['role_id']);
            if (! $project || ! $role) {
                continue;
            }

            if ($existing) {
                $this->projectAssignmentService->updateAssignment($existing, $project, $employee, $role, $start, $end);
            } else {
                $this->projectAssignmentService->createAssignment(
                    $project,
                    $employee,
                    $role,
                    $start,
                    $end,
                    null,
                    $departure->id
                );
            }
        }

        foreach ($departure->projectAssignments()->where('employee_id', $employee->id)->get() as $assignment) {
            $key = (int) $assignment->project_id.'_'.(int) $assignment->role_id;
            if (isset($keepKeys[$key])) {
                continue;
            }
            if (TimeLog::query()->where('project_assignment_id', $assignment->id)->exists()) {
                throw ValidationException::withMessages([
                    'project_id' => 'Nie można usunąć przypisania projektu dla '.$employee->full_name.': są godziny pracy.',
                ]);
            }
            $assignment->delete();
        }
    }

    /**
     * Kierowca auta wyjazdu = nieopłacone uznanie (bonus) na tym evencie.
     * null = kierowca zewnętrzny (usuń nieopłacone uznania za kierowanie).
     */
    protected function syncDepartureVehicleDriver(LogisticsEvent $departure, ?int $driverEmployeeId): void
    {
        if ($driverEmployeeId) {
            $onTrip = $departure->participants()->where('employee_id', $driverEmployeeId)->exists();
            if (! $onTrip) {
                throw ValidationException::withMessages([
                    'driver_employee_id' => 'Kierowca musi być uczestnikiem tego wyjazdu.',
                ]);
            }
        }

        $notes = 'Uznanie za kierowanie pojazdem wyjazdu #'.$departure->id;
        $bonuses = Adjustment::query()
            ->where('logistics_event_id', $departure->id)
            ->where('type', 'bonus')
            ->where(function ($q) use ($notes) {
                $q->where('notes', $notes)
                    ->orWhere('notes', 'like', 'Uznanie za kierowanie pojazdem wyjazdu%');
            })
            ->orderBy('id')
            ->get();

        if ($bonuses->isEmpty()) {
            $bonuses = Adjustment::query()
                ->where('logistics_event_id', $departure->id)
                ->where('type', 'bonus')
                ->orderBy('id')
                ->get();
        }

        if ($driverEmployeeId === null) {
            foreach ($bonuses as $adj) {
                if ($adj->payroll_id !== null) {
                    throw ValidationException::withMessages([
                        'driver_employee_id' => 'Nie można wrócić do kierowcy zewnętrznego: uznanie za kierowanie jest już na liście płac.',
                    ]);
                }
                $adj->delete();
            }

            return;
        }

        $lockedOther = $bonuses->first(
            fn ($adj) => $adj->payroll_id !== null && (int) $adj->employee_id !== $driverEmployeeId
        );
        if ($lockedOther) {
            throw ValidationException::withMessages([
                'driver_employee_id' => 'Nie można zmienić kierowcy: uznanie za kierowanie jest już na liście płac.',
            ]);
        }

        $keep = $bonuses->first(fn ($adj) => (int) $adj->employee_id === $driverEmployeeId)
            ?? $bonuses->first(fn ($adj) => $adj->payroll_id === null);

        if ($keep) {
            if ((int) $keep->employee_id !== $driverEmployeeId) {
                $keep->update(['employee_id' => $driverEmployeeId]);
            }
            foreach ($bonuses as $adj) {
                if ((int) $adj->id !== (int) $keep->id && $adj->payroll_id === null) {
                    $adj->delete();
                }
            }

            return;
        }

        Adjustment::create([
            'employee_id' => $driverEmployeeId,
            'payroll_id' => null,
            'logistics_event_id' => $departure->id,
            'type' => 'bonus',
            'amount' => 0,
            'currency' => 'PLN',
            'notes' => $notes,
            'date' => $departure->event_date->toDateString(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function syncParticipantAssignments(
        LogisticsEvent $departure,
        Employee $employee,
        array $data,
        $existingProject = null,
        bool $projectLocked = false
    ): void {
        $project = Project::find((int) ($data['project_id'] ?? 0));
        $role = Role::find((int) ($data['role_id'] ?? 0));
        if (! $project || ! $role) {
            throw ValidationException::withMessages([
                'project_id' => 'Wybierz projekt i rolę.',
            ]);
        }

        $projectStart = Carbon::parse($data['project_start_date'])->startOfDay();
        $projectEnd = Carbon::parse($data['project_end_date'])->startOfDay();

        if ($existingProject) {
            if ($projectLocked) {
                $this->projectAssignmentService->validateNoTimeLogsOutsideRange($existingProject, $projectStart, $projectEnd);
            }
            $this->projectAssignmentService->updateAssignment(
                $existingProject,
                $project,
                $employee,
                $role,
                $projectStart,
                $projectEnd
            );
        } else {
            $this->projectAssignmentService->createAssignment(
                $project,
                $employee,
                $role,
                $projectStart,
                $projectEnd,
                null,
                $departure->id
            );
        }

        $accommodationId = ! empty($data['accommodation_id']) ? (int) $data['accommodation_id'] : null;
        if ($accommodationId) {
            $accommodation = Accommodation::find($accommodationId);
            if (! $accommodation) {
                throw ValidationException::withMessages([
                    'accommodation_id' => 'Nie znaleziono mieszkania.',
                ]);
            }
            $accStart = Carbon::parse($data['accommodation_start_date'] ?? $data['project_start_date'])->startOfDay();
            $accEnd = Carbon::parse($data['accommodation_end_date'] ?? $data['project_end_date'])->startOfDay();
            $existingAcc = $departure->accommodationAssignments()->where('employee_id', $employee->id)->first();
            if ($existingAcc) {
                $this->accommodationAssignmentService->updateAssignment($existingAcc, $accommodation, $accStart, $accEnd);
            } else {
                $this->accommodationAssignmentService->createAssignment(
                    $employee,
                    $accommodation,
                    $accStart,
                    $accEnd,
                    null,
                    $departure->id,
                    $departure->end_date?->copy()->startOfDay()
                );
            }
        }

        $vehicleId = ! empty($data['vehicle_id']) ? (int) $data['vehicle_id'] : null;
        if ($vehicleId) {
            $vehicle = Vehicle::find($vehicleId);
            if (! $vehicle) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'Nie znaleziono pojazdu.',
                ]);
            }
            $position = VehiclePosition::tryFrom((string) ($data['vehicle_position'] ?? 'passenger'))
                ?? VehiclePosition::PASSENGER;
            $vehStart = Carbon::parse($data['vehicle_start_date'] ?? $data['project_start_date'])->startOfDay();
            $vehEnd = Carbon::parse($data['vehicle_end_date'] ?? $data['project_end_date'])->startOfDay();
            $existingVeh = $departure->vehicleAssignments()->where('employee_id', $employee->id)->first();
            if ($existingVeh) {
                $this->vehicleAssignmentService->updateAssignment($existingVeh, $vehicle, $position, $vehStart, $vehEnd);
            } else {
                $this->vehicleAssignmentService->createAssignment(
                    $employee,
                    $vehicle,
                    $position,
                    $vehStart,
                    $vehEnd,
                    null,
                    $departure->id
                );
            }
        }

        if (empty($departure->vehicle_id)) {
            $amount = $data['ticket_amount'] ?? null;
            $currency = strtoupper(trim((string) ($data['ticket_currency'] ?? '')));
            if ($amount !== null && $amount !== '' && is_numeric($amount) && (float) $amount > 0 && strlen($currency) === 3) {
                $this->upsertParticipantTicket($departure, $employee, (float) $amount, $currency);
            }
        }
    }

    protected function upsertParticipantTicket(
        LogisticsEvent $departure,
        Employee $employee,
        float $amount,
        string $currency
    ): void {
        $ticket = TransportCost::query()
            ->where('logistics_event_id', $departure->id)
            ->where('cost_type', 'ticket')
            ->where('description', 'like', '%'.$employee->full_name.'%')
            ->orderBy('id')
            ->first();

        $payload = [
            'amount' => $amount,
            'currency' => $currency,
            'cost_date' => $departure->event_date->toDateString(),
            'description' => 'Bilet - '.$employee->full_name,
        ];

        if ($ticket) {
            $ticket->update($payload);

            return;
        }

        TransportCost::create(array_merge($payload, [
            'logistics_event_id' => $departure->id,
            'cost_type' => 'ticket',
            'vehicle_id' => null,
            'transport_id' => null,
            'created_by' => auth()->id() ?? 1,
        ]));
    }

    protected function assertDepartureMutable(LogisticsEvent $departure): void
    {
        if ($departure->type !== LogisticsEventType::DEPARTURE) {
            throw new \InvalidArgumentException('Zdarzenie nie jest wyjazdem.');
        }

        if (! in_array($departure->status, [LogisticsEventStatus::PLANNED, LogisticsEventStatus::COMPLETED], true)) {
            throw ValidationException::withMessages([
                'departure' => 'Można edytować tylko aktywny wyjazd (oczekuje na przypisanie albo przypisany).',
            ]);
        }
    }
}
