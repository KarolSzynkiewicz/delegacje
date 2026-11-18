<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\CostEntry;
use App\Support\CostRegistryListing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Rejestr kosztów — jedna filtrowalna tablica nad kosztami ogólnofirmowymi,
 * projektowymi, transportu i serwisu, edytowana inline.
 *
 * Każda widoczna pozycja ma swój wpis w `$rows[id]` (nazwa, spółka, miesiąc,
 * komentarz). Zmiana pola w wierszu zapisuje się od razu przez
 * {@see updatedRows()}; kwoty i daty zostają na kartach źródłowych.
 */
class CostRegistryTable extends Component
{
    use WithPagination;

    /** Wartość filtra spółki oznaczająca pozycje bez przypisanej spółki. */
    public const COMPANY_NONE = CostRegistryListing::COMPANY_NONE;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $sourceTypeFilter = '';

    #[Url(except: '')]
    public string $companyFilter = '';

    #[Url(except: '')]
    public string $yearFilter = '';

    #[Url(except: '')]
    public string $monthFilter = '';

    #[Url(except: '')]
    public string $irrelevantFilter = '';

    #[Url(except: '')]
    public string $currencyFilter = '';

    #[Url(except: 'period')]
    public string $sortField = 'period';

    #[Url(except: 'desc')]
    public string $sortDirection = 'desc';

    /**
     * Edytowalne pola pozycji z bieżącej strony: id => [name, company_id, period, comment].
     * `period` to "RRRR-MM" — jeden select zamiast pary rok/miesiąc.
     *
     * @var array<int, array{name: string, company_id: string, period: string, comment: string}>
     */
    public array $rows = [];

