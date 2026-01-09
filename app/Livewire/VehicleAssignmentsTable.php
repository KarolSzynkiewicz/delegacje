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
    public $dateFrom = '';
    public $dateTo = '';

    protected $queryString = [
        'searchEmployee' => ['except' => ''],
        'searchVehicle' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function updatingSearchEmployee()
    {
        $this->resetPage();
    }

    public function updatingSearchVehicle()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->searchEmployee = '';
        $this->searchVehicle = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
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

        // Filter by date range
        if ($this->dateFrom) {
            $query->where('start_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where(function ($q) {
                $q->where('end_date', '<=', $this->dateTo)
                  ->orWhereNull('end_date');
            });
        }

        $assignments = $query->paginate(20);

        return view('livewire.vehicle-assignments-table', [
            'assignments' => $assignments,
        ]);
    }
}
