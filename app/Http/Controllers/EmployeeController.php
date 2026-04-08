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
        try {
            // Sprawdź powiązane rekordy przed usunięciem
            $assignmentsCount = $employee->assignments()->count();
            $vehicleAssignmentsCount = $employee->vehicleAssignments()->count();
            $accommodationAssignmentsCount = $employee->accommodationAssignments()->count();
            $payrollsCount = $employee->payrolls()->count();
            $advancesCount = $employee->advances()->count();
            $adjustmentsCount = $employee->adjustments()->count();
            $evaluationsCount = $employee->evaluations()->count();
            $rotationsCount = $employee->rotations()->count();
            $employeeDocumentsCount = $employee->employeeDocuments()->count();
            
            // Sprawdź czy są time logs przez assignments
            $timeLogsCount = \App\Models\TimeLog::whereHas('projectAssignment', function($query) use ($employee) {
                $query->where('employee_id', $employee->id);
            })->count();
            
            $employee->delete();
            
            $message = "Pracownik został usunięty.";
            if ($assignmentsCount > 0 || $vehicleAssignmentsCount > 0 || $accommodationAssignmentsCount > 0 || 
                $payrollsCount > 0 || $advancesCount > 0 || $adjustmentsCount > 0 || 
                $evaluationsCount > 0 || $rotationsCount > 0 || $employeeDocumentsCount > 0 || $timeLogsCount > 0) {
                $message .= " Usunięto również: ";
                $deleted = [];
                if ($assignmentsCount > 0) $deleted[] = "{$assignmentsCount} przypisania do projektów";
                if ($timeLogsCount > 0) $deleted[] = "{$timeLogsCount} wpisów czasu pracy";
                if ($vehicleAssignmentsCount > 0) $deleted[] = "{$vehicleAssignmentsCount} przypisania do aut";
                if ($accommodationAssignmentsCount > 0) $deleted[] = "{$accommodationAssignmentsCount} przypisania do domów";
                if ($payrollsCount > 0) $deleted[] = "{$payrollsCount} rekordów płac";
                if ($advancesCount > 0) $deleted[] = "{$advancesCount} zaliczki";
                if ($adjustmentsCount > 0) $deleted[] = "{$adjustmentsCount} obciążeń i uznań";
                if ($evaluationsCount > 0) $deleted[] = "{$evaluationsCount} oceny";
                if ($rotationsCount > 0) $deleted[] = "{$rotationsCount} rotacje";
                if ($employeeDocumentsCount > 0) $deleted[] = "{$employeeDocumentsCount} dokumenty";
                $message .= implode(", ", $deleted) . ".";
            }
            
            return redirect()->route('employees.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('employees.index')
                ->with('error', 'Wystąpił błąd podczas usuwania pracownika: ' . $e->getMessage());
        }
    }
}
