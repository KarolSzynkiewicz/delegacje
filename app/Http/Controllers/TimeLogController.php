<?php

namespace App\Http\Controllers;

use App\Services\TimeLogService;
use App\Models\TimeLog;
use App\Models\ProjectAssignment;
use Illuminate\Http\Request;

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

        $timeLogs = TimeLog::with('projectAssignment.employee', 'projectAssignment.project')
            ->orderBy('start_time', 'desc')
            ->paginate(20);
        
        return view('time-logs.index', compact('timeLogs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', TimeLog::class);

        $assignments = ProjectAssignment::with('employee', 'project', 'role')
            ->whereIn('status', ['active', 'pending'])
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
            ->whereIn('status', ['active', 'pending'])
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
}
