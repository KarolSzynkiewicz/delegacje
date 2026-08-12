<?php

namespace App\Services;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Models\Adjustment;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\LogisticsEventParticipant;
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
        protected LogisticsEventService $logisticsEventService
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
                app(LocationTrackingService::class)->getLocationStatus($employee, now());
            }

            return $result;
        });
    }
}
