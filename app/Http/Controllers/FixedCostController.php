<?php

namespace App\Http\Controllers;

use App\Models\FixedCost;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class FixedCostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $costs = FixedCost::orderBy('created_at', 'desc')
            ->paginate(20);

        return view('fixed-costs.index', compact('costs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('fixed-costs.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'cost_date' => ['required', 'date'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
        ]);

        FixedCost::create($validated);

        return redirect()
            ->route('fixed-costs.index')
            ->with('success', 'Koszt stały został dodany.');
    }

    /**
     * Display the specified resource.
     */
    public function show(FixedCost $fixedCost): View
    {
        return view('fixed-costs.show', compact('fixedCost'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FixedCost $fixedCost): View
    {
        return view('fixed-costs.edit', compact('fixedCost'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FixedCost $fixedCost): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'cost_date' => ['required', 'date'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
        ]);

        $fixedCost->update($validated);

        return redirect()
            ->route('fixed-costs.index')
            ->with('success', 'Koszt stały został zaktualizowany.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FixedCost $fixedCost): RedirectResponse
    {
        $fixedCost->delete();

        return redirect()
            ->route('fixed-costs.index')
            ->with('success', 'Koszt stały został usunięty.');
    }
}
