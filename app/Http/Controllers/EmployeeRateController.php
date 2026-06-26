<?php

namespace App\Http\Controllers;

use App\Models\EmployeeRate;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class EmployeeRateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        // Dane są pobierane przez komponent Livewire EmployeeRatesTable
        return view('employee-rates.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        return view('employee-rates.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'status' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $this->checkOverlap(
            $validated['employee_id'],
            $validated['currency'],
            $validated['start_date'],
            $validated['end_date'] ?? null
        );

        EmployeeRate::create($validated);

        return redirect()->route('employee-rates.index')
            ->with('success', 'Stawka została dodana.');
    }

    /**
     * Display the specified resource.
     */
    public function show(EmployeeRate $employeeRate): View
    {
        $employeeRate->load('employee');
        return view('employee-rates.show', compact('employeeRate'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmployeeRate $employeeRate): View
    {
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        return view('employee-rates.edit', compact('employeeRate', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeRate $employeeRate): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'notes' => 'nullable|string',
        ]);

        $this->checkOverlap(
            $validated['employee_id'],
            $validated['currency'],
            $validated['start_date'],
            $validated['end_date'] ?? null,
            $employeeRate->id
        );

        $employeeRate->update($validated);

        return redirect()->route('employee-rates.index')
            ->with('success', 'Stawka została zaktualizowana.');
    }

    /**
     * Check if a rate with the given date range overlaps with existing rates for the same employee+currency.
     * Throws ValidationException if an overlap is detected.
     *
     * @param int $excludeId Rate ID to exclude from the check (used during update)
     */
    private function checkOverlap(
        int $employeeId,
        string $currency,
        string $startDate,
        ?string $endDate,
        ?int $excludeId = null
    ): void {
        $query = EmployeeRate::where('employee_id', $employeeId)
            ->where('currency', $currency)
            ->where('status', 'active')
            // overlap: existing.start <= new.end AND (existing.end >= new.start OR existing.end IS NULL)
            ->where('start_date', '<=', $endDate ?? '9999-12-31')
            ->where(function ($q) use ($startDate) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $startDate);
            });

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'start_date' => 'Stawka nakłada się z istniejącą stawką dla tego pracownika i waluty w podanym przedziale dat. Zamknij poprzednią stawkę (ustaw datę zakończenia) przed dodaniem nowej.',
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeRate $employeeRate): RedirectResponse
    {
        $employeeRate->delete();

        return redirect()->route('employee-rates.index')
            ->with('success', 'Stawka została usunięta.');
    }
}
