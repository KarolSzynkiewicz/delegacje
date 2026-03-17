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
        
        // Sprawdź czy już istnieje baza (przed utworzeniem nowej)
        $hadExistingBase = false;
        if ($isBase) {
            $hadExistingBase = Location::where('is_base', true)->exists();
        }
        
        // Normalize coordinates - convert empty strings to null
        $latitude = !empty($validated['latitude']) ? (float)$validated['latitude'] : null;
        $longitude = !empty($validated['longitude']) ? (float)$validated['longitude'] : null;
        
        app(\App\Services\LocationService::class)->createLocation(
            $validated['name'],
            $validated['address'],
            $validated['city'] ?? null,
            $validated['postal_code'] ?? null,
            $validated['contact_person'] ?? null,
            $validated['phone'] ?? null,
            $validated['email'] ?? null,
            $validated['description'] ?? null,
            $isBase,
            $latitude,
            $longitude
        );

        $message = 'Lokalizacja została dodana.';
        if ($isBase && $hadExistingBase) {
            $message .= ' Poprzednia baza została automatycznie odznaczona.';
        }

        return redirect()->route('locations.index')->with('success', $message);
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
        
        // Sprawdź czy już istnieje inna baza (przed aktualizacją)
        $hadOtherBase = false;
        if ($isBase && !$location->is_base) {
            $hadOtherBase = Location::where('is_base', true)
                ->where('id', '!=', $location->id)
                ->exists();
        }
        
        // Normalize coordinates - convert empty strings to null
        // Check both validated array and raw request input
        $latitude = null;
        $longitude = null;
        
        if (isset($validated['latitude']) && $validated['latitude'] !== '' && $validated['latitude'] !== null) {
            $latitude = (float)$validated['latitude'];
        } elseif ($request->has('latitude') && $request->input('latitude') !== '' && $request->input('latitude') !== null) {
            $latitude = (float)$request->input('latitude');
        }
        
        if (isset($validated['longitude']) && $validated['longitude'] !== '' && $validated['longitude'] !== null) {
            $longitude = (float)$validated['longitude'];
        } elseif ($request->has('longitude') && $request->input('longitude') !== '' && $request->input('longitude') !== null) {
            $longitude = (float)$request->input('longitude');
        }
        
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
            $isBase,
            $latitude,
            $longitude
        );

        $message = 'Lokalizacja została zaktualizowana.';
        if ($isBase && $hadOtherBase) {
            $message .= ' Poprzednia baza została automatycznie odznaczona.';
        }

        return redirect()->route('locations.index')->with('success', $message);
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
