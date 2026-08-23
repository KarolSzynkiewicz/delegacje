<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithSortableTable;
use App\Models\Location;
use App\Models\Project;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectsTable extends Component
{
    use InteractsWithSortableTable;
    use WithPagination;

    public $search = '';

    public $statusFilter = '';

    public $locationFilter = '';

    public $sortField = 'name';

    public $sortDirection = 'asc';

    public $filterProjectIds = null;

    public $isMineView = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'locationFilter' => ['except' => ''],
        'sortField' => ['except' => 'name'],
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

    public function updatingLocationFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->locationFilter = '';
        $this->sortField = 'name';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'vendor.livewire.simple-pagination';
    }

    protected function sortableFields(): array
    {
        return ['name', 'status', 'type', 'start_date', 'end_date'];
    }

    public function render()
    {
        $query = Project::with('location');

        if ($this->filterProjectIds && is_array($this->filterProjectIds) && ! empty($this->filterProjectIds)) {
            $query->whereIn('id', $this->filterProjectIds);
            $this->isMineView = true;
        }

        if ($this->search) {
            $searchTerm = trim($this->search);
            if (strlen($searchTerm) >= 2) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', '%'.$searchTerm.'%')
                        ->orWhere('client_name', 'like', '%'.$searchTerm.'%');
                });
            }
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->locationFilter) {
            $query->where('location_id', $this->locationFilter);
        }

        $this->applySortToQuery($query);

        $locations = cache()->remember('locations_list', 3600, fn () => Location::orderBy('name')->get());

        return view('livewire.projects-table', [
            'projects' => $query->paginate(15),
            'locations' => $locations,
            'statuses' => \App\Enums\ProjectStatus::cases(),
            'isMineView' => $this->isMineView,
        ]);
    }
}
