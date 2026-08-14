<?php

namespace App\Http\Controllers;

use App\Models\EquipmentIssue;
use App\Services\EquipmentService;
use App\Services\WarehouseService;
use Illuminate\Http\Request;

class EquipmentIssueController extends Controller
{
    public function __construct(
        protected EquipmentService $equipmentService,
        protected WarehouseService $warehouseService,
    ) {}

    public function index(Request $request)
    {
        return redirect()->route('equipment.tab.issues', collect($request->query())->except('warehouse_id')->all());
    }

    public function create(Request $request)
    {
        $warehouse = $this->warehouseService->current($request);

        return view('equipment-issues.create', compact('warehouse'));
    }

    public function store()
    {
        return redirect()->route('equipment-issues.create');
    }

    public function show(EquipmentIssue $equipmentIssue)
    {
        $equipmentIssue->load('equipment', 'variant', 'employee', 'issuer', 'returner');

        return view('equipment-issues.show', compact('equipmentIssue'));
    }

    public function returnForm(EquipmentIssue $equipmentIssue)
    {
        if ($equipmentIssue->isPermanentIssue()) {
            return redirect()
                ->route('equipment-issues.show', $equipmentIssue)
                ->with('error', 'Wydanie bezzwrotne nie podlega zwrotowi.');
        }

        if ($equipmentIssue->status !== EquipmentIssue::STATUS_ISSUED) {
            return redirect()
                ->route('equipment-issues.show', $equipmentIssue)
                ->with('error', 'Pozycja została już zwrócona, zgłoszona jako uszkodzona lub zgubiona.');
        }

        if (! $equipmentIssue->equipment->issuable || ! $equipmentIssue->equipment->returnable) {
            return redirect()
                ->route('equipment-issues.show', $equipmentIssue)
                ->with('error', 'Ta pozycja nie może być zwracana, zgłaszana jako uszkodzona lub zgubiona.');
        }

        return view('equipment-issues.return', compact('equipmentIssue'));
    }

    public function return(Request $request, EquipmentIssue $equipmentIssue)
    {
        $validated = $request->validate([
            'return_date' => 'required|date|after_or_equal:'.$equipmentIssue->issue_date->format('Y-m-d'),
            'status' => 'required|in:returned,damaged,lost',
            'notes' => 'nullable|string',
        ]);

        try {
            $returnDate = \Carbon\Carbon::parse($validated['return_date']);

            $this->equipmentService->returnEquipment(
                $equipmentIssue,
                $returnDate,
                $validated['status'],
                $validated['notes'] ?? null
            );

            $statusMessages = [
                'returned' => 'Pozycja została zwrócona.',
                'damaged' => 'Pozycja została zgłoszona jako uszkodzona.',
                'lost' => 'Pozycja została zgłoszona jako zgubiona.',
            ];

            return redirect()
                ->route('equipment-issues.show', $equipmentIssue)
                ->with('success', $statusMessages[$validated['status']]);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Wystąpił błąd: '.$e->getMessage())
                ->withInput();
        }
    }
}
