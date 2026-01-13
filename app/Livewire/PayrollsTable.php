<?php

namespace App\Livewire;

use App\Models\Payroll;
use App\Models\Employee;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;

class PayrollsTable extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $currencyFilter = '';
    public $sortField = 'period_start';
    public $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'currencyFilter' => ['except' => ''],
        'sortField' => ['except' => 'period_start'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
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
        $this->statusFilter = '';
        $this->currencyFilter = '';
        $this->sortField = 'period_start';
        $this->sortDirection = 'desc';
        $this->resetPage();
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
        $query = Payroll::with('employee');

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

        // Filtrowanie po statusie
        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        // Filtrowanie po walucie
        if (!empty($this->currencyFilter)) {
            $query->where('currency', $this->currencyFilter);
        }

        // Sortowanie
        $query->orderBy($this->sortField, $this->sortDirection);

        $payrolls = $query->paginate(20);

        return view('livewire.payrolls-table', [
            'payrolls' => $payrolls,
        ]);
    }
}
