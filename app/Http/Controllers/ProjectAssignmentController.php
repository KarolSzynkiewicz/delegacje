<?php

namespace App\Http\Controllers;

use App\Models\ProjectAssignment;
use App\Models\Project;
use App\Models\Role;
use App\Models\Employee;
use App\Services\ProjectAssignmentService;
use App\Http\Requests\StoreProjectAssignmentRequest;
use App\Http\Requests\UpdateProjectAssignmentRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProjectAssignmentController extends Controller
{
    public function __construct(
        protected ProjectAssignmentService $assignmentService
    ) {}
    /**
     * Display all assignments (global view).
     */
    public function all(): View
    {
        $assignments = ProjectAssignment::with("employee", "project", "role")
            ->orderBy("start_date", "desc")
            ->paginate(20);
        
        return view("assignments.index", compact("assignments"));
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Project $project): View
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
    public function create(Project $project, Request $request): View
    {
        $startDate = $request->query('start_date') ?? $request->query('date_from');
        $endDate = $request->query('end_date') ?? $request->query('date_to');
        
        // Sprawdź czy daty są w przeszłości
        $isDateInPast = false;
        $startDateCarbon = null;
        $endDateCarbon = null;
        
        if ($startDate) {
            $startDateCarbon = \Carbon\Carbon::parse($startDate);
            $isDateInPast = $startDateCarbon->startOfDay()->isPast();
        }
        if ($endDate && !$isDateInPast) {
            $endDateCarbon = \Carbon\Carbon::parse($endDate);
            $isDateInPast = $endDateCarbon->startOfDay()->isPast();
        }
        
        $employees = $this->assignmentService->getEmployeesWithAvailabilityStatus($startDateCarbon, $endDateCarbon);
        $roles = Role::orderBy("name")->get();
        
        return view("assignments.create", compact("project", "employees", "roles", "startDate", "endDate", "isDateInPast"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectAssignmentRequest $request, Project $project): RedirectResponse
    {
        try {
            $validated = $request->validated();
            
            $employee = Employee::findOrFail($validated['employee_id']);
            $role = Role::findOrFail($validated['role_id']);
            $startDate = \Carbon\Carbon::parse($validated['start_date']);
            $endDate = isset($validated['end_date']) ? \Carbon\Carbon::parse($validated['end_date']) : null;
            
            $assignment = $this->assignmentService->createAssignment(
                $project,
                $employee,
                $role,
                $startDate,
                $endDate,
                $validated['notes'] ?? null
            );

            return redirect()
                ->route("projects.assignments.index", $project)
                ->with("success", "Pracownik został przypisany do projektu.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withInput()
                ->withErrors($e->errors());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ProjectAssignment $assignment): View
    {
        $assignment->load("employee", "project", "role");
        
        return view("assignments.show", compact("assignment"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProjectAssignment $assignment): View
    {
        $projects = Project::orderBy("name")->get();
        
        // Pobierz daty z przypisania do sprawdzenia dostępności
        $startDate = $assignment->start_date;
        $endDate = $assignment->end_date;
        
        // Sprawdź dostępność pracowników dla dat przypisania (wykluczając aktualnie edytowane przypisanie)
        $employees = $this->assignmentService->getEmployeesWithAvailabilityStatus($startDate, $endDate, $assignment->id);
        
        $roles = Role::orderBy("name")->get();
        
        return view("assignments.edit", compact("assignment", "projects", "employees", "roles", "startDate", "endDate"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectAssignmentRequest $request, ProjectAssignment $assignment): RedirectResponse
    {
        try {
            $validated = $request->validated();
            
            $project = Project::findOrFail($validated['project_id']);
            $employee = Employee::findOrFail($validated['employee_id']);
            $role = Role::findOrFail($validated['role_id']);
            $startDate = \Carbon\Carbon::parse($validated['start_date']);
            $endDate = isset($validated['end_date']) ? \Carbon\Carbon::parse($validated['end_date']) : null;
            
            $this->assignmentService->updateAssignment(
                $assignment,
                $project,
                $employee,
                $role,
                $startDate,
                $endDate,
                $validated['notes'] ?? null
            );

            return redirect()
                ->route("projects.assignments.index", $assignment->project_id)
                ->with("success", "Przypisanie zostało zaktualizowane.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->route("assignments.edit", $assignment)
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectAssignment $assignment): RedirectResponse
    {
        // Sprawdź czy są zaksiegowane godziny dla tego przypisania
        $hasTimeLogs = \App\Models\TimeLog::where('project_assignment_id', $assignment->id)->exists();
        
        if ($hasTimeLogs) {
            return redirect()
                ->route("projects.assignments.index", $assignment->project_id)
                ->with("error", "Nie można usunąć przypisania, ponieważ są już zaksiegowane godziny pracy dla tego przypisania. Najpierw usuń lub edytuj wpisy czasu pracy.");
        }
        
        $projectId = $assignment->project_id;
        $assignment->delete();

        return redirect()
            ->route("projects.assignments.index", $projectId)
            ->with("success", "Przypisanie zostało usunięte.");
    }
}
