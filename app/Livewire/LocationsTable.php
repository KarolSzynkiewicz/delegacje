<?php

namespace App\Livewire;

use App\Enums\LocationPurposeType;
use App\Models\Location;
use Livewire\Component;
use Livewire\WithPagination;

class LocationsTable extends Component
{
    use WithPagination;

    public string $search = '';

    /** @var ''|string purpose enum value */
    public string $purposeFilter = '';

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

    protected $queryString = [
        'search'        => ['except' => ''],
        'purposeFilter' => ['except' => ''],
        'sortField'     => ['except' => 'name'],
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
        $this->search        = '';
        $this->purposeFilter = '';
        $this->sortField     = 'name';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField     = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function render()
    {
        $query = Location::query()->with('purposes');

        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                    ->orWhere('address', 'like', '%' . $term . '%')
                    ->orWhere('city', 'like', '%' . $term . '%')
                    ->orWhere('postal_code', 'like', '%' . $term . '%');
            });
        }

        if ($this->purposeFilter !== '') {
            $query->whereHas('purposes', function ($q) {
                $q->where('purpose', $this->purposeFilter);
            });
        }

        $allowedSort = ['name', 'city', 'address'];
        $field       = in_array($this->sortField, $allowedSort, true) ? $this->sortField : 'name';
        $dir         = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        $query->orderBy($field, $dir);

        $locations = $query->paginate(15);

        return view('livewire.locations-table', [
            'locations'     => $locations,
            'purposeTypes'  => LocationPurposeType::cases(),
        ]);
    }
}
