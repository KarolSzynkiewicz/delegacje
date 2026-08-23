<?php

namespace App\Livewire;

use App\Enums\LocationPurposeType;
use App\Livewire\Concerns\InteractsWithSortableTable;
use App\Models\Location;
use Livewire\Component;
use Livewire\WithPagination;

class LocationsTable extends Component
{
    use InteractsWithSortableTable;
    use WithPagination;

    public string $search = '';

    /** @var ''|string purpose enum value */
    public string $purposeFilter = '';

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'purposeFilter' => ['except' => ''],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPurposeFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->purposeFilter = '';
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
        return ['name', 'city', 'address'];
    }

    public function render()
    {
        $query = Location::query()->with('purposes');

        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('address', 'like', '%'.$term.'%')
                    ->orWhere('city', 'like', '%'.$term.'%')
                    ->orWhere('postal_code', 'like', '%'.$term.'%');
            });
        }

        if ($this->purposeFilter !== '') {
            $query->whereHas('purposes', function ($q) {
                $q->where('purpose', $this->purposeFilter);
            });
        }

        $this->applySortToQuery($query);

        $locations = $query->paginate(15);

        return view('livewire.locations-table', [
            'locations' => $locations,
            'purposeTypes' => LocationPurposeType::cases(),
        ]);
    }
}
