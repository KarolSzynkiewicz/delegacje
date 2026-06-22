<?php

namespace App\Livewire;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class CompaniesTable extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->sortField = 'name';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function sortBy(string $field): void
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
        $query = Company::query();

        if (! empty($this->search)) {
            $searchTerm = trim($this->search);
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('nip', 'like', '%'.$searchTerm.'%')
                    ->orWhere('regon', 'like', '%'.$searchTerm.'%')
                    ->orWhere('city', 'like', '%'.$searchTerm.'%')
                    ->orWhere('president_name', 'like', '%'.$searchTerm.'%');
            });
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        return view('livewire.companies-table', [
            'companies' => $query->paginate(20),
        ]);
    }
}
