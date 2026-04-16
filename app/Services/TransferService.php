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
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
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
     * }  $data
     */
    public function commitTransfer(array $data): LogisticsEvent
    {
        return DB::transaction(function () use ($data) {
            $transferDate = $data['transfer_date'] instanceof Carbon
                ? $data['transfer_date']
                : Carbon::parse($data['transfer_date']);

            foreach ($data['employee_ids'] as $employeeId) {
                $employee = Employee::findOrFail($employeeId);
                if (! $this->locationTracking->isEmployeeEligibleForTransfer($employee, $transferDate)) {
                    throw ValidationException::withMessages([
                        'selectedEmployeeIds' => "Pracownik {$employee->full_name} jest w bazie w dniu transferu — użyj wyjazdu lub zjazdu, nie transferu.",
                    ]);
                }
            }

            $event = LogisticsEvent::create([
                'type' => LogisticsEventType::TRANSFER,
                'event_date' => $data['transfer_date'],
                'end_date' => $data['transfer_date'],
                'from_location_id' => $data['from_location_id'],
                'to_location_id' => $data['to_location_id'],
                'vehicle_id' => $data['vehicle_id'],
                'has_transport' => empty($data['vehicle_id']),
                'status' => LogisticsEventStatus::PLANNED,
                'has_reassignment' => $data['has_reassignment'],
                'notes' => $data['notes'],
                'route_distance' => $data['route_distance'],
                'route_duration' => $data['route_duration'],
                'route_waypoints' => $data['route_waypoints'],
                'created_by' => auth()->id(),
            ]);

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

            return $event;
        });
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

        $oldProject = null;
        if (! empty($reassignment['source_project_assignment_id'])) {
            $candidate = ProjectAssignment::query()->find((int) $reassignment['source_project_assignment_id']);
            if (
                $candidate
                && (int) $candidate->employee_id === $employeeId
                && $candidate->start_date <= $transferDate
                && ($candidate->end_date === null || $candidate->end_date >= $transferDate)
            ) {
                $oldProject = $candidate;
            }
        }

        if (! $oldProject) {
            $oldProject = ProjectAssignment::where('employee_id', $employeeId)
                ->where('start_date', '<=', $transferDate)
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $transferDate))
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->first();
        }

        $deletedOldProjectRoleId = null;

        if ($oldProject) {
            $shortenEnd = DateRangeService::normalizeDate($transferDate)->copy()->subDay();
            $oldStart = DateRangeService::normalizeDate($oldProject->start_date);

            if ($shortenEnd->lt($oldStart)) {
                $snapshot = [
                    'project_id' => (int) $oldProject->project_id,
                    'employee_id' => (int) $oldProject->employee_id,
                    'role_id' => (int) $oldProject->role_id,
                    'start_date' => DateRangeService::normalizeDate($oldProject->start_date)->format('Y-m-d'),
                    'end_date' => $oldProject->end_date
                        ? DateRangeService::normalizeDate($oldProject->end_date)->format('Y-m-d')
                        : null,
                    'notes' => $oldProject->notes,
                    'logistics_event_id' => $oldProject->logistics_event_id,
                ];
                $deletedOldProjectRoleId = (int) $oldProject->role_id;
                $oldProject->delete();
                $oldProject = null;

                LogisticsEventParticipant::create([
                    'logistics_event_id' => $event->id,
                    'employee_id' => $employeeId,
                    'assignment_type' => 'project_assignment',
                    'assignment_id' => null,
                    'original_end_date' => null,
                    'restoration_payload' => ['project_assignment' => $snapshot],
                    'status' => 'pending',
                ]);
            } else {
                $originalEnd = $oldProject->end_date;
                $oldProject->update(['end_date' => $shortenEnd]);

                LogisticsEventParticipant::create([
                    'logistics_event_id' => $event->id,
                    'employee_id' => $employeeId,
                    'assignment_type' => 'project_assignment',
                    'assignment_id' => $oldProject->id,
                    'original_end_date' => $originalEnd?->format('Y-m-d'),
                    'status' => 'pending',
                ]);
            }
        }

        if (! $skipOldAccommodationShorten) {
            $oldAccommodation = AccommodationAssignment::where('employee_id', $employeeId)
                ->where('start_date', '<=', $transferDate)
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $transferDate))
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->first();

            if ($oldAccommodation) {
                $shortenEndAcc = DateRangeService::normalizeDate($transferDate)->copy()->subDay();
                $oldAccStart = DateRangeService::normalizeDate($oldAccommodation->start_date);

                if ($shortenEndAcc->lt($oldAccStart)) {
                    $accSnapshot = [
                        'accommodation_id' => (int) $oldAccommodation->accommodation_id,
                        'employee_id' => (int) $oldAccommodation->employee_id,
                        'start_date' => DateRangeService::normalizeDate($oldAccommodation->start_date)->format('Y-m-d'),
                        'end_date' => $oldAccommodation->end_date
                            ? DateRangeService::normalizeDate($oldAccommodation->end_date)->format('Y-m-d')
                            : null,
                        'notes' => $oldAccommodation->notes,
                        'logistics_event_id' => $oldAccommodation->logistics_event_id,
                    ];
                    $oldAccommodation->delete();

                    LogisticsEventParticipant::create([
                        'logistics_event_id' => $event->id,
                        'employee_id' => $employeeId,
                        'assignment_type' => 'accommodation_assignment',
                        'assignment_id' => null,
                        'original_end_date' => null,
                        'restoration_payload' => ['accommodation_assignment' => $accSnapshot],
                        'status' => 'pending',
                    ]);
                } else {
                    $originalEnd = $oldAccommodation->end_date;
                    $oldAccommodation->update(['end_date' => $shortenEndAcc]);

                    LogisticsEventParticipant::create([
                        'logistics_event_id' => $event->id,
                        'employee_id' => $employeeId,
                        'assignment_type' => 'accommodation_assignment',
                        'assignment_id' => $oldAccommodation->id,
                        'original_end_date' => $originalEnd?->format('Y-m-d'),
                        'status' => 'pending',
                    ]);
                }
            }
        }

        if (! $skipOldVehicleShorten) {
            $oldVehicle = VehicleAssignment::query()
                ->where('employee_id', $employeeId)
                ->where('is_return_trip', false)
                ->where('start_date', '<=', $transferDate)
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $transferDate))
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->first();

            if ($oldVehicle) {
                $shortenEndVeh = DateRangeService::normalizeDate($transferDate)->copy()->subDay();
                $oldVehStart = DateRangeService::normalizeDate($oldVehicle->start_date);

                if ($shortenEndVeh->lt($oldVehStart)) {
                    $vehSnapshot = [
                        'vehicle_id' => (int) $oldVehicle->vehicle_id,
                        'employee_id' => (int) $oldVehicle->employee_id,
                        'position' => $oldVehicle->position instanceof VehiclePosition ? $oldVehicle->position->value : $oldVehicle->position,
                        'start_date' => DateRangeService::normalizeDate($oldVehicle->start_date)->format('Y-m-d'),
                        'end_date' => $oldVehicle->end_date
                            ? DateRangeService::normalizeDate($oldVehicle->end_date)->format('Y-m-d')
                            : null,
                        'notes' => $oldVehicle->notes,
                        'is_return_trip' => false,
                        'logistics_event_id' => $oldVehicle->logistics_event_id,
                    ];
                    $oldVehicle->delete();

                    LogisticsEventParticipant::create([
                        'logistics_event_id' => $event->id,
                        'employee_id' => $employeeId,
                        'assignment_type' => 'vehicle_assignment',
                        'assignment_id' => null,
                        'original_end_date' => null,
                        'restoration_payload' => ['vehicle_assignment' => $vehSnapshot],
                        'status' => 'pending',
                    ]);
                } else {
                    $originalEnd = $oldVehicle->end_date;
                    $oldVehicle->update(['end_date' => $shortenEndVeh]);

                    LogisticsEventParticipant::create([
                        'logistics_event_id' => $event->id,
                        'employee_id' => $employeeId,
                        'assignment_type' => 'vehicle_assignment',
                        'assignment_id' => $oldVehicle->id,
                        'original_end_date' => $originalEnd?->format('Y-m-d'),
                        'status' => 'pending',
                    ]);
                }
            }
        }

        $newProjectId = $reassignment['project_id'] ?? null;
        $newAccommodationId = $reassignment['accommodation_id'] ?? null;
        $newVehicleId = $reassignment['vehicle_id'] ?? null;
        $newVehiclePosition = $reassignment['vehicle_position'] ?? VehiclePosition::PASSENGER->value;
        $startDate = $reassignment['start_date'] ?? $transferDate->format('Y-m-d');
        $endDate = ! empty($reassignment['end_date']) ? $reassignment['end_date'] : null;
        $newProjectStartDate = DateRangeService::normalizeDate($transferDate)->format('Y-m-d');

        if ($newProjectId) {
            $roleId = ! empty($reassignment['role_id'])
                ? (int) $reassignment['role_id']
                : ($oldProject?->role_id
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
                'start_date' => $startDate,
                'end_date' => $endDate,
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
            $start = Carbon::parse($startDate);
            $end = $endDate ? Carbon::parse($endDate) : null;

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
