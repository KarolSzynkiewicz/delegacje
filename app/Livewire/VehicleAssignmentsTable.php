<?php

namespace App\Livewire;

use App\Livewire\Concerns\ScopesToEmployee;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class VehicleAssignmentsTable extends Component
{
    use ScopesToEmployee;
    use WithoutUrlPagination;
    use WithPagination;

    public $searchEmployee = '';

    public $searchVehicle = '';

    public $vehicleFilter = '';

    public $statusFilter = '';

    protected function queryString(): array
    {
        return $this->scopedQueryString([
            'searchEmployee' => ['except' => ''],
            'searchVehicle' => ['except' => ''],
            'vehicleFilter' => ['except' => ''],
            'statusFilter' => ['except' => ''],
        ]);
    }

    public function updating($name, $value)
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        if (! $this->isEmployeeScoped()) {
            $this->searchEmployee = '';
        }
        $this->searchVehicle = '';
        $this->vehicleFilter = '';
        $this->statusFilter = '';
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return (bool) ((! $this->isEmployeeScoped() && $this->searchEmployee)
            || $this->searchVehicle
            || $this->vehicleFilter
            || $this->statusFilter);
    }

    public function paginationView()
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function render()
    {
        $query = VehicleAssignment::with(['employee', 'vehicle'])->orderBy('start_date', 'desc');

        if ($this->isEmployeeScoped()) {
            $query->where('employee_id', $this->employeeId);
        } elseif ($this->searchEmployee) {
            $query->whereHas('employee', function ($q) {
                $q->where('first_name', 'like', '%'.$this->searchEmployee.'%')
                    ->orWhere('last_name', 'like', '%'.$this->searchEmployee.'%');
            });
        }

        if ($this->vehicleFilter !== '' && $this->vehicleFilter !== null) {
            $query->where('vehicle_id', (int) $this->vehicleFilter);
        }

        if ($this->searchVehicle) {
            $query->whereHas('vehicle', function ($q) {
                $q->where('registration_number', 'like', '%'.$this->searchVehicle.'%')
                    ->orWhere('brand', 'like', '%'.$this->searchVehicle.'%')
                    ->orWhere('model', 'like', '%'.$this->searchVehicle.'%');
            });
        }

        $today = Carbon::today();
        if ($this->statusFilter === 'active') {
            $query->where('start_date', '<=', $today)
                ->where(function ($q) use ($today) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
                });
        } elseif ($this->statusFilter === 'scheduled') {
            $query->where('start_date', '>', $today);
        } elseif ($this->statusFilter === 'completed') {
            $query->whereNotNull('end_date')->where('end_date', '<', $today);
        }

        return view('livewire.vehicle-assignments-table', [
            'assignments' => $query->paginate(20),
            'vehicles' => Vehicle::orderBy('registration_number')->get(),
        ]);
    }
}
