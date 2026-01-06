<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Dane są pobierane przez komponent Livewire VehiclesTable
        return view('vehicles.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('vehicles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'registration_number' => 'required|string|unique:vehicles',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:1',
            'technical_condition' => 'required|in:excellent,good,fair,poor',
            'inspection_valid_to' => 'nullable|date',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        unset($validated['image']);

        // Add image_path if image was uploaded
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('vehicles', 'public');
            $validated['image_path'] = $imagePath;
        }

        Vehicle::create($validated);
        return redirect()->route('vehicles.index')->with('success', 'Pojazd został dodany.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Vehicle $vehicle)
    {
        return view('vehicles.show', compact('vehicle'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vehicle $vehicle)
    {
        return view('vehicles.edit', compact('vehicle'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'registration_number' => 'required|string|unique:vehicles,registration_number,' . $vehicle->id,
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:1',
            'technical_condition' => 'required|in:excellent,good,fair,poor',
            'inspection_valid_to' => 'nullable|date',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($vehicle->image_path && Storage::disk('public')->exists($vehicle->image_path)) {
                Storage::disk('public')->delete($vehicle->image_path);
            }
            
            $imagePath = $request->file('image')->store('vehicles', 'public');
            $validated['image_path'] = $imagePath;
        }

        unset($validated['image']);

        $vehicle->update($validated);
        return redirect()->route('vehicles.show', $vehicle)->with('success', 'Pojazd został zaktualizowany.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return redirect()->route('vehicles.index')->with('success', 'Pojazd został usunięty.');
    }
}
