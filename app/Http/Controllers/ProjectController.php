<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Location;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        // Dane są pobierane przez komponent Livewire ProjectsTable
        return view('projects.index');
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
        Project::create($request->validated());

        // Wyczyść cache dla dropdowna projektów
        Cache::forget('active_projects_dropdown');

        return redirect()->route('projects.index')->with('success', 'Projekt został dodany.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project): View
    {
        $project->load(['location', 'demands']);
        $project->loadCount(['files', 'tasks', 'assignments', 'comments']);
        $activeTab = 'info';
        return view('projects.show', compact('project', 'activeTab'));
    }

    /**
     * Display the files tab.
     */
    public function showFiles(Project $project): View
    {
        $project->load('files.uploadedBy');
        $project->loadCount(['files', 'tasks', 'assignments', 'comments']);
        $activeTab = 'files';
        return view('projects.show', compact('project', 'activeTab'));
    }

    /**
     * Display the tasks tab.
     */
    public function showTasks(Project $project): View
    {
        $project->load(['tasks.assignedTo', 'tasks.createdBy']);
        $project->loadCount(['files', 'tasks', 'assignments', 'comments']);
        $users = \App\Models\User::orderBy('name')->get();
        $activeTab = 'tasks';
        return view('projects.show', compact('project', 'users', 'activeTab'));
    }

    /**
     * Display the assignments tab.
     */
    public function showAssignments(Project $project): View
    {
        $project->load(['assignments.employee', 'assignments.role']);
        $project->loadCount(['files', 'tasks', 'assignments', 'comments']);
        $activeTab = 'assignments';
        return view('projects.show', compact('project', 'activeTab'));
    }

    /**
     * Display the comments tab.
     */
    public function showComments(Project $project): View
    {
        $project->load('comments.user');
        $project->loadCount(['files', 'tasks', 'assignments', 'comments']);
        $activeTab = 'comments';
        return view('projects.show', compact('project', 'activeTab'));
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
        $project->update($request->validated());

        // Wyczyść cache dla dropdowna projektów (może zmienić się status lub nazwa)
        Cache::forget('active_projects_dropdown');

        return redirect()->route('projects.index')->with('success', 'Projekt został zaktualizowany.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        // Wyczyść cache dla dropdowna projektów
        Cache::forget('active_projects_dropdown');

        return redirect()->route('projects.index')->with('success', 'Projekt został usunięty.');
    }
}
