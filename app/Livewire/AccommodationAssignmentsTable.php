<?php

namespace App\Livewire;

use App\Models\AccommodationAssignment;
use App\Models\Employee; //not used
use App\Models\Accommodation; //not used
use Livewire\Component;
use Livewire\WithPagination;

class AccommodationAssignmentsTable extends Component
{
    use WithPagination;

    public $searchEmployee = '';// to wpisal w input- to stan ui- zmiana ui- odswiezenie danych
    public $searchAccommodation = '';
    public $statusFilter = '';

    protected $queryString = [
        'searchEmployee' => ['except' => ''],
        'searchAccommodation' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

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
        $this->searchEmployee = '';
        $this->searchAccommodation = '';
        $this->statusFilter = '';
        $this->resetPage();
    }

    public function paginationView()
    {
        return 'vendor.livewire.simple-pagination';
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

        $assignments = $query->paginate(20);

        return view('livewire.accommodation-assignments-table', [
            'assignments' => $assignments,
        ]);
    }
}
