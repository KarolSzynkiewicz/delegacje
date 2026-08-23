<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithSortableTable;
use App\Models\Rotation;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class RotationsTable extends Component
{
    use InteractsWithSortableTable;
    use WithPagination;

    public $search = '';

    public $statusFilter = '';

    public $sortField = 'end_date';

    public $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'sortField' => ['except' => 'end_date'],
        'sortDirection' => ['except' => 'asc'],
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
        $this->sortField = 'end_date';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'vendor.livewire.simple-pagination';
    }

    protected function sortableFields(): array
    {
        return ['employee_id', 'start_date', 'end_date'];
    }

    public function render()
    {
        $query = Rotation::with('employee');

        if (! empty($this->search)) {
            $searchTerm = trim($this->search);
            $query->whereHas('employee', function (Builder $q) use ($searchTerm) {
                $q->where(function ($query) use ($searchTerm) {
                    $query->where('first_name', 'like', '%'.$searchTerm.'%')
                        ->orWhere('last_name', 'like', '%'.$searchTerm.'%')
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%'.$searchTerm.'%']);
                });
            });
        }

        if (! empty($this->statusFilter)) {
            match ($this->statusFilter) {
                'scheduled' => $query->scheduled(),
                'active' => $query->active(),
                'completed' => $query->completed(),
                'cancelled' => $query->where('status', 'cancelled'),
                default => null,
            };
        }

        $this->applySortToQuery($query);

        return view('livewire.rotations-table', [
            'rotations' => $query->paginate(20),
        ]);
    }
}
