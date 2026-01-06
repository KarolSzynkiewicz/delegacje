<?php

namespace App\Http\Controllers;

use App\Models\ProjectAssignment;
use App\Models\Project;
use App\Models\Employee;
use App\Models\Role;
use App\Rules\EmployeeHasRole;
use Illuminate\Http\Request;

class ProjectAssignmentController extends Controller
{
    /**
     * Display all assignments (global view).
     */
    public function all()
    {
        $assignments = ProjectAssignment::with("employee", "project", "role")
            ->orderBy("start_date", "desc")
            ->paginate(20);
        
        return view("assignments.index", compact("assignments"));
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Project $project)
    {
        $assignments = $project->assignments()
            ->with("employee", "role")
            ->orderBy("start_date", "desc")
            ->paginate(20);
        
        return view("assignments.index", compact("project", "assignments"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Project $project, Request $request)
    {
        // Get dates from query parameters if provided
        $startDate = $request->query('date_from');
        $endDate = $request->query('date_to');
        
        // Get all employees with their availability status
        $allEmployees = Employee::with(["roles", "employeeDocuments.document"])->orderBy("last_name")->get();
        
        // If dates are provided, add availability status to each employee
        if ($startDate && $endDate) {
            $employees = $allEmployees->map(function ($employee) use ($startDate, $endDate) {
                $status = $employee->getAvailabilityStatus($startDate, $endDate);
                $employee->availability_status = $status;
                return $employee;
            });
        } else {
            // If no dates, all employees are available
            $employees = $allEmployees->map(function ($employee) {
                $employee->availability_status = ['available' => true, 'reasons' => []];
                return $employee;
            });
        }
        
        $roles = Role::orderBy("name")->get();
        
        return view("assignments.create", compact("project", "employees", "roles", "startDate", "endDate"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            "employee_id" => "required|exists:employees,id",
            "role_id" => [
                "required",
                "exists:roles,id",
                new EmployeeHasRole($request->input('employee_id'))
            ],
            "start_date" => "required|date",
            "end_date" => "nullable|date|after_or_equal:start_date",
            "status" => "required|in:pending,active,completed,cancelled",
            "notes" => "nullable|string",
        ]);

        // Check employee availability
        $employee = Employee::findOrFail($validated["employee_id"]);
        $endDate = $validated["end_date"] ?? now()->addYears(10);
        
        // Sprawdź czy pracownik ma aktywną rotację pokrywającą CAŁY okres przypisania
        if (!$employee->hasActiveRotationInDateRange($validated["start_date"], $endDate)) {
            return back()
                ->withInput()
                ->withErrors([
                    "employee_id" => "Pracownik nie ma aktywnej rotacji pokrywającej cały okres przypisania (od {$validated['start_date']} do {$endDate}). Rotacja musi pokrywać cały okres przypisania."
                ]);
        }
        
        // Sprawdź czy pracownik nie ma konfliktujących przypisań
        if (!$employee->isAvailableInDateRange($validated["start_date"], $endDate)) {
            return back()
                ->withInput()
                ->withErrors(["employee_id" => "Pracownik jest już przypisany do innego projektu w tym okresie."]);
        }

        $assignment = $project->assignments()->create($validated);

        return redirect()
            ->route("projects.assignments.index", $project)
            ->with("success", "Pracownik został przypisany do projektu.");
    }

    /**
     * Display the specified resource.
     */
    public function show(ProjectAssignment $assignment)
    {
        $assignment->load("employee", "project", "role");
        
        return view("assignments.show", compact("assignment"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProjectAssignment $assignment)
    {
        $projects = Project::orderBy("name")->get();
        $employees = Employee::with("roles")->orderBy("last_name")->get();
        $roles = Role::orderBy("name")->get();
        
        return view("assignments.edit", compact("assignment", "projects", "employees", "roles"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProjectAssignment $assignment)
    {
        try {
            $validated = $request->validate([
                "project_id" => "required|exists:projects,id",
                "employee_id" => "required|exists:employees,id",
                "role_id" => [
                    "required",
                    "exists:roles,id",
                    new EmployeeHasRole($request->input('employee_id'))
                ],
                "start_date" => "required|date",
                "end_date" => "nullable|date|after_or_equal:start_date",
                "status" => "required|in:pending,active,completed,cancelled",
                "notes" => "nullable|string",
            ]);

            // Check employee availability
            $employee = Employee::findOrFail($validated["employee_id"]);
            $endDate = $validated["end_date"] ?? now()->addYears(10);
            
            // Sprawdź czy pracownik ma aktywną rotację pokrywającą CAŁY okres przypisania
            if (!$employee->hasActiveRotationInDateRange($validated["start_date"], $endDate)) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "employee_id" => "Pracownik nie ma aktywnej rotacji pokrywającej cały okres przypisania (od {$validated['start_date']} do {$endDate}). Rotacja musi pokrywać cały okres przypisania."
                    ]);
            }
            
            // Sprawdź czy pracownik nie ma konfliktujących przypisań (wykluczając aktualne przypisanie)
            $hasConflictingAssignments = $employee->assignments()
                ->where('status', 'active')
                ->where('id', '!=', $assignment->id)
                ->where(function ($query) use ($validated, $endDate) {
                    $query->whereBetween('start_date', [$validated["start_date"], $endDate])
                        ->orWhereBetween('end_date', [$validated["start_date"], $endDate])
                        ->orWhere(function ($q) use ($validated, $endDate) {
                            $q->where('start_date', '<=', $validated["start_date"])
                              ->where('end_date', '>=', $endDate);
                        });
                })
                ->exists();
            
            if ($hasConflictingAssignments) {
                return back()
                    ->withInput()
                    ->withErrors(["employee_id" => "Pracownik jest już przypisany do innego projektu w tym okresie."]);
            }

            $assignment->update($validated);

            return redirect()
                ->route("projects.assignments.index", $assignment->project_id)
                ->with("success", "Przypisanie zostało zaktualizowane.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Przekieruj z powrotem do formularza edycji z błędami i danymi wejściowymi
            return redirect()
                ->route("assignments.edit", $assignment)
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectAssignment $assignment)
    {
        $projectId = $assignment->project_id;
        $assignment->delete();

        return redirect()
            ->route("projects.assignments.index", $projectId)
            ->with("success", "Przypisanie zostało usunięte.");
    }
}
