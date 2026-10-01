<?php

namespace App\Services;

use App\Models\Employee;
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
     * Delta obsady projektu względem tygodnia: odeszli, przybyli, koniec rotacji, przyjeżdżają.
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
        $arrivingIds = $nextIds->diff($thisIds)->values();

        $employeeIds = $leftIds->merge($arrivedIds)->merge($thisIds)->merge($arrivingIds)->unique()->values();
        $employees = $this->loadEmployees($employeeIds);
        $documents = $this->weeklyOverview->loadPlannerDocumentsByEmployee($employeeIds);

        $rotations = $thisIds->isEmpty()
            ? collect()
            : Rotation::query()
                ->whereIn('employee_id', $thisIds)
                ->whereNotNull('end_date')
                ->whereDate('end_date', '>=', $weekStart->toDateString())
                ->whereDate('end_date', '<=', $rotationHorizon->toDateString())
                ->orderBy('end_date')
                ->get()
                ->unique('employee_id')
                ->keyBy('employee_id');

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

        $ending = $rotations->map(function (Rotation $rotation) use ($thisByEmployee, $employees, $documents, $today) {
            $id = (int) $rotation->employee_id;
            $assignment = $thisByEmployee->get($id);
            $end = $rotation->end_date->copy()->startOfDay();
            $days = (int) $today->diffInDays($end, false);
            $badge = $days < 0
                ? (abs($days) === 1 ? 'wczoraj' : abs($days).' dni temu')
                : ($days === 0
                    ? 'dziś'
                    : ($days === 1 ? 'za 1 dzień' : 'za '.$days.' dni'));

            return $this->row(
                $employees->get($id),
                $assignment,
                $documents->get($id, collect()),
                $badge,
                'koniec '.$end->format('d.m'),
                'ending',
            );
        })->filter()->sortBy(fn (array $row) => $row['sort'] ?? 0)->values();

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
