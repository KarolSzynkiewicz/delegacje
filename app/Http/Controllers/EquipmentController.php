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
    public function index(Request $request)
    {
        $query = Equipment::with('requirements.role');

        // Filter by search (name)
        if ($request->filled('search')) {
            $searchTerm = trim($request->search);
            if (strlen($searchTerm) >= 2) {
                $query->where('name', 'like', '%' . $searchTerm . '%');
            }
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by status (low stock / OK)
        if ($request->filled('status')) {
            if ($request->status === 'low_stock') {
                // Filter for low stock: available_quantity <= min_quantity
                // We need to calculate this in the query
                $query->whereRaw('(quantity_in_stock - COALESCE((SELECT SUM(quantity_issued) FROM equipment_issues WHERE equipment_issues.equipment_id = equipment.id AND equipment_issues.status = "issued"), 0)) <= min_quantity');
            } elseif ($request->status === 'ok') {
                // Filter for OK stock: available_quantity > min_quantity
                $query->whereRaw('(quantity_in_stock - COALESCE((SELECT SUM(quantity_issued) FROM equipment_issues WHERE equipment_issues.equipment_id = equipment.id AND equipment_issues.status = "issued"), 0)) > min_quantity');
            }
        }

        $equipment = $query->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        // Get unique categories for filter
        $categories = Equipment::whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->filter()
            ->values();

        return view('equipment.index', compact('equipment', 'categories'));
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
            'returnable' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        // Convert checkbox value to boolean
        $validated['returnable'] = $request->has('returnable') ? true : false;

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
            'returnable' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        // Convert checkbox value to boolean
        $validated['returnable'] = $request->has('returnable') ? true : false;

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
