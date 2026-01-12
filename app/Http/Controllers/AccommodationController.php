<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Http\Controllers\Concerns\HandlesImageUpload;
use App\Http\Requests\StoreAccommodationRequest;
use App\Http\Requests\UpdateAccommodationRequest;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AccommodationController extends Controller
{
    use HandlesImageUpload;
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Accommodation::class);
        // Dane są pobierane przez komponent Livewire AccommodationsTable
        return view('accommodations.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Accommodation::class);
        return view('accommodations.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAccommodationRequest $request): RedirectResponse
    {
        $this->authorize('create', Accommodation::class);
        
        $validated = $this->processImageUpload($request->validated(), $request, 'accommodations');
        Accommodation::create($validated);
        
        return redirect()->route('accommodations.index')->with('success', 'Akomodacja została dodana.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Accommodation $accommodation): View
    {
        $this->authorize('view', $accommodation);
        $assignments = $accommodation->assignments()
            ->with(['employee'])
            ->orderBy('start_date', 'desc')
            ->get();
        
        return view('accommodations.show', compact('accommodation', 'assignments'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Accommodation $accommodation): View
    {
        $this->authorize('update', $accommodation);
        return view('accommodations.edit', compact('accommodation'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAccommodationRequest $request, Accommodation $accommodation): RedirectResponse
    {
        $this->authorize('update', $accommodation);
        
        $validated = $this->processImageUpload($request->validated(), $request, 'accommodations', $accommodation->image_path);
        $accommodation->update($validated);
        
        return redirect()->route('accommodations.show', $accommodation)->with('success', 'Akomodacja została zaktualizowana.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Accommodation $accommodation): RedirectResponse
    {
        $this->authorize('delete', $accommodation);
        $accommodation->delete();
        return redirect()->route('accommodations.index')->with('success', 'Akomodacja została usunięta.');
    }
}
