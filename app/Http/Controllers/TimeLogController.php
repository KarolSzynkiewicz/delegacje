<?php

namespace App\Http\Controllers;

use App\Services\TimeLogService;
use App\Models\TimeLog;
use App\Models\ProjectAssignment;
use App\Models\Project;
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
    public function index()
    {
        $this->authorize('viewAny', TimeLog::class);
        
        return view('time-logs.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', TimeLog::class);

        $assignments = ProjectAssignment::with('employee', 'project', 'role')
            ->whereIn('status', [AssignmentStatus::ACTIVE, AssignmentStatus::IN_TRANSIT, AssignmentStatus::AT_BASE])
            ->get();
        
        return view('time-logs.create', compact('assignments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_assignment_id' => 'required|exists:project_assignments,id',
            'work_date' => 'required|date',
            'hours_worked' => 'required|numeric|min:0|max:24',
            'notes' => 'nullable|string',
        ]);

        try {
            $assignment = ProjectAssignment::findOrFail($validated['project_assignment_id']);
            $this->timeLogService->createTimeLog($assignment, $validated);

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
    public function show(TimeLog $timeLog)
    {
        $this->authorize('view', $timeLog);

        $timeLog->load('projectAssignment.employee', 'projectAssignment.project', 'projectAssignment.role');
        
        return view('time-logs.show', compact('timeLog'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TimeLog $timeLog)
    {
        $this->authorize('update', $timeLog);

        $assignments = ProjectAssignment::with('employee', 'project', 'role')
            ->whereIn('status', [AssignmentStatus::ACTIVE, AssignmentStatus::IN_TRANSIT, AssignmentStatus::AT_BASE])
            ->get();
        
        return view('time-logs.edit', compact('timeLog', 'assignments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TimeLog $timeLog)
    {
        $this->authorize('update', $timeLog);

        $validated = $request->validate([
            'work_date' => 'required|date',
            'hours_worked' => 'required|numeric|min:0|max:24',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->timeLogService->updateTimeLog($timeLog, $validated);

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
    public function destroy(TimeLog $timeLog)
    {
        $this->authorize('delete', $timeLog);

        $timeLog->delete();

        return redirect()
            ->route('time-logs.index')
            ->with('success', 'Zapis czasu pracy został usunięty.');
    }

    /**
     * Display time logs for a specific project assignment.
     */
    public function byAssignment(ProjectAssignment $assignment)
    {
        $this->authorize('viewAny', TimeLog::class);

        $timeLogs = $assignment->timeLogs()
            ->orderBy('start_time', 'desc')
            ->get();
        
        return view('time-logs.by-assignment', compact('assignment', 'timeLogs'));
    }

    /**
     * Display monthly grid for time logs editing.
     */
    public function monthlyGrid(Request $request): View
    {
        $this->authorize('viewAny', TimeLog::class);

        // Get month from query parameter or use current month
        $month = $request->query('month', Carbon::now()->format('Y-m'));
        $currentDate = Carbon::parse($month . '-01');
        $monthStart = $currentDate->copy()->startOfMonth();
        $monthEnd = $currentDate->copy()->endOfMonth();
        $daysInMonth = $monthStart->daysInMonth;

        // Navigation
        $prevMonth = $currentDate->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentDate->copy()->addMonth()->format('Y-m');

        // Get all projects with their assignments
        $projects = Project::with([
            'assignments.employee',
            'assignments.role',
            'assignments.timeLogs' => function($query) use ($monthStart, $monthEnd) {
                $query->whereBetween('start_time', [$monthStart, $monthEnd->endOfDay()]);
            }
        ])
        ->whereHas('assignments', function($query) use ($monthStart, $monthEnd) {
            $query->where(function($q) use ($monthStart, $monthEnd) {
                $q->where('start_date', '<=', $monthEnd)
                  ->where(function($q2) use ($monthStart) {
                      $q2->whereNull('end_date')
                         ->orWhere('end_date', '>=', $monthStart);
                  });
            })
            ->whereIn('status', [AssignmentStatus::ACTIVE, AssignmentStatus::IN_TRANSIT, AssignmentStatus::AT_BASE]);
        })
        ->orderBy('name')
        ->get();
        
        // Pobierz wszystkie time logs dla tego miesiąca (nawet jeśli przypisanie zostało usunięte)
        $allTimeLogs = \App\Models\TimeLog::whereBetween('start_time', [$monthStart, $monthEnd->endOfDay()])
            ->with(['projectAssignment.project', 'projectAssignment.employee'])
            ->get();
        
        // Stwórz mapę time logs po project_id, employee_id i dniu
        // Używamy project_id i employee_id, bo assignment może nie istnieć
        $timeLogsByProjectEmployee = [];
        foreach ($allTimeLogs as $timeLog) {
            if ($timeLog->projectAssignment) {
                $projectId = $timeLog->projectAssignment->project_id;
                $employeeId = $timeLog->projectAssignment->employee_id;
                $assignmentId = $timeLog->project_assignment_id;
                $day = Carbon::parse($timeLog->start_time)->day;
                
                $key = $projectId . '_' . $employeeId;
                if (!isset($timeLogsByProjectEmployee[$key])) {
                    $timeLogsByProjectEmployee[$key] = [];
                }
                $timeLogsByProjectEmployee[$key][$day] = [
                    'hours' => $timeLog->hours_worked,
                    'time_log_id' => $timeLog->id,
                    'assignment_id' => $assignmentId,
                ];
            }
        }

        // Prepare data structure for view
        $projectsData = [];
        foreach ($projects as $project) {
            $assignmentsData = [];
            
            // Group assignments by employee
            $employeesMap = [];
            foreach ($project->assignments as $assignment) {
                if (!in_array($assignment->status, [AssignmentStatus::ACTIVE, AssignmentStatus::IN_TRANSIT, AssignmentStatus::AT_BASE])) {
                    continue;
                }
                
                $employeeId = $assignment->employee_id;
                if (!isset($employeesMap[$employeeId])) {
                    $employeesMap[$employeeId] = [
                        'employee' => $assignment->employee,
                        'assignments' => [],
                    ];
                }
                $employeesMap[$employeeId]['assignments'][] = $assignment;
            }

            // Convert to array and prepare time logs data
            foreach ($employeesMap as $employeeId => $data) {
                $timeLogsMap = [];
                
                // Pobierz time logs dla tego pracownika w tym projekcie (nawet jeśli przypisanie zostało usunięte)
                $key = $project->id . '_' . $employeeId;
                if (isset($timeLogsByProjectEmployee[$key])) {
                    foreach ($timeLogsByProjectEmployee[$key] as $day => $timeLogData) {
                        $timeLogsMap[$day] = $timeLogData;
                    }
                }

                // Check which days are within assignment period (for partial assignment highlighting)
                $daysInAssignment = [];
                foreach ($data['assignments'] as $assignment) {
                    $assignmentStart = Carbon::parse($assignment->start_date);
                    $assignmentEnd = $assignment->end_date ? Carbon::parse($assignment->end_date) : $monthEnd;
                    
                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $checkDate = $monthStart->copy()->addDays($day - 1);
                        if ($checkDate->between($assignmentStart, $assignmentEnd)) {
                            $daysInAssignment[$day] = true;
                        }
                    }
                }

                $assignmentsData[] = [
                    'employee' => $data['employee'],
                    'assignments' => $data['assignments'],
                    'timeLogs' => $timeLogsMap,
                    'daysInAssignment' => $daysInAssignment,
                ];
            }

            if (!empty($assignmentsData)) {
                $projectsData[] = [
                    'project' => $project,
                    'assignments' => $assignmentsData,
                ];
            }
        }

        // Generate days array
        $days = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $monthStart->copy()->addDays($day - 1);
            $days[] = [
                'number' => $day,
                'date' => $date,
                'isWeekend' => $date->isWeekend(),
            ];
        }

        return view('time-logs.monthly-grid', compact(
            'projectsData',
            'days',
            'currentDate',
            'prevMonth',
            'nextMonth',
            'monthStart',
            'monthEnd'
        ));
    }

    /**
     * Bulk update time logs.
     */
    public function bulkUpdate(Request $request)
    {
        $this->authorize('create', TimeLog::class);

        \Log::info('Bulk update request', ['data' => $request->all()]);

        // Convert form data to entries array format
        $entries = [];
        $formEntries = $request->input('entries', []);
        
        foreach ($formEntries as $key => $entry) {
            if (isset($entry['assignment_id']) && isset($entry['date'])) {
                $entries[] = [
                    'assignment_id' => $entry['assignment_id'],
                    'date' => $entry['date'],
                    'hours' => $entry['hours'] ?? 0,
                ];
            }
        }

        try {
            $validated = validator([
                'entries' => $entries
            ], [
                'entries' => 'required|array',
                'entries.*.assignment_id' => 'required|integer|exists:project_assignments,id',
                'entries.*.date' => 'required|date',
                'entries.*.hours' => 'nullable|numeric|min:0|max:24',
            ])->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error', ['errors' => $e->errors()]);
            
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

        $results = [
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'errors' => [],
        ];

        foreach ($validated['entries'] as $index => $entry) {
            try {
                $assignmentId = (int)$entry['assignment_id'];
                $assignment = ProjectAssignment::findOrFail($assignmentId);
                $date = Carbon::parse($entry['date']);
                $hours = isset($entry['hours']) && $entry['hours'] !== '' && $entry['hours'] !== null ? (float)$entry['hours'] : 0;

                \Log::info("Processing entry #{$index}", [
                    'assignment_id' => $assignmentId,
                    'date' => $date->format('Y-m-d'),
                    'hours' => $hours,
                    'raw_entry' => $entry
                ]);

                // Find existing time log
                $timeLog = TimeLog::where('project_assignment_id', $assignment->id)
                    ->whereDate('start_time', $date)
                    ->first();

                if ($timeLog) {
                    \Log::info("Found existing time log", ['id' => $timeLog->id, 'current_hours' => $timeLog->hours_worked]);
                }

                if ($hours > 0) {
                    if ($timeLog) {
                        // Update existing
                        $oldHours = $timeLog->hours_worked;
                        $this->timeLogService->updateTimeLog($timeLog, [
                            'work_date' => $date->format('Y-m-d'),
                            'hours_worked' => $hours,
                        ]);
                        $timeLog->refresh();
                        $results['updated']++;
                        \Log::info('Updated time log', [
                            'id' => $timeLog->id,
                            'old_hours' => $oldHours,
                            'new_hours' => $timeLog->hours_worked,
                            'verified' => $timeLog->hours_worked == $hours
                        ]);
                    } else {
                        // Create new
                        $newTimeLog = $this->timeLogService->createTimeLog($assignment, [
                            'work_date' => $date->format('Y-m-d'),
                            'hours_worked' => $hours,
                        ]);
                        $results['created']++;
                        \Log::info('Created time log', [
                            'id' => $newTimeLog->id,
                            'hours' => $newTimeLog->hours_worked,
                            'verified' => $newTimeLog->hours_worked == $hours
                        ]);
                    }
                } else {
                    // Delete if hours is 0 or empty
                    if ($timeLog) {
                        $deletedId = $timeLog->id;
                        $timeLog->delete();
                        $results['deleted']++;
                        \Log::info('Deleted time log', ['id' => $deletedId]);
                    } else {
                        \Log::info('Skipping entry - hours is 0 and no existing time log to delete');
                    }
                }
            } catch (\Illuminate\Validation\ValidationException $e) {
                $errorMsg = implode(', ', array_merge(...array_values($e->errors())));
                \Log::error('Validation exception in entry', [
                    'entry' => $entry,
                    'error' => $errorMsg
                ]);
                $results['errors'][] = [
                    'assignment_id' => $entry['assignment_id'] ?? null,
                    'date' => $entry['date'] ?? null,
                    'message' => $errorMsg,
                ];
            } catch (\Exception $e) {
                \Log::error('Exception in entry', [
                    'entry' => $entry,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $results['errors'][] = [
                    'assignment_id' => $entry['assignment_id'] ?? null,
                    'date' => $entry['date'] ?? null,
                    'message' => $e->getMessage(),
                ];
            }
        }

        $message = 'Zaktualizowano: ' . $results['created'] . ' utworzono, ' . $results['updated'] . ' zaktualizowano, ' . $results['deleted'] . ' usunięto.';
        if (count($results['errors']) > 0) {
            $message .= ' Błędy: ' . count($results['errors']);
        }

        \Log::info('Bulk update completed', ['results' => $results]);

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
                ->withInput();
        }

        return redirect()->back()
            ->with('success', $message);
    }
}
