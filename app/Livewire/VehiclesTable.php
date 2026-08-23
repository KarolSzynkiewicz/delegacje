<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithSortableTable;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class VehiclesTable extends Component
{
    use InteractsWithSortableTable;
    use WithPagination;

    public $search = '';

    public $conditionFilter = '';

    public $statusFilter = '';

    public $locationFilter = '';

    public $statusDate = '';

    public $sortField = 'registration_number';

    public $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'conditionFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'locationFilter' => ['except' => ''],
        'statusDate' => ['except' => ''],
        'sortField' => ['except' => 'registration_number'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingConditionFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingLocationFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusDate(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->conditionFilter = '';
        $this->statusFilter = '';
        $this->locationFilter = '';
        $this->statusDate = '';
        $this->sortField = 'registration_number';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'vendor.livewire.simple-pagination';
    }

    protected function sortableFields(): array
    {
        return ['registration_number', 'brand', 'model'];
    }

    public function render()
    {
        $query = Vehicle::query();
        $checkDate = $this->statusDate ? \Carbon\Carbon::parse($this->statusDate) : now();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('registration_number', 'like', '%'.$this->search.'%')
                    ->orWhere('brand', 'like', '%'.$this->search.'%')
                    ->orWhere('model', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->conditionFilter) {
            $query->where('technical_condition', $this->conditionFilter);
        }

        if ($this->statusFilter === 'occupied') {
            $query->whereHas('assignments', function ($q) use ($checkDate) {
                $q->where('start_date', '<=', $checkDate)
                    ->where(fn ($q2) => $q2->whereNull('end_date')->orWhere('end_date', '>=', $checkDate));
            });
        } elseif ($this->statusFilter === 'available') {
            $query->whereDoesntHave('assignments', function ($q) use ($checkDate) {
                $q->where('start_date', '<=', $checkDate)
                    ->where(fn ($q2) => $q2->whereNull('end_date')->orWhere('end_date', '>=', $checkDate));
            });
        }

        $this->applySortToQuery($query);

        if ($this->locationFilter) {
            $allVehicles = $query->get();
            $locationTracker = app(\App\Services\LocationTrackingService::class);

            $filteredVehicles = $allVehicles->filter(function ($vehicle) use ($locationTracker, $checkDate) {
                $status = $locationTracker->getVehicleLocationStatus($vehicle, $checkDate);

                return match ($this->locationFilter) {
                    'base' => ! $status['outside_base'] && ! $status['in_transit'],
                    'transit' => $status['in_transit'],
                    'field' => $status['outside_base'] && ! $status['in_transit'],
                    default => true,
                };
            });

            $currentPage = $this->getPage();
            $perPage = 10;
            $vehicles = new LengthAwarePaginator(
                $filteredVehicles->slice(($currentPage - 1) * $perPage, $perPage)->values(),
                $filteredVehicles->count(),
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        } else {
            $query->addSelect([
                'unique_employees_count' => VehicleAssignment::select(DB::raw('count(distinct employee_id)'))
                    ->whereColumn('vehicle_id', 'vehicles.id')
                    ->where('start_date', '<=', $checkDate)
                    ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $checkDate)),
            ]);

            $vehicles = $query->paginate(10);
        }

        return view('livewire.vehicles-table', [
            'vehicles' => $vehicles,
            'checkDate' => $checkDate,
        ]);
    }
}
