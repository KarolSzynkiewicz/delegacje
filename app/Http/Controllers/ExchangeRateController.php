<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * CRUD dla kursów wymiany walut ({@see ExchangeRate}).
 *
 * Kursy są używane WYŁĄCZNIE do prezentacji (przeliczenie orientacyjnej sumy w raportach
 * kontrolingowych) — nigdy nie zmieniają kwot źródłowych zapisanych w systemie.
 */
class ExchangeRateController extends Controller
{
    public function index(Request $request): View
    {
        $rates = ExchangeRate::query()
            ->orderByDesc('rate_date')
            ->orderBy('base_currency')
            ->orderBy('quote_currency')
            ->paginate(30);

        return view('exchange-rates.index', compact('rates'));
    }

    public function create(): View
    {
        return view('exchange-rates.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        ExchangeRate::create($validated);

        return redirect()
            ->route('exchange-rates.index')
            ->with('success', 'Kurs wymiany został dodany.');
    }

    public function edit(ExchangeRate $exchangeRate): View
    {
        return view('exchange-rates.edit', compact('exchangeRate'));
    }

    public function update(Request $request, ExchangeRate $exchangeRate): RedirectResponse
    {
        $validated = $this->validated($request);

        $exchangeRate->update($validated);

        return redirect()
            ->route('exchange-rates.index')
            ->with('success', 'Kurs wymiany został zaktualizowany.');
    }

    public function destroy(ExchangeRate $exchangeRate): RedirectResponse
    {
        $exchangeRate->delete();

        return redirect()
            ->route('exchange-rates.index')
            ->with('success', 'Kurs wymiany został usunięty.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $validated = $request->validate([
            'rate_date' => ['required', 'date'],
            'base_currency' => ['required', 'string', 'size:3'],
            'quote_currency' => ['required', 'string', 'size:3', 'different:base_currency'],
            'rate' => ['required', 'numeric', 'gt:0'],
            'source' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['base_currency'] = strtoupper($validated['base_currency']);
        $validated['quote_currency'] = strtoupper($validated['quote_currency']);

        return $validated;
    }
}
