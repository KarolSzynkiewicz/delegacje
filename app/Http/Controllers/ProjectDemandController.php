<?php

namespace App\Http\Controllers;

use App\Models\ProjectDemand;
use App\Models\Project;
use App\Models\Role;
use App\Services\ProjectDemandService;
use App\Http\Requests\StoreProjectDemandRequest;
use App\Http\Requests\UpdateProjectDemandRequest;
use Illuminate\Http\Request;

class ProjectDemandController extends Controller
{
    public function __construct(
        protected ProjectDemandService $demandService
    ) {}
    
    /**
     * Display all demands (global view).
     */
    public function all()
    {
        // Pobierz wszystkie zapotrzebowania z required_count > 0, pogrupowane po projektach
        $demands = ProjectDemand::with("project", "role")
            ->where('required_count', '>', 0)
            ->orderBy("date_from", "asc")
            ->get()
            ->groupBy('project_id');
        
        return view("demands.all", compact("demands"));
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index(Project $project)
    {
        $demands = $project->demands()
            ->with("role")
            ->orderBy("created_at", "desc")
            ->paginate(20);
        
        return view("demands.index", compact("project", "demands"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Project $project, Request $request)
    {
        $roles = Role::all();
        
        // Pobierz daty z query string jeśli są przekazane (z widoku tygodniowego)
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        
        // Sprawdź czy daty są w przeszłości
        $isDateInPast = false;
        if ($dateFrom) {
            $isDateInPast = \Carbon\Carbon::parse($dateFrom)->startOfDay()->isPast();
        }
        if ($dateTo && !$isDateInPast) {
            $isDateInPast = \Carbon\Carbon::parse($dateTo)->startOfDay()->isPast();
        }
        
        // Pobierz istniejące zapotrzebowania dla tego projektu w tym okresie (jeśli daty są podane)
        $existingDemands = collect();
        if ($dateFrom && $dateTo) {
            $existingDemands = $project->demands()
                ->where('date_from', '<=', $dateTo)
                ->where(function ($q) use ($dateFrom) {
                    $q->whereNull('date_to')
                      ->orWhere('date_to', '>=', $dateFrom);
                })
                ->with('role')
                ->get()
                ->keyBy('role_id');
        }
        
        return view("demands.create", compact("project", "roles", "dateFrom", "dateTo", "existingDemands", "isDateInPast"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectDemandRequest $request, Project $project)
    {
        try {
            $this->demandService->createDemands($project, $request->validated());

            return redirect()
                ->route("projects.demands.index", $project)
                ->with("success", "Zapotrzebowania projektu zostały utworzone.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withInput()
                ->withErrors($e->errors());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ProjectDemand $demand)
    {
        $demand->load("project", "role");
        return view("demands.show", compact("demand"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProjectDemand $demand)
    {
        $demand->load("role");
        $roles = Role::all();
        
        // Sprawdź czy data jest w przeszłości
        $isDateInPast = $demand->date_from->startOfDay()->isPast();
        if ($demand->date_to && !$isDateInPast) {
            $isDateInPast = $demand->date_to->startOfDay()->isPast();
        }
        
        return view("demands.edit", compact("demand", "roles", "isDateInPast"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectDemandRequest $request, ProjectDemand $demand)
    {
        $validated = $request->validated();
        
        // Log dla debugowania
        \Log::info('Updating demand', [
            'demand_id' => $demand->id,
            'validated' => $validated,
            'current_demand' => $demand->toArray()
        ]);
        
        // Jeśli required_count = 0, usuń zapotrzebowanie
        if ($validated['required_count'] == 0) {
            $projectId = $demand->project_id;
            $demand->delete();
            
            return redirect()
                ->route("projects.demands.index", $projectId)
                ->with("success", "Zapotrzebowanie zostało usunięte (ilość ustawiona na 0).");
        }
        
        // Sprawdź czy zapotrzebowanie zostało zaktualizowane
        $updated = $demand->update($validated);
        
        \Log::info('Demand update result', [
            'updated' => $updated,
            'demand_after' => $demand->fresh()->toArray()
        ]);

        return redirect()
            ->route("projects.demands.index", $demand->project_id)
            ->with("success", "Zapotrzebowanie zostało zaktualizowane.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectDemand $demand)
    {
        $projectId = $demand->project_id;
        $demand->delete();

        return redirect()
            ->route("projects.demands.index", $projectId)
            ->with("success", "Zapotrzebowanie zostało usunięte.");
    }
}
