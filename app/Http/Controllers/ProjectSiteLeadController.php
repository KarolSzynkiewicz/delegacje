<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectSiteLeadRequest;
use App\Http\Requests\UpdateProjectSiteLeadRequest;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectSiteLead;
use App\Services\ProjectSiteLeadService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectSiteLeadController extends Controller
{
    public function __construct(
        protected ProjectSiteLeadService $siteLeadService
    ) {}

    public function create(Project $project): View
    {
        return view('project-site-leads.create', [
            'project' => $project,
            'employees' => $this->employeesForSelect(),
        ]);
    }

    public function store(StoreProjectSiteLeadRequest $request, Project $project): RedirectResponse
    {
        $validated = $request->validated();
        $employee = Employee::findOrFail($validated['employee_id']);

        if (! empty($validated['week_start'])) {
            $weekStart = Carbon::parse($validated['week_start'])->startOfWeek();

            $this->siteLeadService->replaceForWeek($project, $employee, $weekStart);

            return redirect()
                ->route('weekly-overview.index', [
                    'start_date' => $weekStart->format('Y-m-d'),
                    'project_id' => $project->id,
                ])
                ->with('success', 'Kierownik został ustawiony.');
        }

        $this->siteLeadService->assign(
            $project,
            $employee,
            Carbon::parse($validated['start_date']),
            isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : null,
            $validated['notes'] ?? null
        );

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Kierownik został ustawiony.');
    }

    public function edit(Project $project, ProjectSiteLead $siteLead): View
    {
        $this->assertBelongsToProject($project, $siteLead);

        return view('project-site-leads.edit', [
            'project' => $project,
            'siteLead' => $siteLead,
            'employees' => $this->employeesForSelect(),
        ]);
    }

    public function update(
        UpdateProjectSiteLeadRequest $request,
        Project $project,
        ProjectSiteLead $siteLead
    ): RedirectResponse {
        $this->assertBelongsToProject($project, $siteLead);

        $validated = $request->validated();

        $this->siteLeadService->update(
            $siteLead,
            Employee::findOrFail($validated['employee_id']),
            Carbon::parse($validated['start_date']),
            isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : null,
            $validated['notes'] ?? null
        );

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Kierownik został zaktualizowany.');
    }

    public function destroy(Project $project, ProjectSiteLead $siteLead): RedirectResponse
    {
        $this->assertBelongsToProject($project, $siteLead);

        $this->siteLeadService->delete($siteLead);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Wpis kierownika został usunięty.');
    }

    private function employeesForSelect()
    {
        return Employee::query()
            ->whereNull('terminated_at')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    private function assertBelongsToProject(Project $project, ProjectSiteLead $siteLead): void
    {
        abort_unless($siteLead->project_id === $project->id, 404);
    }
}
