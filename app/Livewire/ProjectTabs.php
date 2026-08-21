<?php

// review

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;

class ProjectTabs extends Component
{
    public Project $project;

    public string $activeTab = 'info';

    public array $availableTabs = [];

    public bool $isMineView = false; // Flag dla widoku /mine/*

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
        // Definicja wszystkich możliwych tabów z przypisanym permission i ikonami
        $allTabs = [
            'info' => ['label' => 'Informacje', 'permission' => null, 'icon' => 'bi bi-info-circle'],
            'files' => ['label' => 'Pliki', 'permission' => 'project-files.view', 'icon' => 'bi bi-file-earmark'],
            'assignments' => ['label' => 'Przypisani pracownicy', 'permission' => 'assignments.view', 'icon' => 'bi bi-person-check'],
            'comments' => ['label' => 'Komentarze', 'permission' => 'comments.view', 'icon' => 'bi bi-chat-left-text'],
        ];

        // W widoku /mine/* dodaj zakładkę ocen pracowników
        if ($this->isMineView) {
            $allTabs['evaluations'] = ['label' => 'Oceny pracowników', 'permission' => null, 'icon' => 'bi bi-star'];
        }

        // Filtracja po permission - tylko taby do których user ma dostęp
        $this->availableTabs = array_filter($allTabs, function ($tab) {
            // permission === null (np. info) zawsze dostępny
            // lub user ma wymagane permission
            return $tab['permission'] === null || auth()->user()->hasPermission($tab['permission']);
        });
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
            return; // Ignoruj, fallback w validateActiveTab()
        }
        $this->activeTab = $tab;
    }

    protected function getTabData()
    {
        // Filtracja przez relacje hasMany - bez osobnych route
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
        // Pobierz ID pracowników przypisanych do tego projektu
        $employeeIds = $this->project->assignments()
            ->pluck('employee_id')
            ->unique()
            ->toArray();

        // Pobierz oceny dla tych pracowników
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
        ]);

        $this->project->load(['location', 'demands']);

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

        return view('livewire.project-tabs', compact('tabsForComponent', 'isMineView'));
    }
}
