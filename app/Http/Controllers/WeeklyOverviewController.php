<?php

namespace App\Http\Controllers;

use App\Services\WeeklyOverviewService;
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
        
        return view('weekly-overview.index', compact('weeks', 'projects', 'startDate'));
    }
}