    public function updating(string $property): void
    {
        if (str_ends_with($property, 'Filter') || $property === 'search') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search',
            'sourceTypeFilter',
            'companyFilter',
            'yearFilter',
            'monthFilter',
            'irrelevantFilter',
            'currencyFilter',
        ]);
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->sourceTypeFilter !== ''
            || $this->companyFilter !== ''
            || $this->yearFilter !== ''
            || $this->monthFilter !== ''
            || $this->irrelevantFilter !== ''
            || $this->currencyFilter !== '';
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['name', 'period', 'source_type', 'company'], true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = $field === 'period' ? 'desc' : 'asc';
        }

        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'vendor.livewire.simple-pagination';
    }

    // ── Edycja inline ────────────────────────────────────────────────────────

    /**
     * Zapis pojedynczego pola wiersza (hook Livewire dla `rows.{id}.{field}`).
     */
    public function updatedRows(mixed $value, string $key): void
    {
        $this->ensureCanUpdate();

        [$id, $field] = array_pad(explode('.', $key, 2), 2, null);
        if (! ctype_digit((string) $id) || $field === null) {
            return;
        }

        $entry = CostEntry::query()->findOrFail((int) $id);
        $value = is_string($value) ? trim($value) : $value;

        switch ($field) {
            case 'name':
                $this->validateInline("rows.{$id}.name", ['required', 'string', 'max:255'], 'nazwa');
                $entry->update(['name' => $value]);
                break;

            case 'company_id':
                $this->validateInline("rows.{$id}.company_id", ['nullable', 'integer', 'exists:companies,id'], 'spółka');
                $entry->update(['company_id' => $value === '' || $value === null ? null : (int) $value]);
                break;

            case 'period':
                $this->validateInline("rows.{$id}.period", ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'], 'miesiąc');
                [$year, $month] = explode('-', (string) $value);
                $entry->update(['period_year' => (int) $year, 'period_month' => (int) $month]);
                break;

            case 'comment':
                $this->validateInline("rows.{$id}.comment", ['nullable', 'string', 'max:5000'], 'komentarz');
                $entry->update(['comment' => $value === '' || $value === null ? null : $value]);
                break;

            default:
                return;
        }

        $this->dispatch('cost-registry-saved', id: (int) $id, field: $field);
    }

    /**
     * @param  list<string|Rule>  $rules
     */
    private function validateInline(string $attribute, array $rules, string $label): void
    {
        $this->resetErrorBag($attribute);
        $this->validateOnly($attribute, [$attribute => $rules], [], [$attribute => $label]);
    }

    public function toggleIrrelevant(int $id): void
    {
        $this->ensureCanUpdate();

        $entry = CostEntry::query()->findOrFail($id);
        $entry->update(['financially_irrelevant' => ! $entry->financially_irrelevant]);

        $this->dispatch('cost-registry-saved', id: $id, field: 'financially_irrelevant');
    }

    public function canUpdate(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->hasPermission('cost-registry.update');
    }

    private function ensureCanUpdate(): void
    {
        abort_unless($this->canUpdate(), 403);
    }

    // ── Zapytanie ────────────────────────────────────────────────────────────

    protected function listing(): CostRegistryListing
    {
        return new CostRegistryListing(
            search: $this->search,
            sourceTypeFilter: $this->sourceTypeFilter,
            companyFilter: $this->companyFilter,
            yearFilter: $this->yearFilter,
            monthFilter: $this->monthFilter,
            irrelevantFilter: $this->irrelevantFilter,
            currencyFilter: $this->currencyFilter,
            sortField: $this->sortField,
            sortDirection: $this->sortDirection,
        );
    }

    protected function baseQuery(): Builder
    {
        return $this->listing()->query();
    }

    /**
     * Podsumowanie bieżącego filtra: suma per waluta (bez pozycji nieistotnych),
     * liczba pozycji, ile bez spółki, ile nieistotnych.
     *
     * @return array{totals: array<string, float>, count: int, without_company: int, irrelevant: int}
     */
    protected function summary(): array
    {
        $totals = [];

        $sources = [
            CostEntry::SOURCE_FIXED => ['fixed_cost_entries', 'amount'],
            CostEntry::SOURCE_PROJECT => ['project_variable_costs', 'amount'],
            CostEntry::SOURCE_TRANSPORT => ['transport_costs', 'amount'],
            CostEntry::SOURCE_REPAIR => ['vehicle_repairs', 'price'],
        ];

        foreach ($sources as $alias => [$table, $column]) {
            if ($this->sourceTypeFilter !== '' && $this->sourceTypeFilter !== $alias) {
                continue;
            }

            $rows = $this->baseQuery()
                ->where('cost_entries.source_type', $alias)
                ->where('cost_entries.financially_irrelevant', false)
                ->join($table, "{$table}.id", '=', 'cost_entries.source_id')
                ->whereNotNull("{$table}.{$column}")
                ->groupBy(DB::raw("UPPER({$table}.currency)"))
                ->get([
                    DB::raw("UPPER({$table}.currency) as currency"),
                    DB::raw("SUM({$table}.{$column}) as total"),
                ]);

            foreach ($rows as $row) {
                $currency = $row->currency ?: 'PLN';
                $totals[$currency] = round(($totals[$currency] ?? 0.0) + (float) $row->total, 2);
            }
        }

        ksort($totals);

        $counts = $this->baseQuery()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN cost_entries.company_id IS NULL THEN 1 ELSE 0 END) as without_company')
            ->selectRaw('SUM(CASE WHEN cost_entries.financially_irrelevant = 1 THEN 1 ELSE 0 END) as irrelevant')
            ->first();

        return [
            'totals' => $totals,
            'count' => (int) ($counts->total ?? 0),
            'without_company' => (int) ($counts->without_company ?? 0),
            'irrelevant' => (int) ($counts->irrelevant ?? 0),
        ];
    }

    /**
     * Opcje selecta miesiąca rozliczeniowego: "RRRR-MM" => "Styczeń 2026", od najnowszych.
     *
     * @param  list<int>  $years
     * @return array<string, string>
     */
    public static function periodOptions(array $years): array
    {
        $months = CostEntry::monthOptions();
        $options = [];

        foreach ($years as $year) {
            for ($m = 12; $m >= 1; $m--) {
                $options[sprintf('%04d-%02d', $year, $m)] = $months[$m].' '.$year;
            }
        }

        return $options;
    }

    public function render()
    {
        $entries = $this->listing()->sortedQuery()->paginate(25);

        // Pola inline odświeżane z bazy przy każdym renderze — zmiany są już zapisane,
        // więc nic nie ginie, a wiersz zawsze pokazuje stan po walidacji.
        $this->rows = [];
        foreach ($entries as $entry) {
            $this->rows[$entry->id] = [
                'name' => $entry->name,
                'company_id' => $entry->company_id ? (string) $entry->company_id : '',
                'period' => sprintf('%04d-%02d', $entry->period_year, $entry->period_month),
                'comment' => (string) ($entry->comment ?? ''),
            ];
        }

        $bounds = CostEntry::query()
            ->selectRaw('MIN(period_year) as min_year, MAX(period_year) as max_year')
            ->first();
        $currentYear = (int) now()->format('Y');
        $minYear = min((int) ($bounds->min_year ?: $currentYear), $currentYear) - 1;
        $maxYear = max((int) ($bounds->max_year ?: $currentYear), $currentYear) + 1;
        $years = range($maxYear, $minYear);

        $companies = Company::query()->orderBy('name')->get(['id', 'name']);

        return view('livewire.cost-registry-table', [
            'entries' => $entries,
            'summary' => $this->summary(),
            'years' => $years,
            'periodOptions' => self::periodOptions($years),
            'companies' => $companies,
            'sourceTypeOptions' => CostEntry::sourceTypeOptions(),
            'monthOptions' => CostEntry::monthOptions(),
            'hasFilters' => $this->hasActiveFilters(),
            'canUpdate' => $this->canUpdate(),
        ]);
    }
}
