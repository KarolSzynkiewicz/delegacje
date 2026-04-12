<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ExpiringDocumentsService;
use App\Services\WeeklyDashboardKpiService;
use App\Services\WeeklyOverviewService;
use App\Services\WeeklyStabilityService;
use App\ViewModels\WeeklyProjectSummary;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WeeklyOverviewController extends Controller
{
    public function __construct(
        protected WeeklyOverviewService $weeklyOverviewService,
        protected WeeklyStabilityService $stabilityService,
        protected ExpiringDocumentsService $expiringDocumentsService,
        protected WeeklyDashboardKpiService $weeklyDashboardKpiService
    ) {}

    /**
     * Display the weekly overview.
     */
    public function index(Request $request): View
    {
        $startDate = $this->parseStartDate($request);
        $projectId = $request->query('project_id') ? (int) $request->query('project_id') : null;

        $weeks = $this->weeklyOverviewService->getWeeks($startDate);
        $weekStart = $weeks[0]['start'];
        $weekEnd = $weeks[0]['end'];
        $projects = $this->weeklyOverviewService->getProjectsWithWeeklyData($weeks);
        $projects = $this->filterProjectsById($projects, $projectId);

        // Create ViewModels for each project
        $projects = $this->enrichProjectsWithSummary($projects);

        $navigation = $this->buildNavigation('weekly-overview.index', $weeks[0], $projectId);

        // Get all projects for the search dropdown
        $allProjects = $this->getAllProjectsForDropdown($weekStart, $weekEnd);

        // Get users for tasks component
        $users = \App\Models\User::orderBy('name')->get();

        // Get return trips (zjazdy) for the week (exclude CANCELLED)
        // Use event_date (when return starts) - end_date may be NULL
        $returnTrips = \App\Models\LogisticsEvent::where('type', \App\Enums\LogisticsEventType::RETURN)
            ->where('status', '!=', \App\Enums\LogisticsEventStatus::CANCELLED)
            ->whereBetween('event_date', [$weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay()])
            ->with(['participants.employee', 'vehicle'])
            ->orderBy('event_date')
            ->get();

        // Get ALL departures for the week (exclude CANCELLED) - for arrivals section
        // Use end_date (arrival date) to show arrivals in the correct week
        $allDepartures = \App\Models\LogisticsEvent::where('type', \App\Enums\LogisticsEventType::DEPARTURE)
            ->where('status', '!=', \App\Enums\LogisticsEventStatus::CANCELLED)
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay()])
            ->with([
                'participants.employee.projectAssignments' => function ($query) {
                    $query->select('id', 'employee_id', 'logistics_event_id');
                },
                'vehicle',
                'toLocation',
            ])
            ->orderBy('end_date')
            ->get();

        $transferEvents = \App\Models\LogisticsEvent::where('type', \App\Enums\LogisticsEventType::TRANSFER)
            ->where('status', '!=', \App\Enums\LogisticsEventStatus::CANCELLED)
            ->whereBetween('event_date', [$weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay()])
            ->with(['participants.employee', 'vehicle', 'fromLocation', 'toLocation'])
            ->orderBy('event_date')
            ->get();

        // Filter departures to show only those with unassigned participants
        // Now optimized - no N+1! projectAssignments are already loaded
        $departures = $allDepartures->map(function ($departure) {
            // Filter participants who don't have a project assignment from this logistics event
            $filteredParticipants = $departure->participants->filter(function ($participant) use ($departure) {
                if (! $participant->employee) {
                    return false;
                }

                // Check in already loaded collection - NO QUERY!
                $hasAssignment = $participant->employee->projectAssignments
                    ->where('logistics_event_id', $departure->id)
                    ->isNotEmpty();

                return ! $hasAssignment;
            });
            $departure->setRelation('participants', $filteredParticipants);

            return $departure;
        })->filter(function ($departure) {
            return $departure->participants->isNotEmpty();
        });

        // Get employees without project but with vehicle or accommodation
        $employeesWithoutProject = $this->weeklyOverviewService->getEmployeesWithoutProjectButWithResources($weekStart, $weekEnd);

        // Get expiring documents, vehicle inspections, and leases for this month
        $expiringItems = $this->expiringDocumentsService->getExpiringThisMonth();

        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $projectsEndingThisMonth = Project::query()
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->orderBy('end_date')
            ->orderBy('name')
            ->get();

        $employeesInFieldCount = $this->weeklyDashboardKpiService->countEmployeesInFieldForWeek($weekStart, $weekEnd);

        return view('weekly-overview.index', compact('weeks', 'projects', 'startDate', 'navigation', 'projectId', 'allProjects', 'users', 'returnTrips', 'allDepartures', 'transferEvents', 'departures', 'employeesWithoutProject', 'expiringItems', 'employeesInFieldCount', 'projectsEndingThisMonth'));
    }

    /**
     * Display the weekly planner 2 - calendar table view.
     */
    public function planner2(Request $request): View
    {
        $startDate = $this->parseStartDate($request);
        $projectId = $request->query('project_id') ? (int) $request->query('project_id') : null;

        $weeks = $this->weeklyOverviewService->getWeeks($startDate);
        $weekStart = $weeks[0]['start'];
        $weekEnd = $weeks[0]['end'];
        $projects = $this->weeklyOverviewService->getProjectsWithWeeklyData($weeks);
        $projects = $this->filterProjectsById($projects, $projectId);

        $projectsWithCalendar = $this->enrichProjectsWithCalendarData($projects, $weeks);

        $navigation = $this->buildNavigation('weekly-overview.planner2', $weeks[0], $projectId);

        // Get all projects for the search dropdown
        $allProjects = $this->getAllProjectsForDropdown($weekStart, $weekEnd);

        return view('weekly-overview.planner2', compact('weeks', 'projectsWithCalendar', 'startDate', 'navigation', 'projectId', 'allProjects'));
    }

    /**
     * Display the weekly planner 3 - honest aggregation view.
     */
    public function planner3(Request $request): View
    {
        $startDate = $this->parseStartDate($request);
        $projectId = $request->query('project_id');

        $weeks = $this->weeklyOverviewService->getWeeks($startDate);
        $weekStart = $weeks[0]['start'];
        $weekEnd = $weeks[0]['end'];
        $projects = $this->weeklyOverviewService->getProjectsWithWeeklyData($weeks);
        $projects = $this->filterProjectsById($projects, $projectId);

        $week = $weeks[0];
        $projectsWithStability = $this->enrichProjectsWithStability($projects, $weekStart, $weekEnd);

        $navigation = $this->buildNavigation('weekly-overview.planner3', $weeks[0], $projectId);

        return view('weekly-overview.planner3', compact(
            'weeks',
            'projectsWithStability',
            'startDate',
            'navigation',
            'projectId',
            'weekStart',
            'weekEnd'
        ));
    }

    /**
     * Parse start date from request or use current week.
     */
    protected function parseStartDate(Request $request): Carbon
    {
        $startDate = $request->query('start_date');

        return $startDate
            ? Carbon::parse($startDate)->startOfWeek()
            : Carbon::now()->startOfWeek();
    }

    /**
     * Filter projects by ID if provided.
     */
    protected function filterProjectsById(array $projects, ?int $projectId): array
    {
        if (! $projectId) {
            return $projects;
        }

        $filtered = array_filter($projects, function ($projectData) use ($projectId) {
            return $projectData['project']->id == $projectId;
        });

        return array_values($filtered);
    }

    /**
     * Build navigation data for week navigation.
     */
    protected function buildNavigation(string $routeName, array $currentWeek, ?int $projectId = null): array
    {
        $prevWeekStart = $currentWeek['start']->copy()->subWeek()->startOfWeek();
        $nextWeekStart = $currentWeek['end']->copy()->addDay()->startOfWeek();

        $buildUrl = function (Carbon $date) use ($routeName, $projectId) {
            $params = ['start_date' => $date->format('Y-m-d')];
            if ($projectId) {
                $params['project_id'] = $projectId;
            }

            return route($routeName, $params);
        };

        return [
            'current' => $currentWeek,
            'prevUrl' => $buildUrl($prevWeekStart),
            'nextUrl' => $buildUrl($nextWeekStart),
        ];
    }

    /**
     * Enrich projects with summary ViewModels.
     */
    protected function enrichProjectsWithSummary(array $projects): array
    {
        return array_map(function ($projectData) {
            $weekData = $projectData['weeks_data'][0] ?? null;
            $projectData['summary'] = $weekData ? new WeeklyProjectSummary($weekData) : null;

            return $projectData;
        }, $projects);
    }

    /**
     * Enrich projects with calendar data.
     */
    protected function enrichProjectsWithCalendarData(array $projects, array $weeks): array
    {
        return array_map(function ($projectData) use ($weeks) {
            $week = $weeks[0];
            $projectData['calendar'] = $this->weeklyOverviewService->getProjectCalendarData(
                $projectData['project'],
                $week
            );
            $projectData['weeks_data'] = [
                $this->weeklyOverviewService->getProjectWeekData($projectData['project'], $week),
            ];

            return $projectData;
        }, $projects);
    }

    /**
     * Enrich projects with stability data.
     */
    protected function enrichProjectsWithStability(array $projects, Carbon $weekStart, Carbon $weekEnd): array
    {
        return array_map(function ($projectData) use ($weekStart, $weekEnd) {
            $projectData['stability'] = $this->stabilityService->getProjectStability(
                $projectData['project'],
                $weekStart,
                $weekEnd
            );

            return $projectData;
        }, $projects);
    }

    /**
     * Get all active projects for dropdowns.
     * Uses cache with shorter TTL to ensure new projects appear quickly.
     */
    protected function getAllProjectsForDropdown(Carbon $weekStart, Carbon $weekEnd)
    {
        $cacheKey = 'active_projects_dropdown_'.$weekStart->format('Y-m-d');

        return cache()->remember($cacheKey, 300, function () use ($weekStart, $weekEnd) {
            return Project::active()
                ->where(function ($q) use ($weekEnd) {
                    $q->whereNull('start_date')
                        ->orWhereDate('start_date', '<=', $weekEnd->toDateString());
                })
                ->where(function ($q) use ($weekStart) {
                    $q->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $weekStart->toDateString());
                })
                ->orderBy('name')
                ->get();
        });
    }
}
