<?php

namespace App\Livewire;

use App\Enums\LogisticsEventType;
use App\Enums\VehiclePosition;
use App\Livewire\Concerns\ManagesVehicleSeats;
use App\Models\Adjustment;
use App\Models\Employee;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Services\DepartureService;
use App\Support\PublicTransportTicketCosts;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class DepartureParticipantPlanner extends Component
{
    use ManagesVehicleSeats;
    use WithFileUploads;

    public int $departureId;

    public ?int $lockEmployeeId = null;

    public int $currentStep = 1;

    public $departureDate;

    public $endDate;

    public $vehicleId;

    /** @var 'public'|'own' */
    public string $transportMode = 'public';

    public $assignments = [];

    public $assignmentRanges = [];

    public $vehicleSeats = [];

    public $accommodationAssignments = [];

    public $vehicleAssignments = [];

    public $ticketCostsByEmployee = [];

    /** @var 'airport'|'station'|null */
    public ?string $publicTransportHubKind = null;

    public $sharedStartAirportLocationId = null;

    public $sharedEndAirportLocationId = null;

    protected $listeners = [
        'assignment-added' => 'handleAssignmentAdded',
        'assignment-removed' => 'handleAssignmentRemoved',
        'assignment-range-added' => 'handleAssignmentRangeAdded',
        'assignment-range-removed' => 'handleAssignmentRangeRemoved',
        'accommodation-assigned' => 'handleAccommodationAssigned',
        'accommodation-removed' => 'handleAccommodationRemoved',
        'vehicle-assigned' => 'handleVehicleAssigned',
        'vehicle-assignment-removed' => 'handleVehicleAssignmentRemoved',
        'go-to-step' => 'goToStep',
        'save-participant-planner' => 'saveParticipant',
    ];

    public function mount(int $departureId, $employeeId = null): void
    {
        $this->departureId = $departureId;
        $this->lockEmployeeId = $employeeId ? (int) $employeeId : null;
        $this->loadPlannerStateFromDeparture();
    }

    public function goToStep($step): void
    {
        $step = (int) $step;
        if ($step < 1 || $step > 3) {
            return;
        }

        if ($step >= 2 && empty($this->assignmentRanges)) {
            $this->addError('assignment_ranges', 'Przypisz osobę do projektu (krok 1), zanim przejdziesz dalej.');

            return;
        }

        $this->resetErrorBag();
        $this->currentStep = $step;
    }

    public function saveParticipant(): void
    {
        $this->resetErrorBag();

        try {
            app(DepartureService::class)->applyPlannerParticipants(
                $this->departure(),
                [
                    'assignment_ranges' => $this->assignmentRanges,
                    'accommodation_assignments' => $this->accommodationAssignments,
                    'vehicle_assignments' => $this->vehicleAssignments,
                    'ticket_costs_by_employee' => PublicTransportTicketCosts::ensureCurrencies($this->ticketCostsByEmployee),
                    'driver_employee_id' => $this->currentDriverEmployeeId(),
                ],
                $this->lockEmployeeId
            );
        } catch (ValidationException $e) {
            foreach ($e->errors() as $key => $messages) {
                foreach ($messages as $message) {
                    $this->addError($key, $message);
                }
            }

            return;
        }

        $departure = $this->departure();
        $names = Employee::fullNamesByIds($this->selectedEmployeeIds)->implode(', ');

        $message = $this->lockEmployeeId
            ? 'Zaktualizowano przypisania'.($names !== '' ? ': '.$names : '').'.'
            : 'Dopisano uczestnika'.($names !== '' ? ': '.$names : '').'.';

        session()->flash('success', $message);

        $this->redirect(route('departures.show', $departure));
    }

    public function handleAssignmentAdded($data): void
    {
        if (! is_array($data)) {
            return;
        }

        $day = $data['day'] ?? null;
        $projectId = $data['project_id'] ?? null;
        $roleId = $data['role_id'] ?? null;
        $employeeId = $data['employee_id'] ?? null;
        if (! $day || ! $projectId || ! $roleId || ! $employeeId) {
            return;
        }

        if ($this->lockEmployeeId && (int) $employeeId !== $this->lockEmployeeId) {
            return;
        }

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

    public function handleAssignmentRemoved($data = []): void
    {
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

    public function handleAssignmentRangeAdded($data): void
    {
        if (! is_array($data) || empty($data['employee_id']) || empty($data['project_id']) || empty($data['role_id'])) {
            return;
        }

        if ($this->lockEmployeeId && (int) $data['employee_id'] !== $this->lockEmployeeId) {
            return;
        }

        $key = $data['employee_id'].'_'.$data['project_id'].'_'.$data['role_id'];
        $this->assignmentRanges[$key] = [
            'employee_id' => $data['employee_id'],
            'project_id' => $data['project_id'],
            'role_id' => $data['role_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ];

        $this->seatEmployeeInVehicle((int) $data['employee_id']);
        $this->dispatch('refresh-assignments');
    }

    public function handleAssignmentRangeRemoved($data = []): void
    {
        if (empty($data) || ! is_array($data)) {
            return;
        }

        $employeeId = $data['employee_id'] ?? null;
        $projectId = $data['project_id'] ?? null;
        $roleId = $data['role_id'] ?? null;
        if (! $employeeId || ! $projectId || ! $roleId) {
            return;
        }

        unset($this->assignmentRanges[$employeeId.'_'.$projectId.'_'.$roleId]);

        $stillAssigned = collect($this->assignmentRanges)->contains(
            fn ($range) => (int) ($range['employee_id'] ?? 0) === (int) $employeeId
        );
        $alreadyOnTrip = $this->departure()->participants
            ->contains(fn ($p) => (int) $p->employee_id === (int) $employeeId);
        if (! $stillAssigned && ! $alreadyOnTrip) {
            $this->clearEmployeeFromVehicleSeats((int) $employeeId);
        }

        $this->dispatch('refresh-assignments');
    }

    public function handleAccommodationAssigned($data): void
    {
        if (! is_array($data) || empty($data['employee_id'])) {
            return;
        }

        if ($this->lockEmployeeId && (int) $data['employee_id'] !== $this->lockEmployeeId) {
            return;
        }

        $this->accommodationAssignments[$data['employee_id']] = [
            'accommodation_id' => $data['accommodation_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ];
    }

    public function handleAccommodationRemoved($data): void
    {
        if (! is_array($data) || empty($data['employee_id'])) {
            return;
        }

        unset($this->accommodationAssignments[$data['employee_id']]);
    }

    public function handleVehicleAssigned($data): void
    {
        if (! is_array($data) || empty($data['employee_id'])) {
            return;
        }

        if ($this->lockEmployeeId && (int) $data['employee_id'] !== $this->lockEmployeeId) {
            return;
        }

        $this->vehicleAssignments[$data['employee_id']] = [
            'vehicle_id' => $data['vehicle_id'],
            'position' => $data['position'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ];
    }

    public function handleVehicleAssignmentRemoved($data): void
    {
        if (! is_array($data) || empty($data['employee_id'])) {
            return;
        }

        unset($this->vehicleAssignments[$data['employee_id']]);
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

        $this->vehicleSeats[0] = $this->buildSeatRow(0, $employeeId, 'driver', $employeeId === null);
    }

    public function toggleExternalDriver(): void
    {
        if (! isset($this->vehicleSeats[0])) {
            return;
        }

        $current = (bool) ($this->vehicleSeats[0]['external_driver'] ?? true);
        $this->vehicleSeats[0]['external_driver'] = ! $current;
        if (! $current) {
            $previousDriver = (int) ($this->vehicleSeats[0]['employee_id'] ?? 0);
            $this->vehicleSeats[0]['employee_id'] = null;
            if ($previousDriver > 0) {
                $this->seatEmployeeInVehicle($previousDriver);
            }
        }
    }

    public function getSeatEmployeesProperty()
    {
        $byId = [];
        foreach ($this->tripParticipants as $employee) {
            $byId[(int) $employee->id] = $employee;
        }
        foreach ($this->selectedEmployees as $employee) {
            $byId[(int) $employee->id] = $employee;
        }

        return collect($byId)->sortBy(fn ($e) => $e->last_name.' '.$e->first_name)->values();
    }

    public function getDepartureProperty(): LogisticsEvent
    {
        return $this->departure();
    }

    public function getIsEditProperty(): bool
    {
        return $this->lockEmployeeId !== null;
    }

    public function getAllowedEmployeeIdsProperty(): array
    {
        return $this->lockEmployeeId ? [$this->lockEmployeeId] : [];
    }

    public function getTripParticipantsProperty()
    {
        return $this->departure()->participants
            ->map(fn ($p) => $p->employee)
            ->filter()
            ->sortBy(fn ($e) => $e->last_name.' '.$e->first_name)
            ->values();
    }

    public function getSelectedVehicleProperty()
    {
        return $this->departure()->vehicle;
    }

    public function getAvailableVehiclesProperty()
    {
        $vehicle = $this->selectedVehicle;

        return $vehicle ? collect([$vehicle]) : collect();
    }

    public function getAvailablePublicTransportHubsProperty()
    {
        $ids = collect([
            $this->sharedStartAirportLocationId,
            $this->sharedEndAirportLocationId,
        ])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return collect();
        }

        return Location::query()->whereIn('id', $ids)->orderBy('name')->get();
    }

    public function getSelectedEmployeesProperty()
    {
        $ids = $this->selectedEmployeeIds;
        if ($ids === []) {
            return collect();
        }

        return Employee::query()
            ->whereIn('id', $ids)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public function getSelectedEmployeeIdsProperty(): array
    {
        $ids = collect();
        foreach ($this->assignmentRanges as $range) {
            if (! empty($range['employee_id'])) {
                $ids->push((int) $range['employee_id']);
            }
        }

        return $ids->filter(fn ($id) => $id > 0)->unique()->values()->all();
    }

    public function getStep2TabIncompleteProperty(): bool
    {
        foreach ($this->selectedEmployeeIds as $empId) {
            $row = $this->accommodationAssignments[$empId]
                ?? $this->accommodationAssignments[(string) $empId]
                ?? null;
            if (! is_array($row) || empty($row['accommodation_id'])) {
                return true;
            }
        }

        return false;
    }

    public function getStep3TabIncompleteProperty(): bool
    {
        foreach ($this->selectedEmployeeIds as $empId) {
            $row = $this->vehicleAssignments[$empId]
                ?? $this->vehicleAssignments[(string) $empId]
                ?? null;
            if (! is_array($row) || empty($row['vehicle_id'])) {
                return true;
            }
        }

        return false;
    }

    public function getHeaderTicketsIncompleteProperty(): bool
    {
        if ($this->transportMode !== 'public') {
            return false;
        }

        return PublicTransportTicketCosts::areIncompleteForEmployees(
            $this->selectedEmployeeIds,
            $this->ticketCostsByEmployee,
            false
        );
    }

    public function getCancelHrefProperty(): string
    {
        return route('departures.show', $this->departureId);
    }

    public function render()
    {
        return view('livewire.departure-participant-planner');
    }

    protected function departure(): LogisticsEvent
    {
        $departure = LogisticsEvent::query()
            ->with([
                'fromLocation',
                'toLocation',
                'vehicle',
                'participants.employee',
                'projectAssignments',
                'accommodationAssignments',
                'vehicleAssignments.vehicle',
                'transportCosts',
            ])
            ->findOrFail($this->departureId);

        if ($departure->type !== LogisticsEventType::DEPARTURE) {
            abort(404);
        }

        return $departure;
    }

    protected function loadPlannerStateFromDeparture(): void
    {
        $departure = $this->departure();

        $this->departureDate = $departure->event_date->format('Y-m-d');
        $this->endDate = $departure->end_date
            ? $departure->end_date->format('Y-m-d')
            : $this->departureDate;
        $this->vehicleId = $departure->vehicle_id;
        $this->transportMode = $departure->vehicle_id ? 'own' : 'public';
        $this->fillPublicHubsFromDeparture($departure);
        $this->fillVehicleSeatsFromDeparture($departure);

        if (! $this->lockEmployeeId) {
            return;
        }

        $employeeId = $this->lockEmployeeId;
        $employee = Employee::find($employeeId);

        foreach ($departure->projectAssignments as $assignment) {
            if ((int) $assignment->employee_id !== $employeeId) {
                continue;
            }
            $key = $assignment->employee_id.'_'.$assignment->project_id.'_'.$assignment->role_id;
            $this->assignmentRanges[$key] = [
                'employee_id' => (int) $assignment->employee_id,
                'project_id' => (int) $assignment->project_id,
                'role_id' => (int) $assignment->role_id,
                'start_date' => $this->dateString($assignment->start_date),
                'end_date' => $this->dateString($assignment->end_date ?? $assignment->start_date),
            ];
        }

        $acc = $departure->accommodationAssignments->firstWhere('employee_id', $employeeId);
        if ($acc) {
            $this->accommodationAssignments[$employeeId] = [
                'accommodation_id' => (int) $acc->accommodation_id,
                'start_date' => $this->dateString($acc->start_date),
                'end_date' => $this->dateString($acc->end_date ?? $acc->start_date),
            ];
        }

        $veh = $departure->vehicleAssignments->firstWhere('employee_id', $employeeId);
        if ($veh) {
            $position = $veh->position instanceof VehiclePosition
                ? $veh->position->value
                : (string) $veh->position;
            $this->vehicleAssignments[$employeeId] = [
                'vehicle_id' => (int) $veh->vehicle_id,
                'position' => $position !== '' ? $position : 'passenger',
                'start_date' => $this->dateString($veh->start_date),
                'end_date' => $this->dateString($veh->end_date ?? $veh->start_date),
            ];
        }

        if ($this->transportMode === 'public' && $employee) {
            $ticket = $departure->transportCosts
                ->where('cost_type', 'ticket')
                ->first(fn ($c) => str_contains((string) $c->description, $employee->full_name));
            if ($ticket) {
                $this->ticketCostsByEmployee[$employeeId] = [
                    'amount' => $ticket->amount,
                    'currency' => PublicTransportTicketCosts::normalizeCurrency($ticket->currency),
                ];
            }
        }
    }

    protected function fillPublicHubsFromDeparture(LogisticsEvent $departure): void
    {
        foreach ($departure->route_segments ?? [] as $seg) {
            if (($seg['mode'] ?? '') !== 'public') {
                continue;
            }
            $this->publicTransportHubKind = $seg['hub_kind'] ?? null;
            $this->sharedStartAirportLocationId = $seg['start_location_id'] ?? $departure->from_location_id;
            $this->sharedEndAirportLocationId = $seg['end_location_id'] ?? $departure->to_location_id;

            return;
        }

        if ($this->transportMode === 'public') {
            $this->sharedStartAirportLocationId = $departure->from_location_id;
            $this->sharedEndAirportLocationId = $departure->to_location_id;
        }
    }

    protected function fillVehicleSeatsFromDeparture(LogisticsEvent $departure): void
    {
        $vehicle = $departure->vehicle;
        if ($this->transportMode !== 'own' || ! $vehicle) {
            $this->vehicleSeats = [];

            return;
        }

        $capacity = max(1, (int) $vehicle->capacity);
        $driverId = Adjustment::query()
            ->where('logistics_event_id', $departure->id)
            ->where('type', 'bonus')
            ->where('notes', 'like', 'Uznanie za kierowanie pojazdem wyjazdu%')
            ->orderBy('id')
            ->value('employee_id');
        if (! $driverId) {
            $driverId = Adjustment::query()
                ->where('logistics_event_id', $departure->id)
                ->where('type', 'bonus')
                ->orderBy('id')
                ->value('employee_id');
        }
        $driverId = $driverId ? (int) $driverId : null;

        $participantIds = $departure->participants
            ->pluck('employee_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $this->vehicleSeats = [];
        $this->vehicleSeats[0] = [
            'employee_id' => $driverId,
            'position' => 'driver',
            'external_driver' => $driverId === null,
        ];

        $passengers = $participantIds
            ->reject(fn ($id) => $driverId !== null && $id === $driverId)
            ->values();

        for ($i = 1; $i < $capacity; $i++) {
            $eid = $passengers->get($i - 1);
            $this->vehicleSeats[$i] = [
                'employee_id' => $eid ?: null,
                'position' => 'passenger',
                'external_driver' => false,
            ];
        }
    }

    protected function seatEmployeeInVehicle(int $employeeId): void
    {
        if ($this->transportMode !== 'own' || $this->vehicleSeats === []) {
            return;
        }

        $already = collect($this->vehicleSeats)->contains(
            fn ($seat) => (int) ($seat['employee_id'] ?? 0) === $employeeId
        );
        if ($already) {
            return;
        }

        for ($i = 1; $i < count($this->vehicleSeats); $i++) {
            if (empty($this->vehicleSeats[$i]['employee_id'])) {
                $this->vehicleSeats[$i]['employee_id'] = $employeeId;

                return;
            }
        }
    }

    protected function clearEmployeeFromVehicleSeats(int $employeeId): void
    {
        if ($this->vehicleSeats === []) {
            return;
        }

        if ((int) ($this->vehicleSeats[0]['employee_id'] ?? 0) === $employeeId) {
            $this->vehicleSeats[0] = $this->buildSeatRow(0, null, 'driver', true);
        }

        for ($i = 1; $i < count($this->vehicleSeats); $i++) {
            if ((int) ($this->vehicleSeats[$i]['employee_id'] ?? 0) === $employeeId) {
                $this->vehicleSeats[$i]['employee_id'] = null;
            }
        }
        $this->compactPassengerSeats();
    }

    protected function currentDriverEmployeeId(): ?int
    {
        if ($this->transportMode !== 'own' || empty($this->vehicleSeats[0])) {
            return null;
        }

        if (! empty($this->vehicleSeats[0]['external_driver'])) {
            return null;
        }

        $id = (int) ($this->vehicleSeats[0]['employee_id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    protected function dateString(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        return Carbon::parse((string) $value)->format('Y-m-d');
    }
}
