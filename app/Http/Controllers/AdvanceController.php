<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdvanceRequest;
use App\Http\Requests\UpdateAdvanceRequest;
use App\Models\Advance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\GeneratePayrollForEmployee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdvanceController extends Controller
{
    public function index(Request $request): View
    {
        $payrollFilter = $request->query('payroll', 'all');

        $advances = Advance::query()
            ->with(['employee', 'payroll'])
            ->when($payrollFilter === 'linked', fn ($q) => $q->whereNotNull('payroll_id'))
            ->when($payrollFilter === 'unlinked', fn ($q) => $q->whereNull('payroll_id'))
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        return view('advances.index', compact('advances', 'payrollFilter'));
    }

    public function create(): View
    {
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        $payrolls = Payroll::with('employee')
            ->orderBy('period_start', 'desc')
            ->orderBy('employee_id')
            ->get();

        return view('advances.create', compact('employees', 'payrolls'));
    }

    public function store(StoreAdvanceRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $payroll = $this->syncEmployeeFromPayroll($validated);

        Advance::create($validated);

        $this->recalculatePayroll($payroll);

        return redirect()->route('advances.index')
            ->with('success', 'Zaliczka została dodana.');
    }

    public function show(Advance $advance): View
    {
        $advance->load(['employee', 'payroll.employee']);

        return view('advances.show', compact('advance'));
    }

    public function edit(Advance $advance): View
    {
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        $payrolls = Payroll::with('employee')
            ->orderBy('period_start', 'desc')
            ->orderBy('employee_id')
            ->get();

        return view('advances.edit', compact('advance', 'employees', 'payrolls'));
    }

    public function update(UpdateAdvanceRequest $request, Advance $advance): RedirectResponse
    {
        $validated = $request->validated();
        $payroll = $this->syncEmployeeFromPayroll($validated);

        $advance->update($validated);

        $this->recalculatePayroll($payroll);

        return redirect()->route('advances.index')
            ->with('success', 'Zaliczka została zaktualizowana.');
    }

    public function destroy(Advance $advance): RedirectResponse
    {
        $payroll = $advance->payroll;
        $advance->delete();

        $this->recalculatePayroll($payroll);

        return redirect()->route('advances.index')
            ->with('success', 'Zaliczka została usunięta.');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncEmployeeFromPayroll(array &$validated): ?Payroll
    {
        if (empty($validated['payroll_id'])) {
            $validated['payroll_id'] = null;

            return null;
        }

        $payroll = Payroll::findOrFail($validated['payroll_id']);
        $validated['employee_id'] = $payroll->employee_id;

        return $payroll;
    }

    private function recalculatePayroll(?Payroll $payroll): void
    {
        if (! $payroll || ! $payroll->canBeRecalculated()) {
            return;
        }

        $payroll->adjustments_amount = app(GeneratePayrollForEmployee::class)
            ->calculateAdjustmentsAmountForPayroll($payroll);
        $payroll->recalculateTotal();
        $payroll->save();
    }
}
