<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyAssignmentRequest;
use App\Http\Requests\UpdateCompanyAssignmentRequest;
use App\Models\Company;
use App\Models\CompanyAssignment;
use App\Models\Employee;
use App\Services\CompanyAssignmentService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyAssignmentController extends Controller
{
    public function __construct(
        protected CompanyAssignmentService $assignmentService
    ) {}

    public function index(): View
    {
        return view('company-assignments.index');
    }

    public function create(Request $request): View
    {
        $employeeId = $request->query('employee_id');
        $companyId = $request->query('company_id');

        $employee = $employeeId ? Employee::findOrFail($employeeId) : null;
        $company = $companyId ? Company::findOrFail($companyId) : null;

        $employees = $employee
            ? collect([$employee])
            : Employee::orderBy('last_name')->orderBy('first_name')->get();

        $companies = Company::orderBy('name')->get();

        if ($employeeId) {
            session()->flash('_old_input.employee_id', $employeeId);
        }

        if ($companyId) {
            session()->flash('_old_input.company_id', $companyId);
        }

        return view('company-assignments.create', compact('employee', 'company', 'employees', 'companies'));
    }

    public function store(StoreCompanyAssignmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->assignmentService->createAssignment(
            Employee::findOrFail($validated['employee_id']),
            Company::findOrFail($validated['company_id']),
            Carbon::parse($validated['start_date']),
            isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : null,
            $validated['notes'] ?? null
        );

        return redirect()->route('company-assignments.index')
            ->with('success', 'Przypisanie do spółki zostało dodane.');
    }

    public function show(CompanyAssignment $companyAssignment): View
    {
        $companyAssignment->load(['employee', 'company']);

        return view('company-assignments.show', ['assignment' => $companyAssignment]);
    }

    public function edit(CompanyAssignment $companyAssignment): View
    {
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        $companies = Company::orderBy('name')->get();

        return view('company-assignments.edit', [
            'assignment' => $companyAssignment,
            'employees' => $employees,
            'companies' => $companies,
        ]);
    }

    public function update(UpdateCompanyAssignmentRequest $request, CompanyAssignment $companyAssignment): RedirectResponse
    {
        $validated = $request->validated();

        $this->assignmentService->updateAssignment(
            $companyAssignment,
            Employee::findOrFail($validated['employee_id']),
            Company::findOrFail($validated['company_id']),
            Carbon::parse($validated['start_date']),
            isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : null,
            $validated['notes'] ?? null
        );

        return redirect()->route('company-assignments.index')
            ->with('success', 'Przypisanie do spółki zostało zaktualizowane.');
    }

    public function destroy(CompanyAssignment $companyAssignment): RedirectResponse
    {
        $companyAssignment->delete();

        return redirect()->route('company-assignments.index')
            ->with('success', 'Przypisanie do spółki zostało usunięte.');
    }
}
