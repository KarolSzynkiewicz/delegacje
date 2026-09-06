<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithSortableTable;
use App\Models\Accommodation;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class AccommodationsTable extends Component
{
    use InteractsWithSortableTable;
    use WithPagination;

    public $search = '';

    public $statusFilter = '';

    /** @var 'current'|'owned'|'rented'|'ended'|'all' */
    public string $tenureFilter = 'current';

    public string $statusDate = '';

    public $sortField = 'name';

    public $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'tenureFilter' => ['except' => 'current'],
        'statusDate' => ['except' => ''],
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

    public function updatingTenureFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusDate(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->tenureFilter = 'current';
        $this->statusDate = '';
        $this->sortField = 'name';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    public function hasExtraFilters(): bool
    {
        return $this->search !== ''
            || $this->statusFilter !== ''
            || $this->tenureFilter !== 'current'
            || $this->statusDate !== '';
    }

    public function paginationView(): string
    {
        return 'vendor.livewire.simple-pagination';
    }

    protected function sortableFields(): array
    {
        return ['name'];
    }

    public function render(): View
    {
        $checkDate = $this->statusDate ? Carbon::parse($this->statusDate) : now();
        $day = $checkDate->toDateString();
        $occupiedSql = '(SELECT COUNT(*) FROM accommodation_assignments WHERE accommodation_id = accommodations.id AND start_date <= ? AND (end_date IS NULL OR end_date >= ?))';

        $query = Accommodation::query()
            ->with(['location', 'leases'])
            ->withCount([
                'assignments as occupied_count' => fn (Builder $q) => $q->activeAtDate($checkDate),
            ]);

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhereHas('location', fn ($lq) => $lq
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                    );
            });
        }

        match ($this->tenureFilter) {
            'owned' => $query->owned(),
            'rented' => $query->activelyRented($checkDate),
            'ended' => $query->endedRental($checkDate),
            'all' => null,
            default => $query->currentlyHeld($checkDate),
        };

        if ($this->statusFilter === 'full') {
            $query->whereRaw("capacity <= {$occupiedSql}", [$day, $day]);
        } elseif ($this->statusFilter === 'available') {
            $query->whereRaw("capacity > {$occupiedSql}", [$day, $day]);
        } elseif ($this->statusFilter === 'overfilled') {
            $query->whereRaw("capacity < {$occupiedSql}", [$day, $day]);
        }

        $this->applySortToQuery($query);

        return view('livewire.accommodations-table', [
            'accommodations' => $query->paginate(10),
            'checkDate' => $checkDate,
        ]);
    }
}
