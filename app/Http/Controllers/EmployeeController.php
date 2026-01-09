<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Role;
use App\Services\ImageService;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class EmployeeController extends Controller
{
    public function __construct(
        protected ImageService $imageService
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Employee::class);
        // Dane są pobierane przez komponent Livewire EmployeesTable
        return view('employees.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Employee::class);
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
            $validated['image_path'] = $this->imageService->storeImage($request->file('image'), 'employees');
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
        $this->authorize('view', $employee);
        $employee->load(['roles', 'employeeDocuments.document', 'rotations']);
        
        // Get project assignments ordered by start_date descending (newest first)
        $projectAssignments = $employee->assignments()
            ->with(['project', 'role'])
            ->orderBy('start_date', 'desc')
            ->get();
        
        // Get vehicle assignments ordered by start_date descending
        $vehicleAssignments = $employee->vehicleAssignments()
            ->with(['vehicle'])
            ->orderBy('start_date', 'desc')
            ->get();
        
        // Get accommodation assignments ordered by start_date descending
        $accommodationAssignments = $employee->accommodationAssignments()
            ->with(['accommodation'])
            ->orderBy('start_date', 'desc')
            ->get();
        
        return view('employees.show', compact('employee', 'projectAssignments', 'vehicleAssignments', 'accommodationAssignments'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee): View
    {
        $this->authorize('update', $employee);
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
            $validated['image_path'] = $this->imageService->handleImageUpload(
                $request->file('image'),
                'employees',
                $employee->image_path
            );
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
