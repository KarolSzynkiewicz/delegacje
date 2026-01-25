<?php

namespace App\Http\Controllers;

use App\Models\FixedCostTemplate;
use App\Models\FixedCostEntry;
use App\Services\GenerateFixedCostsService;
use App\Http\Requests\GenerateFixedCostsRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class FixedCostController extends Controller
{
    /**
     * Display a listing of the resource (templates and entries).
     */
    public function index(): View
    {
        return $this->indexTemplates();
    }

    /**
     * Display templates tab.
     */
    public function indexTemplates(): View
    {
        $templates = FixedCostTemplate::orderBy('created_at', 'desc')
            ->paginate(20);
        
        $entries = FixedCostEntry::with('template')
            ->orderBy('period_start', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('fixed-costs.index', [
            'templates' => $templates,
            'entries' => $entries,
            'activeTab' => 'templates'
        ]);
    }

    /**
     * Display entries tab.
     */
    public function indexEntries(): View
    {
        $templates = FixedCostTemplate::orderBy('created_at', 'desc')
            ->paginate(20);
        
        $entries = FixedCostEntry::with('template')
            ->orderBy('period_start', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('fixed-costs.index', [
            'templates' => $templates,
            'entries' => $entries,
            'activeTab' => 'entries'
        ]);
    }

    /**
     * Show the form for creating a new template.
     */
    public function create(): View
    {
        return view('fixed-costs.create');
    }

    /**
     * Store a newly created template.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'interval_type' => ['required', 'in:monthly,weekly,yearly'],
            'interval_day' => ['required', 'integer', 'min:1', 'max:31'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->has('is_active') ? true : false;

        FixedCostTemplate::create($validated);

        return redirect()
            ->route('fixed-costs.index')
            ->with('success', 'Szablon kosztu stałego został dodany.');
    }

    /**
     * Display the specified template.
     */
    public function show(FixedCostTemplate $fixedCost): View
    {
        $entries = FixedCostEntry::where('template_id', $fixedCost->id)
            ->orderBy('period_start', 'desc')
            ->paginate(20);

        return view('fixed-costs.show', compact('fixedCost', 'entries'));
    }

    /**
     * Show the form for editing the specified template.
     */
    public function edit(FixedCostTemplate $fixedCost): View
    {
        return view('fixed-costs.edit', compact('fixedCost'));
    }

    /**
     * Update the specified template.
     */
    public function update(Request $request, FixedCostTemplate $fixedCost): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'interval_type' => ['required', 'in:monthly,weekly,yearly'],
            'interval_day' => ['required', 'integer', 'min:1', 'max:31'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->has('is_active') ? true : false;

        $fixedCost->update($validated);

        return redirect()
            ->route('fixed-costs.index')
            ->with('success', 'Szablon kosztu stałego został zaktualizowany.');
    }

    /**
     * Remove the specified template.
     */
    public function destroy(FixedCostTemplate $fixedCost): RedirectResponse
    {
        $fixedCost->delete();

        return redirect()
            ->route('fixed-costs.index')
            ->with('success', 'Szablon kosztu stałego został usunięty.');
    }

    /**
     * Show the form for generating fixed costs.
     */
    public function generateForm(): View
    {
        return view('fixed-costs.generate');
    }

    /**
     * Generate fixed costs for a period.
     */
    public function generate(GenerateFixedCostsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $periodStart = Carbon::parse($validated['period_start']);
        $periodEnd = Carbon::parse($validated['period_end']);

        $service = app(GenerateFixedCostsService::class);
        $result = $service->generateForPeriod($periodStart, $periodEnd, $validated['notes'] ?? null);

        $message = "Wygenerowano {$result['generated']} kosztów stałych.";
        if ($result['skipped'] > 0) {
            $message .= " Pominięto {$result['skipped']} (już istnieją).";
        }
        if (!empty($result['errors'])) {
            $message .= " Błędy: " . implode(', ', $result['errors']);
        }

        return redirect()->route('fixed-costs.index')
            ->with('success', $message);
    }

    /**
     * Show the form for creating a new entry (manual).
     */
    public function createEntry(): View
    {
        $templates = FixedCostTemplate::where('is_active', true)->orderBy('name')->get();
        return view('fixed-costs.create-entry', compact('templates'));
    }

    /**
     * Store a newly created entry (manual).
     */
    public function storeEntry(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'accounting_date' => ['required', 'date'],
            'template_id' => ['nullable', 'exists:fixed_cost_templates,id'],
            'notes' => ['nullable', 'string'],
        ]);

        FixedCostEntry::create($validated);

        return redirect()
            ->route('fixed-costs.tab.entries')
            ->with('success', 'Koszt księgowy został dodany.');
    }

    /**
     * Display the specified entry.
     */
    public function showEntry(FixedCostEntry $entry): View
    {
        return view('fixed-costs.show-entry', compact('entry'));
    }

    /**
     * Remove the specified entry.
     */
    public function destroyEntry(FixedCostEntry $entry): RedirectResponse
    {
        $entry->delete();

        return redirect()
            ->route('fixed-costs.tab.entries')
            ->with('success', 'Koszt księgowy został usunięty.');
    }
}
