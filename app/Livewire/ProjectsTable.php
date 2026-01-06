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

        // Filtrowanie po nazwie/kliencie
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('client_name', 'like', '%' . $this->search . '%');
            });
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

        $projects = $query->paginate(10);
        $locations = Location::orderBy('name')->get();
        $statuses = ['active', 'completed', 'cancelled', 'pending'];

        return view('livewire.projects-table', [
            'projects' => $projects,
            'locations' => $locations,
            'statuses' => $statuses,
        ]);
    }
}
