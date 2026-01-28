<?php

namespace App\Livewire;

use App\Models\EmployeeRate;
use App\Models\Employee;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;

class EmployeeRatesTable extends Component
{
    use WithPagination;

    public $search = '';
    public $currencyFilter = '';
    public $sortField = 'start_date';
    public $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'currencyFilter' => ['except' => ''],
        'sortField' => ['except' => 'start_date'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCurrencyFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->currencyFilter = '';
        $this->sortField = 'start_date';
        $this->sortDirection = 'desc';
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
        $query = EmployeeRate::with('employee');

        // Wyszukiwanie po pracowniku
        if (!empty($this->search)) {
            $searchTerm = trim($this->search);
            $query->whereHas('employee', function (Builder $q) use ($searchTerm) {
                $q->where(function ($query) use ($searchTerm) {
                    $query->where('first_name', 'like', '%' . $searchTerm . '%')
                          ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                          ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $searchTerm . '%']);
                });
            });
        }

        // Filtrowanie po walucie
        if (!empty($this->currencyFilter)) {
            $query->where('currency', $this->currencyFilter);
        }

        // Sortowanie
        $query->orderBy($this->sortField, $this->sortDirection);

        $rates = $query->paginate(20);

        return view('livewire.employee-rates-table', [
            'rates' => $rates,
        ]);
    }
}
