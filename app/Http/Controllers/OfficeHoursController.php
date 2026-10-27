<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class OfficeHoursController extends Controller
{
    public function index(Request $request): View
    {
        return view('office-hours.index', $this->viewData($request, onlyMine: false));
    }

    public function mine(Request $request): View
    {
        return view('office-hours.index', $this->viewData($request, onlyMine: true));
    }

    /**
     * @return array{month: string, week: string|null, viewMode: string, onlyMine: bool}
     */
    private function viewData(Request $request, bool $onlyMine): array
    {
        $month = $request->query('month', now()->format('Y-m'));
        if (! is_string($month) || ! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $week = $request->query('week');
        if (! is_string($week) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $week)) {
            $week = null;
        }

        $viewMode = $request->query('view', 'week');
        if (! in_array($viewMode, ['week', 'month'], true)) {
            $viewMode = 'week';
        }

        return compact('month', 'week', 'viewMode', 'onlyMine');
    }
}
