<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithSortableTable;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UsersTable extends Component
{
    use InteractsWithSortableTable;
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'roleFilter' => ['except' => ''],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->roleFilter = '';
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
        return ['name', 'email'];
    }

    public function render()
    {
        $query = User::query()->with(['roles', 'managedProjects']);

        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%')
                    ->orWhereHas('roles', fn ($rq) => $rq->where('name', 'like', '%'.$term.'%'));
            });
        }

        if ($this->roleFilter !== '') {
            $query->whereHas('roles', fn ($q) => $q->where('name', $this->roleFilter));
        }

        $this->applySortToQuery($query);

        $roles = Role::query()->orderBy('name')->get();

        return view('livewire.users-table', [
            'users' => $query->paginate(15),
            'roles' => $roles,
        ]);
    }
}
