<?php
//review
namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;

class ProjectTabs extends Component
{
    public Project $project;
    public string $activeTab = 'info';
    public array $availableTabs = [];

    protected $queryString = ['activeTab' => ['except' => 'info', 'as' => 'tab']];

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->buildAvailableTabs();
        $this->validateActiveTab();
    }

    protected function buildAvailableTabs()
    {
        // Definicja wszystkich możliwych tabów z przypisanym permission
        $allTabs = [
            'info' => ['label' => 'Informacje', 'permission' => null],
            'files' => ['label' => 'Pliki', 'permission' => 'project-files.view'],
            'tasks' => ['label' => 'Zadania', 'permission' => 'project-tasks.view'],
            'assignments' => ['label' => 'Przypisani pracownicy', 'permission' => 'assignments.view'],
            'comments' => ['label' => 'Komentarze', 'permission' => 'comments.view'],
        ];

        // Filtracja po permission - tylko taby do których user ma dostęp
        $this->availableTabs = array_filter($allTabs, function($tab) {
            // permission === null (np. info) zawsze dostępny
            // lub user ma wymagane permission
            return $tab['permission'] === null || auth()->user()->hasPermission($tab['permission']);
        });
    }

    protected function validateActiveTab()
    {
        if (!isset($this->availableTabs[$this->activeTab])) {
            $this->activeTab = array_key_first($this->availableTabs) ?? 'info';
        }
    }

    public function setTab(string $tab)
    {
        if (!isset($this->availableTabs[$tab])) {
            return; // Ignoruj, fallback w validateActiveTab()
        }
        $this->activeTab = $tab;
    }

    protected function getTabData()
    {
        // Filtracja przez relacje hasMany - bez osobnych route
        return match($this->activeTab) {
            'files' => $this->project->files()->with('uploadedBy')->get(),
            'tasks' => $this->project->tasks()->with(['assignedTo', 'createdBy'])->get(),
            'assignments' => $this->project->assignments()->with(['employee', 'role'])->get(),
            'comments' => $this->project->comments()->with('user')->get(),
            default => null,
        };
    }

    public function render()
    {
        // Load counts for tabs
        $this->project->loadCount([
            'files',
            'tasks',
            'assignments',
            'comments'
        ]);
        
        // Load basic relations for info tab
        $this->project->load(['location', 'demands']);
        
        // Load users for tasks tab if needed
        $users = null;
        if ($this->activeTab === 'tasks') {
            $users = \App\Models\User::orderBy('name')->get();
        }
        
        return view('livewire.project-tabs', compact('users'));
    }
}
