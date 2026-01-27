<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Role;
use App\Http\Controllers\Concerns\HandlesImageUpload;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class EmployeeController extends Controller
{
    use HandlesImageUpload;
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
        return view('employees.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $validated = $this->processImageUpload($request->validated(), $request, 'employees');
        
        $roles = $validated['roles'] ?? [];
        unset($validated['roles']);
        
        $employee = Employee::create($validated);
        $employee->roles()->attach($roles);
        
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
        unset($validated['roles']);
        
        $employee->update($validated);
        $employee->roles()->sync($roles);
        
        return redirect()->route('employees.show', $employee)->with('success', 'Pracownik został zaktualizowany.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Pracownik został usunięty.');
    }
}
