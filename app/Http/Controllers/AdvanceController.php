<?php

namespace App\Http\Controllers;

use App\Models\Advance;
use App\Models\Payroll;
use App\Http\Requests\StoreAdvanceRequest;
use App\Http\Requests\UpdateAdvanceRequest;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AdvanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $advances = Advance::with('employee')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('advances.index', compact('advances'));
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
        return view('advances.create', compact('payrolls'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdvanceRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        // Pobierz payroll i ustaw employee_id automatycznie
        $payroll = Payroll::findOrFail($validated['payroll_id']);
        $validated['employee_id'] = $payroll->employee_id;
        
        Advance::create($validated);
        
        // Przelicz payroll jeśli jest w statusie draft/issued
        if ($payroll->canBeRecalculated()) {
            $payroll->adjustments_amount = app(\App\Services\GeneratePayrollForEmployee::class)->calculateAdjustmentsAmountForPayroll($payroll);
            $payroll->recalculateTotal();
            $payroll->save();
        }

        return redirect()->route('advances.index')
            ->with('success', 'Zaliczka została dodana.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Advance $advance): View
    {
        $advance->load('employee');
        return view('advances.show', compact('advance'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Advance $advance): View
    {
        // Pobierz wszystkie payrolle z informacją o pracowniku i okresie
        $payrolls = Payroll::with('employee')
            ->orderBy('period_start', 'desc')
            ->orderBy('employee_id')
            ->get();
        return view('advances.edit', compact('advance', 'payrolls'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdvanceRequest $request, Advance $advance): RedirectResponse
    {
        $validated = $request->validated();
        
        // Pobierz payroll i ustaw employee_id automatycznie
        $payroll = Payroll::findOrFail($validated['payroll_id']);
        $validated['employee_id'] = $payroll->employee_id;
        
        $advance->update($validated);
        
        // Przelicz payroll jeśli jest w statusie draft/issued
        if ($payroll->canBeRecalculated()) {
            $payroll->adjustments_amount = app(\App\Services\GeneratePayrollForEmployee::class)->calculateAdjustmentsAmountForPayroll($payroll);
            $payroll->recalculateTotal();
            $payroll->save();
        }

        return redirect()->route('advances.index')
            ->with('success', 'Zaliczka została zaktualizowana.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Advance $advance): RedirectResponse
    {
        $payroll = $advance->payroll;
        $advance->delete();
        
        // Przelicz payroll jeśli jest w statusie draft/issued
        if ($payroll && $payroll->canBeRecalculated()) {
            $payroll->adjustments_amount = app(\App\Services\GeneratePayrollForEmployee::class)->calculateAdjustmentsAmountForPayroll($payroll);
            $payroll->recalculateTotal();
            $payroll->save();
        }

        return redirect()->route('advances.index')
            ->with('success', 'Zaliczka została usunięta.');
    }
}
