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
        return $this->showWithTab($employee, 'info');
    }

    /**
     * Display employee documents tab.
     */
    public function showDocuments(Employee $employee): View
    {
        return $this->showWithTab($employee, 'documents');
    }

    /**
     * Display employee rotations tab.
     */
    public function showRotations(Employee $employee): View
    {
        return $this->showWithTab($employee, 'rotations');
    }

    /**
     * Display employee project assignments tab.
     */
    public function showAssignments(Employee $employee): View
    {
        return $this->showWithTab($employee, 'assignments');
    }

    /**
     * Display employee vehicle assignments tab.
     */
    public function showVehicleAssignments(Employee $employee): View
    {
        return $this->showWithTab($employee, 'vehicle-assignments');
    }

    /**
     * Display employee accommodation assignments tab.
     */
    public function showAccommodationAssignments(Employee $employee): View
    {
        return $this->showWithTab($employee, 'accommodation-assignments');
    }

    /**
     * Display employee payrolls tab.
     */
    public function showPayrolls(Employee $employee): View
    {
        return $this->showWithTab($employee, 'payrolls');
    }

    /**
     * Display employee rates tab.
     */
    public function showEmployeeRates(Employee $employee): View
    {
        return $this->showWithTab($employee, 'employee-rates');
    }

    /**
     * Display employee advances tab.
     */
    public function showAdvances(Employee $employee): View
    {
        return $this->showWithTab($employee, 'advances');
    }

    /**
     * Display employee time logs tab.
     */
    public function showTimeLogs(Employee $employee): View
    {
        return $this->showWithTab($employee, 'time-logs');
    }

    /**
     * Display employee adjustments tab.
     */
    public function showAdjustments(Employee $employee): View
    {
        return $this->showWithTab($employee, 'adjustments');
    }

    /**
     * Helper method to show employee with specified active tab.
     */
    protected function showWithTab(Employee $employee, string $activeTab): View
    {
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
        
        // Get data for new tabs
        $payrolls = $employee->payrolls()
            ->orderBy('period_start', 'desc')
            ->get();
        
        $employeeRates = \App\Models\EmployeeRate::where('employee_id', $employee->id)
            ->orderBy('start_date', 'desc')
            ->get();
        
        $advances = $employee->advances()
            ->orderBy('date', 'desc')
            ->get();
        
        // Get time logs through project assignments
        $timeLogs = \App\Models\TimeLog::whereHas('projectAssignment', function($query) use ($employee) {
                $query->where('employee_id', $employee->id);
            })
            ->with(['projectAssignment.project', 'projectAssignment.role'])
            ->orderBy('start_time', 'desc')
            ->get();
        
        $adjustments = $employee->adjustments()
            ->orderBy('date', 'desc')
            ->get();
        
        return view('employees.show', compact(
            'employee', 
            'projectAssignments', 
            'vehicleAssignments', 
            'accommodationAssignments',
            'payrolls',
            'employeeRates',
            'advances',
            'timeLogs',
            'adjustments',
            'activeTab'
        ));
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
