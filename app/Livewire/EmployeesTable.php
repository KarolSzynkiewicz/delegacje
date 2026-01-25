<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\Role;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeesTable extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $sortField = 'last_name';
    public $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'roleFilter' => ['except' => ''],
        'sortField' => ['except' => 'last_name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->roleFilter = '';
        $this->sortField = 'last_name';
        $this->sortDirection = 'asc';
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
        $query = Employee::with(['roles', 'assignments.project']);

        // Filtrowanie po imieniu/nazwisku/emailu
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('last_name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        // Filtrowanie po roli
        if ($this->roleFilter) {
            $query->whereHas('roles', function ($q) {
                $q->where('roles.id', $this->roleFilter);
            });
        }

        // Sortowanie
        if ($this->sortField === 'name') {
            $query->orderBy('last_name', $this->sortDirection)
                  ->orderBy('first_name', $this->sortDirection);
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        $employees = $query->paginate(10);
        $roles = Role::orderBy('name')->get();

        return view('livewire.employees-table', [
            'employees' => $employees,
            'roles' => $roles,
        ]);
    }
}
