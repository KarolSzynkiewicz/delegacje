<?php

namespace App\Services;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\LogisticsEvent;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\ProjectDemand;
use App\Models\Rotation;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Models\VehicleRepair;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class WeeklyDashboardKpiService
{
    public function __construct(
        private LocationTrackingService $locationTracking,
    ) {}

    /**
     * Statystyki dla wybranego tygodnia (np. z nawigacji przeglądu tygodniowego).
     *
     * @return array{
     *     week_start: \Carbon\Carbon,
     *     week_end: \Carbon\Carbon,
     *     week_label: string,
     *     transfers_count: int,
     *     departures_count: int,
     *     returns_count: int,
     *     employees_in_field_count: int
     * }
     */
    public function getKpiForWeek(Carbon $weekStart, Carbon $weekEnd): array
    {
        $rangeStart = $weekStart->copy()->startOfDay();
        $rangeEnd = $weekEnd->copy()->endOfDay();

        $activeEvents = fn ($q) => $q->where('status', '!=', LogisticsEventStatus::CANCELLED);

        $transfersCount = LogisticsEvent::query()
            ->where('type', LogisticsEventType::TRANSFER)
            ->where($activeEvents)
            ->whereBetween('event_date', [$rangeStart, $rangeEnd])
            ->count();

        $departuresCount = LogisticsEvent::query()
            ->where('type', LogisticsEventType::DEPARTURE)
            ->where($activeEvents)
            ->whereBetween('event_date', [$rangeStart, $rangeEnd])
            ->count();

        $returnsCount = LogisticsEvent::query()
            ->where('type', LogisticsEventType::RETURN)
            ->where($activeEvents)
            ->whereBetween('event_date', [$rangeStart, $rangeEnd])
            ->count();

        $employeesInFieldCount = $this->countEmployeesInFieldForWeek($weekStart, $weekEnd);

        return [
            'week_start' => $weekStart->copy(),
            'week_end' => $weekEnd->copy(),
            'week_label' => $weekStart->format('d.m').' – '.$weekEnd->format('d.m.Y'),
            'transfers_count' => $transfersCount,
            'departures_count' => $departuresCount,
            'returns_count' => $returnsCount,
            'employees_in_field_count' => $employeesInFieldCount,
        ];
    }

    /**
     * Unikalne osoby z przypisaniem do projektu przecinającym dany tydzień.
     */
    public function countEmployeesInFieldForWeek(Carbon $weekStart, Carbon $weekEnd): int
    {
        return (int) ProjectAssignment::query()
            ->overlappingWith($weekStart, $weekEnd)
            ->toBase()
            ->selectRaw('count(distinct employee_id) as c')
            ->value('c');
    }

    /**
     * Liczba unikalnych pracowników w polu, pogrupowana po projekcie (tydzień przecina przypisanie).
     *
     * @return Collection<int, object{project_id: int, project_name: string, employee_count: int, needed_count: int}>
     */
    public function employeesInFieldByProjectForWeek(Carbon $weekStart, Carbon $weekEnd): Collection
    {
        $rangeStart = $weekStart->copy()->startOfDay();
        $rangeEnd = $weekEnd->copy()->endOfDay();

        // Bez JOIN do projects w tym samym zapytaniu co overlappingWith — obie tabele mają
        // start_date/end_date i MySQL zgłasza „Column 'start_date' … is ambiguous”.
        $rows = ProjectAssignment::query()
            ->overlappingWith($rangeStart, $rangeEnd)
            ->selectRaw('project_assignments.project_id, COUNT(DISTINCT project_assignments.employee_id) as employee_count')
            ->groupBy('project_assignments.project_id')
            ->get();

        $neededByProject = $this->demandSlotsByProjectForWeek($weekStart, $weekEnd);

        $names = $rows->isEmpty()
            ? collect()
            : Project::query()
                ->whereIn('id', $rows->pluck('project_id'))
                ->pluck('name', 'id');

        $rows = $rows
            ->map(fn ($row) => (object) [
                'project_id' => (int) $row->project_id,
                'project_name' => (string) ($names[$row->project_id] ?? '?'),
                'employee_count' => (int) $row->employee_count,
                'needed_count' => (int) ($neededByProject[$row->project_id] ?? 0),
            ]);

        $missingDemandIds = $neededByProject
            ->filter(fn (int $needed) => $needed > 0)
            ->keys()
            ->diff($rows->pluck('project_id'));

        if ($missingDemandIds->isNotEmpty()) {
            $demandNames = Project::query()
                ->whereIn('id', $missingDemandIds)
                ->pluck('name', 'id');

            foreach ($missingDemandIds as $projectId) {
                $rows->push((object) [
                    'project_id' => (int) $projectId,
                    'project_name' => (string) ($demandNames[$projectId] ?? '?'),
                    'employee_count' => 0,
                    'needed_count' => (int) $neededByProject[$projectId],
                ]);
            }
        }

        return $rows
            ->sortBy('project_name')
            ->values();
    }

    /**
     * Suma zapotrzebowania (slotów) we wszystkich projektach przecinających tydzień.
     * Ta sama reguła co na karcie kierunku: wpisy tej samej roli w tym tygodniu są sumowane.
     */
    public function totalDemandForWeek(Carbon $weekStart, Carbon $weekEnd): int
    {
        return (int) $this->demandSlotsByProjectForWeek($weekStart, $weekEnd)->sum();
    }

    /**
     * Środa po niedzieli zamykającej przeglądany tydzień.
     * Osoby kończące rotację liczymy do tego dnia włącznie.
     */
    public function rotationCutoffAfterWeek(Carbon $weekEnd): Carbon
    {
        return $weekEnd->copy()->startOfDay()->next(Carbon::WEDNESDAY);
    }

    /**
     * Unikalne osoby, których rotacja kończy się w przeglądanym tygodniu
     * albo najpóźniej w środę po tym tygodniu (włącznie).
     *
     * @return Collection<int, Rotation>
     */
    public function rotationsEndingThrough(Carbon $weekStart, Carbon $horizonEnd): Collection
    {
        return Rotation::query()
            ->whereNotNull('end_date')
            ->whereDate('end_date', '>=', $weekStart->toDateString())
            ->whereDate('end_date', '<=', $horizonEnd->toDateString())
            ->with('employee')
            ->orderBy('end_date')
            ->orderBy('id')
            ->get()
            ->unique('employee_id')
            ->values();
    }

    /**
     * @return Collection<int, int> project_id => liczba slotów zapotrzebowania
     */
    private function demandSlotsByProjectForWeek(Carbon $weekStart, Carbon $weekEnd): Collection
    {
        $rows = ProjectDemand::query()
            ->overlappingWith($weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay())
            ->where('required_count', '>', 0)
            ->get(['project_id', 'role_id', 'required_count']);

        return $rows
            ->groupBy(fn ($row) => $row->project_id.'|'.$row->role_id)
            ->map(fn ($group) => (object) [
                'project_id' => (int) $group->first()->project_id,
                'needed' => (int) $group->sum('required_count'),
            ])
            ->groupBy('project_id')
            ->map(fn (Collection $group) => (int) $group->sum('needed'));
    }

    /**
     * Domy trzymane w tym tygodniu: obłożenie (suma osób per dom) / suma miejsc.
     * Pusty dom wchodzi do mianownika. Ta sama osoba w dwóch domach liczy się dwa razy.
     *
     * @return array{occupied: int, capacity: int, houses: Collection<int, object>}
     */
    public function housingForWeek(Carbon $weekStart, Carbon $weekEnd): array
    {
        $start = $weekStart->copy()->startOfDay();
        $end = $weekEnd->copy()->endOfDay();

        $houses = Accommodation::query()
            ->where(function ($query) use ($start, $end) {
                $query->whereDoesntHave('leases', fn ($lease) => $lease->where('type', 'wynajmowany'))
                    ->orWhereHas('leases', function ($lease) use ($start, $end) {
                        $lease->where('type', 'wynajmowany')
                            ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $end->toDateString()))
                            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $start->toDateString()));
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'capacity']);

        $occupiedByHouse = $houses->isEmpty()
            ? collect()
            : AccommodationAssignment::query()
                ->overlappingWith($start, $end)
                ->whereIn('accommodation_id', $houses->pluck('id'))
                ->get(['accommodation_id', 'employee_id'])
                ->groupBy(fn ($row) => (int) $row->accommodation_id)
                ->map(fn (Collection $rows) => $rows->pluck('employee_id')->unique()->count());

        $rows = $houses->map(fn (Accommodation $house) => (object) [
            'id' => (int) $house->id,
            'name' => (string) $house->name,
            'occupied' => (int) ($occupiedByHouse[$house->id] ?? 0),
            'capacity' => (int) ($house->capacity ?? 0),
        ]);

        return [
            'occupied' => (int) $rows->sum('occupied'),
            'capacity' => (int) $rows->sum('capacity'),
            'houses' => $rows->values(),
        ];
    }

    /**
     * Auta w terenie: poza bazą, bez serwisu w tym tygodniu i bez zdarzenia logistycznego.
     * Licznik to osoby z przypisań do tych aut. Mianownik to suma ich pojemności, także pustych.
     *
     * @return array{occupied: int, capacity: int, vehicles: Collection<int, object>}
     */
    public function vehiclesForWeek(Carbon $weekStart, Carbon $weekEnd): array
    {
        $start = $weekStart->copy()->startOfDay();
        $end = $weekEnd->copy()->endOfDay();

        $inServiceIds = VehicleRepair::query()
            ->overlappingWith($start, $end)
            ->pluck('vehicle_id')
            ->filter()
            ->unique()
            ->values();

        $onLogisticsEventIds = LogisticsEvent::query()
            ->whereIn('type', [LogisticsEventType::DEPARTURE, LogisticsEventType::RETURN, LogisticsEventType::TRANSFER])
            ->where('status', '!=', LogisticsEventStatus::CANCELLED)
            ->whereNotNull('vehicle_id')
            ->where('event_date', '<=', $end)
            ->whereRaw('COALESCE(end_date, event_date) >= ?', [$start])
            ->pluck('vehicle_id')
            ->filter()
            ->unique()
            ->values();

        $vehicles = Vehicle::query()
            ->operational()
            ->where('outside_base', true)
            ->when($inServiceIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $inServiceIds->all()))
            ->when($onLogisticsEventIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $onLogisticsEventIds->all()))
            ->orderBy('registration_number')
            ->get(['id', 'brand', 'model', 'registration_number', 'capacity']);

        $occupiedByVehicle = $vehicles->isEmpty()
            ? collect()
            : VehicleAssignment::query()
                ->overlappingWith($start, $end)
                ->whereIn('vehicle_id', $vehicles->pluck('id'))
                ->where(fn ($q) => $q->where('is_return_trip', false)->orWhereNull('is_return_trip'))
                ->get(['vehicle_id', 'employee_id'])
                ->groupBy(fn ($row) => (int) $row->vehicle_id)
                ->map(fn (Collection $rows) => $rows->pluck('employee_id')->unique()->count());

        $rows = $vehicles->map(function (Vehicle $vehicle) use ($occupiedByVehicle) {
            $name = trim(implode(' ', array_filter([
                $vehicle->brand,
                $vehicle->model,
                $vehicle->registration_number,
            ])));

            return (object) [
                'id' => (int) $vehicle->id,
                'name' => $name !== '' ? $name : 'Auto #'.$vehicle->id,
                'occupied' => (int) ($occupiedByVehicle[$vehicle->id] ?? 0),
                'capacity' => (int) ($vehicle->capacity ?? 0),
            ];
        });

        return [
            'occupied' => (int) $rows->sum('occupied'),
            'capacity' => (int) $rows->sum('capacity'),
            'vehicles' => $rows->values(),
        ];
    }

    /**
     * Dzień wysyłki wewnątrz oglądanego tygodnia (config chronologic.dispatch_weekday).
     */
    public function dispatchDayOfWeek(Carbon $weekStart): Carbon
    {
        $target = (int) config('chronologic.dispatch_weekday', Carbon::SATURDAY);
        $day = $weekStart->copy()->startOfDay();

        if ($day->dayOfWeek === $target) {
            return $day;
        }

        return $day->next($target);
    }

    /**
     * Biernik dnia wysyłki do opisu kafelka („na sobotę”).
     */
    public function dispatchDayLabel(Carbon $day): string
    {
        return match ($day->dayOfWeek) {
            Carbon::MONDAY => 'poniedziałek',
            Carbon::TUESDAY => 'wtorek',
            Carbon::WEDNESDAY => 'środę',
            Carbon::THURSDAY => 'czwartek',
            Carbon::FRIDAY => 'piątek',
            Carbon::SATURDAY => 'sobotę',
            Carbon::SUNDAY => 'niedzielę',
            default => $day->locale('pl')->isoFormat('dddd'),
        };
    }

    /**
     * Ławka w dzień wysyłki: w bazie z rotacją / w bazie bez rotacji.
     * Rotacja liczy się, gdy obejmuje ten dzień albo zaczyna się do środy odcięcia włącznie.
     *
     * @return array{with_rotation: int, without_rotation: int, day: Carbon, with_rotation_people: Collection, without_rotation_people: Collection}
     */
    public function benchOnDispatchDay(Carbon $weekStart, Carbon $weekEnd): array
    {
        $day = $this->dispatchDayOfWeek($weekStart);
        $cutoff = $this->rotationCutoffAfterWeek($weekEnd);

        $roster = Employee::query()
            ->where(fn ($q) => $q->whereNull('terminated_at')->orWhereDate('terminated_at', '>', $day->toDateString()))
            ->where(fn ($q) => $q->whereNull('hired_at')->orWhereDate('hired_at', '<=', $day->toDateString()))
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        $notInBase = collect($this->locationTracking->employeeIdsNotInBaseOn($day))->map(fn ($id) => (int) $id);
        $inBase = $roster->diff($notInBase)->values();

        $withRotationIds = Rotation::query()
            ->whereDate('start_date', '<=', $cutoff->toDateString())
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $day->toDateString()))
            ->pluck('employee_id')
            ->map(fn ($id) => (int) $id);

        $withIds = $inBase->intersect($withRotationIds)->values();
        $withoutIds = $inBase->diff($withRotationIds)->values();
        $names = Employee::query()
            ->whereIn('id', $inBase)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->keyBy('id');

        $person = function (int $id) use ($names) {
            $employee = $names->get($id);

            return (object) [
                'id' => $id,
                'name' => $employee?->full_name ?? '—',
            ];
        };

        return [
            'with_rotation' => $withIds->count(),
            'without_rotation' => $withoutIds->count(),
            'day' => $day,
            'with_rotation_people' => $withIds->map(fn (int $id) => $person($id))->sortBy('name')->values(),
            'without_rotation_people' => $withoutIds->map(fn (int $id) => $person($id))->sortBy('name')->values(),
        ];
    }

    /**
     * Ile osób dosłać, żeby przyszły tydzień był pełny.
     * Popyt przyszłego tygodnia minus osoby z przypisaniem na przyszły tydzień,
     * których rotacja nie kończy się do środy odcięcia. Ujemny wynik to nadmiar.
     *
     * @return array{
     *     to_send: int,
     *     next_demand: int,
     *     staying: int,
     *     demand_by_project: Collection<int, object>,
     *     staying_people: Collection<int, object>,
     *     leaving_people: Collection<int, object>
     * }
     */
    public function toSendForNextWeek(Carbon $weekStart, Carbon $weekEnd): array
    {
        $nextStart = $weekStart->copy()->addWeek()->startOfDay();
        $nextEnd = $weekEnd->copy()->addWeek()->endOfDay();
        $cutoff = $this->rotationCutoffAfterWeek($weekEnd);

        $neededByProject = $this->demandSlotsByProjectForWeek($nextStart, $nextEnd);
        $nextDemand = (int) $neededByProject->sum();
        $projectNames = $neededByProject->isEmpty()
            ? collect()
            : Project::query()->whereIn('id', $neededByProject->keys())->get(['id', 'name'])
                ->mapWithKeys(fn (Project $project) => [(int) $project->id => (string) $project->name]);
        $demandByProject = $neededByProject
            ->map(fn (int $needed, int|string $projectId) => (object) [
                'project_id' => (int) $projectId,
                'project_name' => (string) ($projectNames->get((int) $projectId) ?? '?'),
                'needed' => $needed,
            ])
            ->sortBy('project_name')
            ->values();

        $assignedNext = ProjectAssignment::query()
            ->overlappingWith($nextStart, $nextEnd)
            ->pluck('employee_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $ending = $this->rotationsEndingThrough($weekStart, $cutoff);
        $endingIds = $ending->pluck('employee_id')->map(fn ($id) => (int) $id);
        $endingByEmployee = $ending->keyBy(fn ($rotation) => (int) $rotation->employee_id);

        $stayingIds = $assignedNext->diff($endingIds)->values();
        $leavingIds = $assignedNext->intersect($endingIds)->values();
        $names = Employee::query()
            ->whereIn('id', $assignedNext)
            ->get(['id', 'first_name', 'last_name'])
            ->keyBy('id');

        $named = function (Collection $ids) use ($names, $endingByEmployee) {
            return $ids->map(function (int $id) use ($names, $endingByEmployee) {
                $employee = $names->get($id);

                return (object) [
                    'id' => $id,
                    'name' => $employee?->full_name ?? '—',
                    'rotation_end' => $endingByEmployee->get($id)?->end_date,
                ];
            })->sortBy('name')->values();
        };

        $staying = $stayingIds->count();

        return [
            'to_send' => $nextDemand - $staying,
            'next_demand' => $nextDemand,
            'staying' => $staying,
            'demand_by_project' => $demandByProject,
            'staying_people' => $named($stayingIds),
            'leaving_people' => $named($leavingIds),
        ];
    }

    /**
     * Statystyki bieżącego tygodnia kalendarzowego (ISO).
     */
    public function getCurrentWeekKpi(?Carbon $now = null): array
    {
        $now = $now ?? Carbon::now();

        return $this->getKpiForWeek(
            $now->copy()->startOfWeek(),
            $now->copy()->endOfWeek()
        );
    }
}
