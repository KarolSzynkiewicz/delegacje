<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Services\ImageService;
use App\Http\Requests\StoreAccommodationRequest;
use App\Http\Requests\UpdateAccommodationRequest;
use Illuminate\Http\Request;

class AccommodationController extends Controller
{
    public function __construct(
        protected ImageService $imageService
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Accommodation::class);
        // Dane są pobierane przez komponent Livewire AccommodationsTable
        return view('accommodations.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Accommodation::class);
        return view('accommodations.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAccommodationRequest $request)
    {
        $this->authorize('create', Accommodation::class);
        $validated = $request->validated();

        unset($validated['image']);

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image_path'] = $this->imageService->storeImage($request->file('image'), 'accommodations');
        }

        Accommodation::create($validated);
        return redirect()->route('accommodations.index')->with('success', 'Akomodacja została dodana.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Accommodation $accommodation)
    {
        $this->authorize('view', $accommodation);
        return view('accommodations.show', compact('accommodation'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Accommodation $accommodation)
    {
        $this->authorize('update', $accommodation);
        return view('accommodations.edit', compact('accommodation'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAccommodationRequest $request, Accommodation $accommodation)
    {
        $this->authorize('update', $accommodation);
        $validated = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image_path'] = $this->imageService->handleImageUpload(
                $request->file('image'),
                'accommodations',
                $accommodation->image_path
            );
        }

        unset($validated['image']);

        $accommodation->update($validated);
        return redirect()->route('accommodations.show', $accommodation)->with('success', 'Akomodacja została zaktualizowana.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Accommodation $accommodation)
    {
        $this->authorize('delete', $accommodation);
        $accommodation->delete();
        return redirect()->route('accommodations.index')->with('success', 'Akomodacja została usunięta.');
    }
}
