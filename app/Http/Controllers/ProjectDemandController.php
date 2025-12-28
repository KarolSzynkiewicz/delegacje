<?php

namespace App\Http\Controllers;

use App\Models\ProjectDemand;
use App\Models\ProjectDemandRole;
use App\Models\Project;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectDemandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Project $project)
    {
        $demands = $project->demands()
            ->with("demandRoles.role")
            ->orderBy("created_at", "desc")
            ->paginate(20);
        
        return view("demands.index", compact("project", "demands"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Project $project)
    {
        $roles = Role::all();
        return view("demands.create", compact("project", "roles"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            "required_workers_count" => "required|integer|min:0",
            "start_date" => "required|date",
            "end_date" => "nullable|date|after_or_equal:start_date",
            "notes" => "nullable|string",
            "roles" => "required|array|min:1",
            "roles.*.role_id" => "required|exists:roles,id",
            "roles.*.required_count" => "required|integer|min:1",
        ]);

        DB::beginTransaction();
        try {
            $demand = $project->demands()->create([
                "required_workers_count" => $validated["required_workers_count"],
                "start_date" => $validated["start_date"],
                "end_date" => $validated["end_date"],
                "notes" => $validated["notes"],
            ]);

            foreach ($validated["roles"] as $roleData) {
                $demand->demandRoles()->create([
                    "role_id" => $roleData["role_id"],
                    "required_count" => $roleData["required_count"],
                ]);
            }

            DB::commit();

            return redirect()
                ->route("projects.demands.index", $project)
                ->with("success", "Zapotrzebowanie projektu zostało utworzone.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(["error" => "Wystąpił błąd: " . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ProjectDemand $demand)
    {
        $demand->load("project", "demandRoles.role");
        return view("demands.show", compact("demand"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProjectDemand $demand)
    {
        $demand->load("demandRoles");
        $roles = Role::all();
        return view("demands.edit", compact("demand", "roles"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProjectDemand $demand)
    {
        $validated = $request->validate([
            "required_workers_count" => "required|integer|min:0",
            "start_date" => "required|date",
            "end_date" => "nullable|date|after_or_equal:start_date",
            "notes" => "nullable|string",
            "roles" => "required|array|min:1",
            "roles.*.role_id" => "required|exists:roles,id",
            "roles.*.required_count" => "required|integer|min:1",
        ]);

        DB::beginTransaction();
        try {
            $demand->update([
                "required_workers_count" => $validated["required_workers_count"],
                "start_date" => $validated["start_date"],
                "end_date" => $validated["end_date"],
                "notes" => $validated["notes"],
            ]);

            $demand->demandRoles()->delete();
            foreach ($validated["roles"] as $roleData) {
                $demand->demandRoles()->create([
                    "role_id" => $roleData["role_id"],
                    "required_count" => $roleData["required_count"],
                ]);
            }

            DB::commit();

            return redirect()
                ->route("projects.demands.index", $demand->project_id)
                ->with("success", "Zapotrzebowanie zostało zaktualizowane.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(["error" => "Wystąpił błąd: " . $e->getMessage()]);
        }
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
