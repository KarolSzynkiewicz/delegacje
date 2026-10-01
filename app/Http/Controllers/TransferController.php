<?php

namespace App\Http\Controllers;

use App\Enums\LocationPurposeType;
use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Enums\VehiclePosition;
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
use App\Services\TransferService;
use App\Support\DepartureRoutePlan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function __construct(
        protected TransferService $transferService
    ) {}

    public function index(): View
    {
        return view('transfers.index');
    }

    public function create(Request $request): View
    {
        $extendPlanId = $request->integer('plan') ?: null;
        if ($extendPlanId) {
            $plan = LogisticsEvent::query()->find($extendPlanId);
            if (! $plan || ! $plan->isReassignmentPlan()) {
                $extendPlanId = null;
            }
        }

        $parentEventId = $extendPlanId ? null : ($request->integer('parent') ?: null);
        if ($parentEventId) {
            $parent = LogisticsEvent::query()->find($parentEventId);
            $allowed = $parent && (
                $parent->type === LogisticsEventType::DEPARTURE
                || $parent->isReassignmentPlan()
            );
            if (! $allowed) {
                $parentEventId = null;
            }
        }

        return view('transfers.create', [
            'parentEventId' => $parentEventId,
            'extendPlanId' => $extendPlanId,
        ]);
    }

    public function show(LogisticsEvent $transfer): View
    {
        abort_if($transfer->type !== LogisticsEventType::TRANSFER, 404);

        $transfer->load([
            'vehicle',
            'fromLocation',
            'toLocation',
            'creator',
            'participants.employee',
            'participants.assignment' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    ProjectAssignment::class => ['project.location', 'role'],
                    AccommodationAssignment::class => ['accommodation.location'],
                    VehicleAssignment::class => ['vehicle'],
                ]);
            },
            'projectAssignments.project',
            'projectAssignments.role',
            'vehicleAssignments.vehicle',
            'accommodationAssignments.accommodation',
            'driverAdjustments.employee',
            'driverAdjustments.payroll',
            'transportCosts',
            'relatedDeparture',
            'linkedTransportLegs.vehicle',
            'linkedTransportLegs.fromLocation',
            'linkedTransportLegs.toLocation',
            'linkedTransportLegs.driverAdjustments.employee',
        ]);

        $groundLegTicketRows = [];
        if ($transfer->relatedDeparture && $transfer->relatedDeparture->type === LogisticsEventType::DEPARTURE) {
            $groundLegTicketRows = DepartureRoutePlan::collectPublicLegTicketRowsFromSegments(
                is_array($transfer->relatedDeparture->route_segments)
                    ? $transfer->relatedDeparture->route_segments
                    : []
            );
        }
        if ($groundLegTicketRows !== []) {
            $empIds = collect($groundLegTicketRows)->pluck('employee_id')->unique()->values()->all();
            $empNames = Employee::fullNamesByIds($empIds);
            $groundLegTicketRows = collect($groundLegTicketRows)->map(function (array $r) use ($empNames) {
                $r['employee_name'] = $empNames[$r['employee_id']] ?? ('#'.$r['employee_id']);

                return $r;
            })->values()->all();
        }

        $routeStopRows = $transfer->getRouteStopsForDetailView()->values();
        if ($routeStopRows->isEmpty()) {
            $routeStopRows = $this->fallbackTransferRouteStopRows($transfer);
        }

        $locIds = $routeStopRows->where('kind', 'extra_location')->pluck('model_id')->unique()->filter()->all();
        $routeStopLocationsById = $locIds === []
            ? collect()
            : Location::whereIn('id', $locIds)->get()->keyBy('id');

        $hubKind = null;
        if ($transfer->from_location_id && Location::matchesPurpose((int) $transfer->from_location_id, LocationPurposeType::AIRPORT)) {
            $hubKind = 'airport';
        } elseif ($transfer->from_location_id && Location::matchesPurpose((int) $transfer->from_location_id, LocationPurposeType::STATION)) {
            $hubKind = 'station';
        }

        $canMutateParticipants = in_array($transfer->status, [LogisticsEventStatus::PLANNED, LogisticsEventStatus::COMPLETED], true);
        $uniqueParticipantCount = $transfer->participants->pluck('employee_id')->unique()->count();
        $canRemoveParticipants = $canMutateParticipants && $uniqueParticipantCount > 1;

        $participantRows = $transfer->participants
            ->filter(fn ($p) => $p->employee)
            ->unique('employee_id')
            ->values();

        $changeFlagsByEmployee = [];
        $assignmentCellsByEmployee = [];
        if ($transfer->has_reassignment) {
            foreach ($transfer->participants->groupBy('employee_id') as $employeeId => $parts) {
                $changeFlagsByEmployee[(int) $employeeId] = [
                    'project' => $parts->contains(fn ($p) => $p->assignment_type === 'project_assignment'),
                    'vehicle' => $parts->contains(fn ($p) => $p->assignment_type === 'vehicle_assignment'),
                    'accommodation' => $parts->contains(fn ($p) => $p->assignment_type === 'accommodation_assignment'),
                ];
            }
        }

        // Aktualne auto/dom na dzień transferu — do komórek „bez zmiany” (nie ma nowego wpisu z event_id).
        $day = $transfer->event_date?->copy()->startOfDay() ?? now()->startOfDay();
        $employeeIds = $participantRows->pluck('employee_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $currentVehicleByEmployee = [];
        $currentAccommodationByEmployee = [];
        $currentProjectByEmployee = [];

        if ($employeeIds !== []) {
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
                if (! isset($currentVehicleByEmployee[$eid])) {
                    $currentVehicleByEmployee[$eid] = $va;
                }
            }

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
                if (! isset($currentAccommodationByEmployee[$eid])) {
                    $currentAccommodationByEmployee[$eid] = $aa;
                }
            }

            $paRows = ProjectAssignment::query()
                ->whereIn('employee_id', $employeeIds)
                ->where('start_date', '<=', $day)
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $day))
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->with(['project', 'role'])
                ->get();
            foreach ($paRows as $pa) {
                $eid = (int) $pa->employee_id;
                if (! isset($currentProjectByEmployee[$eid])) {
                    $currentProjectByEmployee[$eid] = $pa;
                }
            }
        }

        $snapshotLookups = $this->resolveTransferSnapshotLookups($transfer);

        foreach ($participantRows as $participant) {
            $employeeId = (int) $participant->employee_id;
            $parts = $transfer->participants->where('employee_id', $employeeId);
            $flags = $changeFlagsByEmployee[$employeeId] ?? [
                'project' => false,
                'vehicle' => false,
                'accommodation' => false,
            ];
            $hasReassignment = (bool) $transfer->has_reassignment;

            $assignmentCellsByEmployee[$employeeId] = [
                'project' => $this->buildTransferAssignmentCell(
                    kind: 'project',
                    hasReassignment: $hasReassignment,
                    changed: $hasReassignment && ! empty($flags['project']),
                    eventId: (int) $transfer->id,
                    participants: $parts,
                    eventLinked: $transfer->projectAssignments->where('employee_id', $employeeId)->first(),
                    current: $currentProjectByEmployee[$employeeId] ?? null,
                    snapshotLookups: $snapshotLookups,
                ),
                'vehicle' => $this->buildTransferAssignmentCell(
                    kind: 'vehicle',
                    hasReassignment: $hasReassignment,
                    changed: $hasReassignment && ! empty($flags['vehicle']),
                    eventId: (int) $transfer->id,
                    participants: $parts,
                    eventLinked: $transfer->vehicleAssignments->where('employee_id', $employeeId)->first(),
                    current: $currentVehicleByEmployee[$employeeId] ?? null,
                    snapshotLookups: $snapshotLookups,
                ),
                'accommodation' => $this->buildTransferAssignmentCell(
                    kind: 'accommodation',
                    hasReassignment: $hasReassignment,
                    changed: $hasReassignment && ! empty($flags['accommodation']),
                    eventId: (int) $transfer->id,
                    participants: $parts,
                    eventLinked: $transfer->accommodationAssignments->where('employee_id', $employeeId)->first(),
                    current: $currentAccommodationByEmployee[$employeeId] ?? null,
                    snapshotLookups: $snapshotLookups,
                ),
            ];
        }

        $isReassignmentPlan = $transfer->isReassignmentPlan();

        return view('transfers.show', [
            'transfer' => $transfer,
            'routeStopRows' => $routeStopRows,
            'routeStopLocationsById' => $routeStopLocationsById,
            'routeStopCount' => $routeStopRows->count(),
            'publicHubKind' => $hubKind,
            'groundLegTicketRows' => $groundLegTicketRows,
            'canMutateParticipants' => $canMutateParticipants,
            'canRemoveParticipants' => $canRemoveParticipants,
            'participantRows' => $participantRows,
            'changeFlagsByEmployee' => $changeFlagsByEmployee,
            'assignmentCellsByEmployee' => $assignmentCellsByEmployee,
            'currentVehicleByEmployee' => $currentVehicleByEmployee,
            'currentAccommodationByEmployee' => $currentAccommodationByEmployee,
            'currentProjectByEmployee' => $currentProjectByEmployee,
            'isReassignmentPlan' => $isReassignmentPlan,
            'linkedTransportLegs' => $transfer->linkedTransportLegs
                ->sortByDesc('event_date')
                ->values(),
            'transportParentContext' => $isReassignmentPlan ? null : $this->resolveTransportParentContext($transfer),
            'transportCreatorPanel' => $isReassignmentPlan ? null : $this->buildTransportCreatorPanel($transfer, $hubKind),
        ]);
    }

    /**
     * Kontekst rodzica (wyjazd / zjazd / plan zmian), do którego doklejono ten przejazd.
     *
     * @return array{kind: string, label: string, title: string, url: string, subtitle: string}|null
     */
    private function resolveTransportParentContext(LogisticsEvent $transfer): ?array
    {
        $parent = $transfer->relatedDeparture;
        if (! $parent) {
            return null;
        }

        $dateLabel = $parent->event_date
            ? $parent->event_date->format('d.m.Y')
            : '—';

        if ($parent->type === LogisticsEventType::DEPARTURE) {
            return [
                'kind' => 'departure',
                'label' => 'Wyjazd',
                'title' => 'Transport do wyjazdu #'.$parent->id,
                'url' => route('departures.show', $parent),
                'subtitle' => $dateLabel,
            ];
        }

        if ($parent->type === LogisticsEventType::RETURN) {
            return [
                'kind' => 'return',
                'label' => 'Zjazd',
                'title' => 'Transport do zjazdu #'.$parent->id,
                'url' => route('return-trips.show', $parent),
                'subtitle' => $dateLabel,
            ];
        }

        if ($parent->isReassignmentPlan()) {
            return [
                'kind' => 'plan',
                'label' => 'Plan zmian (transfer)',
                'title' => 'Transport do planu zmian #'.$parent->id,
                'url' => route('transfers.show', $parent),
                'subtitle' => $dateLabel,
            ];
        }

        return [
            'kind' => 'transfer',
            'label' => 'Transfer',
            'title' => 'Transport do transferu #'.$parent->id,
            'url' => route('transfers.show', $parent),
            'subtitle' => $dateLabel,
        ];
    }

    /**
     * Dane do read-only panelu jak w kreatorze transportu (trip-details-panel).
     *
     * @return array<string, mixed>
     */
    private function buildTransportCreatorPanel(LogisticsEvent $transfer, ?string $hubKind): array
    {
        $eventDate = $transfer->event_date
            ? $transfer->event_date->format('Y-m-d')
            : '';
        $endDate = $transfer->end_date
            ? $transfer->end_date->format('Y-m-d')
            : $eventDate;

        $isOwn = (bool) $transfer->vehicle_id;
        $transportMode = $isOwn ? 'own' : 'public';

        $employees = $transfer->participants
            ->pluck('employee')
            ->filter()
            ->unique('id')
            ->values();

        $driverId = $transfer->driverAdjustments
            ->pluck('employee_id')
            ->map(fn ($id) => (int) $id)
            ->first(fn (int $id) => $id > 0);

        $vehicle = $transfer->vehicle;
        $vehicleSeats = [];
        if ($isOwn && $vehicle) {
            $capacity = max(1, (int) ($vehicle->capacity ?? 1));
            $passengerIds = $employees
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->reject(fn (int $id) => $driverId !== null && $id === $driverId)
                ->values()
                ->all();

            $vehicleSeats[0] = [
                'employee_id' => $driverId,
                'position' => 'driver',
                'external_driver' => $driverId === null,
            ];
            $p = 0;
            for ($i = 1; $i < $capacity; $i++) {
                $vehicleSeats[$i] = [
                    'employee_id' => $passengerIds[$p] ?? null,
                    'position' => 'passenger',
                    'external_driver' => false,
                ];
                if (isset($passengerIds[$p])) {
                    $p++;
                }
            }
        }

        $hubs = collect([$transfer->fromLocation, $transfer->toLocation])
            ->filter()
            ->unique('id')
            ->values();

        $ticketCostsByEmployee = [];
        foreach ($employees as $employee) {
            $name = (string) ($employee->full_name ?? '');
            $match = $transfer->transportCosts
                ->where('cost_type', 'ticket')
                ->first(function ($tc) use ($name, $employee) {
                    $desc = (string) ($tc->description ?? '');

                    return ($name !== '' && str_contains($desc, $name))
                        || str_contains($desc, '#'.$employee->id);
                });
            if (! $match) {
                continue;
            }
            $ticketCostsByEmployee[(int) $employee->id] = [
                'amount' => (float) $match->amount,
                'currency' => strtoupper((string) ($match->currency ?: 'PLN')),
                'attachment_path' => $match->file_path,
            ];
        }

        return [
            'departureDate' => $eventDate,
            'endDate' => $endDate,
            'transportMode' => $transportMode,
            'vehicleId' => $transfer->vehicle_id ? (int) $transfer->vehicle_id : null,
            'selectedVehicle' => $vehicle,
            'vehicleSeats' => $vehicleSeats,
            'employees' => $employees,
            'publicTransportHubKind' => $isOwn ? null : $hubKind,
            'sharedStartAirportLocationId' => $isOwn ? null : ($transfer->from_location_id ? (int) $transfer->from_location_id : null),
            'sharedEndAirportLocationId' => $isOwn ? null : ($transfer->to_location_id ? (int) $transfer->to_location_id : null),
            'availablePublicTransportHubs' => $hubs,
            'availableVehicles' => $vehicle ? collect([$vehicle]) : collect(),
            'ticketCostsByEmployee' => $ticketCostsByEmployee,
            'ticketsSectionTitle' => $hubKind === 'airport' ? 'Bilety lotnicze' : 'Bilety',
        ];
    }

    public function removeParticipant(LogisticsEvent $transfer, Employee $employee): RedirectResponse
    {
        abort_if($transfer->type !== LogisticsEventType::TRANSFER, 404);

        try {
            $this->transferService->removeParticipant($transfer, (int) $employee->id);
        } catch (ValidationException $e) {
            return redirect()
                ->route('transfers.show', $transfer)
                ->with('error', collect($e->errors())->flatten()->first() ?? 'Nie udało się wypisać uczestnika.');
        }

        return redirect()
            ->route('transfers.show', $transfer)
            ->with('success', $transfer->has_reassignment
                ? 'Wypisano '.$employee->full_name.'. Przypisania tej osoby zostały przywrócone.'
                : 'Wypisano '.$employee->full_name.' z transferu.');
    }

    /**
     * @param  Collection<int, mixed>  $participants
     * @param  array{projects: array<int, string>, roles: array<int, string>, accommodations: array<int, string>, vehicles: array<int, string>}  $snapshotLookups
     * @return array{state: string, before: ?array<string, mixed>, after: ?array<string, mixed>, current: ?array<string, mixed>}
     */
    private function buildTransferAssignmentCell(
        string $kind,
        bool $hasReassignment,
        bool $changed,
        int $eventId,
        Collection $participants,
        mixed $eventLinked,
        mixed $current,
        array $snapshotLookups,
    ): array {
        $assignmentType = match ($kind) {
            'project' => 'project_assignment',
            'vehicle' => 'vehicle_assignment',
            default => 'accommodation_assignment',
        };

        if ($changed) {
            $beforeVm = null;
            $afterVm = $eventLinked
                ? $this->assignmentViewModel($kind, $eventLinked)
                : null;

            foreach ($participants->where('assignment_type', $assignmentType) as $part) {
                $assignment = $part->assignment;
                if ($assignment && (int) ($assignment->logistics_event_id ?? 0) === $eventId) {
                    $afterVm = $afterVm ?? $this->assignmentViewModel($kind, $assignment);
                    continue;
                }

                if ($assignment) {
                    $beforeVm = $this->assignmentViewModel(
                        $kind,
                        $assignment,
                        $part->original_end_date,
                    );
                    continue;
                }

                $payloadKey = $assignmentType;
                $snapshot = is_array($part->restoration_payload)
                    ? ($part->restoration_payload[$payloadKey] ?? null)
                    : null;
                if (is_array($snapshot)) {
                    $beforeVm = $this->snapshotViewModel($kind, $snapshot, $snapshotLookups);
                }
            }

            return [
                'state' => 'changed',
                'before' => $beforeVm,
                'after' => $afterVm,
                'current' => null,
            ];
        }

        $display = $eventLinked;
        if (! $display && $hasReassignment) {
            $display = $current;
        }
        if (! $hasReassignment) {
            $display = $display ?? $current;
        }

        if (! $display) {
            return [
                'state' => 'empty',
                'before' => null,
                'after' => null,
                'current' => null,
            ];
        }

        return [
            'state' => $hasReassignment ? 'unchanged' : 'plain',
            'before' => null,
            'after' => null,
            'current' => $this->assignmentViewModel($kind, $display),
        ];
    }

    /**
     * @return array{projects: array<int, string>, roles: array<int, string>, accommodations: array<int, string>, vehicles: array<int, string>}
     */
    private function resolveTransferSnapshotLookups(LogisticsEvent $transfer): array
    {
        $projectIds = [];
        $roleIds = [];
        $accommodationIds = [];
        $vehicleIds = [];

        foreach ($transfer->participants as $part) {
            $payload = $part->restoration_payload;
            if (! is_array($payload)) {
                continue;
            }
            if (isset($payload['project_assignment']) && is_array($payload['project_assignment'])) {
                $snap = $payload['project_assignment'];
                if (! empty($snap['project_id'])) {
                    $projectIds[] = (int) $snap['project_id'];
                }
                if (! empty($snap['role_id'])) {
                    $roleIds[] = (int) $snap['role_id'];
                }
            }
            if (isset($payload['accommodation_assignment']) && is_array($payload['accommodation_assignment'])) {
                $snap = $payload['accommodation_assignment'];
                if (! empty($snap['accommodation_id'])) {
                    $accommodationIds[] = (int) $snap['accommodation_id'];
                }
            }
            if (isset($payload['vehicle_assignment']) && is_array($payload['vehicle_assignment'])) {
                $snap = $payload['vehicle_assignment'];
                if (! empty($snap['vehicle_id'])) {
                    $vehicleIds[] = (int) $snap['vehicle_id'];
                }
            }
        }

        return [
            'projects' => $projectIds === []
                ? []
                : Project::query()->whereIn('id', array_unique($projectIds))->pluck('name', 'id')->all(),
            'roles' => $roleIds === []
                ? []
                : Role::query()->whereIn('id', array_unique($roleIds))->pluck('name', 'id')->all(),
            'accommodations' => $accommodationIds === []
                ? []
                : Accommodation::query()->whereIn('id', array_unique($accommodationIds))->pluck('name', 'id')->all(),
            'vehicles' => $vehicleIds === []
                ? []
                : Vehicle::query()->whereIn('id', array_unique($vehicleIds))->pluck('registration_number', 'id')->all(),
        ];
    }

    /**
     * @return array{title: string, subtitle: ?string, dates: string, url: ?string}
     */
    private function assignmentViewModel(string $kind, mixed $assignment, mixed $overrideEndDate = null): array
    {
        $start = $assignment->start_date ?? null;
        $end = $overrideEndDate ?? ($assignment->end_date ?? null);

        return match ($kind) {
            'project' => [
                'title' => $assignment->project?->name ?? '—',
                'subtitle' => $assignment->role?->name,
                'dates' => $this->formatAssignmentDates($start, $end),
                'url' => route('project-assignments.show', $assignment),
            ],
            'vehicle' => [
                'title' => $assignment->vehicle?->registration_number ?? '—',
                'subtitle' => $this->vehiclePositionLabel($assignment->position ?? null),
                'dates' => $this->formatAssignmentDates($start, $end),
                'url' => route('vehicle-assignments.show', $assignment),
            ],
            default => [
                'title' => $assignment->accommodation?->name ?? '—',
                'subtitle' => null,
                'dates' => $this->formatAssignmentDates($start, $end),
                'url' => route('accommodation-assignments.show', $assignment),
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array{projects: array<int, string>, roles: array<int, string>, accommodations: array<int, string>, vehicles: array<int, string>}  $lookups
     * @return array{title: string, subtitle: ?string, dates: string, url: ?string}
     */
    private function snapshotViewModel(string $kind, array $snapshot, array $lookups): array
    {
        $start = $snapshot['start_date'] ?? null;
        $end = $snapshot['end_date'] ?? null;

        return match ($kind) {
            'project' => [
                'title' => $lookups['projects'][(int) ($snapshot['project_id'] ?? 0)] ?? ('#'.($snapshot['project_id'] ?? '—')),
                'subtitle' => $lookups['roles'][(int) ($snapshot['role_id'] ?? 0)] ?? null,
                'dates' => $this->formatAssignmentDates($start, $end),
                'url' => null,
            ],
            'vehicle' => [
                'title' => $lookups['vehicles'][(int) ($snapshot['vehicle_id'] ?? 0)] ?? ('#'.($snapshot['vehicle_id'] ?? '—')),
                'subtitle' => $this->vehiclePositionLabel($snapshot['position'] ?? null),
                'dates' => $this->formatAssignmentDates($start, $end),
                'url' => null,
            ],
            default => [
                'title' => $lookups['accommodations'][(int) ($snapshot['accommodation_id'] ?? 0)] ?? ('#'.($snapshot['accommodation_id'] ?? '—')),
                'subtitle' => null,
                'dates' => $this->formatAssignmentDates($start, $end),
                'url' => null,
            ],
        };
    }

    private function formatAssignmentDates(mixed $start, mixed $end): string
    {
        $startLabel = $start
            ? Carbon::parse($start)->format('d.m.Y')
            : '—';
        $endLabel = $end
            ? Carbon::parse($end)->format('d.m.Y')
            : 'brak daty';

        return $startLabel.' - '.$endLabel;
    }

    private function vehiclePositionLabel(mixed $position): ?string
    {
        if ($position instanceof VehiclePosition) {
            return $position->label();
        }
        if (is_string($position) && $position !== '') {
            return VehiclePosition::tryFrom($position)?->label();
        }

        return null;
    }

    /**
     * Gdy brak route_waypoints (np. transport publiczny: tylko skąd/dokąd w kolumnach).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function fallbackTransferRouteStopRows(LogisticsEvent $transfer): Collection
    {
        $from = $transfer->fromLocation;
        $to = $transfer->toLocation;
        $notes = is_array($transfer->location_stop_notes) ? $transfer->location_stop_notes : [];

        if (! $from && ! $to) {
            return collect();
        }

        $rowForLocation = static function (Location $loc, int $position, array $notes): array {
            $noteKey = (string) $loc->id;
            $note = isset($notes[$noteKey]) ? trim((string) $notes[$noteKey]) : null;

            return [
                'position' => $position,
                'kind' => 'extra_location',
                'model_id' => $loc->id,
                'name' => $loc->name,
                'address_line' => trim(implode(', ', array_filter([$loc->address, $loc->city ?? null]))),
                'employees_label' => null,
                'purpose' => ($note !== null && $note !== '') ? $note : null,
            ];
        };

        if ($from && $to && (int) $from->id === (int) $to->id) {
            return collect([$rowForLocation($from, 1, $notes)]);
        }

        $rows = collect();
        $pos = 0;
        if ($from) {
            $pos++;
            $rows->push($rowForLocation($from, $pos, $notes));
        }
        if ($to && (! $from || (int) $from->id !== (int) $to->id)) {
            $pos++;
            $rows->push($rowForLocation($to, $pos, $notes));
        }

        return $rows;
    }

    public function cancel(LogisticsEvent $transfer): RedirectResponse
    {
        abort_if($transfer->type !== LogisticsEventType::TRANSFER, 404);

        try {
            $this->transferService->cancelTransfer($transfer);
        } catch (ValidationException $e) {
            return redirect()->route('transfers.show', $transfer)
                ->with('error', collect($e->errors())->flatten()->first() ?? 'Tego transferu nie można anulować.');
        }

        $transfer->refresh();

        return redirect()->route('transfers.show', $transfer)
            ->with('success', $transfer->has_reassignment
                ? 'Plan zmian anulowany. Przypisania przywrócone; powiązane transporty też anulowane.'
                : 'Transport został anulowany.');
    }
}
