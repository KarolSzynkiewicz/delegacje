<?php

namespace App\Http\Controllers;

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
        protected WeeklyStabilityService $stabilityService
    ) {}

    /**
     * Display the weekly overview.
     */
    public function index(Request $request): View
    {
        $startDate = $this->parseStartDate($request);
        $weeks = $this->weeklyOverviewService->getWeeks($startDate);
        $projects = $this->weeklyOverviewService->getProjectsWithWeeklyData($weeks);
        
        // Create ViewModels for each project
        $projects = $this->enrichProjectsWithSummary($projects);
        
        $navigation = $this->buildNavigation('weekly-overview.index', $weeks[0]);
        
        return view('weekly-overview.index', compact('weeks', 'projects', 'startDate', 'navigation'));
    }

    /**
     * Display the weekly planner 2 - calendar table view.
     */
    public function planner2(Request $request): View
    {
        $startDate = $this->parseStartDate($request);
        $projectId = $request->query('project_id');
        
        $weeks = $this->weeklyOverviewService->getWeeks($startDate);
        $projects = $this->weeklyOverviewService->getProjectsWithWeeklyData($weeks);
        $projects = $this->filterProjectsById($projects, $projectId);
        
        $projectsWithCalendar = $this->enrichProjectsWithCalendarData($projects, $weeks);
        
        $navigation = $this->buildNavigation('weekly-overview.planner2', $weeks[0], $projectId);
        
        return view('weekly-overview.planner2', compact('weeks', 'projectsWithCalendar', 'startDate', 'navigation', 'projectId'));
    }

    /**
     * Display the weekly planner 3 - honest aggregation view.
     */
    public function planner3(Request $request): View
    {
        $startDate = $this->parseStartDate($request);
        $projectId = $request->query('project_id');
        
        $weeks = $this->weeklyOverviewService->getWeeks($startDate);
        $projects = $this->weeklyOverviewService->getProjectsWithWeeklyData($weeks);
        $projects = $this->filterProjectsById($projects, $projectId);
        
        $week = $weeks[0];
        $weekStart = $week['start'];
        $weekEnd = $week['end'];
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
        if (!$projectId) {
            return $projects;
        }
        
        $filtered = array_filter($projects, function($projectData) use ($projectId) {
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
        
        $buildUrl = function(Carbon $date) use ($routeName, $projectId) {
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
        return array_map(function($projectData) {
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
        return array_map(function($projectData) use ($weeks) {
            $week = $weeks[0];
            $projectData['calendar'] = $this->weeklyOverviewService->getProjectCalendarData(
                $projectData['project'], 
                $week
            );
            $projectData['weeks_data'] = [
                $this->weeklyOverviewService->getProjectWeekData($projectData['project'], $week)
            ];
            return $projectData;
        }, $projects);
    }

    /**
     * Enrich projects with stability data.
     */
    protected function enrichProjectsWithStability(array $projects, Carbon $weekStart, Carbon $weekEnd): array
    {
        return array_map(function($projectData) use ($weekStart, $weekEnd) {
            $projectData['stability'] = $this->stabilityService->getProjectStability(
                $projectData['project'], 
                $weekStart, 
                $weekEnd
            );
            return $projectData;
        }, $projects);
    }
}

