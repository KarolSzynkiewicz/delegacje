<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithSortableTable;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserRolesTable extends Component
{
    use InteractsWithSortableTable;
    use WithPagination;

    public string $search = '';

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

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

    protected function sortableFields(): array
    {
        return ['name'];
    }

    public function render()
    {
        $query = Role::query()->with('permissions')->withCount('users');

        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhereHas('permissions', fn ($pq) => $pq->where('name', 'like', '%'.$term.'%'));
            });
        }

        $this->applySortToQuery($query);

        return view('livewire.user-roles-table', [
            'userRoles' => $query->paginate(15),
            'permissionCount' => Permission::query()->count(),
        ]);
    }
}
