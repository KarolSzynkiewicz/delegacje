<?php

namespace App\Http\Controllers;

use App\Models\ProjectVariableCost;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProjectVariableCostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $costs = ProjectVariableCost::with('project')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('project-variable-costs.index', compact('costs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $projects = Project::orderBy('name')->get();
        return view('project-variable-costs.create', compact('projects'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
        ]);

        ProjectVariableCost::create($validated);

        return redirect()
            ->route('project-variable-costs.index')
            ->with('success', 'Koszt zmienny został dodany.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ProjectVariableCost $projectVariableCost): View
    {
        $projectVariableCost->load('project');
        return view('project-variable-costs.show', compact('projectVariableCost'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProjectVariableCost $projectVariableCost): View
    {
        $projects = Project::orderBy('name')->get();
        return view('project-variable-costs.edit', compact('projectVariableCost', 'projects'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProjectVariableCost $projectVariableCost): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
        ]);

        $projectVariableCost->update($validated);

        return redirect()
            ->route('project-variable-costs.index')
            ->with('success', 'Koszt zmienny został zaktualizowany.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectVariableCost $projectVariableCost): RedirectResponse
    {
        $projectVariableCost->delete();

        return redirect()
            ->route('project-variable-costs.index')
            ->with('success', 'Koszt zmienny został usunięty.');
    }
}
