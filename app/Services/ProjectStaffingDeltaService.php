<?php

namespace App\Services;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Models\Employee;
use App\Models\LogisticsEvent;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Rotation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProjectStaffingDeltaService
{
    public function __construct(
        protected WeeklyOverviewService $weeklyOverview,
        protected WeeklyDashboardKpiService $kpis,
    ) {}

    /**
     * Delta obsady projektu względem tygodnia:
     * 1) odeszli (zeszły → ten), 2) przybyli (ten), 3) jest, a nie będzie (ten → przyszły), 4) przyjeżdżają.
     *
     * @return array{
     *     left: Collection<int, array<string, mixed>>,
     *     arrived: Collection<int, array<string, mixed>>,
     *     ending: Collection<int, array<string, mixed>>,
     *     arriving: Collection<int, array<string, mixed>>,
     *     week_label: string,
     * }
     */
    public function forProjectWeek(Project $project, Carbon $weekStart): array
    {
        $weekStart = $weekStart->copy()->startOfDay()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();
        $prevStart = $weekStart->copy()->subWeek();
        $prevEnd = $weekEnd->copy()->subWeek();
        $nextStart = $weekStart->copy()->addWeek();
        $nextEnd = $weekEnd->copy()->addWeek();
        $rotationHorizon = $this->kpis->rotationCutoffAfterWeek($weekEnd);
        $today = now()->startOfDay();

        $thisWeek = $this->assignmentsFor($project->id, $weekStart, $weekEnd);
        $lastWeek = $this->assignmentsFor($project->id, $prevStart, $prevEnd);
        $nextWeek = $this->assignmentsFor($project->id, $nextStart, $nextEnd);

        $thisByEmployee = $this->latestByEmployee($thisWeek);
        $lastByEmployee = $this->latestByEmployee($lastWeek);
        $nextByEmployee = $this->earliestByEmployee($nextWeek);

        $thisIds = $thisByEmployee->keys();
        $lastIds = $lastByEmployee->keys();
        $nextIds = $nextByEmployee->keys();

        $leftIds = $lastIds->diff($thisIds)->values();
        $arrivedIds = $thisIds->diff($lastIds)->values();
        $leavingIds = $thisIds->diff($nextIds)->values();
        $arrivingIds = $nextIds->diff($thisIds)->values();

        $employeeIds = $leftIds
            ->merge($arrivedIds)
            ->merge($leavingIds)
            ->merge($arrivingIds)
            ->unique()
            ->values();
        $employees = $this->loadEmployees($employeeIds);
        $documents = $this->weeklyOverview->loadPlannerDocumentsByEmployee($employeeIds);

        $rotationsEnding = $leavingIds->isEmpty()
            ? collect()
            : Rotation::query()
                ->whereIn('employee_id', $leavingIds)
                ->whereNotNull('end_date')
                ->whereDate('end_date', '>=', $weekStart->toDateString())
                ->whereDate('end_date', '<=', $rotationHorizon->toDateString())
                ->orderBy('end_date')
                ->get()
                ->unique('employee_id')
                ->keyBy(fn (Rotation $rotation) => (int) $rotation->employee_id);

        $returnsByEmployee = $this->returnsByEmployee($leavingIds, $weekStart, $nextEnd);
        $otherProjectNextIds = $this->employeeIdsOnOtherProjects($leavingIds, $project->id, $nextStart, $nextEnd);

        $left = $leftIds->map(function (int $id) use ($lastByEmployee, $employees, $documents) {
            $assignment = $lastByEmployee->get($id);
            $end = $assignment?->end_date;

            return $this->row(
                $employees->get($id),
                $assignment,
                $documents->get($id, collect()),
                'zeszły tydzień',
                $end ? 'do '.$end->format('d.m') : 'zeszły tydzień',
                'left',
            );
        })->filter()->values();

        $arrived = $arrivedIds->map(function (int $id) use ($thisByEmployee, $employees, $documents) {
            $assignment = $thisByEmployee->get($id);
            $start = $assignment?->start_date;

            return $this->row(
                $employees->get($id),
                $assignment,
                $documents->get($id, collect()),
                'od tego tyg.',
                $start ? 'od '.$start->format('d.m') : 'od tego tyg.',
                'arrived',
            );
        })->filter()->values();

        $ending = $leavingIds->map(function (int $id) use (
            $thisByEmployee,
            $employees,
            $documents,
            $rotationsEnding,
            $returnsByEmployee,
            $otherProjectNextIds,
            $today,
        ) {
            $assignment = $thisByEmployee->get($id);
            $return = $returnsByEmployee->get($id);
            $rotation = $rotationsEnding->get($id);

            if ($return) {
                $when = $return->event_date?->copy()->startOfDay();
                $badge = 'zjazd';
                $detail = $when ? 'zjazd '.$when->format('d.m') : 'zjazd zaplanowany';
                $reason = 'return';
                $sort = 0;
            } elseif ($rotation) {
                $end = $rotation->end_date->copy()->startOfDay();
                $days = (int) $today->diffInDays($end, false);
                $badge = $days < 0
                    ? (abs($days) === 1 ? 'wczoraj' : abs($days).' dni temu')
                    : ($days === 0
                        ? 'dziś'
                        : ($days === 1 ? 'za 1 dzień' : 'za '.$days.' dni'));
                $detail = 'koniec rotacji '.$end->format('d.m');
                $reason = 'rotation';
                $sort = 1;
            } else {
                $end = $assignment?->end_date;
                $elsewhere = isset($otherProjectNextIds[$id]);
                $badge = $elsewhere ? 'inny projekt' : 'bez projektu';
                $detail = $end
                    ? 'koniec przypisania '.$end->format('d.m')
                    : 'koniec przypisania';
                $reason = 'assignment';
                $sort = 2;
            }

            $row = $this->row(
                $employees->get($id),
                $assignment,
                $documents->get($id, collect()),
                $badge,
                $detail,
                'ending',
                $sort,
            );
            if ($row === null) {
                return null;
            }
            $row['reason'] = $reason;
            $row['tone'] = 'ending-'.$reason;

            return $row;
        })->filter()->sortBy(function (array $row): string {
            $name = mb_strtolower($row['employee']->last_name.' '.$row['employee']->first_name);

            return sprintf('%d-%s', $row['sort'] ?? 9, $name);
        })->values();

        $arriving = $arrivingIds->map(function (int $id) use ($nextByEmployee, $employees, $documents) {
            $assignment = $nextByEmployee->get($id);
            $start = $assignment?->start_date?->copy()->startOfDay();
            $dayNames = ['', 'pn', 'wt', 'śr', 'czw', 'pt', 'sob', 'nie'];
            $badge = $start
                ? ($dayNames[(int) $start->format('N')] ?? '').' '.$start->format('d.m')
                : 'przyszły tyg.';

            return $this->row(
                $employees->get($id),
                $assignment,
                $documents->get($id, collect()),
                trim($badge),
                $start ? 'od '.$start->format('d.m') : 'przyszły tydzień',
                'arriving',
                $start?->timestamp,
            );
        })->filter()->sortBy(fn (array $row) => $row['sort'] ?? 0)->values();

        return [
            'left' => $left,
            'arrived' => $arrived,
            'ending' => $ending,
            'arriving' => $arriving,
            'week_label' => $weekStart->locale('pl')->isoFormat('D MMM').' – '.$weekEnd->locale('pl')->isoFormat('D MMM'),
        ];
    }

    /**
     * @param  Collection<int, int>  $employeeIds
     * @return Collection<int, LogisticsEvent>
     */
    private function returnsByEmployee(Collection $employeeIds, Carbon $from, Carbon $to): Collection
    {
        if ($employeeIds->isEmpty()) {
            return collect();
        }

        $events = LogisticsEvent::query()
            ->where('type', LogisticsEventType::RETURN)
            ->where('status', '!=', LogisticsEventStatus::CANCELLED)
            ->whereBetween('event_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->whereHas('participants', fn ($q) => $q->whereIn('employee_id', $employeeIds))
            ->with(['participants' => fn ($q) => $q->whereIn('employee_id', $employeeIds)])
            ->orderBy('event_date')
            ->get();

        $byEmployee = collect();
        foreach ($events as $event) {
            foreach ($event->participants as $participant) {
                $id = (int) $participant->employee_id;
                if (! $byEmployee->has($id)) {
                    $byEmployee->put($id, $event);
                }
            }
        }

        return $byEmployee;
    }

    /**
     * @param  Collection<int, int>  $employeeIds
     * @return array<int, true>
     */
    private function employeeIdsOnOtherProjects(
        Collection $employeeIds,
        int $projectId,
        Carbon $start,
        Carbon $end,
    ): array {
        if ($employeeIds->isEmpty()) {
            return [];
        }

        return ProjectAssignment::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('project_id', '!=', $projectId)
            ->overlappingWith($start, $end)
            ->pluck('employee_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->flip()
            ->map(fn () => true)
            ->all();
    }

    /**
     * @return Collection<int, ProjectAssignment>
     */
    private function assignmentsFor(int $projectId, Carbon $start, Carbon $end): Collection
    {
        return ProjectAssignment::query()
            ->where('project_id', $projectId)
            ->overlappingWith($start, $end)
            ->with(['role:id,name'])
            ->get(['id', 'employee_id', 'project_id', 'role_id', 'start_date', 'end_date']);
    }

    /**
     * @param  Collection<int, ProjectAssignment>  $assignments
     * @return Collection<int, ProjectAssignment>
     */
    private function latestByEmployee(Collection $assignments): Collection
    {
        return $assignments
            ->groupBy('employee_id')
            ->map(function (Collection $rows) {
                return $rows->sortByDesc(fn (ProjectAssignment $row) => $row->end_date?->timestamp ?? PHP_INT_MAX)
                    ->first();
            });
    }

    /**
     * @param  Collection<int, ProjectAssignment>  $assignments
     * @return Collection<int, ProjectAssignment>
     */
    private function earliestByEmployee(Collection $assignments): Collection
    {
        return $assignments
            ->groupBy('employee_id')
            ->map(fn (Collection $rows) => $rows->sortBy(fn (ProjectAssignment $row) => $row->start_date->timestamp)->first());
    }

    /**
     * @param  Collection<int, int>  $ids
     * @return Collection<int, Employee>
     */
    private function loadEmployees(Collection $ids): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        return Employee::query()
            ->whereIn('id', $ids)
            ->with(['latestEvaluation', 'roles:id'])
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  Collection<int, mixed>  $documents
     * @return array<string, mixed>|null
     */
    private function row(
        ?Employee $employee,
        ?ProjectAssignment $assignment,
        Collection $documents,
        string $badge,
        string $detail,
        string $tone,
        ?int $sort = null,
    ): ?array {
        if (! $employee) {
            return null;
        }

        $role = $assignment?->role;
        $seniority = $role ? $employee->seniorityFor($role->id) : null;

        return [
            'employee' => $employee,
            'role' => $role,
            'seniority' => $seniority,
            'planner_documents' => $documents->unique('document_id')->values(),
            'latest_evaluation' => $employee->latestEvaluation,
            'latest_evaluation_score' => $employee->latestEvaluation?->average_score,
            'badge' => $badge,
            'detail' => $detail,
            'tone' => $tone,
            'sort' => $sort ?? 0,
            'timeline_url' => route('employees.show', ['employee' => $employee, 'tab' => 'timeline']),
        ];
    }
}
