<?php

namespace App\Http\Controllers;

use App\Livewire\CostRegistryTable;
use App\Models\Company;
use App\Models\CostEntry;
use App\Models\FixedCostEntry;
use App\Models\FixedCostTemplate;
use App\Services\CostRegistryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Rejestr kosztów — jedna tablica nad kosztami ogólnofirmowymi, projektowymi,
 * transportu i serwisu ({@see CostEntry}). Tabelę renderuje Livewire
 * ({@see \App\Livewire\CostRegistryTable}); `update` istnieje po to, by
 * uprawnienie `cost-registry.update` było widoczne w tabeli ról i miało
 * klasyczny punkt zapisu obok akcji Livewire.
 */
class CostRegistryController extends Controller
{
    public function index(): View
    {
        return view('cost-registry.index');
    }

    /**
     * Siatka szybkiego dodawania wielu kosztów (jak „Dodaj wiele” kosztów
     * ogólnofirmowych), ale z polami rejestru: spółka, miesiąc rozliczeniowy,
     * komentarz, nieistotny finansowo. Źródłem jest zawsze koszt ogólnofirmowy
     * ({@see FixedCostEntry}) — koszty projektowe / transportu / serwisu wymagają
     * powiązań (projekt, wyjazd, pojazd) i mają własne formularze.
     */
    public function createMany(Request $request): View
    {
        $now = now();
        $defaultPeriod = sprintf('%04d-%02d', (int) $now->format('Y'), (int) $now->format('n'));

        $years = range((int) $now->format('Y') + 1, (int) $now->format('Y') - 2);

        return view('cost-registry.create-many', [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'periodOptions' => CostRegistryTable::periodOptions($years),
            'defaultPeriod' => $defaultPeriod,
            'defaultCompanyId' => $request->integer('company') ?: null,
            'categoryOptions' => FixedCostTemplate::categoryOptions(),
            'currencies' => ['PLN', 'EUR', 'USD', 'GBP', 'CZK', 'NOK', 'SEK', 'DKK', 'CHF'],
        ]);
    }

    public function storeMany(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.accounting_date' => ['required', 'date'],
            'entries.*.name' => ['required', 'string', 'max:255'],
            'entries.*.amount' => ['required', 'numeric', 'min:0'],
            'entries.*.currency' => ['required', 'string', 'size:3'],
            'entries.*.company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'entries.*.period' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'entries.*.category' => ['nullable', 'string', 'max:64'],
            'entries.*.comment' => ['nullable', 'string', 'max:5000'],
            'entries.*.financially_irrelevant' => ['nullable', 'boolean'],
        ], [], [
            'entries.*.accounting_date' => 'data',
            'entries.*.name' => 'nazwa',
            'entries.*.amount' => 'kwota',
            'entries.*.currency' => 'waluta',
            'entries.*.company_id' => 'spółka',
            'entries.*.period' => 'miesiąc rozliczeniowy',
            'entries.*.category' => 'kategoria',
            'entries.*.comment' => 'komentarz',
        ]);

        $registry = app(CostRegistryService::class);
        $created = 0;

        DB::transaction(function () use ($validated, $registry, &$created) {
            foreach ($validated['entries'] as $row) {
                [$year, $month] = array_map('intval', explode('-', $row['period']));
                $periodStart = Carbon::create($year, $month, 1)->startOfDay();

                $source = FixedCostEntry::create([
                    'name' => trim($row['name']),
                    'amount' => $row['amount'],
                    'currency' => strtoupper($row['currency']),
                    'category' => filled($row['category'] ?? null) ? $row['category'] : null,
                    'period_start' => $periodStart,
                    'period_end' => $periodStart->copy()->endOfMonth()->startOfDay(),
                    'accounting_date' => $row['accounting_date'],
                    'notes' => filled($row['comment'] ?? null) ? trim($row['comment']) : null,
                ]);

                // Observer już utworzył wrapper — dopisujemy pola rejestru.
                $entry = $registry->syncFor($source);
                $entry?->update([
                    'company_id' => filled($row['company_id'] ?? null) ? (int) $row['company_id'] : null,
                    'period_year' => $year,
                    'period_month' => $month,
                    'comment' => filled($row['comment'] ?? null) ? trim($row['comment']) : null,
                    'financially_irrelevant' => (bool) ($row['financially_irrelevant'] ?? false),
                ]);

                $created++;
            }
        });

        return redirect()
            ->route('cost-registry.index')
            ->with('success', "Dodano {$created} ".($created === 1 ? 'pozycję' : ($created < 5 ? 'pozycje' : 'pozycji')).' do rejestru kosztów.');
    }

    public function update(Request $request, CostEntry $cost_registry): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'period_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'financially_irrelevant' => ['nullable', 'boolean'],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        $cost_registry->update([
            'name' => trim($validated['name']),
            'company_id' => $validated['company_id'] ?? null,
            'period_year' => (int) $validated['period_year'],
            'period_month' => (int) $validated['period_month'],
            'financially_irrelevant' => (bool) ($validated['financially_irrelevant'] ?? false),
            'comment' => filled($validated['comment'] ?? null) ? trim($validated['comment']) : null,
        ]);

        return redirect()
            ->route('cost-registry.index')
            ->with('success', 'Pozycja rejestru została zapisana.');
    }
}
