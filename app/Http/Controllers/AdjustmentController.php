<?php

namespace App\Http\Controllers;

use App\Models\Adjustment;
use App\Models\Payroll;
use App\Http\Requests\StoreAdjustmentRequest;
use App\Http\Requests\UpdateAdjustmentRequest;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AdjustmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $adjustments = Adjustment::with('employee')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('adjustments.index', compact('adjustments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        // Pobierz wszystkie payrolle z informacją o pracowniku i okresie
        $payrolls = Payroll::with('employee')
            ->orderBy('period_start', 'desc')
            ->orderBy('employee_id')
            ->get();
        return view('adjustments.create', compact('payrolls'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdjustmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        // Pobierz payroll i ustaw employee_id automatycznie
        $payroll = Payroll::findOrFail($validated['payroll_id']);
        $validated['employee_id'] = $payroll->employee_id;
        
        Adjustment::create($validated);

        return redirect()->route('adjustments.index')
            ->with('success', 'Kara/nagroda została dodana.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Adjustment $adjustment): View
    {
        $adjustment->load('employee');
        return view('adjustments.show', compact('adjustment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Adjustment $adjustment): View
    {
        // Pobierz wszystkie payrolle z informacją o pracowniku i okresie
        $payrolls = Payroll::with('employee')
            ->orderBy('period_start', 'desc')
            ->orderBy('employee_id')
            ->get();
        return view('adjustments.edit', compact('adjustment', 'payrolls'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdjustmentRequest $request, Adjustment $adjustment): RedirectResponse
    {
        $validated = $request->validated();
        
        // Pobierz payroll i ustaw employee_id automatycznie
        $payroll = Payroll::findOrFail($validated['payroll_id']);
        $validated['employee_id'] = $payroll->employee_id;
        
        $adjustment->update($validated);
        
        // Przelicz payroll jeśli jest w statusie draft/issued
        if ($payroll->canBeRecalculated()) {
            $payroll->adjustments_amount = app(\App\Services\GeneratePayrollForEmployee::class)->calculateAdjustmentsAmountForPayroll($payroll);
            $payroll->recalculateTotal();
            $payroll->save();
        }

        return redirect()->route('adjustments.index')
            ->with('success', 'Kara/nagroda została zaktualizowana.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Adjustment $adjustment): RedirectResponse
    {
        $payroll = $adjustment->payroll;
        $adjustment->delete();
        
        // Przelicz payroll jeśli jest w statusie draft/issued
        if ($payroll && $payroll->canBeRecalculated()) {
            $payroll->adjustments_amount = app(\App\Services\GeneratePayrollForEmployee::class)->calculateAdjustmentsAmountForPayroll($payroll);
            $payroll->recalculateTotal();
            $payroll->save();
        }

        return redirect()->route('adjustments.index')
            ->with('success', 'Kara/nagroda została usunięta.');
    }
}
