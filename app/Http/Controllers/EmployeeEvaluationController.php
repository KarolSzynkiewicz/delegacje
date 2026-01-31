<?php

namespace App\Http\Controllers;

use App\Models\EmployeeEvaluation;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class EmployeeEvaluationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        // Dane są pobierane przez komponent Livewire EmployeeEvaluationsTable
        return view('employee-evaluations.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        return view('employee-evaluations.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'engagement' => 'required|integer|min:1|max:10',
            'skills' => 'required|integer|min:1|max:10',
            'orderliness' => 'required|integer|min:1|max:10',
            'behavior' => 'required|integer|min:1|max:10',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Autoryzacja przez Policy
        $this->authorize('create', [EmployeeEvaluation::class, $validated['employee_id']]);

        $validated['created_by'] = auth()->id();

        EmployeeEvaluation::create($validated);

        return redirect()->route('employee-evaluations.index')
            ->with('success', 'Ocena została dodana.');
    }

    /**
     * Display the specified resource.
     */
    public function show(EmployeeEvaluation $employeeEvaluation): View
    {
        $employeeEvaluation->load(['employee', 'createdBy']);
        return view('employee-evaluations.show', compact('employeeEvaluation'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmployeeEvaluation $employeeEvaluation): View
    {
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        return view('employee-evaluations.edit', compact('employeeEvaluation', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeEvaluation $employeeEvaluation): RedirectResponse
    {
        // Autoryzacja przez Policy
        $this->authorize('update', $employeeEvaluation);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'engagement' => 'required|integer|min:1|max:10',
            'skills' => 'required|integer|min:1|max:10',
            'orderliness' => 'required|integer|min:1|max:10',
            'behavior' => 'required|integer|min:1|max:10',
            'notes' => 'nullable|string|max:1000',
        ]);

        $employeeEvaluation->update($validated);

        return redirect()->route('employee-evaluations.index')
            ->with('success', 'Ocena została zaktualizowana.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeEvaluation $employeeEvaluation): RedirectResponse
    {
        // Autoryzacja przez Policy
        $this->authorize('delete', $employeeEvaluation);

        $employeeEvaluation->delete();

        return redirect()->route('employee-evaluations.index')
            ->with('success', 'Ocena została usunięta.');
    }
}
