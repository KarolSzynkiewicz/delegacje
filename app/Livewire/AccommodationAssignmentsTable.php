<?php

namespace App\Livewire;

use App\Livewire\Concerns\ScopesToEmployee;
use App\Models\AccommodationAssignment;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class AccommodationAssignmentsTable extends Component
{
    use ScopesToEmployee;
    use WithoutUrlPagination;
    use WithPagination;

    public $searchEmployee = '';

    public $searchAccommodation = '';

    public $statusFilter = '';

    protected function queryString(): array
    {
        return $this->scopedQueryString([
            'searchEmployee' => ['except' => ''],
            'searchAccommodation' => ['except' => ''],
            'statusFilter' => ['except' => ''],
        ]);
    }

    public function updatingSearchEmployee()
    {
        $this->resetPage();
    }

    public function updatingSearchAccommodation()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        if (! $this->isEmployeeScoped()) {
            $this->searchEmployee = '';
        }
        $this->searchAccommodation = '';
        $this->statusFilter = '';
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return (bool) ((! $this->isEmployeeScoped() && $this->searchEmployee)
            || $this->searchAccommodation
            || $this->statusFilter);
    }

    public function paginationView()
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function render()
    {
        $query = AccommodationAssignment::with(['employee', 'accommodation'])->orderBy('start_date', 'desc');

        if ($this->isEmployeeScoped()) {
            $query->where('employee_id', $this->employeeId);
        } elseif ($this->searchEmployee) {
            $query->whereHas('employee', function ($q) {
                $q->where('first_name', 'like', '%'.$this->searchEmployee.'%')
                    ->orWhere('last_name', 'like', '%'.$this->searchEmployee.'%');
            });
        }

        if ($this->searchAccommodation) {
            $query->whereHas('accommodation', function ($q) {
                $q->where('name', 'like', '%'.$this->searchAccommodation.'%')
                    ->orWhere('address', 'like', '%'.$this->searchAccommodation.'%');
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

        return view('livewire.accommodation-assignments-table', [
            'assignments' => $query->paginate(20),
        ]);
    }
}
