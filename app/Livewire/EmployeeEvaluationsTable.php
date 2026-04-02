<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\EmployeeEvaluation;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeeEvaluationsTable extends Component
{
    use WithPagination;

    public $search = '';

    public $employeeFilter = '';

    public $sortField = 'created_at';

    public $sortDirection = 'desc';

    // Optional filter for /mine/* routes
    public $filterEmployeeIds = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'employeeFilter' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingEmployeeFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->employeeFilter = '';
        $this->sortField = 'created_at';
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

    public function paginationView()
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function render()
    {
        $query = EmployeeEvaluation::with(['employee', 'createdBy']);

        // Filtrowanie po pracownikach (dla /mine/*)
        if ($this->filterEmployeeIds && is_array($this->filterEmployeeIds) && ! empty($this->filterEmployeeIds)) {
            $query->whereIn('employee_id', $this->filterEmployeeIds);
        }

        // Filtrowanie po pracowniku (select przekazuje ID)
        if ($this->employeeFilter !== '' && $this->employeeFilter !== null) {
            $query->where('employee_id', (int) $this->employeeFilter);
        }

        // Wyszukiwanie (po uwagach)
        if ($this->search) {
            $query->where('notes', 'like', '%'.$this->search.'%');
        }

        // Sortowanie
        $query->orderBy($this->sortField, $this->sortDirection);

        $evaluations = $query->paginate(10);
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();

        return view('livewire.employee-evaluations-table', [
            'evaluations' => $evaluations,
            'employees' => $employees,
        ]);
    }
}
