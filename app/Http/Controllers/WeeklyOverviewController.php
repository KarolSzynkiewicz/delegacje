<?php

namespace App\Http\Controllers;

use App\Services\WeeklyOverviewService;
use App\ViewModels\WeeklyProjectSummary;
use Illuminate\View\View;

class WeeklyOverviewController extends Controller
{
    public function __construct(
        protected WeeklyOverviewService $weeklyOverviewService
    ) {}

    /**
     * Display the weekly overview.
     */
    public function index(\Illuminate\Http\Request $request): View
    {
        // Get start date from query parameter or use current week
        $startDate = $request->query('start_date');
        if ($startDate) {
            $startDate = \Carbon\Carbon::parse($startDate)->startOfWeek();
        } else {
            $startDate = \Carbon\Carbon::now()->startOfWeek();
        }
        
        $weeks = $this->weeklyOverviewService->getWeeks($startDate);
        $projects = $this->weeklyOverviewService->getProjectsWithWeeklyData($weeks);
        
        // Create ViewModels for each project - summary directly on projectData
        $projects = array_map(function($projectData) {
            $weekData = $projectData['weeks_data'][0] ?? null;
            $projectData['summary'] = $weekData ? new WeeklyProjectSummary($weekData) : null;
            return $projectData;
        }, $projects);
        
        // Navigation data - move date logic out of Blade
        $currentWeek = $weeks[0];
        $prevWeekStart = $currentWeek['start']->copy()->subWeek()->startOfWeek();
        $nextWeekStart = $currentWeek['end']->copy()->addDay()->startOfWeek();
        
        $navigation = [
            'current' => $currentWeek,
            'prevUrl' => route('weekly-overview.index', ['start_date' => $prevWeekStart->format('Y-m-d')]),
            'nextUrl' => route('weekly-overview.index', ['start_date' => $nextWeekStart->format('Y-m-d')]),
        ];
        
        return view('weekly-overview.index', compact('weeks', 'projects', 'startDate', 'navigation'));
    }
}

