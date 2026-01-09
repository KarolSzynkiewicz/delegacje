<?php

namespace App\Livewire;

use App\Models\Rotation;
use App\Models\Employee;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;

class RotationsTable extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $sortField = 'end_date';
    public $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'sortField' => ['except' => 'end_date'],
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

    public function clearFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->sortField = 'end_date';
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
        $query = Rotation::with('employee');

        // Wyszukiwanie po pracowniku
        if (!empty($this->search)) {
            $searchTerm = trim($this->search);
            $query->whereHas('employee', function (Builder $q) use ($searchTerm) {
                $q->where(function ($query) use ($searchTerm) {
                    $query->where('first_name', 'like', '%' . $searchTerm . '%')
                          ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                          ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $searchTerm . '%']);
                });
            });
        }

        // Filtrowanie po statusie
        if (!empty($this->statusFilter)) {
            $today = now()->toDateString();
            switch ($this->statusFilter) {
                case 'scheduled':
                    $query->whereDate('start_date', '>', $today)
                        ->where(function ($q) {
                            $q->whereNull('status')
                              ->orWhere('status', '!=', 'cancelled');
                        });
                    break;
                case 'active':
                    $query->whereDate('start_date', '<=', $today)
                        ->whereDate('end_date', '>=', $today)
                        ->where(function ($q) {
                            $q->whereNull('status')
                              ->orWhere('status', '!=', 'cancelled');
                        });
                    break;
                case 'completed':
                    $query->whereDate('end_date', '<', $today)
                        ->where(function ($q) {
                            $q->whereNull('status')
                              ->orWhere('status', '!=', 'cancelled');
                        });
                    break;
                case 'cancelled':
                    $query->where('status', 'cancelled');
                    break;
            }
        }

        // Sortowanie
        $query->orderBy($this->sortField, $this->sortDirection);

        $rotations = $query->paginate(20);

        return view('livewire.rotations-table', [
            'rotations' => $rotations,
        ]);
    }
}
