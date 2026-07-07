<?php

namespace App\Http\Controllers;

use App\Services\TimeLogService;
use App\Models\TimeLog;
use App\Models\ProjectAssignment;
use App\Http\Requests\StoreTimeLogRequest;
use App\Http\Requests\UpdateTimeLogRequest;
use App\Enums\AssignmentStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class TimeLogController extends Controller
{
    protected $timeLogService;

    public function __construct(TimeLogService $timeLogService)
    {
        $this->timeLogService = $timeLogService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('time-logs.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        // Get active assignments (based on dates)
        $assignments = ProjectAssignment::with('employee', 'project', 'role')
            ->active()
            ->orderBy('start_date', 'desc')
            ->get();
        
        return view('time-logs.create', compact('assignments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTimeLogRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $assignment = ProjectAssignment::findOrFail($validated['project_assignment_id']);
            $workDate = Carbon::parse($validated['work_date']);
            $hoursWorked = (float) $validated['hours_worked'];
            
            $this->timeLogService->createTimeLog(
                $assignment,
                $workDate,
                $hoursWorked,
                $validated['notes'] ?? null
            );

            return redirect()
                ->route('time-logs.index')
                ->with('success', 'Zapis czasu pracy został dodany.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TimeLog $timeLog): View
    {
        $timeLog->load('projectAssignment.employee', 'projectAssignment.project', 'projectAssignment.role');
        
        return view('time-logs.show', compact('timeLog'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TimeLog $timeLog): View
    {
        // Get active assignments (based on dates)
        $assignments = ProjectAssignment::with('employee', 'project', 'role')
            ->active()
            ->orderBy('start_date', 'desc')
            ->get();
        
        return view('time-logs.edit', compact('timeLog', 'assignments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTimeLogRequest $request, TimeLog $timeLog): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $workDate = Carbon::parse($validated['work_date']);
            $hoursWorked = (float) $validated['hours_worked'];
            
            $this->timeLogService->updateTimeLog(
                $timeLog,
                $workDate,
                $hoursWorked,
                $validated['notes'] ?? null
            );

            return redirect()
                ->route('time-logs.index')
                ->with('success', 'Zapis czasu pracy został zaktualizowany.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TimeLog $timeLog): RedirectResponse
    {
        $timeLog->delete();

        return redirect()
            ->route('time-logs.index')
            ->with('success', 'Zapis czasu pracy został usunięty.');
    }

    /**
     * Display monthly grid for time logs editing.
     */
    public function monthlyGrid(Request $request): View
    {
        $month = $request->query('month', Carbon::now()->format('Y-m'));

        $projectId = $request->query('project_id');
        $userPerPage = 10;
        $userPage = (int) $request->query('user_page', 1);
        if ($userPage < 1) {
            $userPage = 1;
        }

        $currentDate = Carbon::parse($month . '-01');
        $monthStart = $currentDate->copy()->startOfMonth();
        $monthEnd = $currentDate->copy()->endOfMonth();

        // Projekty, które mają aktywne przypisania w tym miesiącu.
        // Dzięki temu domyślnie nie ładujemy całej siatki (limit `max_input_vars`).
        $projectsForDropdown = \App\Models\Project::query()
            ->whereHas('assignments', function ($q) use ($monthStart, $monthEnd) {
                $q->where('start_date', '<=', $monthEnd)
                    ->where(function ($q2) use ($monthStart) {
                        $q2->whereNull('end_date')
                            ->orWhere('end_date', '>=', $monthStart);
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $availableProjectIds = $projectsForDropdown->pluck('id')->values()->all();

        // Jeżeli użytkownik nie podał projektu w query, ustaw domyślnie pierwszy z listy.
        if (!$projectId && !empty($availableProjectIds)) {
            $projectId = $availableProjectIds[0];
        }

        // Jeżeli podano projekt, ale nie należy do listy z przypisaniami w tym miesiącu - pokaż pustą siatkę.
        // (Zostawiamy UX: użytkownik nadal może wybrać dowolny projekt, nawet jeśli w danym miesiącu nie ma przypisań.)
        $gridProjectIds = $projectId ? [(int) $projectId] : null;

        $data = $this->timeLogService->getMonthlyGridData($month, $gridProjectIds);
        $data['availableProjects'] = $projectsForDropdown;
        $data['selectedProjectId'] = $projectId ? (int) $projectId : null;
        $data['isMineRoute'] = false;
        $data['userPage'] = $userPage;
        $data['userPerPage'] = $userPerPage;

        return view('time-logs.monthly-grid', $data);
    }

    /**
     * Display analytics / read-only monthly grid for time logs.
     */
    public function analytics(Request $request): \Illuminate\View\View|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $month = $request->query('month', Carbon::now()->format('Y-m'));
        $projectIdsParam = $request->query('project_ids');
        $format = $request->query('format');

        $currentDate = Carbon::parse($month . '-01');
        $monthStart = $currentDate->copy()->startOfMonth();
        $monthEnd = $currentDate->copy()->endOfMonth();
        $daysInMonth = $monthStart->daysInMonth;

        $prevMonth = $currentDate->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentDate->copy()->addMonth()->format('Y-m');

        // Projects with assignments active in this month
        $availableProjects = \App\Models\Project::query()
            ->whereHas('assignments', function ($q) use ($monthStart, $monthEnd) {
                $q->where('start_date', '<=', $monthEnd)
                    ->where(function ($q2) use ($monthStart) {
                        $q2->whereNull('end_date')
                            ->orWhere('end_date', '>=', $monthStart);
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $allProjectIds = $availableProjects->pluck('id')->all();

        // Resolve selected project IDs from query string
        if ($projectIdsParam !== null) {
            $selectedProjectIds = array_values(array_intersect(
                array_map('intval', (array) $projectIdsParam),
                $allProjectIds
            ));
        } else {
            $selectedProjectIds = $allProjectIds;
        }

        // Generate days array
        $days = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = $monthStart->copy()->addDays($d - 1)->startOfDay();
            $days[] = [
                'number' => $d,
                'date' => $date,
                'isWeekend' => $date->isWeekend(),
            ];
        }

        // Group days into ISO calendar weeks (weeks that overlap the month)
        $weeks = [];
        $weekGroupsMap = [];
        foreach ($days as $dayData) {
            $isoWeek = $dayData['date']->isoWeek();
            $isoYear = $dayData['date']->isoWeekYear();
            $wKey = $isoYear . 'W' . sprintf('%02d', $isoWeek);
            $weekGroupsMap[$wKey][] = $dayData['number'];
        }
        $wi = 1;
        foreach ($weekGroupsMap as $wKey => $dayNums) {
            $firstDate = $monthStart->copy()->addDays($dayNums[0] - 1);
            $lastDate = $monthStart->copy()->addDays($dayNums[count($dayNums) - 1] - 1);
            $weeks[] = [
                'key' => $wKey,
                'index' => $wi++,
                'days' => $dayNums,
                'label' => 'Tydz. ' . ($wi - 1),
                'dateRange' => $firstDate->format('d') . '–' . $lastDate->format('d'),
            ];
        }

        // Fetch all time logs for the month (only employees with actual entries)
        $queryProjectIds = !empty($selectedProjectIds) ? $selectedProjectIds : [0];

        $rawLogs = TimeLog::whereBetween('start_time', [$monthStart, $monthEnd->copy()->endOfDay()])
            ->join('project_assignments as pa', 'time_logs.project_assignment_id', '=', 'pa.id')
            ->join('projects as pr', 'pa.project_id', '=', 'pr.id')
            ->join('employees as em', 'pa.employee_id', '=', 'em.id')
            ->whereIn('pa.project_id', $queryProjectIds)
            ->select(
                'time_logs.hours_worked',
                'time_logs.start_time',
                'pa.project_id',
                'pa.employee_id',
                'pr.name as project_name',
                'em.first_name',
                'em.last_name'
            )
            ->get();

        // Aggregate by project → employee → day
        $byProject = [];

        foreach ($rawLogs as $log) {
            $pid = $log->project_id;
            $eid = $log->employee_id;
            $day = Carbon::parse($log->start_time)->day;
            $h = (float) $log->hours_worked;

            if (!isset($byProject[$pid])) {
                $byProject[$pid] = [
                    'name' => $log->project_name,
                    'employees' => [],
                    'dailyTotals' => [],
                    'weeklyTotals' => [],
                    'monthTotal' => 0.0,
                ];
            }
            if (!isset($byProject[$pid]['employees'][$eid])) {
                $byProject[$pid]['employees'][$eid] = [
                    'first_name' => $log->first_name,
                    'last_name' => $log->last_name,
                    'dailyHours' => [],
                    'weeklyTotals' => [],
                    'monthTotal' => 0.0,
                ];
            }

            $byProject[$pid]['employees'][$eid]['dailyHours'][$day] =
                ($byProject[$pid]['employees'][$eid]['dailyHours'][$day] ?? 0.0) + $h;
            $byProject[$pid]['employees'][$eid]['monthTotal'] += $h;
            $byProject[$pid]['dailyTotals'][$day] =
                ($byProject[$pid]['dailyTotals'][$day] ?? 0.0) + $h;
            $byProject[$pid]['monthTotal'] += $h;
        }

        // Compute weekly totals and sort employees
        foreach ($byProject as $pid => &$projData) {
            foreach ($weeks as $week) {
                $wt = 0.0;
                foreach ($week['days'] as $dn) {
                    $wt += $projData['dailyTotals'][$dn] ?? 0.0;
                }
                $projData['weeklyTotals'][$week['key']] = $wt;

                foreach ($projData['employees'] as $eid => &$empData) {
                    $ewt = 0.0;
                    foreach ($week['days'] as $dn) {
                        $ewt += $empData['dailyHours'][$dn] ?? 0.0;
                    }
                    $empData['weeklyTotals'][$week['key']] = $ewt;
                }
                unset($empData);
            }
            uasort($projData['employees'], fn ($a, $b) => strcmp(
                mb_strtolower($a['last_name'] . $a['first_name']),
                mb_strtolower($b['last_name'] . $b['first_name'])
            ));
        }
        unset($projData);

        // Sort projects by name
        uasort($byProject, fn ($a, $b) => strcmp(mb_strtolower($a['name']), mb_strtolower($b['name'])));

        // Grand totals across all projects
        $grandDailyTotals = [];
        $grandWeeklyTotals = [];
        $grandMonthTotal = 0.0;

        foreach ($byProject as $projData) {
            foreach ($projData['dailyTotals'] as $dn => $h) {
                $grandDailyTotals[$dn] = ($grandDailyTotals[$dn] ?? 0.0) + $h;
            }
            $grandMonthTotal += $projData['monthTotal'];
        }
        foreach ($weeks as $week) {
            $wt = 0.0;
            foreach ($week['days'] as $dn) {
                $wt += $grandDailyTotals[$dn] ?? 0.0;
            }
            $grandWeeklyTotals[$week['key']] = $wt;
        }

        // Build chart data for JavaScript
        $chartProjectLabels = [];
        $chartProjectTotals = [];
        $chartWeekLabels = array_map(
            fn ($w) => $w['label'] . ' (' . $w['dateRange'] . ')',
            $weeks
        );
        $chartWeekDatasets = [];
        $chartDayLabels = array_map(
            fn ($d) => $d['date']->format('d') . ' ' . $d['date']->locale('pl')->translatedFormat('D'),
            $days
        );
        $chartDayDatasets = [];

        foreach ($byProject as $projData) {
            $chartProjectLabels[] = $projData['name'];
            $chartProjectTotals[] = round($projData['monthTotal'], 2);

            $wData = [];
            foreach ($weeks as $week) {
                $wData[] = round($projData['weeklyTotals'][$week['key']] ?? 0.0, 2);
            }
            $chartWeekDatasets[] = ['label' => $projData['name'], 'data' => $wData];

            $dData = [];
            foreach ($days as $d) {
                $dData[] = round($projData['dailyTotals'][$d['number']] ?? 0.0, 2);
            }
            $chartDayDatasets[] = ['label' => $projData['name'], 'data' => $dData];
        }

        $chartData = [
            'projectLabels' => $chartProjectLabels,
            'projectTotals' => $chartProjectTotals,
            'weekLabels' => $chartWeekLabels,
            'weekDatasets' => $chartWeekDatasets,
            'dayLabels' => $chartDayLabels,
            'dayDatasets' => $chartDayDatasets,
        ];

        // CSV export
        if ($format === 'csv') {
            $filename = 'time-logs-analytics-' . $month . '.csv';

            return response()->streamDownload(
                function () use ($byProject, $days, $weeks, $grandDailyTotals, $grandWeeklyTotals, $grandMonthTotal) {
                    $out = fopen('php://output', 'w');
                    fputs($out, "\xEF\xBB\xBF");

                    $hdr = ['Projekt', 'Pracownik'];
                    foreach ($days as $d) {
                        $hdr[] = $d['date']->format('d.m') . ' ' . $d['date']->format('D');
                    }
                    foreach ($weeks as $w) {
                        $hdr[] = 'Σ ' . $w['label'];
                    }
                    $hdr[] = 'Σ Miesiąc';
                    fputcsv($out, $hdr, ';');

                    foreach ($byProject as $projData) {
                        foreach ($projData['employees'] as $empData) {
                            $row = [$projData['name'], $empData['last_name'] . ' ' . $empData['first_name']];
                            foreach ($days as $d) {
                                $h = $empData['dailyHours'][$d['number']] ?? 0.0;
                                $row[] = $h > 0 ? number_format($h, 2, ',', '') : '';
                            }
                            foreach ($weeks as $w) {
                                $wh = $empData['weeklyTotals'][$w['key']] ?? 0.0;
                                $row[] = $wh > 0 ? number_format($wh, 2, ',', '') : '';
                            }
                            $row[] = number_format($empData['monthTotal'], 2, ',', '');
                            fputcsv($out, $row, ';');
                        }

                        $row = [$projData['name'] . ' [SUMA]', '—'];
                        foreach ($days as $d) {
                            $h = $projData['dailyTotals'][$d['number']] ?? 0.0;
                            $row[] = $h > 0 ? number_format($h, 2, ',', '') : '';
                        }
                        foreach ($weeks as $w) {
                            $wh = $projData['weeklyTotals'][$w['key']] ?? 0.0;
                            $row[] = $wh > 0 ? number_format($wh, 2, ',', '') : '';
                        }
                        $row[] = number_format($projData['monthTotal'], 2, ',', '');
                        fputcsv($out, $row, ';');
                    }

                    $row = ['ŁĄCZNIE', '—'];
                    foreach ($days as $d) {
                        $h = $grandDailyTotals[$d['number']] ?? 0.0;
                        $row[] = $h > 0 ? number_format($h, 2, ',', '') : '';
                    }
                    foreach ($weeks as $w) {
                        $wh = $grandWeeklyTotals[$w['key']] ?? 0.0;
                        $row[] = $wh > 0 ? number_format($wh, 2, ',', '') : '';
                    }
                    $row[] = number_format($grandMonthTotal, 2, ',', '');
                    fputcsv($out, $row, ';');

                    fclose($out);
                },
                $filename,
                ['Content-Type' => 'text/csv; charset=UTF-8']
            );
        }

        return view('time-logs.analytics', compact(
            'byProject', 'days', 'weeks', 'currentDate', 'prevMonth', 'nextMonth',
            'monthStart', 'monthEnd', 'availableProjects', 'selectedProjectIds',
            'grandDailyTotals', 'grandWeeklyTotals', 'grandMonthTotal',
            'chartData', 'month'
        ));
    }

    /**
     * Bulk update time logs.
     */
    public function bulkUpdate(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        // Convert form data to entries array format
        $entries = [];
        $formEntries = $request->input('entries', []);

        // Nowy format z `monthly-grid`:
        // entries[assignment_id][date] = hours
        // (cel: ograniczyć liczbę inputów w requestcie i nie przekraczać `max_input_vars`)
        foreach ($formEntries as $assignmentId => $dates) {
            if (!is_array($dates)) {
                continue;
            }

            foreach ($dates as $date => $hours) {
                // hours może przyjść jako:
                // - numeric: 5.75
                // - format UI: H:MM (np. 5:30)
                $parsedHours = null;
                $rawNotEmpty = !($hours === '' || $hours === null);

                if ($rawNotEmpty) {
                    $hoursStr = trim((string) $hours);
                    $hoursStrNormalized = str_replace(',', '.', $hoursStr);

                    // Obsłuż format `H:MM`
                    if (str_contains($hoursStrNormalized, ':')) {
                        [$hStr, $mStr] = array_pad(explode(':', $hoursStrNormalized, 2), 2, null);
                        $hStr = $hStr !== null ? trim((string) $hStr) : null;
                        $mStr = $mStr !== null ? trim((string) $mStr) : null;

                        if ($hStr !== null && $mStr !== null && is_numeric($hStr) && is_numeric($mStr)) {
                            $h = (int) $hStr;
                            $m = (int) $mStr;

                            $totalMinutes = $h * 60 + $m;
                            if ($m < 0 || $m >= 60 || ($totalMinutes % 15) !== 0) {
                                $parsedHours = 'invalid';
                            } else {
                                $parsedHours = $totalMinutes / 60;
                            }
                        } else {
                            $parsedHours = 'invalid';
                        }
                    } else {
                        // Obsłuż format liczbowy `5.75` (także z przecinkiem: `5,75`)
                        if (is_numeric($hoursStrNormalized)) {
                            $hoursFloat = (float) $hoursStrNormalized;
                            $totalMinutes = (int) round($hoursFloat * 60);

                            if (($totalMinutes % 15) !== 0) {
                                $parsedHours = 'invalid';
                            } else {
                                $parsedHours = $totalMinutes / 60;
                            }
                        } else {
                            $parsedHours = 'invalid';
                        }
                    }
                }

                $entries[] = [
                    'assignment_id' => (int) $assignmentId,
                    'date' => $date,
                    // '' => null (żeby przechodziło `nullable|numeric` w walidacji)
                    'hours' => $parsedHours,
                ];
            }
        }

        try {
            validator([
                'entries' => $entries
            ], [
                'entries' => 'required|array',
                'entries.*.assignment_id' => 'required|integer|exists:project_assignments,id',
                'entries.*.date' => 'required|date|before_or_equal:today',
                'entries.*.hours' => 'nullable|numeric|min:0|max:24',
            ], [
                'entries.*.hours.numeric' => 'Dozwolone są jedynie następujące części godzin: 15, 30 lub 45 minut (kroki co 15 minut).',
                'entries.*.date.before_or_equal' => 'Nie można wpisywać godzin na dzień większy niż dziś.',
            ])->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Błąd walidacji: ' . implode(', ', array_merge(...array_values($e->errors()))),
                    'errors' => $e->errors(),
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        }

        // Middleware już sprawdził uprawnienia - można aktualizować wszystkie
        $results = $this->timeLogService->bulkUpdateTimeLogs($entries);

        $message = 'Zaktualizowano: ' . $results['created'] . ' utworzono, ' . $results['updated'] . ' zaktualizowano, ' . $results['deleted'] . ' usunięto.';
        
        if (count($results['errors']) > 0) {
            $errorMessages = [];
            foreach ($results['errors'] as $error) {
                $dateStr = $error['date'] ?? 'nieznana data';
                $errorMessages[] = "Data {$dateStr}: " . ($error['message'] ?? 'Nieznany błąd');
            }
            $message .= ' Błędy (' . count($results['errors']) . '): ' . implode('; ', $errorMessages);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => count($results['errors']) === 0,
                'message' => $message,
                'results' => $results,
            ]);
        }

        if (count($results['errors']) > 0) {
            return redirect()->back()
                ->with('error', $message)
                ->with('bulkErrors', $results['errors'])
                ->withInput();
        }

        return redirect()->back()
            ->with('success', $message);
    }
}
