<?php

namespace App\Services;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Enums\VehiclePosition;
use App\Enums\VehicleType;
use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\AccommodationLease;
use App\Models\Employee;
use App\Models\LogisticsEvent;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Role;
use App\Models\Rotation;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Support\AssignmentTimelineMath;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AssignmentTimelineService
{
    public const DAY_WIDTH = 22;

    public function __construct(
        protected ProjectAssignmentService $projectAssignments,
        protected AccommodationAssignmentService $accommodations,
        protected VehicleAssignmentService $vehicles,
        protected VehicleValidationService $vehicleValidation,
        protected RotationService $rotations,
        protected LocationTrackingService $locations,
    ) {}

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function window(Carbon $focus): array
    {
        $start = $focus->copy()->startOfDay()->startOfWeek(Carbon::MONDAY)->subWeeks(2);
        $end = $start->copy()->addWeeks(16)->subDay();

        return [$start, $end->startOfDay()];
    }

    /**
     * @param  array<string, array{view: bool, create: bool, update: bool}>  $can
     * @return array<string, mixed>
     */
    public function employeeBoard(Employee $employee, Carbon $start, Carbon $end, array $can): array
    {
        $rotations = Rotation::query()
            ->where('employee_id', $employee->id)
            ->overlappingWith($start, $end)
            ->orderBy('start_date')
            ->get();

        $projects = ProjectAssignment::query()
            ->where('employee_id', $employee->id)
            ->overlappingWith($start, $end)
            ->with('project:id,name')
            ->orderBy('start_date')
            ->get();

        $houses = AccommodationAssignment::query()
            ->where('employee_id', $employee->id)
            ->overlappingWith($start, $end)
            ->with('accommodation:id,name')
            ->orderBy('start_date')
            ->get();

        $cars = VehicleAssignment::query()
            ->where('employee_id', $employee->id)
            ->overlappingWith($start, $end)
            ->with('vehicle:id,registration_number,brand,model')
            ->orderBy('start_date')
            ->get();

        $rotationRanges = $this->rangesFrom($rotations);
        $windowStart = $start->toDateString();
        $windowEnd = $end->toDateString();
        $windowBound = [['start' => $windowStart, 'end' => $windowEnd]];
        $rotationBounds = $this->rotationBounds($rotationRanges, $windowStart, $windowEnd);

        $lanes = [];
        if ($can['rotation']['view'] ?? false) {
            $lanes[] = $this->lane(
                'rotation',
                'Rotacja',
                $can['rotation'],
                $this->barsFrom($rotations, fn (Rotation $row): string => 'Rotacja', $windowStart, $windowEnd, $windowBound, $rotationRanges),
                $windowBound,
                $this->closeRanges($rotationRanges, $windowBound, $windowStart, $windowEnd),
            );
        }
        if ($can['project']['view'] ?? false) {
            $ranges = $this->rangesFrom($projects);
            $lanes[] = $this->lane(
                'project',
                'Projekt',
                $can['project'],
                $this->barsFrom($projects, fn (ProjectAssignment $row): string => $row->project?->name ?? 'Projekt', $windowStart, $windowEnd, $rotationRanges, $ranges),
                $rotationBounds,
                $this->closeRanges($ranges, $rotationRanges, $windowStart, $windowEnd),
            );
        }
        if ($can['accommodation']['view'] ?? false) {
            $ranges = $this->rangesFrom($houses);
            $lanes[] = $this->lane(
                'accommodation',
                'Dom',
                $can['accommodation'],
                $this->barsFrom($houses, fn (AccommodationAssignment $row): string => $row->accommodation?->name ?? 'Dom', $windowStart, $windowEnd, $rotationRanges, $ranges),
                $rotationBounds,
                $this->closeRanges($ranges, $rotationRanges, $windowStart, $windowEnd),
            );
        }
        if ($can['vehicle']['view'] ?? false) {
            $ranges = $this->rangesFrom($cars);
            $lanes[] = $this->lane(
                'vehicle',
                'Auto',
                $can['vehicle'],
                $this->barsFrom($cars, function (VehicleAssignment $row): string {
                    $vehicle = $row->vehicle;
                    $name = trim(($vehicle->brand ?? '').' '.($vehicle->model ?? '').' '.($vehicle->registration_number ?? ''));

                    return $name !== '' ? $name : 'Auto';
                }, $windowStart, $windowEnd, $rotationRanges, $ranges, true),
                $rotationBounds,
                $this->closeRanges($ranges, $rotationRanges, $windowStart, $windowEnd),
            );
        }

        return $this->frame($start, $end, $lanes, $this->markersForEmployees([$employee->id], $start, $end));
    }

    /**
     * @param  array{view: bool, create: bool, update: bool}  $can
     * @return array<string, mixed>
     */
    public function resourceBoard(string $type, int $id, Carbon $start, Carbon $end, array $can): array
    {
        $windowStart = $start->toDateString();
        $windowEnd = $end->toDateString();

        [$label, $rows, $employeeIds] = match ($type) {
            'project' => $this->projectRows($id, $start, $end),
            'vehicle' => $this->vehicleRows($id, $start, $end),
            'accommodation' => $this->houseRows($id, $start, $end),
            default => ['Przypisania', collect(), []],
        };

        $rotations = $employeeIds === []
            ? collect()
            : Rotation::query()->whereIn('employee_id', $employeeIds)->overlappingWith($start, $end)->get();

        $siblings = $this->siblingRanges($type, $employeeIds, $start, $end);
        $rotationsByEmployee = $rotations->groupBy('employee_id');

        $bars = [];
        foreach ($rows as $row) {
            $employeeId = (int) $row->employee_id;
            $employeeRotations = $this->rangesFrom($rotationsByEmployee->get($employeeId, collect()));
            $barStart = $row->start_date->toDateString();
            $bound = $this->spanBound($barStart, $employeeRotations, $windowStart, $windowEnd);
            $others = $this->closeRanges(array_values(array_filter(
                $siblings[$employeeId] ?? [],
                fn (array $range): bool => (int) $range['id'] !== (int) $row->id
            )), $employeeRotations, $windowStart, $windowEnd);
            $limits = AssignmentTimelineMath::resizeLimits(
                $row->start_date->toDateString(),
                $row->end_date?->toDateString(),
                $others,
                $bound['start'],
                $bound['end'],
            );
            $pixels = $this->pixels($windowStart, $windowEnd, $row->start_date->toDateString(), $row->end_date?->toDateString());
            $bars[] = [
                'id' => (int) $row->id,
                'start' => $row->start_date->toDateString(),
                'end' => $row->end_date?->toDateString(),
                'open' => $row->end_date === null,
                'label' => $this->resourceBarLabel($type, $row),
                'min' => $limits['min'],
                'max' => $limits['max'],
                'locked' => $type === 'vehicle' && (bool) $row->is_return_trip,
                'left' => $pixels['left'],
                'width' => $pixels['width'],
            ];
        }

        $packed = $this->pack($bars);

        $laneKey = match ($type) {
            'project' => 'project',
            'vehicle' => 'vehicle',
            default => 'accommodation',
        };

        $lanes = [[
            'key' => $laneKey,
            'label' => $label,
            'can_create' => $can['create'],
            'can_update' => $can['update'],
            'bars' => $packed['bars'],
            'rows' => $packed['rows'],
            'gaps' => $can['create'] ? [['start' => $windowStart, 'end' => $windowEnd]] : [],
        ]];

        $markers = $type === 'vehicle'
            ? $this->markersForVehicle($id, $start, $end)
            : $this->markersForEmployees($employeeIds, $start, $end);

        return $this->frame($start, $end, $lanes, $markers);
    }

    /**
     * @return list<array{key: string, label: string, meta: string, enabled: bool, group: int, driver_taken: bool}>
     */
    public function optionsFor(string $lane, Employee $employee, string $start, string $end): array
    {
        return match ($lane) {
            'project' => $this->projectOptions($employee, $start, $end),
            'accommodation' => $this->houseOptions($employee, $start, $end),
            'vehicle' => $this->vehicleOptions($employee, $start, $end),
            default => [],
        };
    }

    /**
     * @return list<array{key: string, label: string, meta: string, enabled: bool, group: int, driver_taken: bool}>
     */
    public function resourceOptions(string $type, int $resourceId, string $start, string $end): array
    {
        $rotations = Rotation::query()
            ->overlappingWith($start, $end)
            ->with('employee:id,first_name,last_name')
            ->get();

        $employees = $rotations
            ->map(fn (Rotation $rotation) => $rotation->employee)
            ->filter()
            ->unique('id')
            ->values();

        if ($type === 'project') {
            return $this->resourceProjectOptions($resourceId, $employees, $start, $end);
        }

        if ($type === 'accommodation') {
            return $this->resourceHouseOptions($resourceId, $employees, $start, $end);
        }

        return $this->resourceVehicleOptions($resourceId, $employees, $start, $end);
    }

    /**
     * @param  array{lane: string, id: ?int, start: string, end: ?string, keep_open: bool}  $proposal
     */
    public function commitEmployee(Employee $employee, array $proposal, ?string $choice, string $seat): void
    {
        $start = Carbon::parse($proposal['start'])->startOfDay();
        $end = $proposal['keep_open'] || $proposal['end'] === null
            ? null
            : Carbon::parse($proposal['end'])->startOfDay();

        if ($proposal['id']) {
            $this->resizeEmployee($employee, $proposal['lane'], (int) $proposal['id'], $start, $end, (bool) $proposal['keep_open']);

            return;
        }

        if ($end === null) {
            throw ValidationException::withMessages(['end_date' => 'Nowy zakres potrzebuje daty końca.']);
        }

        match ($proposal['lane']) {
            'rotation' => $this->rotations->createRotation($employee, $start, $end),
            'project' => $this->createProject($employee, (string) $choice, $start, $end),
            'accommodation' => $this->accommodations->createAssignment(
                $employee,
                Accommodation::query()->findOrFail((int) $choice),
                $start,
                $end,
            ),
            'vehicle' => $this->vehicles->createAssignment(
                $employee,
                Vehicle::query()->findOrFail((int) $choice),
                $seat === VehiclePosition::DRIVER->value ? VehiclePosition::DRIVER : VehiclePosition::PASSENGER,
                $start,
                $end,
            ),
            default => throw ValidationException::withMessages(['lane' => 'Nieznany tor.']),
        };
    }

    /**
     * @param  array{lane: string, id: ?int, start: string, end: ?string, keep_open: bool}  $proposal
     */
    public function commitResource(string $type, int $resourceId, array $proposal, ?string $choice, string $seat): void
    {
        $start = Carbon::parse($proposal['start'])->startOfDay();
        $end = $proposal['keep_open'] || $proposal['end'] === null
            ? null
            : Carbon::parse($proposal['end'])->startOfDay();

        if ($proposal['id']) {
            $assignment = $this->resourceAssignment($type, $resourceId, (int) $proposal['id']);
            $this->resizeEmployee($assignment->employee, $proposal['lane'], (int) $assignment->id, $start, $end, (bool) $proposal['keep_open']);

            return;
        }

        if ($end === null || $choice === null || $choice === '') {
            throw ValidationException::withMessages(['choice' => 'Wybierz osobę.']);
        }

        if ($type === 'project') {
            [$employeeId, $roleId] = array_pad(explode(':', $choice, 2), 2, null);
            $employee = Employee::query()->findOrFail((int) $employeeId);
            $this->projectAssignments->createAssignment(
                Project::query()->findOrFail($resourceId),
                $employee,
                Role::query()->findOrFail((int) $roleId),
                $start,
                $end,
            );

            return;
        }

        $employee = Employee::query()->findOrFail((int) $choice);
        if ($type === 'accommodation') {
            $this->accommodations->createAssignment(
                $employee,
                Accommodation::query()->findOrFail($resourceId),
                $start,
                $end,
            );

            return;
        }

        $this->vehicles->createAssignment(
            $employee,
            Vehicle::query()->findOrFail($resourceId),
            $seat === VehiclePosition::DRIVER->value ? VehiclePosition::DRIVER : VehiclePosition::PASSENGER,
            $start,
            $end,
        );
    }

    /**
     * @param  list<array{start: string, end: string}>  $gaps
     */
    public function assertRangeInGaps(string $start, string $end, array $gaps): void
    {
        if (! AssignmentTimelineMath::rangeInsideGap($start, $end, $gaps)) {
            throw ValidationException::withMessages([
                'start_date' => 'Zakres musi leżeć w jednej rotacji i nie nachodzić na inny pasek tego toru.',
            ]);
        }
    }

    /**
     * A bar that already starts before the visible window keeps that start.
     * Only the edge that moved has to land inside the rotation.
     *
     * @param  array{min: string, max: string}  $limits
     */
    public function assertResizeInside(string $start, ?string $end, bool $keepOpen, array $limits, string $originalStart, ?string $originalEnd): void
    {
        if ($start !== $originalStart && ($start < $limits['min'] || $start > $limits['max'])) {
            throw ValidationException::withMessages([
                'start_date' => 'Początek wychodzi poza rotację albo nachodzi na sąsiedni pasek.',
            ]);
        }
        if (! $keepOpen && $end !== null && $end !== $originalEnd && ($end < $start || $end > $limits['max'] || $end < $limits['min'])) {
            throw ValidationException::withMessages([
                'end_date' => 'Koniec wychodzi poza rotację albo nachodzi na sąsiedni pasek.',
            ]);
        }
    }

    /**
     * @return list<array{key: string, label: string, meta: string, enabled: bool, group: int, driver_taken: bool}>
     */
    public function projectOptions(Employee $employee, string $start, string $end): array
    {
        $roleIds = $employee->roles()->pluck('roles.id')->map(fn ($id) => (int) $id)->all();

        $projects = Project::query()
            ->active()
            ->whereHas('demands', fn ($query) => $query->overlappingWith($start, $end))
            ->with([
                'demands' => fn ($query) => $query->overlappingWith($start, $end)->with('role:id,name'),
                'assignments' => fn ($query) => $query->overlappingWith($start, $end),
            ])
            ->orderBy('name')
            ->get(['id', 'name']);

        $options = [];
        foreach ($projects as $project) {
            $demandsByRole = $project->demands->groupBy('role_id');
            $assignedByRole = $project->assignments->groupBy('role_id');
            foreach ($demandsByRole as $roleId => $demands) {
                $role = $demands->first()?->role;
                $demandRanges = $demands->map(fn ($demand) => [
                    'start' => $demand->start_date->toDateString(),
                    'end' => $demand->end_date?->toDateString(),
                    'required' => (int) $demand->required_count,
                ])->all();
                $assigned = ($assignedByRole->get($roleId) ?? collect())->map(fn ($row) => [
                    'start' => $row->start_date->toDateString(),
                    'end' => $row->end_date?->toDateString(),
                ])->all();
                $free = AssignmentTimelineMath::minFree($demandRanges, $assigned, $start, $end);
                $hasRole = in_array((int) $roleId, $roleIds, true);
                $enabled = $hasRole && $free > 0;
                $options[] = $this->option(
                    $project->id.':'.$roleId,
                    trim($project->name.' · '.($role->name ?? 'rola')),
                    $hasRole ? ($free > 0 ? 'min. '.$free : 'brak miejsc') : 'brak roli',
                    $enabled,
                    $enabled ? 0 : 1,
                );
            }
        }

        return $this->sortOptions($options);
    }

    /**
     * @return list<array{key: string, label: string, meta: string, enabled: bool, group: int, driver_taken: bool}>
     */
    public function houseOptions(Employee $employee, string $start, string $end): array
    {
        $houses = Accommodation::query()
            ->orderBy('name')
            ->get(['id', 'name', 'capacity', 'location_id']);

        return $this->sortOptions($this->houseChoices($houses, $start, $end));
    }

    /**
     * @return list<array{key: string, label: string, meta: string, enabled: bool, group: int, driver_taken: bool}>
     */
    public function vehicleOptions(Employee $employee, string $start, string $end): array
    {
        $projectIds = ProjectAssignment::query()
            ->where('employee_id', $employee->id)
            ->overlappingWith($start, $end)
            ->pluck('project_id');

        $sameProjectVehicleIds = [];
        if ($projectIds->isNotEmpty()) {
            $coworkerIds = ProjectAssignment::query()
                ->whereIn('project_id', $projectIds)
                ->where('employee_id', '!=', $employee->id)
                ->overlappingWith($start, $end)
                ->pluck('employee_id');

            if ($coworkerIds->isNotEmpty()) {
                $sameProjectVehicleIds = VehicleAssignment::query()
                    ->whereIn('employee_id', $coworkerIds)
                    ->overlappingWith($start, $end)
                    ->pluck('vehicle_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->all();
            }
        }

        $vehicles = Vehicle::query()
            ->operational()
            ->where('type', VehicleType::COMPANY_VEHICLE->value)
            ->orderBy('registration_number')
            ->get(['id', 'registration_number', 'brand', 'model', 'capacity']);

        $assignments = VehicleAssignment::query()
            ->whereIn('vehicle_id', $vehicles->pluck('id'))
            ->overlappingWith($start, $end)
            ->get(['id', 'vehicle_id', 'start_date', 'end_date', 'position']);

        $conflicting = $this->vehicleValidation->vehicleIdsConflictingWithLogisticsEvent(
            $vehicles->pluck('id')->map(fn ($id) => (int) $id)->all(),
            Carbon::parse($start)->startOfDay(),
            Carbon::parse($end)->startOfDay(),
        );

        $byVehicle = $assignments->groupBy('vehicle_id');
        $outside = array_fill_keys(
            $this->locations->vehicleIdsOutsideBaseOn(
                $vehicles->pluck('id')->map(fn ($id) => (int) $id)->all(),
                Carbon::parse($start)->startOfDay(),
            ),
            true,
        );
        $options = [];
        foreach ($vehicles as $vehicle) {
            $rows = $byVehicle->get($vehicle->id, collect());
            $ranges = $rows->map(fn ($row) => [
                'start' => $row->start_date->toDateString(),
                'end' => $row->end_date?->toDateString(),
            ])->all();
            $capacity = (int) $vehicle->capacity;
            $occupancy = AssignmentTimelineMath::peak($ranges, $start, $end, $capacity);
            $driverTaken = $rows->contains(function ($row): bool {
                $position = $row->position;

                return $position === VehiclePosition::DRIVER || $position === VehiclePosition::DRIVER->value;
            });
            $conflict = in_array((int) $vehicle->id, $conflicting, true);
            $inField = isset($outside[(int) $vehicle->id]);
            $free = $capacity - $occupancy['peak'];
            $sameProject = in_array((int) $vehicle->id, $sameProjectVehicleIds, true);
            $enabled = ! $conflict && $inField && $free > 0;
            $meta = $conflict
                ? 'kolizja z wyjazdem'
                : (! $inField
                    ? 'w bazie'
                    : ($free > 0
                        ? ($sameProject ? 'ten sam projekt · ' : '').$occupancy['peak'].'/'.$capacity
                        : $occupancy['peak'].'/'.$capacity));
            $group = $enabled ? ($sameProject ? 0 : 1) : 2;
            $option = $this->option(
                (string) $vehicle->id,
                trim(($vehicle->brand ?? '').' '.($vehicle->model ?? '').' '.($vehicle->registration_number ?? '')) ?: 'Auto',
                $meta,
                $enabled,
                $group,
            );
            $option['driver_taken'] = $driverTaken;
            $options[] = $option;
        }

        return $this->sortOptions($options);
    }

    /**
     * @param  Collection<int, Accommodation>  $houses
     * @return list<array{key: string, label: string, meta: string, enabled: bool, group: int, driver_taken: bool}>
     */
    private function houseChoices(Collection $houses, string $start, string $end): array
    {
        if ($houses->isEmpty()) {
            return [];
        }

        $ids = $houses->pluck('id');
        $assignments = AccommodationAssignment::query()
            ->whereIn('accommodation_id', $ids)
            ->overlappingWith($start, $end)
            ->get(['id', 'accommodation_id', 'start_date', 'end_date']);
        $leases = AccommodationLease::query()->whereIn('accommodation_id', $ids)->get();
        $byHouse = $assignments->groupBy('accommodation_id');
        $leasesByHouse = $leases->groupBy('accommodation_id');

        $options = [];
        foreach ($houses as $house) {
            $ranges = ($byHouse->get($house->id) ?? collect())->map(fn ($row) => [
                'start' => $row->start_date->toDateString(),
                'end' => $row->end_date?->toDateString(),
            ])->all();
            $capacity = (int) $house->capacity;
            $occupancy = AssignmentTimelineMath::peak($ranges, $start, $end, $capacity);
            $leaseOk = $this->leaseCovers($leasesByHouse->get($house->id, collect()), $start, $end);
            $free = $capacity - $occupancy['peak'];
            $enabled = $leaseOk && $free > 0;
            $meta = ! $leaseOk
                ? 'poza umową'
                : ($free > 0
                    ? $occupancy['peak'].'/'.$capacity
                    : $capacity.'/'.$capacity.' od '.Carbon::parse($occupancy['full_from'] ?? $start)->format('d.m'));
            $options[] = $this->option((string) $house->id, $house->name, $meta, $enabled, $enabled ? 0 : 1);
        }

        return $options;
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return list<array{key: string, label: string, meta: string, enabled: bool, group: int, driver_taken: bool}>
     */
    private function resourceProjectOptions(int $projectId, Collection $employees, string $start, string $end): array
    {
        $project = Project::query()->with([
            'demands' => fn ($query) => $query->overlappingWith($start, $end)->with('role:id,name'),
            'assignments' => fn ($query) => $query->overlappingWith($start, $end),
        ])->find($projectId);

        if (! $project) {
            return [];
        }

        $employeeIds = $employees->pluck('id');
        $roleMap = $employeeIds->isEmpty()
            ? collect()
            : Employee::query()->whereIn('id', $employeeIds)->with('roles:id')->get()->keyBy('id');

        $assignedByRole = $project->assignments->groupBy('role_id');
        $options = [];
        foreach ($employees as $employee) {
            $roles = $roleMap->get($employee->id)?->roles->pluck('id')->map(fn ($id) => (int) $id)->all() ?? [];
            $demandsByRole = $project->demands->groupBy('role_id');
            if ($demandsByRole->isEmpty()) {
                $options[] = $this->option((string) $employee->id, $employee->full_name, 'brak zapotrzebowania', false, 1);

                continue;
            }
            foreach ($demandsByRole as $roleId => $demands) {
                $demandRanges = $demands->map(fn ($demand) => [
                    'start' => $demand->start_date->toDateString(),
                    'end' => $demand->end_date?->toDateString(),
                    'required' => (int) $demand->required_count,
                ])->all();
                $assigned = ($assignedByRole->get($roleId) ?? collect())->map(fn ($row) => [
                    'start' => $row->start_date->toDateString(),
                    'end' => $row->end_date?->toDateString(),
                ])->all();
                $free = AssignmentTimelineMath::minFree($demandRanges, $assigned, $start, $end);
                $hasRole = in_array((int) $roleId, $roles, true);
                $enabled = $hasRole && $free > 0;
                $roleName = $demands->first()?->role?->name ?? 'rola';
                $options[] = $this->option(
                    $employee->id.':'.$roleId,
                    $employee->full_name.' · '.$roleName,
                    $hasRole ? ($free > 0 ? 'min. '.$free : 'brak miejsc') : 'brak roli',
                    $enabled,
                    $enabled ? 0 : 1,
                );
            }
        }

        return $this->sortOptions($options);
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return list<array{key: string, label: string, meta: string, enabled: bool, group: int, driver_taken: bool}>
     */
    private function resourceHouseOptions(int $houseId, Collection $employees, string $start, string $end): array
    {
        $house = Accommodation::query()->find($houseId);
        if (! $house) {
            return [];
        }

        $choice = $this->houseChoices(collect([$house]), $start, $end)[0] ?? null;
        $houseOpen = $choice['enabled'] ?? false;
        $meta = $choice['meta'] ?? '';

        $busy = AccommodationAssignment::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->overlappingWith($start, $end)
            ->pluck('employee_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $options = [];
        foreach ($employees as $employee) {
            $already = in_array((int) $employee->id, $busy, true);
            $enabled = $houseOpen && ! $already;
            $options[] = $this->option(
                (string) $employee->id,
                $employee->full_name,
                $already ? 'ma już dom' : $meta,
                $enabled,
                $enabled ? 0 : 1,
            );
        }

        return $this->sortOptions($options);
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return list<array{key: string, label: string, meta: string, enabled: bool, group: int, driver_taken: bool}>
     */
    private function resourceVehicleOptions(int $vehicleId, Collection $employees, string $start, string $end): array
    {
        $vehicle = Vehicle::query()->find($vehicleId);
        if (! $vehicle) {
            return [];
        }

        $rows = VehicleAssignment::query()
            ->where('vehicle_id', $vehicleId)
            ->overlappingWith($start, $end)
            ->get(['employee_id', 'start_date', 'end_date', 'position']);

        $ranges = $rows->map(fn ($row) => [
            'start' => $row->start_date->toDateString(),
            'end' => $row->end_date?->toDateString(),
        ])->all();
        $capacity = (int) $vehicle->capacity;
        $occupancy = AssignmentTimelineMath::peak($ranges, $start, $end, $capacity);
        $free = $capacity - $occupancy['peak'];
        $driverTaken = $rows->contains(fn ($row): bool => $row->position === VehiclePosition::DRIVER || $row->position === VehiclePosition::DRIVER->value);
        $conflicting = $this->vehicleValidation->vehicleIdsConflictingWithLogisticsEvent(
            [$vehicleId],
            Carbon::parse($start)->startOfDay(),
            Carbon::parse($end)->startOfDay(),
        );
        $conflict = $conflicting !== [];
        $seated = $rows->pluck('employee_id')->map(fn ($id) => (int) $id)->all();

        $options = [];
        foreach ($employees as $employee) {
            $already = in_array((int) $employee->id, $seated, true);
            $enabled = ! $conflict && $free > 0 && ! $already;
            $meta = $already ? 'już w aucie' : ($conflict ? 'kolizja z wyjazdem' : $occupancy['peak'].'/'.$capacity);
            $option = $this->option((string) $employee->id, $employee->full_name, $meta, $enabled, $enabled ? 0 : 1);
            $option['driver_taken'] = $driverTaken;
            $options[] = $option;
        }

        return $this->sortOptions($options);
    }

    private function resizeEmployee(Employee $employee, string $lane, int $id, Carbon $start, ?Carbon $end, bool $keepOpen): void
    {
        match ($lane) {
            'rotation' => $this->resizeRotation($employee, $id, $start, $end),
            'project' => $this->resizeProject($employee, $id, $start, $end, $keepOpen),
            'accommodation' => $this->resizeHouse($employee, $id, $start, $end, $keepOpen),
            'vehicle' => $this->resizeVehicle($employee, $id, $start, $end, $keepOpen),
            default => throw ValidationException::withMessages(['lane' => 'Nieznany tor.']),
        };
    }

    private function resizeRotation(Employee $employee, int $id, Carbon $start, ?Carbon $end): void
    {
        $rotation = Rotation::query()->where('employee_id', $employee->id)->findOrFail($id);
        if ($end === null) {
            throw ValidationException::withMessages(['end_date' => 'Rotacja potrzebuje daty końca.']);
        }
        $this->rotations->updateRotation($rotation, $start, $end, $rotation->notes);
    }

    private function resizeProject(Employee $employee, int $id, Carbon $start, ?Carbon $end, bool $keepOpen): void
    {
        $assignment = ProjectAssignment::query()->where('employee_id', $employee->id)->with(['project', 'role'])->findOrFail($id);
        if ($keepOpen || $end === null) {
            $this->assertNoOpenOverlap(ProjectAssignment::class, $employee->id, $assignment->id, $start);
            $checkEnd = $this->checkEnd($employee, $start);
            $this->projectAssignments->updateAssignment(
                $assignment,
                $assignment->project,
                $employee,
                $assignment->role,
                $start,
                $checkEnd,
                $assignment->notes,
            );
            $assignment->refresh();
            $assignment->forceFill(['end_date' => null])->save();

            return;
        }

        $this->projectAssignments->updateAssignment(
            $assignment,
            $assignment->project,
            $employee,
            $assignment->role,
            $start,
            $end,
            $assignment->notes,
        );
    }

    private function resizeHouse(Employee $employee, int $id, Carbon $start, ?Carbon $end, bool $keepOpen): void
    {
        $assignment = AccommodationAssignment::query()->where('employee_id', $employee->id)->with('accommodation')->findOrFail($id);
        if ($keepOpen || $end === null) {
            $this->assertNoOpenOverlap(AccommodationAssignment::class, $employee->id, $assignment->id, $start);
            $this->accommodations->assertCanAssign($employee, $assignment->accommodation, $start, $this->checkEnd($employee, $start), $assignment->id);
            $assignment->update(['start_date' => $start]);

            return;
        }

        $this->accommodations->updateAssignment($assignment, $assignment->accommodation, $start, $end, $assignment->notes);
    }

    private function resizeVehicle(Employee $employee, int $id, Carbon $start, ?Carbon $end, bool $keepOpen): void
    {
        $assignment = VehicleAssignment::query()->where('employee_id', $employee->id)->with('vehicle')->findOrFail($id);
        if ($assignment->is_return_trip) {
            throw ValidationException::withMessages(['vehicle_id' => 'Przypisanie zjazdu zmienia się w planerze zjazdu.']);
        }

        $position = $assignment->position instanceof VehiclePosition
            ? $assignment->position
            : VehiclePosition::from((string) $assignment->position);

        if ($keepOpen || $end === null) {
            $this->vehicleValidation->validateForProjectAssignmentOrFail(
                $assignment->vehicle,
                $employee,
                $position,
                $start,
                $this->checkEnd($employee, $start),
                $assignment->id,
                null,
                $assignment->logistics_event_id,
            );
            $assignment->update(['start_date' => $start]);

            return;
        }

        $this->vehicles->updateAssignment($assignment, $assignment->vehicle, $position, $start, $end, $assignment->notes);
    }

    private function createProject(Employee $employee, string $choice, Carbon $start, Carbon $end): void
    {
        [$projectId, $roleId] = array_pad(explode(':', $choice, 2), 2, null);
        if (! $projectId || ! $roleId) {
            throw ValidationException::withMessages(['project_id' => 'Wybierz projekt i rolę.']);
        }

        $this->projectAssignments->createAssignment(
            Project::query()->findOrFail((int) $projectId),
            $employee,
            Role::query()->findOrFail((int) $roleId),
            $start,
            $end,
        );
    }

    private function checkEnd(Employee $employee, Carbon $start): Carbon
    {
        $rotation = Rotation::query()
            ->where('employee_id', $employee->id)
            ->where('start_date', '<=', $start->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->first();

        return $rotation
            ? Carbon::parse($rotation->end_date)->startOfDay()
            : $start->copy();
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function assertNoOpenOverlap(string $model, int $employeeId, int $excludeId, Carbon $start): void
    {
        $overlap = $model::query()
            ->where('employee_id', $employeeId)
            ->where('id', '!=', $excludeId)
            ->overlappingWith($start, null)
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'start_date' => 'Otwarty zakres nachodzi na inne przypisanie tego toru.',
            ]);
        }
    }

    private function resourceAssignment(string $type, int $resourceId, int $assignmentId): ProjectAssignment|VehicleAssignment|AccommodationAssignment
    {
        $assignment = match ($type) {
            'project' => ProjectAssignment::query()->with('employee')->findOrFail($assignmentId),
            'vehicle' => VehicleAssignment::query()->with('employee')->findOrFail($assignmentId),
            default => AccommodationAssignment::query()->with('employee')->findOrFail($assignmentId),
        };

        $ownerId = match ($type) {
            'project' => (int) $assignment->project_id,
            'vehicle' => (int) $assignment->vehicle_id,
            default => (int) $assignment->accommodation_id,
        };

        if ($ownerId !== $resourceId || ! $assignment->employee) {
            throw ValidationException::withMessages(['id' => 'To przypisanie nie należy do tej karty.']);
        }

        return $assignment;
    }

    /**
     * @return array{0: string, 1: Collection, 2: list<int>}
     */
    private function projectRows(int $id, Carbon $start, Carbon $end): array
    {
        $rows = ProjectAssignment::query()
            ->where('project_id', $id)
            ->overlappingWith($start, $end)
            ->with(['employee:id,first_name,last_name', 'role:id,name'])
            ->orderBy('start_date')
            ->get();

        return ['Ludzie', $rows, $rows->pluck('employee_id')->map(fn ($value) => (int) $value)->unique()->values()->all()];
    }

    /**
     * @return array{0: string, 1: Collection, 2: list<int>}
     */
    private function vehicleRows(int $id, Carbon $start, Carbon $end): array
    {
        $rows = VehicleAssignment::query()
            ->where('vehicle_id', $id)
            ->overlappingWith($start, $end)
            ->with('employee:id,first_name,last_name')
            ->orderBy('start_date')
            ->get();

        return ['Ludzie', $rows, $rows->pluck('employee_id')->map(fn ($value) => (int) $value)->unique()->values()->all()];
    }

    /**
     * @return array{0: string, 1: Collection, 2: list<int>}
     */
    private function houseRows(int $id, Carbon $start, Carbon $end): array
    {
        $rows = AccommodationAssignment::query()
            ->where('accommodation_id', $id)
            ->overlappingWith($start, $end)
            ->with('employee:id,first_name,last_name')
            ->orderBy('start_date')
            ->get();

        return ['Ludzie', $rows, $rows->pluck('employee_id')->map(fn ($value) => (int) $value)->unique()->values()->all()];
    }

    /**
     * @param  list<int>  $employeeIds
     * @return array<int, list<array{id: int, start: string, end: ?string}>>
     */
    private function siblingRanges(string $type, array $employeeIds, Carbon $start, Carbon $end): array
    {
        if ($employeeIds === []) {
            return [];
        }

        $query = match ($type) {
            'project' => ProjectAssignment::query(),
            'vehicle' => VehicleAssignment::query(),
            default => AccommodationAssignment::query(),
        };

        return $query
            ->whereIn('employee_id', $employeeIds)
            ->overlappingWith($start, $end)
            ->get(['id', 'employee_id', 'start_date', 'end_date'])
            ->groupBy('employee_id')
            ->map(fn (Collection $rows) => $rows->map(fn ($row) => [
                'id' => (int) $row->id,
                'start' => $row->start_date->toDateString(),
                'end' => $row->end_date?->toDateString(),
            ])->all())
            ->all();
    }

    private function resourceBarLabel(string $type, Model $row): string
    {
        $name = $row->employee?->full_name ?? 'Osoba';
        if ($type === 'project') {
            return trim($name.' · '.($row->role?->name ?? ''));
        }
        if ($type === 'vehicle') {
            $position = $row->position instanceof VehiclePosition ? $row->position->label() : '';

            return trim($name.' · '.$position);
        }

        return $name;
    }

    /**
     * @param  array<string, array{view: bool, create: bool, update: bool}>  $can
     * @param  list<array<string, mixed>>  $bars
     * @param  list<array{start: string, end: string}>  $bounds
     * @param  list<array{start: string, end: ?string}>  $blocks
     * @return array<string, mixed>
     */
    private function lane(string $key, string $label, array $can, array $bars, array $bounds, array $blocks): array
    {
        $blockRanges = array_map(fn (array $block): array => [
            'start' => $block['start'],
            'end' => $block['end'] ?? $block['start'],
        ], $blocks);
        $packed = $this->pack($bars);

        return [
            'key' => $key,
            'label' => $label,
            'can_create' => (bool) ($can['create'] ?? false),
            'can_update' => (bool) ($can['update'] ?? false),
            'bars' => $packed['bars'],
            'rows' => $packed['rows'],
            'gaps' => $can['create'] ? AssignmentTimelineMath::gaps($bounds, $blockRanges) : [],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $bars
     * @return array{bars: list<array<string, mixed>>, rows: int}
     */
    private function pack(array $bars): array
    {
        $rowEnds = [];
        foreach ($bars as $index => $bar) {
            $finish = $bar['end'] ?? '9999-12-31';
            $placed = false;
            foreach ($rowEnds as $row => $occupiedUntil) {
                if ($occupiedUntil < $bar['start']) {
                    $bars[$index]['row'] = $row;
                    $rowEnds[$row] = $finish;
                    $placed = true;
                    break;
                }
            }
            if (! $placed) {
                $bars[$index]['row'] = count($rowEnds);
                $rowEnds[] = $finish;
            }
        }

        return ['bars' => $bars, 'rows' => max(1, count($rowEnds))];
    }

    /**
     * @param  Collection<int, Model>  $rows
     * @param  list<array{start: string, end: string}>  $bounds
     * @param  list<array{start: string, end: ?string, id?: int}>  $ranges
     * @return list<array<string, mixed>>
     */
    private function barsFrom(Collection $rows, callable $label, string $windowStart, string $windowEnd, array $bounds, array $ranges, bool $lockReturn = false): array
    {
        $bars = [];
        foreach ($rows as $row) {
            $start = $row->start_date->toDateString();
            $end = $row->end_date?->toDateString();
            $bound = $this->spanBound($start, $bounds, $windowStart, $windowEnd);
            $siblings = $this->closeRanges(array_values(array_filter(
                $ranges,
                fn (array $range): bool => (int) ($range['id'] ?? 0) !== (int) $row->id
            )), $bounds, $windowStart, $windowEnd);
            $limits = AssignmentTimelineMath::resizeLimits($start, $end, $siblings, $bound['start'], $bound['end']);
            $pixels = $this->pixels($windowStart, $windowEnd, $start, $end);
            $bars[] = [
                'id' => (int) $row->id,
                'start' => $start,
                'end' => $end,
                'open' => $end === null,
                'label' => $label($row),
                'min' => $limits['min'],
                'max' => $limits['max'],
                'locked' => $lockReturn && (bool) ($row->is_return_trip ?? false),
                'left' => $pixels['left'],
                'width' => $pixels['width'],
            ];
        }

        return $bars;
    }

    /**
     * @param  list<array{start: string, end: ?string}>  $rotations
     * @return list<array{start: string, end: string}>
     */
    private function rotationBounds(array $rotations, string $windowStart, string $windowEnd): array
    {
        $bounds = [];
        foreach ($rotations as $rotation) {
            $start = $rotation['start'] > $windowStart ? $rotation['start'] : $windowStart;
            $end = ($rotation['end'] ?? $windowEnd) < $windowEnd ? ($rotation['end'] ?? $windowEnd) : $windowEnd;
            if ($start <= $end) {
                $bounds[] = ['start' => $start, 'end' => $end];
            }
        }

        return $bounds;
    }

    /**
     * Rotation (or window) that contains the bar, clipped to the visible axis.
     * A bar that starts before the window still uses the rotation under its visible days.
     *
     * @param  list<array{start: string, end: ?string}>  $bounds
     * @return array{start: string, end: string}
     */
    private function spanBound(string $day, array $bounds, string $windowStart, string $windowEnd): array
    {
        $lookup = $day < $windowStart ? $windowStart : $day;
        foreach ($bounds as $bound) {
            $boundEnd = $bound['end'] ?? $windowEnd;
            if ($lookup >= $bound['start'] && $lookup <= $boundEnd) {
                $start = $bound['start'] > $windowStart ? $bound['start'] : $windowStart;
                $end = $boundEnd < $windowEnd ? $boundEnd : $windowEnd;
                if ($start <= $end) {
                    return ['start' => $start, 'end' => $end];
                }
            }
        }

        return ['start' => $lookup, 'end' => $lookup];
    }

    /**
     * Open bars occupy the rest of their rotation so a later gap cannot be drawn through them.
     *
     * @param  list<array{start: string, end: ?string, id?: int}>  $ranges
     * @param  list<array{start: string, end: ?string}>  $bounds
     * @return list<array{start: string, end: string, id?: int}>
     */
    private function closeRanges(array $ranges, array $bounds, string $windowStart, string $windowEnd): array
    {
        return array_map(function (array $range) use ($bounds, $windowStart, $windowEnd): array {
            if ($range['end'] === null) {
                $range['end'] = $this->spanBound($range['start'], $bounds, $windowStart, $windowEnd)['end'];
            }

            return $range;
        }, $ranges);
    }

    /**
     * @param  Collection<int, Model>  $rows
     * @return list<array{id: int, start: string, end: ?string}>
     */
    private function rangesFrom(Collection $rows): array
    {
        return $rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'start' => $row->start_date->toDateString(),
            'end' => $row->end_date?->toDateString(),
        ])->all();
    }

    /**
     * @param  list<array<string, mixed>>  $lanes
     * @param  list<array{date: string, kind: string, label: string}>  $markers
     * @return array<string, mixed>
     */
    private function frame(Carbon $start, Carbon $end, array $lanes, array $markers): array
    {
        $windowStart = $start->toDateString();
        $windowEnd = $end->toDateString();
        $dayCount = AssignmentTimelineMath::dayIndex($windowStart, $windowEnd) + 1;

        $ticks = [];
        $cursor = $start->copy();
        if (! $cursor->isMonday()) {
            $cursor->next(Carbon::MONDAY);
        }
        $months = [1 => 'sty', 2 => 'lut', 3 => 'mar', 4 => 'kwi', 5 => 'maj', 6 => 'cze', 7 => 'lip', 8 => 'sie', 9 => 'wrz', 10 => 'paź', 11 => 'lis', 12 => 'gru'];
        while ($cursor->lte($end)) {
            $ticks[] = [
                'left' => AssignmentTimelineMath::dayIndex($windowStart, $cursor->toDateString()) * self::DAY_WIDTH,
                'label' => $cursor->format('j').' '.$months[(int) $cursor->month],
            ];
            $cursor->addWeek();
        }

        $bands = AssignmentTimelineMath::onSiteBands($markers, $windowStart, $windowEnd);
        $visibleMarkers = array_values(array_filter(
            $markers,
            fn (array $marker): bool => $marker['date'] >= $windowStart && $marker['date'] <= $windowEnd,
        ));

        return [
            'start' => $windowStart,
            'end' => $windowEnd,
            'day_count' => $dayCount,
            'day_width' => self::DAY_WIDTH,
            'width' => $dayCount * self::DAY_WIDTH,
            'ticks' => $ticks,
            'lanes' => $lanes,
            'markers' => array_map(function (array $marker) use ($windowStart): array {
                $marker['left'] = AssignmentTimelineMath::dayIndex($windowStart, $marker['date']) * self::DAY_WIDTH;

                return $marker;
            }, $visibleMarkers),
            'bands' => array_map(function (array $band) use ($windowStart): array {
                $left = AssignmentTimelineMath::dayIndex($windowStart, $band['start']) * self::DAY_WIDTH;
                $span = AssignmentTimelineMath::dayIndex($band['start'], $band['end']) + 1;

                return ['left' => $left, 'width' => $span * self::DAY_WIDTH];
            }, $bands),
        ];
    }

    /**
     * @param  list<int>  $employeeIds
     * @return list<array{date: string, kind: string, label: string}>
     */
    private function markersForEmployees(array $employeeIds, Carbon $start, Carbon $end): array
    {
        if ($employeeIds === []) {
            return [];
        }

        $events = LogisticsEvent::query()
            ->whereIn('type', [LogisticsEventType::DEPARTURE, LogisticsEventType::RETURN])
            ->where('status', '!=', LogisticsEventStatus::CANCELLED)
            ->where(function ($query) use ($start, $end) {
                $from = $start->toDateString();
                $to = $end->toDateString();
                $query->whereBetween('event_date', [$from, $to])
                    ->orWhereBetween('end_date', [$from, $to]);
            })
            ->whereHas('participants', fn ($query) => $query->whereIn('employee_id', $employeeIds))
            ->orderBy('event_date')
            ->get(['id', 'type', 'event_date', 'end_date']);

        return $this->dedupeMarkers($events);
    }

    /**
     * @return list<array{date: string, kind: string, label: string}>
     */
    private function markersForVehicle(int $vehicleId, Carbon $start, Carbon $end): array
    {
        $events = LogisticsEvent::query()
            ->where('vehicle_id', $vehicleId)
            ->whereIn('type', [LogisticsEventType::DEPARTURE, LogisticsEventType::RETURN])
            ->where('status', '!=', LogisticsEventStatus::CANCELLED)
            ->where(function ($query) use ($start, $end) {
                $from = $start->toDateString();
                $to = $end->toDateString();
                $query->whereBetween('event_date', [$from, $to])
                    ->orWhereBetween('end_date', [$from, $to]);
            })
            ->orderBy('event_date')
            ->get(['id', 'type', 'event_date', 'end_date']);

        return $this->dedupeMarkers($events);
    }

    /**
     * @param  Collection<int, LogisticsEvent>  $events
     * @return list<array{date: string, kind: string, label: string}>
     */
    private function dedupeMarkers(Collection $events): array
    {
        $seen = [];
        $markers = [];
        foreach ($events as $event) {
            $isReturn = $event->type === LogisticsEventType::RETURN;
            $date = $isReturn
                ? $event->event_date->toDateString()
                : ($event->end_date ?? $event->event_date)->toDateString();
            $kind = $isReturn ? 'return' : 'arrival';
            $key = $kind.'|'.$date;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $markers[] = [
                'date' => $date,
                'kind' => $kind,
                'label' => $isReturn ? 'Zjazd' : 'Przyjazd',
            ];
        }

        usort($markers, fn (array $a, array $b): int => $a['date'] <=> $b['date']);

        return $markers;
    }

    /**
     * @return array{left: int, width: int}
     */
    public function pixels(string $windowStart, string $windowEnd, string $barStart, ?string $barEnd, ?int $dayWidth = null): array
    {
        $dayWidth ??= self::DAY_WIDTH;
        $start = $barStart < $windowStart ? $windowStart : $barStart;
        $end = $barEnd ?? $windowEnd;
        if ($end > $windowEnd) {
            $end = $windowEnd;
        }
        if ($start > $end) {
            $start = $end;
        }
        $left = AssignmentTimelineMath::dayIndex($windowStart, $start) * $dayWidth;
        $width = (AssignmentTimelineMath::dayIndex($start, $end) + 1) * $dayWidth;

        return ['left' => $left, 'width' => max($dayWidth, $width)];
    }

    /**
     * @param  Collection<int, AccommodationLease>  $leases
     */
    private function leaseCovers(Collection $leases, string $start, string $end): bool
    {
        foreach ($leases as $lease) {
            $leaseStart = $lease->start_date?->toDateString();
            $leaseEnd = $lease->end_date?->toDateString();
            if ($leaseStart && $leaseStart > $start) {
                continue;
            }
            if ($leaseEnd && $leaseEnd < $end) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @return array{key: string, label: string, meta: string, enabled: bool, group: int, driver_taken: bool}
     */
    private function option(string $key, string $label, string $meta, bool $enabled, int $group): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'meta' => $meta,
            'enabled' => $enabled,
            'group' => $group,
            'driver_taken' => false,
        ];
    }

    /**
     * @param  list<array{key: string, label: string, meta: string, enabled: bool, group: int, driver_taken: bool}>  $options
     * @return list<array{key: string, label: string, meta: string, enabled: bool, group: int, driver_taken: bool}>
     */
    private function sortOptions(array $options): array
    {
        usort($options, function (array $a, array $b): int {
            if ($a['group'] !== $b['group']) {
                return $a['group'] <=> $b['group'];
            }

            return $a['label'] <=> $b['label'];
        });

        return $options;
    }
}
