<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Location;
use App\Models\Project;
use App\Services\ProjectHourlyRateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

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
    public function create(Request $request): View
    {
        $locations = Location::all();
        $clients = Client::query()
            ->customers()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $selectedClientId = $request->integer('client_id') ?: null;
        $contactsByClient = $this->contactsByClientPayload($clients->pluck('id')->all());

        return view('projects.create', compact('locations', 'clients', 'selectedClientId', 'contactsByClient'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data = $this->syncClientName($data);
        $project = Project::create($data);
        app(ProjectHourlyRateService::class)->seedOpeningRate($project);

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
        $project->load([
            'location',
            'client',
            'clientContact',
            'demands.role',
            'milestones',
            'siteLeads.employee',
            'equipmentConsumptions' => fn ($movements) => $movements
                ->with(['equipment', 'variant', 'warehouse.location', 'creator'])
                ->latest('id')
                ->limit(50),
        ]);

        return view('projects.show', compact('project'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project): View
    {
        $locations = Location::all();
        $clients = Client::query()
            ->where(function ($q) use ($project) {
                $q->customers()->where('is_active', true);
                if ($project->client_id) {
                    $q->orWhere('id', $project->client_id);
                }
            })
            ->orderBy('name')
            ->get();
        $selectedClientId = $project->client_id;
        $contactsByClient = $this->contactsByClientPayload($clients->pluck('id')->all());

        return view('projects.edit', compact('project', 'locations', 'clients', 'selectedClientId', 'contactsByClient'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $data = $request->validated();
        unset($data['hourly_rate']);
        $data = $this->syncClientName($data);
        $project->update($data);

        // Wyczyść cache dla dropdowna projektów (może zmienić się status lub nazwa)
        Cache::forget('active_projects_dropdown');

        return redirect()->route('projects.index')->with('success', 'Projekt został zaktualizowany.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function syncClientName(array $data): array
    {
        $clientId = $data['client_id'] ?? null;
        if ($clientId) {
            $client = Client::query()->find($clientId);
            $data['client_name'] = $client?->name;

            $contactId = $data['client_contact_id'] ?? null;
            if ($contactId) {
                $belongs = ClientContact::query()
                    ->whereKey($contactId)
                    ->where('client_id', $clientId)
                    ->exists();
                if (! $belongs) {
                    $data['client_contact_id'] = null;
                }
            }
        } else {
            $data['client_id'] = null;
            $data['client_contact_id'] = null;
        }

        return $data;
    }

    /**
     * @param  list<int|string>  $clientIds
     * @return array<string, list<array{id:int,label:string}>>
     */
    private function contactsByClientPayload(array $clientIds): array
    {
        if ($clientIds === []) {
            return [];
        }

        return ClientContact::query()
            ->whereIn('client_id', $clientIds)
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get(['id', 'client_id', 'name', 'role', 'is_primary'])
            ->groupBy(fn (ClientContact $c) => (string) $c->client_id)
            ->map(fn ($contacts) => $contacts->map(fn (ClientContact $c) => [
                'id' => $c->id,
                'label' => $c->label(),
            ])->values()->all())
            ->all();
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
            $filesCount = $project->files()->count();
            $commentsCount = $project->comments()->count();
            $variableCostsCount = $project->variableCosts()->count();

            // Sprawdź time logs przez assignments
            $timeLogsCount = \App\Models\TimeLog::whereHas('projectAssignment', function ($query) use ($project) {
                $query->where('project_id', $project->id);
            })->count();

            $project->delete();

            // Wyczyść cache dla dropdowna projektów
            Cache::forget('active_projects_dropdown');

            $message = 'Projekt został usunięty.';
            if ($assignmentsCount > 0 || $demandsCount > 0 ||
                $filesCount > 0 || $commentsCount > 0 || $variableCostsCount > 0 || $timeLogsCount > 0) {
                $message .= ' Usunięto również: ';
                $deleted = [];
                if ($assignmentsCount > 0) {
                    $deleted[] = "{$assignmentsCount} przypisania pracowników";
                }
                if ($timeLogsCount > 0) {
                    $deleted[] = "{$timeLogsCount} wpisów czasu pracy";
                }
                if ($demandsCount > 0) {
                    $deleted[] = "{$demandsCount} zapotrzebowań";
                }
                if ($filesCount > 0) {
                    $deleted[] = "{$filesCount} plików";
                }
                if ($commentsCount > 0) {
                    $deleted[] = "{$commentsCount} komentarzy";
                }
                if ($variableCostsCount > 0) {
                    $deleted[] = "{$variableCostsCount} kosztów zmiennych";
                }
                $message .= implode(', ', $deleted).'.';
            }

            return redirect()->route('projects.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('projects.index')
                ->with('error', 'Wystąpił błąd podczas usuwania projektu: '.$e->getMessage());
        }
    }
}
