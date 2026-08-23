<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithSortableTable;
use App\Models\Accommodation;
use Livewire\Component;
use Livewire\WithPagination;

class AccommodationsTable extends Component
{
    use InteractsWithSortableTable;
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

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->sortField = 'name';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'vendor.livewire.simple-pagination';
    }

    protected function sortableFields(): array
    {
        return ['name'];
    }

    public function render()
    {
        $query = Accommodation::with(['location', 'activeLease']);

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

        if ($this->statusFilter === 'full') {
            $query->whereRaw('capacity <= (SELECT COUNT(*) FROM accommodation_assignments WHERE accommodation_id = accommodations.id AND status = "active")');
        } elseif ($this->statusFilter === 'available') {
            $query->whereRaw('capacity > (SELECT COUNT(*) FROM accommodation_assignments WHERE accommodation_id = accommodations.id AND status = "active")');
        }

        $this->applySortToQuery($query);

        return view('livewire.accommodations-table', [
            'accommodations' => $query->paginate(10),
        ]);
    }
}
