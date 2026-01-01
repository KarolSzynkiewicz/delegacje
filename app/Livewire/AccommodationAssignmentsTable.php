<?php

namespace App\Livewire;

use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\Accommodation;
use Livewire\Component;
use Livewire\WithPagination;

class AccommodationAssignmentsTable extends Component
{
    use WithPagination;

    public $searchEmployee = '';
    public $searchAccommodation = '';
    public $dateFrom = '';
    public $dateTo = '';

    protected $queryString = [
        'searchEmployee' => ['except' => ''],
        'searchAccommodation' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function updatingSearchEmployee()
    {
        $this->resetPage();
    }

    public function updatingSearchAccommodation()
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
        $this->searchAccommodation = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = AccommodationAssignment::with(['employee', 'accommodation'])
            ->orderBy('start_date', 'desc');

        // Filter by employee
        if ($this->searchEmployee) {
            $query->whereHas('employee', function ($q) {
                $q->where('first_name', 'like', '%' . $this->searchEmployee . '%')
                  ->orWhere('last_name', 'like', '%' . $this->searchEmployee . '%');
            });
        }

        // Filter by accommodation
        if ($this->searchAccommodation) {
            $query->whereHas('accommodation', function ($q) {
                $q->where('name', 'like', '%' . $this->searchAccommodation . '%')
                  ->orWhere('address', 'like', '%' . $this->searchAccommodation . '%');
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

        return view('livewire.accommodation-assignments-table', [
            'assignments' => $assignments,
        ]);
    }
}
