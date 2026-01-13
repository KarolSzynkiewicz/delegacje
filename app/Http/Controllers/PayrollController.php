<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\Employee;
use App\Services\GeneratePayrollForEmployee;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class PayrollController extends Controller
{
    public function __construct(
        protected GeneratePayrollForEmployee $generatePayrollService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        // Dane są pobierane przez komponent Livewire PayrollsTable
        return view('payrolls.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        return view('payrolls.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'currency' => 'required|string|size:3',
            'notes' => 'nullable|string',
        ]);

        try {
            $payroll = $this->generatePayrollService->generate(
                $validated['employee_id'],
                $validated['period_start'],
                $validated['period_end'],
                $validated['currency'],
                $validated['notes'] ?? null
            );

            return redirect()->route('payrolls.show', $payroll)
                ->with('success', 'Payroll został wygenerowany.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Payroll $payroll): View
    {
        $payroll->load('employee');
        return view('payrolls.show', compact('payroll'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payroll $payroll): View
    {
        // Payroll jest niemutowalny, ale można edytować tylko adjustments_amount i status
        return view('payrolls.edit', compact('payroll'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payroll $payroll): RedirectResponse
    {
        $validated = $request->validate([
            'adjustments_amount' => 'required|numeric',
            'status' => 'required|string|in:draft,approved,paid',
            'notes' => 'nullable|string',
        ]);

        $payroll->adjustments_amount = $validated['adjustments_amount'];
        $payroll->status = $validated['status'];
        if (isset($validated['notes'])) {
            $payroll->notes = $validated['notes'];
        }
        
        // Recalculate total
        $payroll->recalculateTotal();
        $payroll->save();

        return redirect()->route('payrolls.show', $payroll)
            ->with('success', 'Payroll został zaktualizowany.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payroll $payroll): RedirectResponse
    {
        // Only allow deletion of draft payrolls
        if ($payroll->status->value !== 'draft') {
            return back()
                ->withErrors(['error' => 'Można usunąć tylko payroll ze statusem "Szkic".']);
        }

        $payroll->delete();

        return redirect()->route('payrolls.index')
            ->with('success', 'Payroll został usunięty.');
    }
}
