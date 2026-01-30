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
    public function create(Request $request): View
    {
        $projectId = $request->query('project_id');
        $project = null;
        
        if ($projectId) {
            $project = Project::findOrFail($projectId);
        }
        
        // Jeśli nie ma projektu, pobierz listę projektów do wyboru
        $projects = $project ? collect([$project]) : Project::orderBy('name')->get();
        
        $startDate = $request->query('start_date') ?? $request->query('date_from');
        $endDate = $request->query('end_date') ?? $request->query('date_to');
        $employeeId = $request->query('employee_id');
        $roleId = $request->query('role_id');
        
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
        
        $employees = $this->assignmentService->getEmployeesWithAvailabilityStatus(
            $startDateCarbon, 
            $endDateCarbon, 
            null, // excludeAssignmentId
            $roleId ? (int)$roleId : null, // roleId
            $projectId ? (int)$projectId : null // projectId
        );
        $roles = Role::orderBy("name")->get();
        
        // If employee_id is provided, set it in old input for pre-selection
        if ($employeeId) {
            $request->merge(['employee_id' => $employeeId]);
            // Also set it in session for old() helper
            session()->flash('_old_input.employee_id', $employeeId);
        }
        
        // If project_id is provided, set it in old input for pre-selection
        if ($projectId) {
            session()->flash('_old_input.project_id', $projectId);
        }
        
        // If role_id is provided, set it in old input for pre-selection
        if ($roleId) {
            session()->flash('_old_input.role_id', $roleId);
        }
        
        return view("assignments.create", compact("project", "projects", "employees", "roles", "startDate", "endDate", "isDateInPast", "employeeId", "roleId"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectAssignmentRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            
            $projectId = $validated['project_id'] ?? $request->input('project_id');
            if (!$projectId) {
                return redirect()->route('projects.index')
                    ->with('error', 'Musisz wybrać projekt');
            }
            
            $project = Project::findOrFail($projectId);
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

            // Redirect to weekly overview with the assignment's start date
            $startDateParam = $startDate->format('Y-m-d');
            return redirect()
                ->route("weekly-overview.index", ['start_date' => $startDateParam])
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
        $employees = $this->assignmentService->getEmployeesWithAvailabilityStatus(
            $startDate, 
            $endDate, 
            $assignment->id, // excludeAssignmentId
            $assignment->role_id, // roleId
            $assignment->project_id // projectId
        );
        
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
                ->route("projects.show", $assignment->project_id)
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
        $reasons = [];
        
        // Sprawdź czy są zaksiegowane godziny dla tego przypisania
        $hasTimeLogs = \App\Models\TimeLog::where('project_assignment_id', $assignment->id)->exists();
        if ($hasTimeLogs) {
            $reasons[] = "są już zaksiegowane godziny pracy dla tego przypisania";
        }
        
        // Sprawdź czy przypisanie jest powiązane z zjazdem (LogisticsEventParticipant)
        $hasLogisticsEvents = \App\Models\LogisticsEventParticipant::where('assignment_type', 'project_assignment')
            ->where('assignment_id', $assignment->id)
            ->exists();
        if ($hasLogisticsEvents) {
            $reasons[] = "przypisanie jest powiązane z zjazdem lub wyjazdem";
        }
        
        // Sprawdź czy są powiązane problemy z wyposażeniem (EquipmentIssue)
        $hasEquipmentIssues = \App\Models\EquipmentIssue::where('project_assignment_id', $assignment->id)->exists();
        if ($hasEquipmentIssues) {
            $reasons[] = "są powiązane problemy z wyposażeniem";
        }
        
        if (!empty($reasons)) {
            $message = "Nie można usunąć przypisania, ponieważ " . implode(", ", $reasons) . ".";
            if ($hasTimeLogs) {
                $message .= " Najpierw usuń lub edytuj wpisy czasu pracy.";
            }
            if ($hasLogisticsEvents) {
                $message .= " Najpierw usuń lub edytuj powiązane zjazdy/wyjazdy.";
            }
            
            return redirect()
                ->route("projects.show", $assignment->project_id)
                ->with("error", $message);
        }
        
        $projectId = $assignment->project_id;
        $assignment->delete();

        return redirect()
            ->route("projects.show", $projectId)
            ->with("success", "Przypisanie zostało usunięte.");
    }
}
