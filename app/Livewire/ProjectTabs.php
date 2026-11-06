<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\ProjectMilestone;
use Livewire\Component;

class ProjectTabs extends Component
{
    public Project $project;

    public string $activeTab = 'info';

    public array $availableTabs = [];

    public bool $isMineView = false;

    public string $newMilestoneTitle = '';

    protected $queryString = ['activeTab' => ['except' => 'info', 'as' => 'tab']];

    public function mount(Project $project, bool $isMineView = false)
    {
        $this->project = $project;
        $this->isMineView = $isMineView;
        $this->buildAvailableTabs();
        $this->validateActiveTab();
    }

    protected function buildAvailableTabs()
    {
        $allTabs = [
            'info' => ['label' => 'Informacje', 'permission' => null, 'icon' => 'bi bi-info-circle'],
            'files' => ['label' => 'Pliki', 'permission' => 'project-files.view', 'icon' => 'bi bi-file-earmark'],
            'timeline' => ['label' => 'Oś przypisań', 'permission' => 'timeline', 'icon' => 'bi bi-bar-chart-steps'],
            'assignments' => ['label' => 'Przypisani pracownicy', 'permission' => 'assignments.view', 'icon' => 'bi bi-person-check'],
            'comments' => ['label' => 'Raporty z działania', 'permission' => 'comments.view', 'icon' => 'bi bi-journal-text'],
        ];

        if ($this->isMineView) {
            $allTabs['evaluations'] = ['label' => 'Oceny pracowników', 'permission' => null, 'icon' => 'bi bi-star'];
        }

        $this->availableTabs = array_filter($allTabs, function ($tab, $key) {
            if ($key === 'timeline') {
                $user = auth()->user();

                return $user->hasPermission('project-assignments.view')
                    || $user->hasPermission('assignments.view');
            }

            return $tab['permission'] === null || auth()->user()->hasPermission($tab['permission']);
        }, ARRAY_FILTER_USE_BOTH);
    }

    protected function validateActiveTab()
    {
        if (! isset($this->availableTabs[$this->activeTab])) {
            $this->activeTab = array_key_first($this->availableTabs) ?? 'info';
        }
    }

    public function setTab(string $tab)
    {
        if (! isset($this->availableTabs[$tab])) {
            return;
        }
        $this->activeTab = $tab;
    }

    protected function canManageProject(): bool
    {
        if ($this->isMineView) {
            return false;
        }

        $user = auth()->user();

        return $user && ($user->isAdmin() || $user->hasPermission('projects.update'));
    }

    public function addMilestone(): void
    {
        if (! $this->canManageProject()) {
            return;
        }

        $validated = $this->validate([
            'newMilestoneTitle' => ['required', 'string', 'max:255'],
        ], [
            'newMilestoneTitle.required' => 'Podaj nazwę kamienia milowego.',
        ]);

        $nextOrder = (int) $this->project->milestones()->max('sort_order') + 1;

        $this->project->milestones()->create([
            'title' => trim($validated['newMilestoneTitle']),
            'sort_order' => $nextOrder,
            'is_done' => false,
        ]);

        $this->newMilestoneTitle = '';
        $this->project->unsetRelation('milestones');
    }

    public function toggleMilestone(int $milestoneId): void
    {
        if (! $this->canManageProject()) {
            return;
        }

        $milestone = $this->project->milestones()->whereKey($milestoneId)->first();
        if (! $milestone) {
            return;
        }

        $milestone->markDone(! $milestone->is_done);
        $this->project->unsetRelation('milestones');
    }

    public function deleteMilestone(int $milestoneId): void
    {
        if (! $this->canManageProject()) {
            return;
        }

        ProjectMilestone::query()
            ->where('project_id', $this->project->id)
            ->whereKey($milestoneId)
            ->delete();

        $this->project->unsetRelation('milestones');
    }

    protected function getTabData()
    {
        return match ($this->activeTab) {
            'files' => $this->project->files()->with('uploadedBy')->get(),
            'assignments' => $this->project->assignments()->with(['employee', 'role'])->get(),
            'comments' => $this->project->comments()->with('user')->get(),
            'evaluations' => $this->getProjectEvaluations(),
            default => null,
        };
    }

    protected function getProjectEvaluations()
    {
        $employeeIds = $this->project->assignments()
            ->pluck('employee_id')
            ->unique()
            ->toArray();

        return \App\Models\EmployeeEvaluation::whereIn('employee_id', $employeeIds)
            ->with(['employee', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function render()
    {
        $this->project->loadCount([
            'files',
            'assignments',
            'comments',
            'milestones',
        ]);

        $this->project->load([
            'location',
            'client',
            'clientContact',
            'demands.role',
            'milestones',
            'siteLeads.employee',
            'hourlyRates',
        ]);

        $tabsForComponent = [];
        foreach ($this->availableTabs as $tabKey => $tab) {
            $count = match ($tabKey) {
                'files' => $this->project->files_count ?? 0,
                'assignments' => $this->project->assignments_count ?? 0,
                'comments' => $this->project->comments_count ?? 0,
                'evaluations' => $this->getProjectEvaluations()->count(),
                default => null,
            };

            $tabsForComponent[$tabKey] = [
                'label' => $tab['label'],
                'icon' => $tab['icon'] ?? null,
                'count' => $count,
                'wireClick' => "setTab('{$tabKey}')",
            ];
        }

        $isMineView = $this->isMineView;
        $canManage = $this->canManageProject();

        return view('livewire.project-tabs', compact('tabsForComponent', 'isMineView', 'canManage'));
    }
}
