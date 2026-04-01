<?php

namespace App\Http\Controllers;

use App\Models\Adjustment;
use App\Models\Employee;
use App\Models\Payroll;
use App\Http\Requests\StoreAdjustmentRequest;
use App\Http\Requests\UpdateAdjustmentRequest;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdjustmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $payrollFilter = $request->query('payroll', 'all'); // all|linked|unlinked
        $typeFilter = $request->query('type', 'all'); // all|bonus|penalty
        $sort = $request->query('sort', 'date');
        $dir = $request->query('dir', 'desc');

        $allowedSorts = ['date', 'amount', 'created_at'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'date';
        }
        $dir = $dir === 'asc' ? 'asc' : 'desc';

        $q = Adjustment::query()
            ->with(['employee', 'payroll'])
            ->when($payrollFilter === 'linked', fn ($qq) => $qq->whereNotNull('payroll_id'))
            ->when($payrollFilter === 'unlinked', fn ($qq) => $qq->whereNull('payroll_id'))
            ->when(in_array($typeFilter, ['bonus', 'penalty'], true), fn ($qq) => $qq->where('type', $typeFilter));

        // Stable ordering
        $q->orderBy($sort, $dir)->orderBy('created_at', 'desc');

        // Keep filters/sort in pagination links
        $adjustments = $q->paginate(20)->appends($request->query());
        
        return view('adjustments.index', compact('adjustments', 'payrollFilter', 'typeFilter', 'sort', 'dir'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();

        // Pobierz wszystkie payrolle z informacją o pracowniku i okresie (do późniejszego przypięcia)
        $payrolls = Payroll::with('employee')
            ->orderBy('period_start', 'desc')
            ->orderBy('employee_id')
            ->get();

        return view('adjustments.create', compact('employees', 'payrolls'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdjustmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        // Jeśli payroll wybrany — wymuś spójność employee_id
        $payroll = null;
        if (!empty($validated['payroll_id'])) {
            $payroll = Payroll::findOrFail($validated['payroll_id']);
            $validated['employee_id'] = $payroll->employee_id;
        }
        
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
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();

        // Pobierz wszystkie payrolle z informacją o pracowniku i okresie
        $payrolls = Payroll::with('employee')
            ->orderBy('period_start', 'desc')
            ->orderBy('employee_id')
            ->get();

        return view('adjustments.edit', compact('adjustment', 'employees', 'payrolls'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdjustmentRequest $request, Adjustment $adjustment): RedirectResponse
    {
        $validated = $request->validated();
        
        $payroll = null;
        if (!empty($validated['payroll_id'])) {
            // Pobierz payroll i ustaw employee_id automatycznie
            $payroll = Payroll::findOrFail($validated['payroll_id']);
            $validated['employee_id'] = $payroll->employee_id;
        }
        
        $adjustment->update($validated);
        
        // Przelicz payroll jeśli jest w statusie draft/issued
        if ($payroll && $payroll->canBeRecalculated()) {
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
