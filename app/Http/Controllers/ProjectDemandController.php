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
    public function index()
    {
        $demands = ProjectDemand::with('project', 'demandRoles.role')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('demands.index', compact('demands'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $projectId = $request->query('project_id');
        $project = $projectId ? Project::findOrFail($projectId) : null;
        $projects = Project::where('status', 'active')->get();
        $roles = Role::all();
        
        return view('demands.create', compact('projects', 'roles', 'project'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'required_workers_count' => 'required|integer|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'roles' => 'required|array|min:1',
            'roles.*.role_id' => 'required|exists:roles,id',
            'roles.*.required_count' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            // Create project demand
            $demand = ProjectDemand::create([
                'project_id' => $validated['project_id'],
                'required_workers_count' => $validated['required_workers_count'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'notes' => $validated['notes'],
            ]);

            // Create demand roles
            foreach ($validated['roles'] as $roleData) {
                ProjectDemandRole::create([
                    'project_demand_id' => $demand->id,
                    'role_id' => $roleData['role_id'],
                    'required_count' => $roleData['required_count'],
                ]);
            }

            DB::commit();

            return redirect()
                ->route('demands.show', $demand)
                ->with('success', 'Zapotrzebowanie projektu zostało utworzone.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()
                ->withInput()
                ->withErrors(['error' => 'Wystąpił błąd podczas tworzenia zapotrzebowania: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ProjectDemand $demand)
    {
        $demand->load('project', 'demandRoles.role');
        
        // Get current assignments for this project
        $currentAssignments = $demand->project->assignments()
            ->with('employee', 'role')
            ->where('status', 'active')
            ->get();
        
        return view('demands.show', compact('demand', 'currentAssignments'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProjectDemand $demand)
    {
        $demand->load('demandRoles');
        $projects = Project::all();
        $roles = Role::all();
        
        return view('demands.edit', compact('demand', 'projects', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProjectDemand $demand)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'required_workers_count' => 'required|integer|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'roles' => 'required|array|min:1',
            'roles.*.role_id' => 'required|exists:roles,id',
            'roles.*.required_count' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            // Update project demand
            $demand->update([
                'project_id' => $validated['project_id'],
                'required_workers_count' => $validated['required_workers_count'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'notes' => $validated['notes'],
            ]);

            // Delete old demand roles and create new ones
            $demand->demandRoles()->delete();
            
            foreach ($validated['roles'] as $roleData) {
                ProjectDemandRole::create([
                    'project_demand_id' => $demand->id,
                    'role_id' => $roleData['role_id'],
                    'required_count' => $roleData['required_count'],
                ]);
            }

            DB::commit();

            return redirect()
                ->route('demands.show', $demand)
                ->with('success', 'Zapotrzebowanie projektu zostało zaktualizowane.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()
                ->withInput()
                ->withErrors(['error' => 'Wystąpił błąd podczas aktualizacji zapotrzebowania: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectDemand $demand)
    {
        $demand->delete();

        return redirect()
            ->route('demands.index')
            ->with('success', 'Zapotrzebowanie projektu zostało usunięte.');
    }
}
