<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AccommodationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $accommodations = Accommodation::paginate(10);
        return view('accommodations.index', compact('accommodations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('accommodations.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        unset($validated['image']);

        // Add image_path if image was uploaded
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('accommodations', 'public');
            $validated['image_path'] = $imagePath;
        }

        Accommodation::create($validated);
        return redirect()->route('accommodations.index')->with('success', 'Akomodacja została dodana.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Accommodation $accommodation)
    {
        return view('accommodations.show', compact('accommodation'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Accommodation $accommodation)
    {
        return view('accommodations.edit', compact('accommodation'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Accommodation $accommodation)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($accommodation->image_path && Storage::disk('public')->exists($accommodation->image_path)) {
                Storage::disk('public')->delete($accommodation->image_path);
            }
            
            $imagePath = $request->file('image')->store('accommodations', 'public');
            $validated['image_path'] = $imagePath;
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
        $accommodation->delete();
        return redirect()->route('accommodations.index')->with('success', 'Akomodacja została usunięta.');
    }
}
