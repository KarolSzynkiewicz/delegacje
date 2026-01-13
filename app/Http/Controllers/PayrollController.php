<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\Employee;
use App\Services\GeneratePayrollForEmployee;
use App\Http\Requests\GenerateBatchPayrollRequest;
use App\Http\Requests\StorePayrollRequest;
use App\Http\Requests\UpdatePayrollRequest;
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
     * Show the form for creating a new resource (single employee).
     */
    public function create(): View
    {
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        return view('payrolls.create', compact('employees'));
    }

    /**
     * Show the form for generating payroll for all employees (batch).
     */
    public function generateBatchForm(): View
    {
        return view('payrolls.generate-batch');
    }

    /**
     * Generate payroll for all employees who have any TimeLogs in the period.
     */
    public function generateBatch(GenerateBatchPayrollRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $periodStart = Carbon::parse($validated['period_start']);
        $periodEnd = Carbon::parse($validated['period_end']);

        // Find all employees who have TimeLogs in this period
        $employeeIds = $this->generatePayrollService->getEmployeeIdsWithTimeLogsInPeriod($periodStart, $periodEnd);

        if ($employeeIds->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Nie znaleziono żadnych pracowników z logowanymi godzinami w tym okresie.']);
        }

        $generated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($employeeIds as $employeeId) {
            try {
                // Check if payroll already exists
                if (Payroll::existsForPeriod($employeeId, $periodStart, $periodEnd)) {
                    $skipped++;
                    continue;
                }

                $this->generatePayrollService->generate(
                    $employeeId,
                    $periodStart,
                    $periodEnd,
                    'PLN', // Domyślna waluta, system automatycznie określi właściwą na podstawie stawek
                    $validated['notes'] ?? null
                );
                $generated++;
            } catch (\Exception $e) {
                $errors[] = "Pracownik ID {$employeeId}: " . $e->getMessage();
            }
        }

        $message = "Wygenerowano {$generated} list płac.";
        if ($skipped > 0) {
            $message .= " Pominięto {$skipped} (już istnieją).";
        }
        if (!empty($errors)) {
            $message .= " Błędy: " . implode(', ', $errors);
        }

        return redirect()->route('payrolls.index')
            ->with('success', $message);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePayrollRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $payroll = $this->generatePayrollService->generate(
                $validated['employee_id'],
                $validated['period_start'],
                $validated['period_end'],
                'PLN', // Domyślna waluta, system automatycznie określi właściwą na podstawie stawek
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
        $payroll->load(['employee', 'adjustments', 'advances']);
        
        // Get TimeLogs for this payroll period
        $timeLogs = $this->generatePayrollService->getTimeLogsForPeriod(
            $payroll->employee_id,
            $payroll->period_start,
            $payroll->period_end
        )->load('projectAssignment.project');
        
        // Prepare detailed hours breakdown
        $hoursBreakdown = [];
        foreach ($timeLogs as $timeLog) {
            $workDate = \Carbon\Carbon::parse($timeLog->start_time)->toDateString();
            $hoursWorked = (float) $timeLog->hours_worked;
            
            if ($hoursWorked <= 0) {
                continue;
            }
            
            // Find rate for this date
            $rate = $this->generatePayrollService->findEmployeeRateForDate(
                $payroll->employee_id,
                $workDate,
                $payroll->currency
            );
            
            if (!$rate) {
                $rate = $this->generatePayrollService->findAnyEmployeeRateForDate(
                    $payroll->employee_id,
                    $workDate
                );
            }
            
            if ($rate) {
                $amount = $hoursWorked * (float) $rate->amount;
                $hoursBreakdown[] = [
                    'date' => $workDate,
                    'project' => $timeLog->projectAssignment->project->name ?? 'N/A',
                    'hours' => $hoursWorked,
                    'rate' => (float) $rate->amount,
                    'rate_currency' => $rate->currency,
                    'amount' => $amount,
                    'notes' => $timeLog->notes,
                ];
            }
        }
        
        return view('payrolls.show', compact('payroll', 'hoursBreakdown'));
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
    public function update(UpdatePayrollRequest $request, Payroll $payroll): RedirectResponse
    {
        $validated = $request->validated();

        // Recalculate adjustments_amount from advances/adjustments linked to this payroll
        $payroll->adjustments_amount = $this->generatePayrollService->calculateAdjustmentsAmountForPayroll($payroll);
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
     * Recalculate single payroll based on current EmployeeRates, Adjustments and Advances.
     * This is useful when rates, adjustments or advances are added or changed after payroll generation.
     */
    public function recalculate(Payroll $payroll): RedirectResponse
    {
        try {
            $this->generatePayrollService->recalculate($payroll);
            
            return back()->with('success', 'Payroll został przeliczony (godziny, kary, nagrody i zaliczki).');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Błąd podczas przeliczania: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payroll $payroll): RedirectResponse
    {
        // Only allow deletion of draft payrolls
        if (!$payroll->canBeDeleted()) {
            return back()
                ->withErrors(['error' => 'Można usunąć tylko payroll ze statusem "Szkic".']);
        }

        $payroll->delete();

        return redirect()->route('payrolls.index')
            ->with('success', 'Payroll został usunięty.');
    }

    /**
     * Recalculate all payrolls that can be recalculated.
     */
    public function recalculateAll(): RedirectResponse
    {
        $payrolls = Payroll::recalculatable()->get();
        $recalculated = 0;
        $errors = [];

        foreach ($payrolls as $payroll) {
            try {
                $this->generatePayrollService->recalculate($payroll);
                $recalculated++;
            } catch (\Exception $e) {
                $errors[] = "Payroll ID {$payroll->id}: " . $e->getMessage();
            }
        }

        $message = "Przeliczono {$recalculated} payrolli.";
        if (!empty($errors)) {
            $message .= " Błędy: " . implode(', ', $errors);
        }

        return redirect()->route('payrolls.index')
            ->with('success', $message);
    }
}
