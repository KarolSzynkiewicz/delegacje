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
            ->orderBy("start_date", "asc")
            ->get()
            ->groupBy('project_id');
        
        return view("demands.all", compact("demands"));
    }
    
    /**
     * Show the form for creating a new resource without project in URL.
     */
    public function createWithoutProject(Request $request)
    {
        $roles = Role::all();
        $projects = Project::with('location')
            ->orderBy('name')
            ->get();
        
        // Pobierz daty z query string jeśli są przekazane (z widoku tygodniowego)
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $projectId = $request->query('project_id');
        
        // Sprawdź czy daty są w przeszłości
        $isDateInPast = false;
        if ($startDate) {
            $isDateInPast = \Carbon\Carbon::parse($startDate)->startOfDay()->isPast();
        }
        
        // Pobierz istniejące zapotrzebowania dla wybranego projektu w tym okresie (jeśli daty i projekt są podane)
        $existingDemands = collect();
        $existingEndDate = null;
        if ($startDate && $endDate && $projectId) {
            $project = Project::find($projectId);
            if ($project) {
                $existingDemands = $project->demands()
                    ->where('start_date', '<=', $endDate)
                    ->where(function ($q) use ($startDate) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', $startDate);
                    })
                    ->with('role')
                    ->get();
                
                // Znajdź najpóźniejszą datę do z istniejących zapotrzebowań
                $demandsWithDateTo = $existingDemands->filter(function($d) {
                    return $d->end_date !== null;
                });
                
                if ($demandsWithDateTo->isNotEmpty()) {
                    $latestDateTo = $demandsWithDateTo->max('end_date');
                    $existingEndDate = $latestDateTo ? $latestDateTo->format('Y-m-d') : null;
                } else {
                    $uniqueStartDate = $existingDemands->pluck('start_date')->unique();
                    if ($uniqueStartDate->count() === 1 && $uniqueStartDate->first()->format('Y-m-d') === $startDate) {
                        $existingEndDate = $endDate;
                    } else {
                        $existingEndDate = null;
                    }
                }
                
                $existingDemands = $existingDemands->keyBy('role_id');
            }
        }
        
        return view("demands.create", compact("projects", "roles", "startDate", "endDate", "projectId", "existingDemands", "existingEndDate", "isDateInPast"));
    }
    
    /**
     * Store a newly created resource in storage without project in URL.
     */
    public function storeWithoutProject(StoreProjectDemandRequest $request)
    {
        try {
            $validated = $request->validated();
            
            // Pobierz projekt z formularza
            $project = Project::findOrFail($validated['project_id']);
            
            $startDate = \Carbon\Carbon::parse($validated['start_date']);
            $endDate = isset($validated['end_date']) ? \Carbon\Carbon::parse($validated['end_date']) : null;
            
            $this->demandService->createDemands(
                $project,
                $startDate,
                $endDate,
                $validated['notes'] ?? null,
                $validated['demands'] ?? []
            );

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
        $projects = Project::with('location')
            ->orderBy('name')
            ->get();
        
        // Pobierz daty z query string jeśli są przekazane (z widoku tygodniowego)
        // Używamy start_date i end_date zgodnie z konwencją (README.md linia 463)
        $startDate = $request->query('start_date') ?: $request->input('start_date');
        $endDate = $request->query('end_date') ?: $request->input('end_date');
        
        // Normalizuj puste stringi do null (ale zachowaj wartości jeśli są)
        $startDate = ($startDate && trim($startDate) !== '') ? trim($startDate) : null;
        $endDate = ($endDate && trim($endDate) !== '') ? trim($endDate) : null;
        
        // Sprawdź czy daty są w przeszłości (sprawdzamy tylko startDate, bo jeśli startDate jest w przyszłości, to endDate też)
        $isDateInPast = false;
        if ($startDate) {
            $isDateInPast = \Carbon\Carbon::parse($startDate)->startOfDay()->isPast();
        }
        
        // Pobierz istniejące zapotrzebowania dla tego projektu w tym okresie (jeśli daty są podane)
        $existingDemands = collect();
        $existingEndDate = $endDate; // Domyślnie używaj daty z parametrów
        
        if ($startDate && $endDate) {
            $startDateCarbon = \Carbon\Carbon::parse($startDate);
            $endDateCarbon = \Carbon\Carbon::parse($endDate);
            
            // Użyj metody overlappingWith z traitu HasDateRange
            $existingDemands = $project->demands()
                ->overlappingWith($startDateCarbon, $endDateCarbon)
                ->with('role')
                ->get();
            
            // Jeśli są zapotrzebowania, sprawdź czy wszystkie mają te same daty
            if ($existingDemands->isNotEmpty()) {
                $uniqueStartDates = $existingDemands->pluck('start_date')->map(fn($d) => $d->format('Y-m-d'))->unique();
                $uniqueEndDates = $existingDemands->pluck('end_date')->map(fn($d) => $d ? $d->format('Y-m-d') : null)->unique();
                
                // Jeśli wszystkie zapotrzebowania mają tę samą start_date i end_date, użyj tych dat
                if ($uniqueStartDates->count() === 1 && $uniqueEndDates->count() === 1) {
                    $existingEndDate = $uniqueEndDates->first() ?? $endDate;
                }
                // W przeciwnym razie użyj daty z parametrów (już ustawione wyżej)
            }
            
            // Klucjuj po role_id (jeśli jest wiele dla tej samej roli, weź najnowsze)
            $existingDemands = $existingDemands->sortByDesc('created_at')->keyBy('role_id');
        }
        
        return view("demands.create", compact("project", "projects", "roles", "startDate", "endDate", "existingDemands", "existingEndDate", "isDateInPast"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectDemandRequest $request, Project $project)
    {
        try {
            $validated = $request->validated();
            
            $startDate = \Carbon\Carbon::parse($validated['start_date']);
            $endDate = isset($validated['end_date']) ? \Carbon\Carbon::parse($validated['end_date']) : null;
            
            $this->demandService->createDemands(
                $project,
                $startDate,
                $endDate,
                $validated['notes'] ?? null,
                $validated['demands'] ?? []
            );

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
        $demand->load("role", "project");
        $roles = Role::all();
        $projects = Project::with('location')
            ->orderBy('name')
            ->get();
        
        // Sprawdź czy data jest w przeszłości
        $isDateInPast = $demand->start_date->startOfDay()->isPast();
        if ($demand->end_date && !$isDateInPast) {
            $isDateInPast = $demand->end_date->startOfDay()->isPast();
        }
        
        return view("demands.edit", compact("demand", "roles", "projects", "isDateInPast"));
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
        
        // Odśwież model, aby uzyskać zaktualizowany project_id (może być zmieniony)
        $demand->refresh();
        
        \Log::info('Demand update result', [
            'updated' => $updated,
            'demand_after' => $demand->toArray()
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
