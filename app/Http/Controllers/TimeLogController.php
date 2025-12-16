<?php

namespace App\Http\Controllers;

use App\Models\TimeLog;
use App\Models\Delegation;
use Illuminate\Http\Request;

class TimeLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $timeLogs = TimeLog::with('delegation')->get();
        return view('time_logs.index', compact('timeLogs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $delegations = Delegation::where('status', '!=', 'cancelled')->get();
        return view('time_logs.create', compact('delegations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'delegation_id' => 'required|exists:delegations,id',
            'start_time' => 'required|date_time',
            'end_time' => 'nullable|date_time|after_or_equal:start_time',
            'hours_worked' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        TimeLog::create($validated);

        return redirect()->route('time_logs.index')->with('success', 'Zapis czasu pracy został dodany.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TimeLog $timeLog)
    {
        $timeLog->load('delegation');
        return view('time_logs.show', compact('timeLog'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TimeLog $timeLog)
    {
        $delegations = Delegation::where('status', '!=', 'cancelled')->get();
        return view('time_logs.edit', compact('timeLog', 'delegations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TimeLog $timeLog)
    {
        $validated = $request->validate([
            'delegation_id' => 'required|exists:delegations,id',
            'start_time' => 'required|date_time',
            'end_time' => 'nullable|date_time|after_or_equal:start_time',
            'hours_worked' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $timeLog->update($validated);

        return redirect()->route('time_logs.index')->with('success', 'Zapis czasu pracy został zaktualizowany.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TimeLog $timeLog)
    {
        $timeLog->delete();

        return redirect()->route('time_logs.index')->with('success', 'Zapis czasu pracy został usunięty.');
    }
}
