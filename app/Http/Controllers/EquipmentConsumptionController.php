<?php

namespace App\Http\Controllers;

use App\Services\WarehouseService;
use Illuminate\Http\Request;

class EquipmentConsumptionController extends Controller
{
    public function __construct(protected WarehouseService $warehouseService) {}

    public function index(Request $request)
    {
        return redirect()->route('equipment.tab.issues', array_merge(
            collect($request->query())->except('warehouse_id')->all(),
            ['kind' => $request->query('kind', 'consumption')]
        ));
    }

    public function create(Request $request)
    {
        $warehouse = $this->warehouseService->current($request);
        $warehouses = $this->warehouseService->all();

        return view('equipment-consumptions.create', compact('warehouse', 'warehouses'));
    }
}
