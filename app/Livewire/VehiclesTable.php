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
    public $locationFilter = '';
    public $statusDate = ''; // Filtr daty
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

    public function updatingLocationFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusDate()
    {
        $this->resetPage();
    }

    public function clearFilters()
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

        // Determine date for status check
        $checkDate = $this->statusDate ? \Carbon\Carbon::parse($this->statusDate) : now();

        // Filtrowanie po statusie (zajęty/wolny) - używa checkDate
        if ($this->statusFilter) {
            if ($this->statusFilter === 'occupied') {
                $query->whereHas('assignments', function ($q) use ($checkDate) {
                    $q->where('start_date', '<=', $checkDate)
                      ->where(function ($q2) use ($checkDate) {
                          $q2->whereNull('end_date')
                            ->orWhere('end_date', '>=', $checkDate);
                      });
                });
            } else {
                $query->whereDoesntHave('assignments', function ($q) use ($checkDate) {
                    $q->where('start_date', '<=', $checkDate)
                      ->where(function ($q2) use ($checkDate) {
                          $q2->whereNull('end_date')
                            ->orWhere('end_date', '>=', $checkDate);
                      });
                });
            }
        }

        // Sortowanie
        $query->orderBy($this->sortField, $this->sortDirection);

        // Filtrowanie po lokalizacji (wymaga sprawdzenia statusu dla każdego pojazdu)
        if ($this->locationFilter) {
            $allVehicles = $query->get();
            $locationTracker = app(\App\Services\LocationTrackingService::class);
            
            $filteredVehicles = $allVehicles->filter(function ($vehicle) use ($locationTracker, $checkDate) {
                $status = $locationTracker->getVehicleLocationStatus($vehicle, $checkDate);
                
                $locationMatch = false;
                if ($this->locationFilter === 'base') {
                    $locationMatch = !$status['outside_base'] && !$status['in_transit'];
                } elseif ($this->locationFilter === 'transit') {
                    $locationMatch = $status['in_transit'];
                } elseif ($this->locationFilter === 'field') {
                    $locationMatch = $status['outside_base'] && !$status['in_transit'];
                }
                
                return $locationMatch;
            });
            
            // Paginate manually
            $currentPage = $this->getPage();
            $perPage = 10;
            $currentPageItems = $filteredVehicles->slice(($currentPage - 1) * $perPage, $perPage)->values();
            
            $vehicles = new \Illuminate\Pagination\LengthAwarePaginator(
                $currentPageItems,
                $filteredVehicles->count(),
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        } else {
        // Załaduj liczbę unikalnych pracowników (dla wyświetlenia X/Y)
        // Liczymy unikalnych pracowników, nie przypisania (jeden pracownik może mieć wiele przypisań)
        $query->addSelect([
            'unique_employees_count' => \App\Models\VehicleAssignment::select(\Illuminate\Support\Facades\DB::raw('count(distinct employee_id)'))
                ->whereColumn('vehicle_id', 'vehicles.id')
                    ->where('start_date', '<=', $checkDate)
                    ->where(function ($q) use ($checkDate) {
                    $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', $checkDate);
                })
        ]);

        $vehicles = $query->paginate(10);
        }

        return view('livewire.vehicles-table', [
            'vehicles' => $vehicles,
            'checkDate' => $checkDate,
        ]);
    }
}
