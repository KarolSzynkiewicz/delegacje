<?php

namespace App\Support;

use App\Models\Company;
use App\Models\CostEntry;
use App\Models\FixedCostEntry;
use App\Models\ProjectVariableCost;
use App\Models\VehicleRepair;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;

/**
 * Filtr i sortowanie rejestru kosztów — jedno miejsce dla tabeli Livewire
 * i eksportu CSV, żeby plik zawsze odpowiadał temu, co widać na ekranie.
 */
class CostRegistryListing
{
    /** Wartość filtra spółki oznaczająca pozycje bez przypisanej spółki. */
    public const COMPANY_NONE = 'none';

    /** @var list<string> */
    private const SORTABLE = ['name', 'period', 'source_type', 'company'];

    public function __construct(
        public readonly string $search = '',
        public readonly string $sourceTypeFilter = '',
        public readonly string $companyFilter = '',
        public readonly string $yearFilter = '',
        public readonly string $monthFilter = '',
        public readonly string $irrelevantFilter = '',
        public readonly string $currencyFilter = '',
        public readonly string $sortField = 'period',
        public readonly string $sortDirection = 'desc',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $sortField = self::queryString($request, 'sortField');
        $sortDirection = self::queryString($request, 'sortDirection');

        return new self(
            search: self::queryString($request, 'search'),
            sourceTypeFilter: self::queryString($request, 'sourceTypeFilter'),
            companyFilter: self::queryString($request, 'companyFilter'),
            yearFilter: self::queryString($request, 'yearFilter'),
            monthFilter: self::queryString($request, 'monthFilter'),
            irrelevantFilter: self::queryString($request, 'irrelevantFilter'),
            currencyFilter: self::queryString($request, 'currencyFilter'),
            sortField: in_array($sortField, self::SORTABLE, true) ? $sortField : 'period',
            sortDirection: $sortDirection === 'asc' ? 'asc' : 'desc',
        );
    }

    public function query(): Builder
    {
        // Kolumny kwalifikowane nazwą tabeli — summary() dokleja JOIN
        // do tabel źródłowych, które też mają `name` czy `currency`.
        $query = CostEntry::query();

        if ($this->search !== '') {
            $term = trim($this->search);
            $query->where(function (Builder $q) use ($term) {
                $q->where('cost_entries.name', 'like', '%'.$term.'%')
                    ->orWhere('cost_entries.comment', 'like', '%'.$term.'%');
            });
        }

        if ($this->sourceTypeFilter !== '' && array_key_exists($this->sourceTypeFilter, CostEntry::sourceTypeOptions())) {
            $query->where('cost_entries.source_type', $this->sourceTypeFilter);
        }

        if ($this->companyFilter === self::COMPANY_NONE) {
            $query->whereNull('cost_entries.company_id');
        } elseif ($this->companyFilter !== '' && ctype_digit($this->companyFilter)) {
            $query->where('cost_entries.company_id', (int) $this->companyFilter);
        }

        if ($this->yearFilter !== '' && ctype_digit($this->yearFilter)) {
            $query->where('cost_entries.period_year', (int) $this->yearFilter);
        }

        if ($this->monthFilter !== '' && ctype_digit($this->monthFilter)) {
            $query->where('cost_entries.period_month', (int) $this->monthFilter);
        }

        if ($this->irrelevantFilter === 'yes') {
            $query->where('cost_entries.financially_irrelevant', true);
        } elseif ($this->irrelevantFilter === 'no') {
            $query->where('cost_entries.financially_irrelevant', false);
        }

        if ($this->currencyFilter !== '') {
            $currency = strtoupper(trim($this->currencyFilter));
            $query->whereHasMorph(
                'source',
                array_values(CostEntry::sourceModels()),
                fn (Builder $q) => $q->whereRaw('UPPER(currency) = ?', [$currency])
            );
        }

        return $query;
    }

    /**
     * Zapytanie gotowe do listy i eksportu: relacje źródła plus bieżące sortowanie.
     */
    public function sortedQuery(): Builder
    {
        $query = $this->query()->with([
            'company:id,name',
            'source' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    ProjectVariableCost::class => ['project:id,name'],
                    VehicleRepair::class => ['vehicle:id,registration_number'],
                    FixedCostEntry::class => ['template:id,name'],
                ]);
            },
        ]);

        $this->applySort($query);

        return $query;
    }

    public function applySort(Builder $query): void
    {
        $dir = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        $field = in_array($this->sortField, self::SORTABLE, true) ? $this->sortField : 'period';

        match ($field) {
            'name' => $query->orderBy('cost_entries.name', $dir),
            'source_type' => $query->orderBy('cost_entries.source_type', $dir)->orderBy('cost_entries.name'),
            'company' => $query
                ->orderBy(
                    Company::query()->select('name')->whereColumn('companies.id', 'cost_entries.company_id'),
                    $dir
                )
                ->orderBy('cost_entries.name'),
            default => $query
                ->orderBy('cost_entries.period_year', $dir)
                ->orderBy('cost_entries.period_month', $dir)
                ->orderByDesc('cost_entries.id'),
        };
    }

    /**
     * Fragment nazwy pliku z aktywnych filtrów okresu i waluty.
     */
    public function filenameSuffix(): string
    {
        $parts = array_filter([
            $this->yearFilter !== '' && ctype_digit($this->yearFilter) ? $this->yearFilter : null,
            $this->monthFilter !== '' && ctype_digit($this->monthFilter)
                ? str_pad($this->monthFilter, 2, '0', STR_PAD_LEFT)
                : null,
            $this->currencyFilter !== '' ? strtolower($this->currencyFilter) : null,
        ]);

        return $parts === [] ? '' : '-'.implode('-', $parts);
    }

    private static function queryString(Request $request, string $key): string
    {
        $value = $request->query($key, '');

        return is_string($value) ? trim($value) : '';
    }
}
