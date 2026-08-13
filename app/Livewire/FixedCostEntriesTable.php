<?php

namespace App\Livewire;

use App\Models\FixedCostEntry;
use App\Models\FixedCostTemplate;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class FixedCostEntriesTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $categoryFilter = '';

    public string $sortField = 'period_start';

    public string $sortDirection = 'desc';

    /** @var list<string> */
    private const SORTABLE = [
        'name',
        'amount',
        'category',
        'period_start',
        'accounting_date',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
        'sortField' => ['except' => 'period_start'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->categoryFilter = '';
        $this->sortField = 'period_start';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, self::SORTABLE, true)) {
            return;
        }

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
        $query = FixedCostEntry::query()->with('template');

        if ($this->search !== '') {
            $term = trim($this->search);
            $matchingCategoryKeys = collect(FixedCostTemplate::categoryOptions())
                ->filter(fn (string $label): bool => str_contains(mb_strtolower($label), mb_strtolower($term)))
                ->keys()
                ->all();

            $query->where(function (Builder $q) use ($term, $matchingCategoryKeys) {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('notes', 'like', '%'.$term.'%')
                    ->orWhereHas('template', function (Builder $templateQuery) use ($term) {
                        $templateQuery->where('name', 'like', '%'.$term.'%');
                    });

                if ($matchingCategoryKeys !== []) {
                    $q->orWhereIn('category', $matchingCategoryKeys);
                }
            });
        }

        if ($this->categoryFilter !== '') {
            $query->where('category', $this->categoryFilter);
        }

        $field = in_array($this->sortField, self::SORTABLE, true) ? $this->sortField : 'period_start';
        $dir = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        $query->orderBy($field, $dir)
            ->orderByDesc('created_at');

        $hasFilters = $this->search !== '' || $this->categoryFilter !== '';

        return view('livewire.fixed-cost-entries-table', [
            'entries' => $query->paginate(20),
            'categoryOptions' => FixedCostTemplate::categoryOptions(),
            'hasFilters' => $hasFilters,
        ]);
    }
}
