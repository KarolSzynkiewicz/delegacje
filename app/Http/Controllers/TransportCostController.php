<?php

namespace App\Http\Controllers;

use App\Models\TransportCost;
use App\Models\LogisticsEvent;
use App\Models\Vehicle;
use App\Models\Transport;
use Illuminate\Http\Request;

class TransportCostController extends Controller
{
    /**
     * Display a listing of transport costs.
     */
    public function index()
    {
        $this->authorize('viewAny', TransportCost::class);

        $costs = TransportCost::with(['logisticsEvent', 'vehicle', 'transport', 'creator'])
            ->orderBy('cost_date', 'desc')
            ->paginate(20);

        return view('transport-costs.index', compact('costs'));
    }

    /**
     * Show the form for creating a new transport cost.
     */
    public function create()
    {
        $this->authorize('create', TransportCost::class);

        $events = LogisticsEvent::orderBy('event_date', 'desc')->get();
        $vehicles = Vehicle::orderBy('registration_number')->get();
        $transports = Transport::with('logisticsEvent')->orderBy('departure_datetime', 'desc')->get();

        return view('transport-costs.create', compact('events', 'vehicles', 'transports'));
    }

    /**
     * Store a newly created transport cost.
     */
    public function store(Request $request)
    {
        $this->authorize('create', TransportCost::class);

        $validated = $request->validate([
            'logistics_event_id' => 'nullable|exists:logistics_events,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'transport_id' => 'nullable|exists:transports,id',
            'cost_type' => 'required|string|in:fuel,ticket,parking,toll,other',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'cost_date' => 'required|date',
            'description' => 'nullable|string',
            'receipt_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        TransportCost::create($validated);

        return redirect()
            ->route('transport-costs.index')
            ->with('success', 'Koszt transportu został dodany.');
    }

    /**
     * Display the specified transport cost.
     */
    public function show(TransportCost $transportCost)
    {
        $this->authorize('view', $transportCost);

        $transportCost->load(['logisticsEvent', 'vehicle', 'transport', 'creator']);

        return view('transport-costs.show', compact('transportCost'));
    }

    /**
     * Show the form for editing the specified transport cost.
     */
    public function edit(TransportCost $transportCost)
    {
        $this->authorize('update', $transportCost);

        $events = LogisticsEvent::orderBy('event_date', 'desc')->get();
        $vehicles = Vehicle::orderBy('registration_number')->get();
        $transports = Transport::with('logisticsEvent')->orderBy('departure_datetime', 'desc')->get();

        return view('transport-costs.edit', compact('transportCost', 'events', 'vehicles', 'transports'));
    }

    /**
     * Update the specified transport cost.
     */
    public function update(Request $request, TransportCost $transportCost)
    {
        $this->authorize('update', $transportCost);

        $validated = $request->validate([
            'logistics_event_id' => 'nullable|exists:logistics_events,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'transport_id' => 'nullable|exists:transports,id',
            'cost_type' => 'required|string|in:fuel,ticket,parking,toll,other',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'cost_date' => 'required|date',
            'description' => 'nullable|string',
            'receipt_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $transportCost->update($validated);

        return redirect()
            ->route('transport-costs.index')
            ->with('success', 'Koszt transportu został zaktualizowany.');
    }

    /**
     * Remove the specified transport cost.
     */
    public function destroy(TransportCost $transportCost)
    {
        $this->authorize('delete', $transportCost);

        $transportCost->delete();

        return redirect()
            ->route('transport-costs.index')
            ->with('success', 'Koszt transportu został usunięty.');
    }
}
