<?php

namespace App\Livewire;

use App\Models\CompanyAssignment;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class CompanyAssignmentsTable extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $sortField = 'start_date';
    public $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'sortField' => ['except' => 'start_date'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->sortField = 'start_date';
        $this->sortDirection = 'desc';
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
        $query = CompanyAssignment::with(['employee', 'company']);

        if (! empty($this->search)) {
            $searchTerm = trim($this->search);
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->whereHas('employee', function (Builder $eq) use ($searchTerm) {
                    $eq->where('first_name', 'like', '%'.$searchTerm.'%')
                        ->orWhere('last_name', 'like', '%'.$searchTerm.'%')
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%'.$searchTerm.'%']);
                })->orWhereHas('company', function (Builder $cq) use ($searchTerm) {
                    $cq->where('name', 'like', '%'.$searchTerm.'%')
                        ->orWhere('nip', 'like', '%'.$searchTerm.'%');
                });
            });
        }

        if ($this->statusFilter === 'active') {
            $query->active();
        } elseif ($this->statusFilter === 'completed') {
            $query->completed();
        } elseif ($this->statusFilter === 'scheduled') {
            $query->scheduled();
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        return view('livewire.company-assignments-table', [
            'assignments' => $query->paginate(20),
        ]);
    }
}
