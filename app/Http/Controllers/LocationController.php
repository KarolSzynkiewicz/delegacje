<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $locations = Location::all();
        return view('locations.index', compact('locations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('locations.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLocationRequest $request): RedirectResponse
    {
        
        $validated = $request->validated();
        $isBase = $request->has('is_base') && $request->input('is_base') == '1';
        
        app(\App\Services\LocationService::class)->createLocation(
            $validated['name'],
            $validated['address'],
            $validated['city'] ?? null,
            $validated['postal_code'] ?? null,
            $validated['contact_person'] ?? null,
            $validated['phone'] ?? null,
            $validated['email'] ?? null,
            $validated['description'] ?? null,
            $isBase
        );

        return redirect()->route('locations.index')->with('success', 'Lokalizacja została dodana.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Location $location): View
    {
        $location->load('projects');
        return view('locations.show', compact('location'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Location $location): View
    {
        return view('locations.edit', compact('location'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {
        
        $validated = $request->validated();
        $isBase = $request->has('is_base') && $request->input('is_base') == '1';
        
        app(\App\Services\LocationService::class)->updateLocation(
            $location,
            $validated['name'],
            $validated['address'],
            $validated['city'] ?? null,
            $validated['postal_code'] ?? null,
            $validated['contact_person'] ?? null,
            $validated['phone'] ?? null,
            $validated['email'] ?? null,
            $validated['description'] ?? null,
            $isBase
        );

        return redirect()->route('locations.index')->with('success', 'Lokalizacja została zaktualizowana.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Location $location): RedirectResponse
    {
        
        $location->delete();

        return redirect()->route('locations.index')->with('success', 'Lokalizacja została usunięta.');
    }
}
