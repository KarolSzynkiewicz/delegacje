<?php

namespace App\Livewire;

use App\Models\VehicleAssignment;
use App\Models\Employee;
use App\Models\Vehicle;
use Livewire\Component;
use Livewire\WithPagination;

class VehicleAssignmentsTable extends Component
{
    use WithPagination;

    public $searchEmployee = '';
    public $searchVehicle = '';
    public $statusFilter = '';

    protected $queryString = [
        'searchEmployee' => ['except' => ''],
        'searchVehicle' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function updatingSearchEmployee()
    {
        $this->resetPage();
    }

    public function updatingSearchVehicle()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->searchEmployee = '';
        $this->searchVehicle = '';
        $this->statusFilter = '';
        $this->resetPage();
    }

    public function paginationView()
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function render()
    {
        $query = VehicleAssignment::with(['employee', 'vehicle'])
            ->orderBy('start_date', 'asc');

        // Filter by employee
        if ($this->searchEmployee) {
            $query->whereHas('employee', function ($q) {
                $q->where('first_name', 'like', '%' . $this->searchEmployee . '%')
                  ->orWhere('last_name', 'like', '%' . $this->searchEmployee . '%');
            });
        }

        // Filter by vehicle
        if ($this->searchVehicle) {
            $query->whereHas('vehicle', function ($q) {
                $q->where('registration_number', 'like', '%' . $this->searchVehicle . '%')
                  ->orWhere('brand', 'like', '%' . $this->searchVehicle . '%')
                  ->orWhere('model', 'like', '%' . $this->searchVehicle . '%');
            });
        }

        // Filter by status
        if ($this->statusFilter === 'active') {
            $today = \Carbon\Carbon::today();
            $query->where('start_date', '<=', $today)
                  ->where(function ($q) use ($today) {
                      $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', $today);
                  });
        } elseif ($this->statusFilter === 'scheduled') {
            $today = \Carbon\Carbon::today();
            $query->where('start_date', '>', $today);
        } elseif ($this->statusFilter === 'completed') {
            $today = \Carbon\Carbon::today();
            $query->whereNotNull('end_date')
                  ->where('end_date', '<', $today);
        }
        // Note: 'cancelled' filter removed - assignments are physically deleted when cancelled

        $assignments = $query->paginate(20);

        return view('livewire.vehicle-assignments-table', [
            'assignments' => $assignments,
        ]);
    }
}
