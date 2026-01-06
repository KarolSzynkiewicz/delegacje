<?php

namespace App\Livewire;

use App\Models\Vehicle;
use Livewire\Component;
use Livewire\WithPagination;

class VehiclesTable extends Component
{
    use WithPagination;

    public $search = '';
    public $conditionFilter = '';
    public $statusFilter = '';
    public $sortField = 'registration_number';
    public $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'conditionFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'sortField' => ['except' => 'registration_number'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingConditionFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->conditionFilter = '';
        $this->statusFilter = '';
        $this->sortField = 'registration_number';
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
        $query = Vehicle::query();

        // Filtrowanie po numerze rejestracyjnym/marce/modelu
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('registration_number', 'like', '%' . $this->search . '%')
                  ->orWhere('brand', 'like', '%' . $this->search . '%')
                  ->orWhere('model', 'like', '%' . $this->search . '%');
            });
        }

        // Filtrowanie po stanie technicznym
        if ($this->conditionFilter) {
            $query->where('technical_condition', $this->conditionFilter);
        }

        // Filtrowanie po statusie (zajęty/wolny)
        if ($this->statusFilter) {
            if ($this->statusFilter === 'occupied') {
                $query->whereHas('assignments', function ($q) {
                    $q->where('status', 'active');
                });
            } else {
                $query->whereDoesntHave('assignments', function ($q) {
                    $q->where('status', 'active');
                });
            }
        }

        // Sortowanie
        $query->orderBy($this->sortField, $this->sortDirection);

        $vehicles = $query->paginate(10);
        $conditions = ['excellent', 'good', 'fair', 'poor'];

        return view('livewire.vehicles-table', [
            'vehicles' => $vehicles,
            'conditions' => $conditions,
        ]);
    }
}
