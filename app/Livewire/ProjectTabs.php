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
            'tasks' => ['label' => 'Zadania', 'permission' => 'project-tasks.view', 'icon' => 'bi bi-list-check'],
            'assignments' => ['label' => 'Przypisani pracownicy', 'permission' => 'assignments.view', 'icon' => 'bi bi-person-check'],
            'comments' => ['label' => 'Komentarze', 'permission' => 'comments.view', 'icon' => 'bi bi-chat-left-text'],
        ];

        // W widoku /mine/* dodaj zakładkę ocen pracowników
        if ($this->isMineView) {
            $allTabs['evaluations'] = ['label' => 'Oceny pracowników', 'permission' => null, 'icon' => 'bi bi-star'];
        }

        // Filtracja po permission - tylko taby do których user ma dostęp
        $this->availableTabs = array_filter($allTabs, function($tab) {
            // permission === null (np. info) zawsze dostępny
            // lub user ma wymagane permission
            // W widoku /mine/* zadania są zawsze dostępne (kierownik widzi swoje zadania)
            if ($this->isMineView && $tab['permission'] === 'project-tasks.view') {
                return true;
            }
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
            'tasks' => $this->isMineView 
                ? $this->project->tasks()
                    ->where('assigned_to', auth()->id())
                    ->with(['assignedTo', 'createdBy'])
                    ->get()
                : $this->project->tasks()->with(['assignedTo', 'createdBy'])->get(),
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
        // Load counts for tabs
        if ($this->isMineView) {
            // Dla widoku /mine/* - policz tylko zadania przypisane do użytkownika
            $this->project->loadCount([
                'files',
                'assignments',
                'comments'
            ]);
            // Policz zadania przypisane do użytkownika
            $this->project->tasks_count = $this->project->tasks()
                ->where('assigned_to', auth()->id())
                ->count();
        } else {
            $this->project->loadCount([
                'files',
                'tasks',
                'assignments',
                'comments'
            ]);
        }
        
        // Load basic relations for info tab
        $this->project->load(['location', 'demands']);
        
        // Load users for tasks tab if needed
        $users = null;
        $filteredTasks = null;
        if ($this->activeTab === 'tasks') {
            $users = \App\Models\User::orderBy('name')->get();
            
            // Dla widoku /mine/* - załaduj tylko zadania przypisane do użytkownika
            if ($this->isMineView) {
                $filteredTasks = $this->project->tasks()
                    ->where('assigned_to', auth()->id())
                    ->with(['assignedTo', 'createdBy'])
                    ->get();
                // Przypisz przefiltrowane zadania do projektu, żeby komponent project-tasks je widział
                $this->project->setRelation('tasks', $filteredTasks);
            }
        }
        
        // Przygotuj taby dla komponentu
        $tabsForComponent = [];
        foreach ($this->availableTabs as $tabKey => $tab) {
            $count = match($tabKey) {
                'files' => $this->project->files_count ?? 0,
                'tasks' => $this->project->tasks_count ?? 0,
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
        
        $isMineView = $this->isMineView; // Przekaż jako zmienną lokalną
        return view('livewire.project-tabs', compact('users', 'tabsForComponent', 'isMineView'));
    }
}
