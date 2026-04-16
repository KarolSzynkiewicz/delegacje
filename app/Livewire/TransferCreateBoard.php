<?php

namespace App\Livewire;

use App\Enums\ProjectStatus;
use App\Enums\VehiclePosition;
use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Role;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Services\AssignmentQueryService;
use App\Services\DateRangeService;
use App\Services\DeparturePlannerService;
use App\Services\TransferService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class TransferCreateBoard extends Component
{
    /** Data transferu (dzień). */
    public string $transferDate = '';

    /** assignment | transport */
    public string $mode = 'assignment';

    public ?string $successBanner = null;

    /**
     * Szkic: project_assignment_id => docelowy project_id (kolumna).
     * Nie zapisuje się w bazie — tylko UI przed kolejnym krokiem.
     *
     * @var array<int, int>
     */
    public array $draftProjectByAssignment = [];

    /**
     * Szkic: po potwierdzeniu kalendarza — planowana rola i zakres dat nowego przypisania.
     *
     * @var array<int, array{role_id: int, role_name: string, start_date: string, end_date: string}>
     */
    public array $draftAssignmentDetails = [];

    // —— Modal: braki ról (14 dni) ——
    public bool $showGapsModal = false;

    public ?int $pendingAssignmentId = null;

    public ?int $pendingTargetProjectId = null;

    /** Fragment z getProjectGapsForTwoWeeks: id, name, location, roles[] */
    public ?array $gapsModalProject = null;

    /** @var array<int, array{id:int,name:string,min_gaps?:int,max_gaps?:int}> */
    public array $gapsModalRoles = [];

    // —— Modal: kalendarz przypisania ——
    public bool $showCalendarModal = false;

    public ?int $pendingEmployeeId = null;

    public ?int $selectedRoleId = null;

    public array $employeeAvailability = [];

    public ?string $selectedStartDate = null;

    public ?string $selectedEndDate = null;

    public ?string $calendarMonthStart = null;

    /**
     * board — tablica Kanban | followup — pytanie o mieszkanie/pojazd |
     * accommodation | vehicle | done — podsumowanie szkicu.
     */
    public string $wizardPhase = 'board';

    /** true = „Przypisz nowe” — krok wyboru mieszkania; false = „Nie zmienia się” (keep current przy zapisie). */
    public bool $assignNewAccommodation = false;

    /** true = „Przypisz nowe” — krok wyboru pojazdu; false = „Nie zmienia się”. */
    public bool $assignNewVehicle = false;

    /**
     * Zgodnie z kreatorem wyjazdu: zakresy przypisań projektowych (ze szkicu).
     *
     * @var array<string, array{employee_id: int, project_id: int, role_id: int, start_date: string, end_date: string}>
     */
    public array $assignmentRanges = [];

    /**
     * @var array<int, array{accommodation_id: int, start_date: string, end_date: string}>
     */
    public array $accommodationAssignments = [];

    /**
     * @var array<int, array{vehicle_id: int, position: string, start_date: string, end_date: string}>
     */
    public array $vehicleAssignments = [];

    protected DeparturePlannerService $departurePlannerService;

    protected TransferService $transferService;

    protected AssignmentQueryService $assignmentQueryService;

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
        AssignmentQueryService $assignmentQueryService
    ): void {
        $this->departurePlannerService = $departurePlannerService;
        $this->transferService = $transferService;
        $this->assignmentQueryService = $assignmentQueryService;
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

    public function mount(): void
    {
        $this->transferDate = now()->format('Y-m-d');
    }

    public function updatedTransferDate(): void
    {
        $this->draftProjectByAssignment = [];
        $this->draftAssignmentDetails = [];
        $this->successBanner = null;
        $this->resetTransferWizardState();
        $this->closeAllModals();
    }

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
        $this->assignNewAccommodation = false;
        $this->assignNewVehicle = false;
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

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function onTransferWizardBack(?array $payload = null): void
    {
        $screen = is_array($payload) ? ($payload['screen'] ?? null) : null;

        if ($screen === 'accommodation') {
            $this->wizardPhase = 'followup';

            return;
        }

        if ($screen === 'vehicle') {
            if ($this->assignNewAccommodation) {
                $this->wizardPhase = 'accommodation';
            } else {
                $this->wizardPhase = 'followup';
            }
        }
    }

    public function finishWizardBackToBoard(): void
    {
        $this->resetTransferWizardState();
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

    /**
     * @return list<int>
     */
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
     * Wiersze podsumowania szkicu projektowego (osoby → docelowy projekt, rola, daty).
     *
     * @return list<array{employee_name: string, project_name: string, role_name: string, date_label: string}>
     */
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
            $dateLabel = $details['start_date'] === $details['end_date']
                ? $start
                : $start.' – '.$end;
            $rows[] = [
                'employee_name' => $pa?->employee?->full_name ?? '?',
                'project_name' => $project?->name ?? '?',
                'role_name' => $details['role_name'] ?? '?',
                'date_label' => $dateLabel,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{employee_name: string, label: string, dates: string}>
     */
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
                'dates' => Carbon::parse($row['start_date'])->format('d.m.Y')
                    .' – '.Carbon::parse($row['end_date'])->format('d.m.Y'),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{employee_name: string, label: string, dates: string, position: string}>
     */
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
            $pos = ($row['position'] ?? '') === 'driver' ? 'Kierowca' : 'Pasażer';
            $rows[] = [
                'employee_name' => $emp?->full_name ?? '?',
                'label' => $veh?->registration_number ?? ('#'.$row['vehicle_id']),
                'dates' => Carbon::parse($row['start_date'])->format('d.m.Y')
                    .' – '.Carbon::parse($row['end_date'])->format('d.m.Y'),
                'position' => $pos,
            ];
        }

        return $rows;
    }

    /**
     * Podgląd przypisań, które zostaną skrócone lub zastąpione przy zapisie transferu.
     *
     * @return list<array{kind_label: string, employee_name: string, item_label: string, detail: string}>
     */
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

            if ($paStart->gte($transferDay)) {
                $detail = 'Przypisanie do „'.($pa->project?->name ?? '?').'” zaczyna się w dniu transferu ('.$dateLabel.'), więc nie da się go skrócić do dnia wcześniejszego bez sprzeczności z datami. '
                    .'Zostanie usunięte i zastąpione nowym przypisaniem do „'.$targetName.'” od '.$dateLabel.' — zgodnie z celem: od tego dnia praca w nowym projekcie (w praktyce „od dziś gdzie indziej”).';
            } else {
                $detail = 'Skrócenie końca przypisania do '.$dayBeforeLabel.' (wcześniej do: '.$endWas.').';
            }

            $rows[] = [
                'kind_label' => 'Projekt',
                'employee_name' => $pa->employee?->full_name ?? '?',
                'item_label' => $pa->project?->name ?? '?',
                'detail' => $detail,
            ];
        }

        foreach ($this->draftEmployeeIds as $employeeId) {
            $employee = Employee::query()->find($employeeId);
            $name = $employee?->full_name ?? '?';

            if ($this->assignNewAccommodation) {
                $aa = AccommodationAssignment::query()
                    ->where('employee_id', $employeeId)
                    ->where('start_date', '<=', $transferDay)
                    ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $transferDay))
                    ->orderByDesc('start_date')
                    ->orderByDesc('id')
                    ->with('accommodation')
                    ->first();
                if ($aa) {
                    $endWas = $aa->end_date ? $aa->end_date->format('d.m.Y') : 'otwarte (brak końca)';
                    $aaStart = DateRangeService::normalizeDate($aa->start_date);
                    if ($aaStart->gte($transferDay)) {
                        $detail = 'Przypisanie do „'.($aa->accommodation?->name ?? '?').'” zaczyna się w dniu transferu — zostanie usunięte i zastąpione nowym mieszkaniem od '.$dateLabel.'.';
                    } else {
                        $detail = 'Skrócenie końca przypisania do '.$dayBeforeLabel.' (wcześniej do: '.$endWas.').';
                    }
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
                    ->orderByDesc('start_date')
                    ->orderByDesc('id')
                    ->with('vehicle')
                    ->first();
                if ($va && $va->vehicle) {
                    $endWas = $va->end_date ? $va->end_date->format('d.m.Y') : 'otwarte (brak końca)';
                    $vaStart = DateRangeService::normalizeDate($va->start_date);
                    if ($vaStart->gte($transferDay)) {
                        $detail = 'Przypisanie do pojazdu '.$va->vehicle->registration_number.' zaczyna się w dniu transferu — zostanie usunięte i zastąpione nowym od '.$dateLabel.'.';
                    } else {
                        $detail = 'Skrócenie końca przypisania do '.$dayBeforeLabel.' (wcześniej do: '.$endWas.').';
                    }
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

    public function saveTransferFromSummary(): void
    {
        if ($this->wizardPhase !== 'done') {
            return;
        }

        $validation = $this->validateTransferBeforeCommit();
        if ($validation !== null) {
            session()->flash('warning', $validation);

            return;
        }

        try {
            $payload = $this->buildCommitTransferPayload();
            $event = $this->transferService->commitTransfer($payload);
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: $e->getMessage();
            session()->flash('warning', $msg);

            return;
        } catch (\Throwable $e) {
            session()->flash('warning', $e->getMessage());

            return;
        }

        $this->draftProjectByAssignment = [];
        $this->draftAssignmentDetails = [];
        $this->successBanner = null;
        $this->resetTransferWizardState();

        session()->flash('success', 'Transfer został zapisany.');

        $this->redirect(route('transfers.show', $event), navigate: true);
    }

    protected function validateTransferBeforeCommit(): ?string
    {
        if ($this->draftProjectByAssignment === []) {
            return 'Brak szkicu przypisań do projektu.';
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
                return 'Ta sama osoba występuje w szkicu więcej niż raz — zostaw jedno przeniesienie na pracownika.';
            }
            $seenEmployees[$eid] = true;
        }

        foreach ($this->draftEmployeeIds as $employeeId) {
            if ($this->assignNewAccommodation && empty($this->accommodationAssignments[$employeeId]['accommodation_id'])) {
                return 'Brak przypisania mieszkania dla: '.(Employee::find($employeeId)?->full_name ?? 'ID '.$employeeId).'.';
            }
            if ($this->assignNewVehicle && empty($this->vehicleAssignments[$employeeId]['vehicle_id'])) {
                return 'Brak przypisania pojazdu dla: '.(Employee::find($employeeId)?->full_name ?? 'ID '.$employeeId).'.';
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildCommitTransferPayload(): array
    {
        $transferDate = Carbon::parse($this->transferDate)->startOfDay();

        $firstAssignmentId = array_key_first($this->draftProjectByAssignment);
        $firstPa = ProjectAssignment::query()->with('project.location')->find((int) $firstAssignmentId);
        $firstTargetId = (int) ($this->draftProjectByAssignment[$firstAssignmentId] ?? 0);
        $targetProject = Project::query()->with('location')->find($firstTargetId);

        $base = Location::getBase();
        $fromLocationId = (int) ($firstPa?->project?->location_id ?? $base->id);
        $toLocationId = (int) ($targetProject?->location_id ?? $fromLocationId);
        if ($fromLocationId === 0) {
            $fromLocationId = $base->id;
        }
        if ($toLocationId === 0) {
            $toLocationId = $base->id;
        }

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

            $accRow = $this->accommodationAssignments[$employeeId] ?? null;
            $vehRow = $this->vehicleAssignments[$employeeId] ?? null;

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
                'vehicle_position' => ($vehRow['position'] ?? null)
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
            'transfer_date' => $transferDate,
            'vehicle_id' => null,
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
        ];
    }

    public function handleAccommodationAssigned(array $data): void
    {
        if ($this->wizardPhase !== 'accommodation' || empty($data['employee_id'])) {
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
        if ($this->wizardPhase !== 'vehicle' || empty($data['employee_id'])) {
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
     * Po upuszczeniu karty na projekt: walidacja + modal braków w rolach.
     */
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

        if ($slice && ! empty($slice['roles'])) {
            $this->gapsModalRoles = $slice['roles'];
        } else {
            $this->gapsModalRoles = $this->fallbackRolesFromDemands($project, $arrival);
        }

        if ($this->gapsModalRoles === []) {
            if ($assignment->role_id) {
                $this->openCalendarForRole((int) $assignment->role_id);

                return;
            }
            $this->successBanner = null;
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

    /**
     * @return array<int, array{id:int,name:string,min_gaps:int,max_gaps:int}>
     */
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
            $employee,
            $project,
            $role,
            $monthStart,
            $monthEnd,
            [],
            [],
            $minDate,
            true
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

        $this->showCalendarModal = false;
        $this->resetPendingDrop();
        $this->resetValidation(['confirmation']);
    }

    public function moveAssignment(int $assignmentId, int $toProjectId): void
    {
        $date = Carbon::parse($this->transferDate)->startOfDay();

        $assignment = ProjectAssignment::query()
            ->activeAtDate($date)
            ->find($assignmentId);

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
        $this->resetTransferWizardState();
    }

    /**
     * @return list<array{project: \App\Models\Project, assignments: Collection<int, ProjectAssignment>}>
     */
    public function getColumnsProperty(): array
    {
        $date = Carbon::parse($this->transferDate)->startOfDay();

        /** @var Collection<int, array{project: Project, assignments: Collection<int, ProjectAssignment>}> $byProject */
        $byProject = collect();

        foreach (
            Project::query()
                ->where('status', ProjectStatus::ACTIVE)
                ->activeAtDate($date)
                ->with('location')
                ->orderBy('name')
                ->get() as $project
        ) {
            $byProject->put($project->id, [
                'project' => $project,
                'assignments' => collect(),
            ]);
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

                $byProject->put($effectiveProjectId, [
                    'project' => $project,
                    'assignments' => collect(),
                ]);
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
