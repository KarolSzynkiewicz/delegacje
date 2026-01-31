<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Location;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectsTable extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $locationFilter = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    
    // Optional filter for /mine/* routes
    public $filterProjectIds = null;
    public $isMineView = false; // Flag to use /mine/* routes

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'locationFilter' => ['except' => ''],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingLocationFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->locationFilter = '';
        $this->sortField = 'name';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    public function paginationView()
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        
        $this->resetPage();
    }

    public function render()
    {
        $query = Project::with('location');
        
        // Filtrowanie po zarządzanych projektach (dla /mine/*)
        if ($this->filterProjectIds && is_array($this->filterProjectIds) && !empty($this->filterProjectIds)) {
            $query->whereIn('id', $this->filterProjectIds);
            $this->isMineView = true; // Ustaw flagę jeśli filtrujemy
        }

        // Filtrowanie po nazwie/kliencie
        if ($this->search) {
            $searchTerm = trim($this->search);
            if (strlen($searchTerm) >= 2) { // Minimum 2 znaki dla wyszukiwania
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', '%' . $searchTerm . '%')
                      ->orWhere('client_name', 'like', '%' . $searchTerm . '%');
                });
            }
        }

        // Filtrowanie po statusie
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        // Filtrowanie po lokalizacji
        if ($this->locationFilter) {
            $query->where('location_id', $this->locationFilter);
        }

        // Sortowanie
        $query->orderBy($this->sortField, $this->sortDirection);

        $projects = $query->paginate(15);
        
        // Cache locations - zmieniają się rzadko
        $locations = cache()->remember('locations_list', 3600, function () {
            return Location::orderBy('name')->get();
        });
        
        $statuses = ['active', 'completed', 'cancelled', 'pending'];

        return view('livewire.projects-table', [
            'projects' => $projects,
            'locations' => $locations,
            'statuses' => $statuses,
        ]);
    }
}
