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
        $this->authorize('viewAny', Location::class);
        $locations = Location::all();
        return view('locations.index', compact('locations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Location::class);
        return view('locations.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLocationRequest $request): RedirectResponse
    {
        $this->authorize('create', Location::class);
        
        Location::create($request->validated());

        return redirect()->route('locations.index')->with('success', 'Lokalizacja została dodana.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Location $location): View
    {
        $this->authorize('view', $location);
        $location->load('projects');
        return view('locations.show', compact('location'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Location $location): View
    {
        $this->authorize('update', $location);
        return view('locations.edit', compact('location'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {
        $this->authorize('update', $location);
        
        $location->update($request->validated());

        return redirect()->route('locations.index')->with('success', 'Lokalizacja została zaktualizowana.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Location $location): RedirectResponse
    {
        $this->authorize('delete', $location);
        
        $location->delete();

        return redirect()->route('locations.index')->with('success', 'Lokalizacja została usunięta.');
    }
}
