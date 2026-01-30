<?php

namespace App\Http\Controllers;

use App\Services\ProfitabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    protected ProfitabilityService $profitabilityService;

    public function __construct(ProfitabilityService $profitabilityService)
    {
        $this->profitabilityService = $profitabilityService;
    }

    /**
     * Display the profitability dashboard.
     */
    public function index(Request $request): View
    {
        $month = $this->parseMonth($request);
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        
        $projectsProfitability = $this->profitabilityService->getActiveProjectsProfitabilityForMonth($monthStart, $monthEnd);
        $topEmployees = $this->profitabilityService->getTopEmployeesByRevenueForMonth($monthStart, $monthEnd, 10);
        $longestRotations = $this->profitabilityService->getEmployeesWithLongestRotations(10);
        $summary = $this->profitabilityService->getRevenueVsCostsSummaryForMonth($monthStart, $monthEnd);
        
        $navigation = $this->buildNavigation($month);

        return view('dashboard.profitability', compact(
            'projectsProfitability',
            'topEmployees',
            'longestRotations',
            'summary',
            'navigation',
            'month'
        ));
    }

    /**
     * Parse month from request or use current month.
     */
    protected function parseMonth(Request $request): Carbon
    {
        $year = $request->query('year');
        $month = $request->query('month');
        
        if ($year && $month) {
            return Carbon::create((int)$year, (int)$month, 1);
        }
        
        return Carbon::now()->startOfMonth();
    }

    /**
     * Build navigation data for month navigation.
     */
    protected function buildNavigation(Carbon $currentMonth): array
    {
        $prevMonth = $currentMonth->copy()->subMonth();
        $nextMonth = $currentMonth->copy()->addMonth();
        
        // Polish month names
        $months = [
            1 => 'Styczeń', 2 => 'Luty', 3 => 'Marzec', 4 => 'Kwiecień',
            5 => 'Maj', 6 => 'Czerwiec', 7 => 'Lipiec', 8 => 'Sierpień',
            9 => 'Wrzesień', 10 => 'Październik', 11 => 'Listopad', 12 => 'Grudzień'
        ];
        
        $monthName = $months[$currentMonth->format('n')] ?? $currentMonth->format('F');
        
        return [
            'current' => [
                'month' => $currentMonth->format('m'),
                'year' => $currentMonth->format('Y'),
                'label' => $monthName . ' ' . $currentMonth->format('Y'),
                'start' => $currentMonth->copy()->startOfMonth(),
                'end' => $currentMonth->copy()->endOfMonth(),
            ],
            'prevUrl' => route('profitability.index') . '?year=' . $prevMonth->format('Y') . '&month=' . $prevMonth->format('m'),
            'nextUrl' => route('profitability.index') . '?year=' . $nextMonth->format('Y') . '&month=' . $nextMonth->format('m'),
        ];
    }
}
