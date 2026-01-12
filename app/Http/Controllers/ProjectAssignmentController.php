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

class ProjectAssignmentController extends Controller
{
    public function __construct(
        protected ProjectAssignmentService $assignmentService
    ) {}
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
        $startDate = $request->query('date_from');
        $endDate = $request->query('date_to');
        
        // Sprawdź czy daty są w przeszłości
        $isDateInPast = false;
        if ($startDate) {
            $isDateInPast = \Carbon\Carbon::parse($startDate)->startOfDay()->isPast();
        }
        if ($endDate && !$isDateInPast) {
            $isDateInPast = \Carbon\Carbon::parse($endDate)->startOfDay()->isPast();
        }
        
        $employees = $this->assignmentService->getEmployeesWithAvailabilityStatus($startDate, $endDate);
        $roles = Role::orderBy("name")->get();
        
        return view("assignments.create", compact("project", "employees", "roles", "startDate", "endDate", "isDateInPast"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectAssignmentRequest $request, Project $project)
    {
        try {
            $assignment = $this->assignmentService->createAssignment(
                $project,
                $request->validated()
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
        
        // Pobierz daty z przypisania do sprawdzenia dostępności
        $startDate = $assignment->start_date->format('Y-m-d');
        $endDate = $assignment->end_date ? $assignment->end_date->format('Y-m-d') : $startDate;
        
        // Sprawdź dostępność pracowników dla dat przypisania (wykluczając aktualnie edytowane przypisanie)
        $employees = $this->assignmentService->getEmployeesWithAvailabilityStatus($startDate, $endDate, $assignment->id);
        
        $roles = Role::orderBy("name")->get();
        
        return view("assignments.edit", compact("assignment", "projects", "employees", "roles", "startDate", "endDate"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectAssignmentRequest $request, ProjectAssignment $assignment)
    {
        try {
            $this->assignmentService->updateAssignment(
                $assignment,
                $request->validated()
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
    public function destroy(ProjectAssignment $assignment)
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
