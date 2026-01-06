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
        
        return view("demands.create", compact("project", "roles", "dateFrom", "dateTo"));
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
        return view("demands.edit", compact("demand", "roles"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectDemandRequest $request, ProjectDemand $demand)
    {
        $demand->update($request->validated());

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
