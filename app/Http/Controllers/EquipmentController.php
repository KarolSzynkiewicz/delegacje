<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Role;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    /**
     * Display a listing of equipment.
     */
    public function index()
    {
        $equipment = Equipment::with('requirements.role')
            ->orderBy('name')
            ->paginate(20);

        return view('equipment.index', compact('equipment'));
    }

    /**
     * Show the form for creating a new equipment.
     */
    public function create()
    {
        return view('equipment.create');
    }

    /**
     * Store a newly created equipment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'quantity_in_stock' => 'required|integer|min:0',
            'min_quantity' => 'required|integer|min:0',
            'unit' => 'required|string|max:10',
            'unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        Equipment::create($validated);

        return redirect()
            ->route('equipment.index')
            ->with('success', 'Sprzęt został dodany.');
    }

    /**
     * Display the specified equipment.
     */
    public function show(Equipment $equipment)
    {
        $equipment->load('requirements.role', 'issues.employee', 'issues.projectAssignment');

        return view('equipment.show', compact('equipment'));
    }

    /**
     * Show the form for editing the specified equipment.
     */
    public function edit(Equipment $equipment)
    {
        $roles = Role::orderBy('name')->get();

        return view('equipment.edit', compact('equipment', 'roles'));
    }

    /**
     * Update the specified equipment.
     */
    public function update(Request $request, Equipment $equipment)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'quantity_in_stock' => 'required|integer|min:0',
            'min_quantity' => 'required|integer|min:0',
            'unit' => 'required|string|max:10',
            'unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $equipment->update($validated);

        return redirect()
            ->route('equipment.index')
            ->with('success', 'Sprzęt został zaktualizowany.');
    }

    /**
     * Remove the specified equipment.
     */
    public function destroy(Equipment $equipment)
    {
        $equipment->delete();

        return redirect()
            ->route('equipment.index')
            ->with('success', 'Sprzęt został usunięty.');
    }
}
