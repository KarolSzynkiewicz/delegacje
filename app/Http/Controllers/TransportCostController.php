<?php

namespace App\Http\Controllers;

use App\Models\TransportCost;
use App\Models\LogisticsEvent;
use App\Models\Vehicle;
use App\Models\Transport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TransportCostController extends Controller
{
    /**
     * Display a listing of transport costs.
     */
    public function index()
    {
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
        $validated = $request->validate([
            'logistics_event_id' => 'nullable|exists:logistics_events,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'transport_id' => 'nullable|exists:transports,id',
            'cost_type' => 'required|string|in:fuel,ticket,parking,toll,other',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|in:PLN,EUR,USD,GBP',
            'cost_date' => 'required|date',
            'description' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        // Upload pliku jeśli został przesłany
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $directory = 'transport_costs';
            $filePath = $file->store($directory, 'public');
            $validated['file_path'] = $filePath;
        }

        unset($validated['file']);

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
        $transportCost->load(['logisticsEvent', 'vehicle', 'transport', 'creator']);

        return view('transport-costs.show', compact('transportCost'));
    }

    /**
     * Show the form for editing the specified transport cost.
     */
    public function edit(TransportCost $transportCost)
    {
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
        $validated = $request->validate([
            'logistics_event_id' => 'nullable|exists:logistics_events,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'transport_id' => 'nullable|exists:transports,id',
            'cost_type' => 'required|string|in:fuel,ticket,parking,toll,other',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|in:PLN,EUR,USD,GBP',
            'cost_date' => 'required|date',
            'description' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
            'remove_file' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        // Usuń plik jeśli zaznaczono checkbox
        if ($request->has('remove_file') && $request->boolean('remove_file') && $transportCost->file_path) {
            Storage::disk('public')->delete($transportCost->file_path);
            $validated['file_path'] = null;
        }

        // Upload nowego pliku jeśli został przesłany
        if ($request->hasFile('file')) {
            // Usuń stary plik jeśli istnieje
            if ($transportCost->file_path) {
                Storage::disk('public')->delete($transportCost->file_path);
            }
            
            $file = $request->file('file');
            $directory = 'transport_costs';
            $filePath = $file->store($directory, 'public');
            $validated['file_path'] = $filePath;
        }

        unset($validated['file'], $validated['remove_file']);

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
        $transportCost->delete();

        return redirect()
            ->route('transport-costs.index')
            ->with('success', 'Koszt transportu został usunięty.');
    }
}
