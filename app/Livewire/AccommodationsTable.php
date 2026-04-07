<?php

namespace App\Livewire;

use App\Models\Accommodation;
use Livewire\Component;
use Livewire\WithPagination;

class AccommodationsTable extends Component
{
    use WithPagination;

    public $search = '';

    public $statusFilter = '';

    public $sortField = 'name';

    public $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
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

    public function clearFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
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
        $query = Accommodation::with(['location', 'activeLease']);

        // Filtrowanie po nazwie/adresie (własny adres lub przez lokalizację)
        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhereHas('location', fn ($lq) => $lq
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                    );
            });
        }

        // Filtrowanie po statusie (pełne/wolne miejsca)
        if ($this->statusFilter) {
            if ($this->statusFilter === 'full') {
                $query->whereRaw('capacity <= (SELECT COUNT(*) FROM accommodation_assignments WHERE accommodation_id = accommodations.id AND status = "active")');
            } elseif ($this->statusFilter === 'available') {
                $query->whereRaw('capacity > (SELECT COUNT(*) FROM accommodation_assignments WHERE accommodation_id = accommodations.id AND status = "active")');
            }
        }

        // Sortowanie
        $query->orderBy($this->sortField, $this->sortDirection);

        $accommodations = $query->paginate(10);

        return view('livewire.accommodations-table', [
            'accommodations' => $accommodations,
        ]);
    }
}
