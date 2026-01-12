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
        $this->authorize('viewAny', Vehicle::class);
        // Dane są pobierane przez komponent Livewire VehiclesTable
        return view('vehicles.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Vehicle::class);
        return view('vehicles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $this->authorize('create', Vehicle::class);
        
        $validated = $this->processImageUpload($request->validated(), $request, 'vehicles');
        Vehicle::create($validated);
        
        return redirect()->route('vehicles.index')->with('success', 'Pojazd został dodany.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Vehicle $vehicle): View
    {
        $this->authorize('view', $vehicle);
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
        $this->authorize('update', $vehicle);
        return view('vehicles.edit', compact('vehicle'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('update', $vehicle);
        
        $validated = $this->processImageUpload($request->validated(), $request, 'vehicles', $vehicle->image_path);
        $vehicle->update($validated);
        
        return redirect()->route('vehicles.show', $vehicle)->with('success', 'Pojazd został zaktualizowany.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('delete', $vehicle);
        $vehicle->delete();
        return redirect()->route('vehicles.index')->with('success', 'Pojazd został usunięty.');
    }
}
