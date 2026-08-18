<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WarehouseController extends Controller
{
    public function __construct(protected WarehouseService $warehouseService) {}

    public function index(Request $request)
    {
        $warehouseId = Warehouse::query()->orderBy('id')->value('id');

        if ($warehouseId) {
            return redirect()->route('equipment.tab.stock', ['warehouse_id' => $warehouseId]);
        }

        $warehouses = $this->warehouseService->all();

        return view('warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        $locations = $this->warehouseService->locationsWithoutWarehouse();

        if ($locations->isEmpty()) {
            return $this->redirectToLowestWarehouse()
                ->with('error', 'Wszystkie lokalizacje mają już magazyn. Dodaj najpierw nową lokalizację.');
        }

        return view('warehouses.create', compact('locations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id|unique:warehouses,location_id',
            'name' => 'nullable|string|max:255',
        ], [
            'location_id.unique' => 'Ta lokalizacja ma już magazyn.',
        ]);

        try {
            $created = $this->warehouseService->createForLocation(
                Location::query()->findOrFail($validated['location_id']),
                $validated['name'] ?? null
            );
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('equipment.tab.stock', ['warehouse_id' => $created->id])
            ->with('success', "Dodano magazyn „{$created->name}”.");
    }

    public function edit(Request $request, Warehouse $warehouse)
    {
        $current = $this->warehouseService->current($request);
        $warehouses = $this->warehouseService->all();
        $warehouse->load('location');

        return view('warehouses.edit', [
            'warehouse' => $warehouse,
            'currentWarehouse' => $current,
            'warehouses' => $warehouses,
        ]);
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_default' => 'nullable|boolean',
        ]);

        try {
            $updated = $this->warehouseService->update(
                $warehouse,
                $validated['name'],
                $request->boolean('is_default')
            );
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('equipment.tab.stock', ['warehouse_id' => $updated->id])
            ->with('success', "Zapisano magazyn „{$updated->name}”.");
    }

    public function destroy(Request $request, Warehouse $warehouse)
    {
        $fallbackId = $this->warehouseService->default()->id;
        if ($fallbackId === $warehouse->id) {
            $fallbackId = Warehouse::query()->where('id', '!=', $warehouse->id)->value('id');
        }

        try {
            $this->warehouseService->delete($warehouse);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        if ($request->session()->get(WarehouseService::SESSION_KEY) === $warehouse->id && $fallbackId) {
            $request->session()->put(WarehouseService::SESSION_KEY, $fallbackId);
        }

        return $this->redirectToLowestWarehouse()
            ->with('success', 'Magazyn został usunięty.');
    }

    private function redirectToLowestWarehouse(): RedirectResponse
    {
        $warehouseId = Warehouse::query()->orderBy('id')->value('id');

        if ($warehouseId) {
            return redirect()->route('equipment.tab.stock', ['warehouse_id' => $warehouseId]);
        }

        return redirect()->route('warehouses.index');
    }
}
