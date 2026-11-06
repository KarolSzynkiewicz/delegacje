<?php

namespace App\Livewire;

use App\Enums\ClientType;
use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class ClientsTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

    public string $statusFilter = 'active';

    /** @var string all|client|service_provider */
    public string $typeFilter = 'all';

    /** @var string all|followups */
    public string $attentionFilter = 'all';

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
        'statusFilter' => ['except' => 'active'],
        'typeFilter' => ['except' => 'all'],
        'attentionFilter' => ['except' => 'all'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAttentionFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->sortField = 'name';
        $this->sortDirection = 'asc';
        $this->statusFilter = 'active';
        $this->typeFilter = 'all';
        $this->attentionFilter = 'all';
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
        $query = Client::query()
            ->with(['primaryContact'])
            ->withCount('projects')
            ->withCount([
                'contactAttempts as due_followups_count' => function (Builder $q) {
                    $q->whereNotNull('next_action_at')
                        ->where('next_action_at', '<=', now()->endOfDay()->addDays(3));
                },
                'contactAttempts as overdue_followups_count' => function (Builder $q) {
                    $q->whereNotNull('next_action_at')
                        ->where('next_action_at', '<', now());
                },
            ])
            ->withMax('contactAttempts as last_contact_at', 'contacted_at');

        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        if (in_array($this->typeFilter, [ClientType::Client->value, ClientType::ServiceProvider->value], true)) {
            $query->ofType($this->typeFilter);
        }

        if ($this->attentionFilter === 'followups') {
            $query->whereHas('contactAttempts', function (Builder $q) {
                $q->whereNotNull('next_action_at')
                    ->where('next_action_at', '<=', now()->endOfDay()->addDays(3));
            });
        }

        if ($this->search !== '') {
            $searchTerm = trim($this->search);
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('short_name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('nip', 'like', '%'.$searchTerm.'%')
                    ->orWhere('city', 'like', '%'.$searchTerm.'%')
                    ->orWhere('contact_person', 'like', '%'.$searchTerm.'%')
                    ->orWhereHas('contacts', function (Builder $contactQuery) use ($searchTerm) {
                        $contactQuery->where('name', 'like', '%'.$searchTerm.'%')
                            ->orWhere('email', 'like', '%'.$searchTerm.'%')
                            ->orWhere('phone', 'like', '%'.$searchTerm.'%');
                    });
            });
        }

        $allowedSort = ['name', 'created_at', 'projects_count', 'overdue_followups_count', 'due_followups_count'];
        $sortField = in_array($this->sortField, $allowedSort, true) ? $this->sortField : 'name';

        if ($this->attentionFilter === 'followups') {
            $query->orderByDesc('overdue_followups_count')
                ->orderByDesc('due_followups_count');
        }

        $query->orderBy($sortField, $this->sortDirection === 'desc' ? 'desc' : 'asc');

        return view('livewire.clients-table', [
            'clients' => $query->paginate(20),
            'typeOptions' => ClientType::options(),
        ]);
    }
}
