<?php

namespace App\Services;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Models\LogisticsEvent;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\ProjectDemand;
use App\Models\Rotation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class WeeklyDashboardKpiService
{
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
