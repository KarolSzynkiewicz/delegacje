<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Role;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
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
        $this->authorize('create', Employee::class);
        
        $validated = $request->validated();
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('employees', 'public');
            $validated['image_path'] = $imagePath;
        }
        
        unset($validated['image']);
        
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
        $employee->load(['roles', 'employeeDocuments.document', 'rotations']);
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
        $this->authorize('update', $employee);
        
        $validated = $request->validated();
        
        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($employee->image_path && Storage::disk('public')->exists($employee->image_path)) {
                Storage::disk('public')->delete($employee->image_path);
            }
            
            $imagePath = $request->file('image')->store('employees', 'public');
            $validated['image_path'] = $imagePath;
        }
        
        unset($validated['image']);
        
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
        $this->authorize('delete', $employee);
        
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Pracownik został usunięty.');
    }
}
