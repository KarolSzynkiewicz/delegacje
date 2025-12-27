<?php

namespace App\Http\Controllers;

use App\Models\ProjectAssignment;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Role;
use Illuminate\Http\Request;

class ProjectAssignmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $assignments = ProjectAssignment::with('employee', 'project', 'role')
            ->orderBy('start_date', 'desc')
            ->paginate(20);
        
        return view('assignments.index', compact('assignments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::with('role')->get();
        $projects = Project::where('status', 'active')->get();
        $roles = Role::all();
        
        return view('assignments.create', compact('employees', 'projects', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'employee_id' => 'required|exists:employees,id',
            'role_id' => 'required|exists:roles,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:pending,active,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $assignment = ProjectAssignment::create($validated);

        return redirect()
            ->route('assignments.show', $assignment)
            ->with('success', 'Przypisanie pracownika do projektu zostało utworzone.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ProjectAssignment $assignment)
    {
        $assignment->load('employee', 'project', 'role', 'timeLogs');
        
        return view('assignments.show', compact('assignment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProjectAssignment $assignment)
    {
        $employees = Employee::with('role')->get();
        $projects = Project::all();
        $roles = Role::all();
        
        return view('assignments.edit', compact('assignment', 'employees', 'projects', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProjectAssignment $assignment)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'employee_id' => 'required|exists:employees,id',
            'role_id' => 'required|exists:roles,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:pending,active,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $assignment->update($validated);

        return redirect()
            ->route('assignments.show', $assignment)
            ->with('success', 'Przypisanie pracownika zostało zaktualizowane.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectAssignment $assignment)
    {
        $assignment->delete();

        return redirect()
            ->route('assignments.index')
            ->with('success', 'Przypisanie pracownika zostało usunięte.');
    }

    /**
     * Display assignments for a specific project.
     */
    public function byProject(Project $project)
    {
        $assignments = $project->assignments()
            ->with('employee', 'role')
            ->orderBy('start_date', 'desc')
            ->get();
        
        return view('assignments.by-project', compact('project', 'assignments'));
    }

    /**
     * Display assignments for a specific employee.
     */
    public function byEmployee(Employee $employee)
    {
        $assignments = $employee->assignments()
            ->with('project', 'role')
            ->orderBy('start_date', 'desc')
            ->get();
        
        return view('assignments.by-employee', compact('employee', 'assignments'));
    }

    /**
     * Check employee availability in a date range.
     */
    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $isAvailable = $employee->isAvailableInDateRange(
            $validated['start_date'],
            $validated['end_date']
        );

        return response()->json([
            'available' => $isAvailable,
            'employee' => $employee->full_name,
        ]);
    }
}
