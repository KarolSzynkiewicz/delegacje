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
    public function store(Request $request, Project $project)
    {
        // Walidacja wspólnych dat
        $validated = $request->validate([
            "date_from" => "required|date",
            "date_to" => "nullable|date|after_or_equal:date_from",
            "notes" => "nullable|string",
            "demands" => "required|array",
            "demands.*.role_id" => "required|exists:roles,id",
            "demands.*.required_count" => "required|integer|min:0",
        ], [
            "demands.required" => "Brak danych o rolach.",
            "date_from.required" => "Data rozpoczęcia jest wymagana.",
            "date_from.date" => "Data rozpoczęcia musi być poprawną datą.",
            "date_to.after_or_equal" => "Data zakończenia musi być późniejsza lub równa dacie rozpoczęcia.",
        ]);

        // Filtruj tylko te role, które mają ilość > 0
        $demandsToCreate = [];
        foreach ($validated["demands"] as $roleId => $demandData) {
            // Sprawdź czy dane są poprawne
            if (!isset($demandData["role_id"]) || !isset($demandData["required_count"])) {
                continue;
            }
            
            $requiredCount = (int) $demandData["required_count"];
            if ($requiredCount > 0) {
                $demandsToCreate[] = [
                    "role_id" => (int) $demandData["role_id"],
                    "required_count" => $requiredCount,
                    "date_from" => $validated["date_from"],
                    "date_to" => $validated["date_to"] ?? null,
                    "notes" => $validated["notes"] ?? null,
                ];
            }
        }

        // Sprawdź czy jest przynajmniej jedno zapotrzebowanie
        if (empty($demandsToCreate)) {
            return back()
                ->withInput()
                ->withErrors(["demands" => "Musisz podać ilość większą od 0 dla przynajmniej jednej roli."]);
        }

        DB::beginTransaction();
        try {
            foreach ($demandsToCreate as $demandData) {
                $project->demands()->create($demandData);
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
