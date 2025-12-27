<?php

namespace App\Http\Controllers;

use App\Models\TimeLog;
use App\Models\ProjectAssignment;
use Illuminate\Http\Request;

class TimeLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $timeLogs = TimeLog::with('projectAssignment.employee', 'projectAssignment.project')
            ->orderBy('start_time', 'desc')
            ->paginate(20);
        
        return view('time_logs.index', compact('timeLogs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $assignments = ProjectAssignment::with('employee', 'project', 'role')
            ->whereIn('status', ['active', 'pending'])
            ->get();
        
        return view('time_logs.create', compact('assignments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_assignment_id' => 'required|exists:project_assignments,id',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
            'hours_worked' => 'nullable|numeric|min:0|max:24',
            'notes' => 'nullable|string',
        ]);

        TimeLog::create($validated);

        return redirect()
            ->route('time_logs.index')
            ->with('success', 'Zapis czasu pracy został dodany.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TimeLog $timeLog)
    {
        $timeLog->load('projectAssignment.employee', 'projectAssignment.project', 'projectAssignment.role');
        
        return view('time_logs.show', compact('timeLog'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TimeLog $timeLog)
    {
        $assignments = ProjectAssignment::with('employee', 'project', 'role')
            ->whereIn('status', ['active', 'pending'])
            ->get();
        
        return view('time_logs.edit', compact('timeLog', 'assignments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TimeLog $timeLog)
    {
        $validated = $request->validate([
            'project_assignment_id' => 'required|exists:project_assignments,id',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
            'hours_worked' => 'nullable|numeric|min:0|max:24',
            'notes' => 'nullable|string',
        ]);

        $timeLog->update($validated);

        return redirect()
            ->route('time_logs.index')
            ->with('success', 'Zapis czasu pracy został zaktualizowany.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TimeLog $timeLog)
    {
        $timeLog->delete();

        return redirect()
            ->route('time_logs.index')
            ->with('success', 'Zapis czasu pracy został usunięty.');
    }

    /**
     * Display time logs for a specific project assignment.
     */
    public function byAssignment(ProjectAssignment $assignment)
    {
        $timeLogs = $assignment->timeLogs()
            ->orderBy('start_time', 'desc')
            ->get();
        
        return view('time_logs.by-assignment', compact('assignment', 'timeLogs'));
    }
}
