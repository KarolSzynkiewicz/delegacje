<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeBankAccountRequest;
use App\Http\Requests\UpdateEmployeeBankAccountRequest;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployeeBankAccountController extends Controller
{
    public function create(Request $request): View
    {
        $selectedEmployeeId = $request->query('employee_id');
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        $backUrl = $this->backUrl($selectedEmployeeId);

        return view('employee-bank-accounts.create', compact('employees', 'selectedEmployeeId', 'backUrl'));
    }

    public function store(StoreEmployeeBankAccountRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->checkOverlap(
            (int) $validated['employee_id'],
            $validated['start_date'],
            $validated['end_date'] ?? null
        );

        EmployeeBankAccount::create($validated);

        return $this->redirectToEmployee((int) $validated['employee_id'])
            ->with('success', 'Konto bankowe zostało dodane.');
    }

    public function show(EmployeeBankAccount $employeeBankAccount): View
    {
        $employeeBankAccount->load('employee');
        $backUrl = $this->backUrl($employeeBankAccount->employee_id);

        return view('employee-bank-accounts.show', compact('employeeBankAccount', 'backUrl'));
    }

    public function edit(EmployeeBankAccount $employeeBankAccount): View
    {
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        $backUrl = $this->backUrl($employeeBankAccount->employee_id);

        return view('employee-bank-accounts.edit', compact('employeeBankAccount', 'employees', 'backUrl'));
    }

    public function update(UpdateEmployeeBankAccountRequest $request, EmployeeBankAccount $employeeBankAccount): RedirectResponse
    {
        $validated = $request->validated();

        $this->checkOverlap(
            (int) $validated['employee_id'],
            $validated['start_date'],
            $validated['end_date'] ?? null,
            $employeeBankAccount->id
        );

        $employeeBankAccount->update($validated);

        return $this->redirectToEmployee((int) $validated['employee_id'])
            ->with('success', 'Konto bankowe zostało zaktualizowane.');
    }

    public function destroy(EmployeeBankAccount $employeeBankAccount): RedirectResponse
    {
        $employeeId = $employeeBankAccount->employee_id;
        $employeeBankAccount->delete();

        return $this->redirectToEmployee($employeeId)
            ->with('success', 'Konto bankowe zostało usunięte.');
    }

    private function checkOverlap(
        int $employeeId,
        string $startDate,
        ?string $endDate,
        ?int $excludeId = null
    ): void {
        $query = EmployeeBankAccount::query()
            ->where('employee_id', $employeeId)
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
                'start_date' => 'Konto nakłada się z istniejącym numerem w podanym przedziale dat. Zamknij poprzednie konto (ustaw datę zakończenia) przed dodaniem nowego.',
            ]);
        }
    }

    private function redirectToEmployee(int $employeeId): RedirectResponse
    {
        return redirect()->route('employees.show', [
            'employee' => $employeeId,
            'tab' => 'bank',
        ]);
    }

    private function backUrl(int|string|null $employeeId): string
    {
        if ($employeeId) {
            return route('employees.show', [
                'employee' => $employeeId,
                'tab' => 'bank',
            ]);
        }

        return route('employees.index');
    }
}
