<?php

namespace App\Http\Controllers;

use App\Models\EmployeeRate;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

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

        $employeeRate->update($validated);

        return redirect()->route('employee-rates.index')
            ->with('success', 'Stawka została zaktualizowana.');
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
