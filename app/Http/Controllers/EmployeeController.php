<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesImageUpload;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\Role;
use App\Services\EmployeeLifecycleService;
use App\Services\EmployeeRoleSeniorityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    use HandlesImageUpload;

    public function __construct(
        protected EmployeeLifecycleService $employeeLifecycle,
        protected EmployeeRoleSeniorityService $roleSeniority
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        // Dane są pobierane przez komponent Livewire EmployeesTable
        return view('employees.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $roles = Role::all();
        $hasRoles = $roles->count() > 0;

        return view('employees.create', compact('roles', 'hasRoles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $validated = $this->processImageUpload($request->validated(), $request, 'employees');

        $roles = $validated['roles'] ?? [];
        $seniority = $validated['role_seniority'] ?? [];
        unset($validated['roles'], $validated['role_seniority']);

        // Jedna transakcja: jeśli powiązanie kandydata / zapis cyklu życia się nie powiedzie,
        // cofamy też utworzenie pracownika i ról.
        $employee = DB::transaction(function () use ($validated, $roles, $seniority) {
            $employee = Employee::create($validated);
            $this->roleSeniority->syncRoles($employee, $roles, $seniority);

            $this->employeeLifecycle->recordHireOutsideProcess($employee);

            return $employee;
        });

        return redirect()->route('employees.index')->with('success', 'Pracownik został dodany.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee): View
    {
        // Tylko podstawowe dane - reszta w Livewire EmployeeTabs
        $employee->load('roles');

        return view('employees.show', compact('employee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee): View
    {
        $roles = Role::all();

        return view('employees.edit', compact('employee', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $validated = $this->processImageUpload($request->validated(), $request, 'employees', $employee->image_path);

        $roles = $validated['roles'] ?? [];
        $seniority = $validated['role_seniority'] ?? [];
        unset($validated['roles'], $validated['role_seniority']);

        $employee->update($validated);
        $this->roleSeniority->syncRoles($employee, $roles, $seniority);

        return redirect()->route('employees.show', $employee)->with('success', 'Pracownik został zaktualizowany.');
    }
}
