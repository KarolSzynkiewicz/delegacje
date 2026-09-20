<?php

namespace App\Livewire;

use App\Livewire\Concerns\ClosesAndParksSprints;
use App\Livewire\Concerns\InteractsWithSortableTable;
use App\Models\Sprint;
use Livewire\Component;
use Livewire\WithPagination;

class SprintsTable extends Component
{
    use ClosesAndParksSprints;
    use InteractsWithSortableTable;
    use WithPagination;

    public string $search = '';

    /** @var list<string> */
    public array $statusFilters = [Sprint::BOARD_ACTIVE, Sprint::BOARD_UPCOMING];

    public string $sortField = 'start_date';

    public string $sortDirection = 'desc';

    public ?string $flash = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilters' => ['except' => [Sprint::BOARD_ACTIVE, Sprint::BOARD_UPCOMING]],
        'sortField' => ['except' => 'start_date'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(string $status): void
    {
        if (! in_array($status, Sprint::BOARD_STATUSES, true)) {
            return;
        }

        if (in_array($status, $this->statusFilters, true)) {
            if (count($this->statusFilters) === 1) {
                return;
            }
            $this->statusFilters = array_values(array_filter(
                $this->statusFilters,
                fn (string $value) => $value !== $status
            ));
        } else {
            $this->statusFilters[] = $status;
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilters = [Sprint::BOARD_ACTIVE, Sprint::BOARD_UPCOMING];
        $this->sortField = 'start_date';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function resetStatusFilters(): void
    {
        $this->statusFilters = [Sprint::BOARD_ACTIVE, Sprint::BOARD_UPCOMING];
        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function canMutate(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->isAdmin() || $user->hasPermission('tasks.update');
    }

    public function render()
    {
        $query = Sprint::query()
            ->with([
                'tasks:id,sprint_id,status,assigned_to,category',
                'tasks.assignedTo:id,name,image_path',
                'readinessItems:id,sprint_id,completed_at',
                'doneItems:id,sprint_id,completed_at',
                'milestones:id,sprint_id,completed_at',
            ])
            ->boardStatus($this->statusFilters);

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($inner) use ($term) {
                $inner->where('name', 'like', $term)
                    ->orWhere('goal', 'like', $term);
            });
        }

        $this->applySortToQuery($query);

        $sprints = $query->paginate(20);

        return view('livewire.sprints-table', [
            'sprints' => $sprints,
            'canMutate' => $this->canMutate(),
            'hasFilters' => $this->hasActiveFilters(),
            'hasAnySprints' => $sprints->total() > 0 || Sprint::query()->exists(),
        ]);
    }

    protected function sortableFields(): array
    {
        return ['name', 'start_date', 'end_date'];
    }

    protected function assertCanMutateSprints(): void
    {
        if (! $this->canMutate()) {
            abort(403);
        }
    }

    protected function onSprintLifecycleChanged(Sprint $sprint, string $action): void
    {
        $this->flash = match ($action) {
            'closed' => 'Sprint „'.$sprint->name.'” został zakończony.',
            'parked' => 'Sprint „'.$sprint->name.'” odstawiony na później.',
            'unparked' => 'Sprint „'.$sprint->name.'” wrócił na listę.',
            default => null,
        };

        if ($action === 'parked' && ! in_array(Sprint::BOARD_LATER, $this->statusFilters, true)) {
            $this->flash .= ' Włącz filtr „Później”, żeby go zobaczyć.';
        }

        if ($action === 'closed' && ! in_array(Sprint::BOARD_CLOSED, $this->statusFilters, true)) {
            $this->flash .= ' Włącz filtr „Zakończony”, żeby go zobaczyć.';
        }
    }

    private function hasActiveFilters(): bool
    {
        if ($this->search !== '') {
            return true;
        }

        $current = $this->statusFilters;
        sort($current);

        return $current !== [Sprint::BOARD_ACTIVE, Sprint::BOARD_UPCOMING];
    }
}
