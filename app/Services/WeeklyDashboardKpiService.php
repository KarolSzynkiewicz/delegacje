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
     * @return array{occupied: int, capacity: int}
     */
    public function housingForWeek(Carbon $weekStart, Carbon $weekEnd): array
    {
        $start = $weekStart->copy()->startOfDay();
        $end = $weekEnd->copy()->endOfDay();

        $heldIds = Accommodation::query()
            ->where(function ($query) use ($start, $end) {
                $query->whereDoesntHave('leases', fn ($lease) => $lease->where('type', 'wynajmowany'))
                    ->orWhereHas('leases', function ($lease) use ($start, $end) {
                        $lease->where('type', 'wynajmowany')
                            ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $end->toDateString()))
                            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $start->toDateString()));
                    });
            })
            ->pluck('id');

        $capacity = (int) Accommodation::query()->whereIn('id', $heldIds)->sum('capacity');

        if ($heldIds->isEmpty()) {
            return ['occupied' => 0, 'capacity' => 0];
        }

        $occupied = AccommodationAssignment::query()
            ->overlappingWith($start, $end)
            ->whereIn('accommodation_id', $heldIds)
            ->get(['accommodation_id', 'employee_id'])
            ->groupBy('accommodation_id')
            ->sum(fn (Collection $rows) => $rows->pluck('employee_id')->unique()->count());

        return ['occupied' => (int) $occupied, 'capacity' => $capacity];
    }

    /**
     * Auta w terenie: poza bazą, bez serwisu w tym tygodniu i bez zdarzenia logistycznego.
     * Licznik to osoby z przypisań do tych aut. Mianownik to suma ich pojemności, także pustych.
     *
     * @return array{occupied: int, capacity: int}
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
            ->get(['id', 'capacity']);

        $capacity = (int) $vehicles->sum(fn ($vehicle) => (int) ($vehicle->capacity ?? 0));
        $ids = $vehicles->pluck('id');

        if ($ids->isEmpty()) {
            return ['occupied' => 0, 'capacity' => 0];
        }

        $occupied = VehicleAssignment::query()
            ->overlappingWith($start, $end)
            ->whereIn('vehicle_id', $ids)
            ->where(fn ($q) => $q->where('is_return_trip', false)->orWhereNull('is_return_trip'))
            ->get(['vehicle_id', 'employee_id'])
            ->groupBy('vehicle_id')
            ->sum(fn (Collection $rows) => $rows->pluck('employee_id')->unique()->count());

        return ['occupied' => (int) $occupied, 'capacity' => $capacity];
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
     * @return array{with_rotation: int, without_rotation: int, day: Carbon}
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

        $withRotation = $inBase->intersect($withRotationIds)->count();

        return [
            'with_rotation' => $withRotation,
            'without_rotation' => $inBase->count() - $withRotation,
            'day' => $day,
        ];
    }

    /**
     * Ile osób dosłać, żeby przyszły tydzień był pełny.
     * Popyt przyszłego tygodnia minus osoby z przypisaniem na przyszły tydzień,
     * których rotacja nie kończy się do środy odcięcia. Ujemny wynik to nadmiar.
     *
     * @return array{to_send: int, next_demand: int, staying: int}
     */
    public function toSendForNextWeek(Carbon $weekStart, Carbon $weekEnd): array
    {
        $nextStart = $weekStart->copy()->addWeek()->startOfDay();
        $nextEnd = $weekEnd->copy()->addWeek()->endOfDay();
        $cutoff = $this->rotationCutoffAfterWeek($weekEnd);

        $nextDemand = $this->totalDemandForWeek($nextStart, $nextEnd);

        $assignedNext = ProjectAssignment::query()
            ->overlappingWith($nextStart, $nextEnd)
            ->pluck('employee_id')
            ->map(fn ($id) => (int) $id)
            ->unique();

        $endingIds = $this->rotationsEndingThrough($weekStart, $cutoff)
            ->pluck('employee_id')
            ->map(fn ($id) => (int) $id);

        $staying = $assignedNext->diff($endingIds)->count();

        return [
            'to_send' => $nextDemand - $staying,
            'next_demand' => $nextDemand,
            'staying' => $staying,
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
