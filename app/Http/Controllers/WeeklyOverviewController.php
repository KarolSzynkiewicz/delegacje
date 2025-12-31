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
    public function index(): View
    {
        $weeks = $this->weeklyOverviewService->getWeeks();
        $projects = $this->weeklyOverviewService->getProjectsWithWeeklyData($weeks);
        
        return view('weekly-overview.index', compact('weeks', 'projects'));
    }
}

