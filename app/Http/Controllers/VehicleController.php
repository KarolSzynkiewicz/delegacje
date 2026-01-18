<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Http\Controllers\Concerns\HandlesImageUpload;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class VehicleController extends Controller
{
    use HandlesImageUpload;
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        // Dane są pobierane przez komponent Livewire VehiclesTable
        return view('vehicles.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('vehicles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $validated = $this->processImageUpload($request->validated(), $request, 'vehicles');
        Vehicle::create($validated);
        
        return redirect()->route('vehicles.index')->with('success', 'Pojazd został dodany.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Vehicle $vehicle): View
    {
        $assignments = $vehicle->assignments()
            ->with(['employee'])
            ->orderBy('start_date', 'desc')
            ->get();
        
        return view('vehicles.show', compact('vehicle', 'assignments'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vehicle $vehicle): View
    {
        return view('vehicles.edit', compact('vehicle'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $validated = $this->processImageUpload($request->validated(), $request, 'vehicles', $vehicle->image_path);
        $vehicle->update($validated);
        
        return redirect()->route('vehicles.show', $vehicle)->with('success', 'Pojazd został zaktualizowany.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();
        return redirect()->route('vehicles.index')->with('success', 'Pojazd został usunięty.');
    }
}
