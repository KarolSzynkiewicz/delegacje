<?php

namespace App\Services;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Enums\VehiclePosition;
use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\LogisticsEvent;
use App\Models\LogisticsEventParticipant;
use App\Models\ProjectAssignment;
use App\Models\Role;
use App\Models\TransportCost;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TransferService
{
    public function __construct(
        protected AssignmentQueryService $assignmentQueryService,
        protected VehicleAssignmentService $vehicleAssignmentService,
        protected LocationTrackingService $locationTracking
    ) {}

    /**
     * Execute a transfer with optional reassignment of project/accommodation.
     *
     * @param  array{
     *     employee_ids: int[],
     *     from_location_id: int,
     *     to_location_id: int,
     *     transfer_date: Carbon,
     *     vehicle_id: int|null,
     *     notes: string|null,
     *     route_distance: float|null,
     *     route_duration: int|null,
     *     route_waypoints: int[]|null,
     *     has_reassignment: bool,
     *     reassignments: array<int, array{project_id: int|null, accommodation_id: int|null, vehicle_id: int|null, vehicle_position?: string, role_id?: int, start_date: string, end_date: string|null, keep_current?: bool, source_project_assignment_id?: int, skip_old_accommodation_shorten?: bool, skip_old_vehicle_shorten?: bool}>,
     *     driver_employee_id: int|null,
     *     driver_payment_amount: float|null,
     *     driver_payment_currency: string|null,
     *     driver_payroll_id: int|null,
     *     location_stop_notes?: array<string, string>|null,
     *     public_ticket_lines?: list<array{employee_id: int, amount: float, currency: string, attachment_path?: string|null, description?: string, notes?: string|null}>,
     *     related_departure_id?: int|null,
     * }  $data
     */
    public function commitTransfer(array $data): LogisticsEvent
    {
        return DB::transaction(function () use ($data) {
            $transferDate = $data['transfer_date'] instanceof Carbon
                ? $data['transfer_date']
                : Carbon::parse($data['transfer_date']);

            // Ze zmianą przypisań: uczestnik musi być już poza bazą (inaczej wyjazd/zjazd).
            // Bez zmiany przypisań: dozwolone osoby w bazie (np. podjazd i powrót bez zmiany stanu).
            if ($data['has_reassignment']) {
                foreach ($data['employee_ids'] as $employeeId) {
                    $employee = Employee::findOrFail($employeeId);
                    if (! $this->locationTracking->isEmployeeEligibleForTransfer($employee, $transferDate)) {
                        throw ValidationException::withMessages([
                            'selectedEmployeeIds' => "Pracownik {$employee->full_name} jest aktualnie w lokalizacji bazy (siedziba) w dniu transferu — użyj wyjazdu lub zjazdu, nie transferu.",
                        ]);
                    }
                }
            }

            $relatedDepartureId = isset($data['related_departure_id']) ? (int) $data['related_departure_id'] : null;
            if ($relatedDepartureId !== null && $relatedDepartureId <= 0) {
                $relatedDepartureId = null;
            }
            if ($relatedDepartureId !== null) {
                $relatedExists = LogisticsEvent::query()
                    ->whereKey($relatedDepartureId)
                    ->where(function ($q) {
                        $q->where('type', LogisticsEventType::DEPARTURE)
                            ->orWhere(function ($q2) {
                                $q2->where('type', LogisticsEventType::TRANSFER)
                                    ->where('has_reassignment', true);
                            });
                    })
                    ->exists();
                if (! $relatedExists) {
                    throw ValidationException::withMessages([
                        'related_departure_id' => 'Wybrane zdarzenie nadrzędne (wyjazd lub plan zmian) nie istnieje.',
                    ]);
                }
            }

            $eventAttributes = [
                'type' => LogisticsEventType::TRANSFER,
                'event_date' => $data['transfer_date'],
                'end_date' => $data['transfer_date'],
                'from_location_id' => $data['from_location_id'],
                'to_location_id' => $data['to_location_id'],
                'vehicle_id' => $data['vehicle_id'],
                'has_transport' => ! $data['has_reassignment'] && empty($data['vehicle_id']),
                'status' => LogisticsEventStatus::PLANNED,
                'has_reassignment' => $data['has_reassignment'],
                'notes' => $data['notes'],
                'route_distance' => $data['route_distance'],
                'route_duration' => $data['route_duration'],
                'route_waypoints' => $data['route_waypoints'],
                'created_by' => auth()->id() ?? 1,
                'related_departure_id' => $relatedDepartureId,
            ];

            $locationStopNotes = $data['location_stop_notes'] ?? null;
            if (
                Schema::hasColumn('logistics_events', 'location_stop_notes')
                && is_array($locationStopNotes)
                && $locationStopNotes !== []
            ) {
                $eventAttributes['location_stop_notes'] = $locationStopNotes;
            }

            $event = LogisticsEvent::create($eventAttributes);

            foreach ($data['employee_ids'] as $employeeId) {
                if ($data['has_reassignment']) {
                    $this->processReassignment(
                        $event,
                        $employeeId,
                        $transferDate,
                        $data['reassignments'][$employeeId] ?? []
                    );
                } else {
                    LogisticsEventParticipant::create([
                        'logistics_event_id' => $event->id,
                        'employee_id' => $employeeId,
                        'status' => 'pending',
                    ]);
                }
            }

            if (
                ($data['driver_employee_id'] ?? null)
                && ($data['driver_payment_amount'] ?? 0) > 0
            ) {
                \App\Models\Adjustment::create([
                    'employee_id' => $data['driver_employee_id'],
                    'logistics_event_id' => $event->id,
                    'payroll_id' => $data['driver_payroll_id'] ?: null,
                    'amount' => $data['driver_payment_amount'],
                    'currency' => $data['driver_payment_currency'] ?? 'PLN',
                    'type' => 'bonus',
                    'date' => $transferDate->toDateString(),
                    'notes' => 'Wynagrodzenie za transfer',
                ]);
            }

            $ticketLines = $data['public_ticket_lines'] ?? [];
            if (is_array($ticketLines) && $ticketLines !== []) {
                foreach ($ticketLines as $line) {
                    $employeeId = (int) ($line['employee_id'] ?? 0);
                    if ($employeeId <= 0) {
                        continue;
                    }
                    $amount = (float) ($line['amount'] ?? 0);
                    if ($amount <= 0) {
                        continue;
                    }
                    TransportCost::create([
                        'logistics_event_id' => $event->id,
                        'vehicle_id' => null,
                        'transport_id' => null,
                        'cost_type' => 'ticket',
                        'amount' => $amount,
                        'currency' => strtoupper((string) ($line['currency'] ?? 'PLN')),
                        'cost_date' => $transferDate->toDateString(),
                        'description' => (string) ($line['description'] ?? 'Bilet'),
                        'file_path' => $line['attachment_path'] ?? null,
                        'notes' => isset($line['notes']) && is_string($line['notes']) && $line['notes'] !== '' ? $line['notes'] : null,
                        'created_by' => auth()->id() ?? 1,
                    ]);
                }
            }

            return $event;
        });
    }

    /**
     * Daty nowego przypisania: nie wcześniej niż dzień transferu, koniec nie przed startem.
     *
     * @return array{0: Carbon, 1: Carbon|null}
     */
    protected function assignmentWindow(Carbon $transferDay, ?string $start, ?string $end): array
    {
        $selectedStart = DateRangeService::normalizeDate($start ?? $transferDay);
        $rangeStart = $selectedStart->lt($transferDay) ? $transferDay->copy() : $selectedStart->copy();
        $rangeEnd = ($end !== null && $end !== '')
            ? DateRangeService::normalizeDate($end)
            : null;
        if ($rangeEnd !== null && $rangeEnd->lt($rangeStart)) {
            $rangeEnd = $rangeStart->copy();
        }

        return [$rangeStart, $rangeEnd];
    }

    protected function projectAssignmentSnapshot(ProjectAssignment $assignment): array
    {
        return [
            'project_id' => (int) $assignment->project_id,
            'employee_id' => (int) $assignment->employee_id,
            'role_id' => (int) $assignment->role_id,
            'start_date' => DateRangeService::normalizeDate($assignment->start_date)->format('Y-m-d'),
            'end_date' => $assignment->end_date
                ? DateRangeService::normalizeDate($assignment->end_date)->format('Y-m-d')
                : null,
            'notes' => $assignment->notes,
            'logistics_event_id' => $assignment->logistics_event_id,
        ];
    }

    protected function accommodationAssignmentSnapshot(AccommodationAssignment $assignment): array
    {
        return [
            'accommodation_id' => (int) $assignment->accommodation_id,
            'employee_id' => (int) $assignment->employee_id,
            'start_date' => DateRangeService::normalizeDate($assignment->start_date)->format('Y-m-d'),
            'end_date' => $assignment->end_date
                ? DateRangeService::normalizeDate($assignment->end_date)->format('Y-m-d')
                : null,
            'notes' => $assignment->notes,
            'logistics_event_id' => $assignment->logistics_event_id,
        ];
    }

    protected function vehicleAssignmentSnapshot(VehicleAssignment $assignment): array
    {
        return [
            'vehicle_id' => (int) $assignment->vehicle_id,
            'employee_id' => (int) $assignment->employee_id,
            'position' => $assignment->position instanceof VehiclePosition ? $assignment->position->value : $assignment->position,
            'start_date' => DateRangeService::normalizeDate($assignment->start_date)->format('Y-m-d'),
            'end_date' => $assignment->end_date
                ? DateRangeService::normalizeDate($assignment->end_date)->format('Y-m-d')
                : null,
            'notes' => $assignment->notes,
            'is_return_trip' => false,
            'logistics_event_id' => $assignment->logistics_event_id,
        ];
    }

    /**
     * Zamyka każde przypisanie pracownika nachodzące na nowy okres.
     * Wiersz zaczynający się wcześniej jest skracany do dnia przed startem.
     * Wiersz, który zaczyna się już w nowym okresie — także ręcznie przestawiony —
     * jest usuwany i zapisywany na uczestniku, żeby anulowanie mogło go odtworzyć.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     * @param  callable(\Illuminate\Database\Eloquent\Model): array<string, mixed>  $snapshot
     * @param  (callable(\Illuminate\Database\Eloquent\Builder): void)|null  $constrain
     */
    protected function retireOverlappingAssignments(
        string $modelClass,
        string $assignmentType,
        string $payloadKey,
        int $employeeId,
        LogisticsEvent $event,
        Carbon $rangeStart,
        ?Carbon $rangeEnd,
        callable $snapshot,
        ?callable $constrain = null,
    ): ?int {
        $query = $modelClass::query()
            ->where('employee_id', $employeeId)
            ->where(function ($inner) use ($rangeStart) {
                $inner->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $rangeStart->toDateString());
            });

        if ($rangeEnd !== null) {
            $query->whereDate('start_date', '<=', $rangeEnd->toDateString());
        }

        if ($constrain !== null) {
            $constrain($query);
        }

        $cut = $rangeStart->copy()->subDay();
        $deletedRoleId = null;

        foreach ($query->orderBy('start_date')->orderBy('id')->get() as $assignment) {
            $oldStart = DateRangeService::normalizeDate($assignment->start_date);

            if ($cut->lt($oldStart)) {
                $candidateRole = $assignment->getAttribute('role_id');
                if ($deletedRoleId === null && $candidateRole) {
                    $deletedRoleId = (int) $candidateRole;
                }

                $payload = [$payloadKey => $snapshot($assignment)];
                $assignment->delete();

                LogisticsEventParticipant::create([
                    'logistics_event_id' => $event->id,
                    'employee_id' => $employeeId,
                    'assignment_type' => $assignmentType,
                    'assignment_id' => null,
                    'original_end_date' => null,
                    'restoration_payload' => $payload,
                    'status' => 'pending',
                ]);

                continue;
            }

            $originalEnd = $assignment->end_date;
            $assignment->update(['end_date' => $cut->toDateString()]);

            LogisticsEventParticipant::create([
                'logistics_event_id' => $event->id,
                'employee_id' => $employeeId,
                'assignment_type' => $assignmentType,
                'assignment_id' => $assignment->id,
                'original_end_date' => $originalEnd
                    ? DateRangeService::normalizeDate($originalEnd)->format('Y-m-d')
                    : null,
                'status' => 'pending',
            ]);
        }

        return $deletedRoleId;
    }

    /**
     * Close old assignments and create new ones for a single employee.
     */
    protected function processReassignment(
        LogisticsEvent $event,
        int $employeeId,
        Carbon $transferDate,
        array $reassignment
    ): void {
        $keepCurrent = $reassignment['keep_current'] ?? false;

        if ($keepCurrent) {
            LogisticsEventParticipant::create([
                'logistics_event_id' => $event->id,
                'employee_id' => $employeeId,
                'status' => 'pending',
            ]);

            return;
        }

        $skipOldAccommodationShorten = ! empty($reassignment['skip_old_accommodation_shorten']);
        $skipOldVehicleShorten = ! empty($reassignment['skip_old_vehicle_shorten']);

        $sourceRoleId = null;
        if (! empty($reassignment['source_project_assignment_id'])) {
            $sourceProject = ProjectAssignment::query()->find((int) $reassignment['source_project_assignment_id']);
            if ($sourceProject && (int) $sourceProject->employee_id === $employeeId) {
                $sourceRoleId = (int) $sourceProject->role_id;
            }
        }

        $transferDay = DateRangeService::normalizeDate($transferDate);
        [$projectStart, $projectEnd] = $this->assignmentWindow(
            $transferDay,
            $reassignment['start_date'] ?? null,
            $reassignment['end_date'] ?? null,
        );
        $hasOwnAccommodationDates = ! empty($reassignment['accommodation_start_date']);
        [$accommodationStart, $accommodationEnd] = $this->assignmentWindow(
            $transferDay,
            $hasOwnAccommodationDates ? $reassignment['accommodation_start_date'] : ($reassignment['start_date'] ?? null),
            $hasOwnAccommodationDates ? ($reassignment['accommodation_end_date'] ?? null) : ($reassignment['end_date'] ?? null),
        );
        $hasOwnVehicleDates = ! empty($reassignment['vehicle_start_date']);
        [$vehicleStart, $vehicleEnd] = $this->assignmentWindow(
            $transferDay,
            $hasOwnVehicleDates ? $reassignment['vehicle_start_date'] : ($reassignment['start_date'] ?? null),
            $hasOwnVehicleDates ? ($reassignment['vehicle_end_date'] ?? null) : ($reassignment['end_date'] ?? null),
        );

        $newProjectId = $reassignment['project_id'] ?? null;
        $newAccommodationId = $reassignment['accommodation_id'] ?? null;
        $newVehicleId = $reassignment['vehicle_id'] ?? null;
        $newVehiclePosition = $reassignment['vehicle_position'] ?? VehiclePosition::PASSENGER->value;
        $newProjectStartDate = $projectStart->format('Y-m-d');
        $endDate = $projectEnd?->format('Y-m-d');

        $deletedOldProjectRoleId = null;
        if ($newProjectId) {
            $deletedOldProjectRoleId = $this->retireOverlappingAssignments(
                ProjectAssignment::class,
                'project_assignment',
                'project_assignment',
                $employeeId,
                $event,
                $transferDay,
                $transferDay,
                $this->projectAssignmentSnapshot(...),
            );
            $deletedInRangeRoleId = $this->retireOverlappingAssignments(
                ProjectAssignment::class,
                'project_assignment',
                'project_assignment',
                $employeeId,
                $event,
                $projectStart,
                $projectEnd,
                $this->projectAssignmentSnapshot(...),
            );
            $deletedOldProjectRoleId ??= $deletedInRangeRoleId;
        }

        if (! $skipOldAccommodationShorten) {
            $this->retireOverlappingAssignments(
                AccommodationAssignment::class,
                'accommodation_assignment',
                'accommodation_assignment',
                $employeeId,
                $event,
                $transferDay,
                $transferDay,
                $this->accommodationAssignmentSnapshot(...),
            );
            $this->retireOverlappingAssignments(
                AccommodationAssignment::class,
                'accommodation_assignment',
                'accommodation_assignment',
                $employeeId,
                $event,
                $accommodationStart,
                $accommodationEnd,
                $this->accommodationAssignmentSnapshot(...),
            );
        }

        if (! $skipOldVehicleShorten) {
            $vehicleSnapshot = $this->vehicleAssignmentSnapshot(...);
            $onlyOutbound = fn ($query) => $query->where('is_return_trip', false);
            $this->retireOverlappingAssignments(
                VehicleAssignment::class,
                'vehicle_assignment',
                'vehicle_assignment',
                $employeeId,
                $event,
                $transferDay,
                $transferDay,
                $vehicleSnapshot,
                $onlyOutbound,
            );
            $this->retireOverlappingAssignments(
                VehicleAssignment::class,
                'vehicle_assignment',
                'vehicle_assignment',
                $employeeId,
                $event,
                $vehicleStart,
                $vehicleEnd,
                $vehicleSnapshot,
                $onlyOutbound,
            );
        }

        if ($newProjectId) {
            $roleId = ! empty($reassignment['role_id'])
                ? (int) $reassignment['role_id']
                : ($sourceRoleId
                    ?? $deletedOldProjectRoleId
                    ?? Employee::find($employeeId)?->roles()->orderBy('id')->first()?->id
                    ?? Role::query()->orderBy('id')->value('id'));

            if (! $roleId) {
                throw new \InvalidArgumentException(
                    'Nie można utworzyć przypisania do projektu: brak roli (role_id). Uzupełnij role pracownika lub role w systemie.'
                );
            }

            $newProject = ProjectAssignment::create([
                'project_id' => $newProjectId,
                'employee_id' => $employeeId,
                'role_id' => $roleId,
                'start_date' => $newProjectStartDate,
                'end_date' => $endDate,
                'logistics_event_id' => $event->id,
                'notes' => 'Przeniesienie (transfer #'.$event->id.')',
            ]);

            LogisticsEventParticipant::create([
                'logistics_event_id' => $event->id,
                'employee_id' => $employeeId,
                'assignment_type' => 'project_assignment',
                'assignment_id' => $newProject->id,
                'original_end_date' => null,
                'status' => 'pending',
            ]);
        }

        if ($newAccommodationId) {
            $newAccommodation = AccommodationAssignment::create([
                'accommodation_id' => $newAccommodationId,
                'employee_id' => $employeeId,
                'start_date' => $accommodationStart->format('Y-m-d'),
                'end_date' => $accommodationEnd?->format('Y-m-d'),
                'logistics_event_id' => $event->id,
                'notes' => 'Przeniesienie (transfer #'.$event->id.')',
            ]);

            LogisticsEventParticipant::create([
                'logistics_event_id' => $event->id,
                'employee_id' => $employeeId,
                'assignment_type' => 'accommodation_assignment',
                'assignment_id' => $newAccommodation->id,
                'original_end_date' => null,
                'status' => 'pending',
            ]);
        }

        if ($newVehicleId) {
            $employee = Employee::findOrFail($employeeId);
            $vehicle = Vehicle::findOrFail((int) $newVehicleId);
            $position = VehiclePosition::from((string) $newVehiclePosition);
            $start = $vehicleStart->copy();
            $end = $vehicleEnd?->copy();

            $newVehicle = $this->vehicleAssignmentService->createAssignment(
                $employee,
                $vehicle,
                $position,
                $start,
                $end,
                'Przeniesienie (transfer #'.$event->id.')',
                $event->id
            );

            LogisticsEventParticipant::create([
                'logistics_event_id' => $event->id,
                'employee_id' => $employeeId,
                'assignment_type' => 'vehicle_assignment',
                'assignment_id' => $newVehicle->id,
                'original_end_date' => null,
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Reverse a transfer's reassignment effects.
     *
     * Inaczej niż przy anulowaniu zjazdu/powrotu: transfer mógł utworzyć **nowe**
     * przypisania (inny projekt, mieszkanie, auto), nie tylko skrócić end_date.
     *
     * - Przypisania utworzone przy tym transferze (`logistics_event_id` = ten event)
     *   są usuwane w całości.
     * - Wcześniejsze przypisania tylko skrócone (np. projekt: dzień przed transferem) są przywracane:
     *   `end_date` = `original_end_date` uczestnika (w tym `null`, gdy wcześniej było bez końca).
     * - Przypisanie do projektu / mieszkania / pojazdu usunięte zamiast skrócenia (start w dniu transferu)
     *   jest odtwarzane z `restoration_payload` na uczestniku (`project_assignment`, `accommodation_assignment`, `vehicle_assignment`).
     *
     * Rozróżnienie po `logistics_event_id` jest konieczne, bo przy skróceniu otwartego
     * przypisania `original_end_date` na uczestniku też jest null — wtedy błędna logika
     * „brak original_end_date => usuń przypisanie” kasowałaby stary wiersz zamiast przywrócić null.
     */
    public function reverseTransfer(LogisticsEvent $transfer): void
    {
        if ($transfer->type !== LogisticsEventType::TRANSFER) {
            throw new \InvalidArgumentException('Can only reverse transfers.');
        }

        if (! $transfer->has_reassignment) {
            $transfer->participants()->delete();

            return;
        }

        DB::transaction(function () use ($transfer) {
            $participants = $transfer->participants()->with('assignment')->get();
            $transferId = (int) $transfer->id;

            foreach ($participants as $participant) {
                if ($participant->restoration_payload) {
                    continue;
                }

                if (! $participant->assignment_type || ! $participant->assignment_id) {
                    continue;
                }

                $assignment = $participant->assignment;
                if (! $assignment) {
                    continue;
                }

                $createdByThisTransfer = (int) ($assignment->logistics_event_id ?? 0) === $transferId;

                if ($createdByThisTransfer) {
                    $assignment->delete();

                    continue;
                }

                $assignment->update([
                    'end_date' => $participant->original_end_date,
                ]);
            }

            foreach ($participants as $participant) {
                $payload = $participant->restoration_payload;
                if (! is_array($payload)) {
                    continue;
                }

                if (! empty($payload['project_assignment']) && is_array($payload['project_assignment'])) {
                    ProjectAssignment::create(Arr::only(
                        $payload['project_assignment'],
                        [
                            'project_id',
                            'employee_id',
                            'role_id',
                            'start_date',
                            'end_date',
                            'notes',
                            'logistics_event_id',
                        ]
                    ));
                }

                if (! empty($payload['accommodation_assignment']) && is_array($payload['accommodation_assignment'])) {
                    AccommodationAssignment::create(Arr::only(
                        $payload['accommodation_assignment'],
                        [
                            'accommodation_id',
                            'employee_id',
                            'start_date',
                            'end_date',
                            'notes',
                            'logistics_event_id',
                        ]
                    ));
                }

                if (! empty($payload['vehicle_assignment']) && is_array($payload['vehicle_assignment'])) {
                    VehicleAssignment::create(Arr::only(
                        $payload['vehicle_assignment'],
                        [
                            'vehicle_id',
                            'employee_id',
                            'position',
                            'start_date',
                            'end_date',
                            'notes',
                            'is_return_trip',
                            'logistics_event_id',
                        ]
                    ));
                }
            }

            $transfer->participants()->delete();
        });
    }

    /**
     * Anuluje transfer: przy planie zmian odwraca przypisania i kaskadowo anuluje
     * doklejone przejazdy (related_departure_id → ten event).
     */
    public function cancelTransfer(LogisticsEvent $transfer): void
    {
        if ($transfer->type !== LogisticsEventType::TRANSFER) {
            throw new \InvalidArgumentException('Can only cancel transfers.');
        }

        if (! in_array($transfer->status, [LogisticsEventStatus::PLANNED, LogisticsEventStatus::COMPLETED], true)) {
            throw ValidationException::withMessages([
                'status' => 'Tego transferu nie można anulować.',
            ]);
        }

        DB::transaction(function () use ($transfer) {
            $children = LogisticsEvent::query()
                ->where('related_departure_id', $transfer->id)
                ->where('type', LogisticsEventType::TRANSFER)
                ->whereIn('status', [LogisticsEventStatus::PLANNED, LogisticsEventStatus::COMPLETED])
                ->orderBy('id')
                ->get();

            foreach ($children as $child) {
                $this->cancelTransfer($child);
            }

            if ($transfer->has_reassignment) {
                $this->reverseTransfer($transfer);
            }

            $transfer->update(['status' => LogisticsEventStatus::CANCELLED]);
        });
    }

    /**
     * Wypisuje jedną osobę z transferu i cofa efekty reassignment tylko dla niej.
     *
     * @return array{participants_removed: int, cancelled_empty: bool}
     */
    public function removeParticipant(LogisticsEvent $transfer, int $employeeId): array
    {
        if ($transfer->type !== LogisticsEventType::TRANSFER) {
            throw new \InvalidArgumentException('Can only remove participants from transfers.');
        }

        if (! in_array($transfer->status, [LogisticsEventStatus::PLANNED, LogisticsEventStatus::COMPLETED], true)) {
            throw ValidationException::withMessages([
                'employee_id' => 'Można wypisać uczestnika tylko z aktywnego transferu.',
            ]);
        }

        $participantRows = $transfer->participants()->where('employee_id', $employeeId)->get();
        if ($participantRows->isEmpty()) {
            throw ValidationException::withMessages([
                'employee_id' => 'Ta osoba nie jest uczestnikiem tego transferu.',
            ]);
        }

        $uniqueEmployees = $transfer->participants()->pluck('employee_id')->unique();
        if ($uniqueEmployees->count() <= 1) {
            throw ValidationException::withMessages([
                'employee_id' => 'Nie można wypisać ostatniego uczestnika — anuluj cały transfer.',
            ]);
        }

        return DB::transaction(function () use ($transfer, $employeeId, $participantRows) {
            if ($transfer->has_reassignment) {
                $this->reverseParticipantReassignment($transfer, $participantRows);
            }

            $removed = $transfer->participants()->where('employee_id', $employeeId)->delete();

            $cancelledEmpty = false;
            if ($transfer->participants()->count() === 0) {
                $transfer->update(['status' => LogisticsEventStatus::CANCELLED]);
                $cancelledEmpty = true;
            }

            $employee = Employee::find($employeeId);
            if ($employee) {
                $this->locationTracking->getLocationStatus($employee, now());
            }

            return [
                'participants_removed' => (int) $removed,
                'cancelled_empty' => $cancelledEmpty,
            ];
        });
    }

    /**
     * Dopisuje osobę do istniejącego transferu (opcjonalnie ze zmianą przypisań).
     *
     * @param  array{
     *     employee_id: int,
     *     project_id?: int|null,
     *     role_id?: int|null,
     *     start_date?: string|null,
     *     end_date?: string|null,
     *     accommodation_id?: int|null,
     *     vehicle_id?: int|null,
     *     vehicle_position?: string|null,
     *     keep_current?: bool,
     * }  $data
     */
    public function addParticipant(LogisticsEvent $transfer, array $data): void
    {
        if ($transfer->type !== LogisticsEventType::TRANSFER) {
            throw new \InvalidArgumentException('Can only add participants to transfers.');
        }

        if (! in_array($transfer->status, [LogisticsEventStatus::PLANNED, LogisticsEventStatus::COMPLETED], true)) {
            throw ValidationException::withMessages([
                'employee_id' => 'Można dopisać uczestnika tylko do aktywnego transferu.',
            ]);
        }

        $employeeId = (int) ($data['employee_id'] ?? 0);
        $employee = Employee::find($employeeId);
        if (! $employee) {
            throw ValidationException::withMessages([
                'employee_id' => 'Nie znaleziono pracownika.',
            ]);
        }

        if ($transfer->participants()->where('employee_id', $employeeId)->exists()) {
            throw ValidationException::withMessages([
                'employee_id' => 'Ta osoba jest już uczestnikiem tego transferu.',
            ]);
        }

        $transferDate = $transfer->event_date instanceof Carbon
            ? $transfer->event_date->copy()->startOfDay()
            : Carbon::parse($transfer->event_date)->startOfDay();

        if ($transfer->has_reassignment) {
            if (! $this->locationTracking->isEmployeeEligibleForTransfer($employee, $transferDate)) {
                throw ValidationException::withMessages([
                    'employee_id' => "Pracownik {$employee->full_name} jest w lokalizacji bazy w dniu transferu — użyj wyjazdu lub zjazdu.",
                ]);
            }
        }

        DB::transaction(function () use ($transfer, $employeeId, $transferDate, $data) {
            if (! $transfer->has_reassignment || ! empty($data['keep_current'])) {
                LogisticsEventParticipant::create([
                    'logistics_event_id' => $transfer->id,
                    'employee_id' => $employeeId,
                    'status' => 'pending',
                ]);

                return;
            }

            $projectId = (int) ($data['project_id'] ?? 0);
            if ($projectId <= 0) {
                throw ValidationException::withMessages([
                    'project_id' => 'Wybierz projekt docelowy.',
                ]);
            }

            $this->processReassignment($transfer, $employeeId, $transferDate, [
                'project_id' => $projectId,
                'role_id' => ! empty($data['role_id']) ? (int) $data['role_id'] : null,
                'start_date' => $data['start_date'] ?? $transferDate->format('Y-m-d'),
                'end_date' => $data['end_date'] ?? null,
                'accommodation_id' => ! empty($data['accommodation_id']) ? (int) $data['accommodation_id'] : null,
                'accommodation_start_date' => $data['accommodation_start_date'] ?? null,
                'accommodation_end_date' => $data['accommodation_end_date'] ?? null,
                'vehicle_id' => ! empty($data['vehicle_id']) ? (int) $data['vehicle_id'] : null,
                'vehicle_start_date' => $data['vehicle_start_date'] ?? null,
                'vehicle_end_date' => $data['vehicle_end_date'] ?? null,
                'vehicle_position' => $data['vehicle_position'] ?? VehiclePosition::PASSENGER->value,
                'skip_old_accommodation_shorten' => empty($data['accommodation_id']),
                'skip_old_vehicle_shorten' => empty($data['vehicle_id']),
            ]);
        });

        $this->locationTracking->getLocationStatus($employee, now());
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\LogisticsEventParticipant>  $participantRows
     */
    protected function reverseParticipantReassignment(LogisticsEvent $transfer, $participantRows): void
    {
        $transferId = (int) $transfer->id;
        $participantRows->loadMissing('assignment');

        foreach ($participantRows as $participant) {
            if ($participant->restoration_payload) {
                continue;
            }

            if (! $participant->assignment_type || ! $participant->assignment_id) {
                continue;
            }

            $assignment = $participant->assignment;
            if (! $assignment) {
                continue;
            }

            $createdByThisTransfer = (int) ($assignment->logistics_event_id ?? 0) === $transferId;

            if ($createdByThisTransfer) {
                $assignment->delete();

                continue;
            }

            $assignment->update([
                'end_date' => $participant->original_end_date,
            ]);
        }

        foreach ($participantRows as $participant) {
            $payload = $participant->restoration_payload;
            if (! is_array($payload)) {
                continue;
            }

            if (! empty($payload['project_assignment']) && is_array($payload['project_assignment'])) {
                ProjectAssignment::create(Arr::only(
                    $payload['project_assignment'],
                    [
                        'project_id',
                        'employee_id',
                        'role_id',
                        'start_date',
                        'end_date',
                        'notes',
                        'logistics_event_id',
                    ]
                ));
            }

            if (! empty($payload['accommodation_assignment']) && is_array($payload['accommodation_assignment'])) {
                AccommodationAssignment::create(Arr::only(
                    $payload['accommodation_assignment'],
                    [
                        'accommodation_id',
                        'employee_id',
                        'start_date',
                        'end_date',
                        'notes',
                        'logistics_event_id',
                    ]
                ));
            }

            if (! empty($payload['vehicle_assignment']) && is_array($payload['vehicle_assignment'])) {
                VehicleAssignment::create(Arr::only(
                    $payload['vehicle_assignment'],
                    [
                        'vehicle_id',
                        'employee_id',
                        'position',
                        'start_date',
                        'end_date',
                        'notes',
                        'is_return_trip',
                        'logistics_event_id',
                    ]
                ));
            }
        }
    }

    /**
     * Get current assignments for employees to display in the reassignment wizard.
     *
     * @return array<int, array{project: ProjectAssignment|null, accommodation: AccommodationAssignment|null}>
     */
    public function getCurrentAssignments(array $employeeIds, Carbon $date): array
    {
        $result = [];

        foreach ($employeeIds as $employeeId) {
            $project = ProjectAssignment::where('employee_id', $employeeId)
                ->where('start_date', '<=', $date)
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date))
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->with('project.location')
                ->first();

            $accommodation = AccommodationAssignment::where('employee_id', $employeeId)
                ->where('start_date', '<=', $date)
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date))
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->with('accommodation.location')
                ->first();

            $recentDeparture = LogisticsEvent::whereHas('participants', fn ($q) => $q->where('employee_id', $employeeId))
                ->where('type', LogisticsEventType::DEPARTURE)
                ->where('status', '!=', LogisticsEventStatus::CANCELLED)
                ->where('event_date', '>=', $date->copy()->subDays(3))
                ->where('event_date', '<=', $date)
                ->exists();

            $result[$employeeId] = [
                'project' => $project,
                'accommodation' => $accommodation,
                'has_recent_departure' => $recentDeparture,
            ];
        }

        return $result;
    }
}
