<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Location;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $projects = Project::with('location')->get();
        return view('projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $locations = Location::all();
        return view('projects.create', compact('locations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', Project::class);
        
        Project::create($request->validated());

        return redirect()->route('projects.index')->with('success', 'Projekt został dodany.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project): View
    {
        $project->load('location', 'assignments');
        return view('projects.show', compact('project'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project): View
    {
        $locations = Location::all();
        return view('projects.edit', compact('project', 'locations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);
        
        $project->update($request->validated());

        return redirect()->route('projects.index')->with('success', 'Projekt został zaktualizowany.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);
        
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Projekt został usunięty.');
    }
}
