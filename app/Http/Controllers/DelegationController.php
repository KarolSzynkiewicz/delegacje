<?php

namespace App\Http\Controllers;

use App\Models\Delegation;
use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;

class DelegationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $delegations = Delegation::with('employee', 'project')->get();
        return view('delegations.index', compact('delegations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = User::where('role', 'employee')->get();
        $projects = Project::all();
        return view('delegations.create', compact('employees', 'projects'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'project_id' => 'required|exists:projects,id',
            'start_time' => 'required|date_time',
            'end_time' => 'nullable|date_time|after_or_equal:start_time',
            'status' => 'required|in:pending,active,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        Delegation::create($validated);

        return redirect()->route('delegations.index')->with('success', 'Delegacja została dodana.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Delegation $delegation)
    {
        $delegation->load('employee', 'project', 'timeLogs');
        return view('delegations.show', compact('delegation'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Delegation $delegation)
    {
        $employees = User::where('role', 'employee')->get();
        $projects = Project::all();
        return view('delegations.edit', compact('delegation', 'employees', 'projects'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Delegation $delegation)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'project_id' => 'required|exists:projects,id',
            'start_time' => 'required|date_time',
            'end_time' => 'nullable|date_time|after_or_equal:start_time',
            'status' => 'required|in:pending,active,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $delegation->update($validated);

        return redirect()->route('delegations.index')->with('success', 'Delegacja została zaktualizowana.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Delegation $delegation)
    {
        $delegation->delete();

        return redirect()->route('delegations.index')->with('success', 'Delegacja została usunięta.');
    }
}
