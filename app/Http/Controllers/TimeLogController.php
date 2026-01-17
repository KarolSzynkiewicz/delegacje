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
        $this->authorize('viewAny', TimeLog::class);
        
        return view('time-logs.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', TimeLog::class);

        $assignments = ProjectAssignment::with('employee', 'project', 'role')
            ->whereIn('status', [AssignmentStatus::ACTIVE, AssignmentStatus::IN_TRANSIT, AssignmentStatus::AT_BASE])
            ->orderBy('start_date', 'desc')
            ->get();
        
        return view('time-logs.create', compact('assignments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTimeLogRequest $request): RedirectResponse
    {
        $this->authorize('create', TimeLog::class);

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
        $this->authorize('view', $timeLog);

        $timeLog->load('projectAssignment.employee', 'projectAssignment.project', 'projectAssignment.role');
        
        return view('time-logs.show', compact('timeLog'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TimeLog $timeLog): View
    {
        $this->authorize('update', $timeLog);

        $assignments = ProjectAssignment::with('employee', 'project', 'role')
            ->whereIn('status', [AssignmentStatus::ACTIVE, AssignmentStatus::IN_TRANSIT, AssignmentStatus::AT_BASE])
            ->orderBy('start_date', 'desc')
            ->get();
        
        return view('time-logs.edit', compact('timeLog', 'assignments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTimeLogRequest $request, TimeLog $timeLog): RedirectResponse
    {
        $this->authorize('update', $timeLog);

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
        $this->authorize('delete', $timeLog);

        $timeLog->delete();

        return redirect()
            ->route('time-logs.index')
            ->with('success', 'Zapis czasu pracy został usunięty.');
    }

    /**
     * Display time logs for a specific project assignment.
     */
    public function byAssignment(ProjectAssignment $assignment): View
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

        $month = $request->query('month', Carbon::now()->format('Y-m'));
        $data = $this->timeLogService->getMonthlyGridData($month);

        return view('time-logs.monthly-grid', $data);
    }

    /**
     * Bulk update time logs.
     */
    public function bulkUpdate(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('create', TimeLog::class);

        // Convert form data to entries array format
        $entries = [];
        $formEntries = $request->input('entries', []);
        
        foreach ($formEntries as $entry) {
            if (isset($entry['assignment_id']) && isset($entry['date'])) {
                $entries[] = [
                    'assignment_id' => $entry['assignment_id'],
                    'date' => $entry['date'],
                    'hours' => $entry['hours'] ?? 0,
                ];
            }
        }

        try {
            validator([
                'entries' => $entries
            ], [
                'entries' => 'required|array',
                'entries.*.assignment_id' => 'required|integer|exists:project_assignments,id',
                'entries.*.date' => 'required|date',
                'entries.*.hours' => 'nullable|numeric|min:0|max:24',
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
