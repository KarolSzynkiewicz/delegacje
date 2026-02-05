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
        // Tylko podstawowe dane - reszta w Livewire ProjectTabs
        $project->load(['location', 'demands']);
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
        try {
            // Sprawdź powiązane rekordy przed usunięciem
            $assignmentsCount = $project->assignments()->count();
            $demandsCount = $project->demands()->count();
            $tasksCount = $project->tasks()->count();
            $filesCount = $project->files()->count();
            $commentsCount = $project->comments()->count();
            $variableCostsCount = $project->variableCosts()->count();
            
            // Sprawdź time logs przez assignments
            $timeLogsCount = \App\Models\TimeLog::whereHas('projectAssignment', function($query) use ($project) {
                $query->where('project_id', $project->id);
            })->count();
            
            $project->delete();

            // Wyczyść cache dla dropdowna projektów
            Cache::forget('active_projects_dropdown');

            $message = "Projekt został usunięty.";
            if ($assignmentsCount > 0 || $demandsCount > 0 || $tasksCount > 0 || 
                $filesCount > 0 || $commentsCount > 0 || $variableCostsCount > 0 || $timeLogsCount > 0) {
                $message .= " Usunięto również: ";
                $deleted = [];
                if ($assignmentsCount > 0) $deleted[] = "{$assignmentsCount} przypisania pracowników";
                if ($timeLogsCount > 0) $deleted[] = "{$timeLogsCount} wpisów czasu pracy";
                if ($demandsCount > 0) $deleted[] = "{$demandsCount} zapotrzebowań";
                if ($tasksCount > 0) $deleted[] = "{$tasksCount} zadań";
                if ($filesCount > 0) $deleted[] = "{$filesCount} plików";
                if ($commentsCount > 0) $deleted[] = "{$commentsCount} komentarzy";
                if ($variableCostsCount > 0) $deleted[] = "{$variableCostsCount} kosztów zmiennych";
                $message .= implode(", ", $deleted) . ".";
            }

            return redirect()->route('projects.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('projects.index')
                ->with('error', 'Wystąpił błąd podczas usuwania projektu: ' . $e->getMessage());
        }
    }
}
