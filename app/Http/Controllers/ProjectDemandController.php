<?php

namespace App\Http\Controllers;

use App\Models\ProjectDemand;
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
            ->with("role")
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
            "demands" => "required|array|min:1",
            "demands.*.role_id" => "required|exists:roles,id",
            "demands.*.required_count" => "required|integer|min:1",
            "demands.*.date_from" => "required|date",
            "demands.*.date_to" => "nullable|date",
            "demands.*.notes" => "nullable|string",
        ]);

        // Walidacja date_to >= date_from dla każdego zapotrzebowania
        foreach ($validated["demands"] as $key => $demand) {
            if (isset($demand["date_to"]) && $demand["date_to"] < $demand["date_from"]) {
                return back()
                    ->withInput()
                    ->withErrors(["demands.{$key}.date_to" => "Data zakończenia musi być późniejsza lub równa dacie rozpoczęcia."]);
            }
        }

        DB::beginTransaction();
        try {
            foreach ($validated["demands"] as $demandData) {
                $project->demands()->create([
                    "role_id" => $demandData["role_id"],
                    "required_count" => $demandData["required_count"],
                    "date_from" => $demandData["date_from"],
                    "date_to" => $demandData["date_to"] ?? null,
                    "notes" => $demandData["notes"] ?? null,
                ]);
            }

            DB::commit();

            return redirect()
                ->route("projects.demands.index", $project)
                ->with("success", "Zapotrzebowania projektu zostały utworzone.");
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
    public function update(Request $request, ProjectDemand $demand)
    {
        $validated = $request->validate([
            "role_id" => "required|exists:roles,id",
            "required_count" => "required|integer|min:1",
            "date_from" => "required|date",
            "date_to" => "nullable|date|after_or_equal:date_from",
            "notes" => "nullable|string",
        ]);

        $demand->update($validated);

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
