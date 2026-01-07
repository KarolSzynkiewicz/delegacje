<?php

namespace App\Http\Controllers;

use App\Models\VehicleAssignment;
use App\Models\Employee;
use App\Models\Vehicle;
use App\Services\VehicleAssignmentService;
use App\Http\Requests\StoreVehicleAssignmentRequest;
use App\Http\Requests\UpdateVehicleAssignmentRequest;
use Illuminate\Http\Request;

class VehicleAssignmentController extends Controller
{
    public function __construct(
        protected VehicleAssignmentService $assignmentService
    ) {}
    /**
     * Display all vehicle assignments (global view).
     */
    public function all()
    {
        $assignments = VehicleAssignment::with('employee', 'vehicle')
            ->orderBy('start_date', 'desc')
            ->paginate(20);
        
        return view('vehicle-assignments.index', compact('assignments'));
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Employee $employee)
    {
        $assignments = $employee->vehicleAssignments()
            ->with('vehicle')
            ->orderBy('start_date', 'desc')
            ->paginate(20);
        
        return view('vehicle-assignments.index', compact('employee', 'assignments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Employee $employee, Request $request)
    {
        $vehicles = Vehicle::orderBy('registration_number')->get();
        
        // Pobierz daty z query string jeśli są przekazane (z widoku tygodniowego)
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        
        return view('vehicle-assignments.create', compact('employee', 'vehicles', 'dateFrom', 'dateTo'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVehicleAssignmentRequest $request, Employee $employee)
    {
        $validated = $request->validated();

        try {
            $this->assignmentService->createAssignment($employee->id, $validated);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withInput()
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('employees.vehicles.index', $employee)
            ->with('success', 'Pojazd został przypisany do pracownika.');
    }

    /**
     * Display the specified resource.
     */
    public function show(VehicleAssignment $vehicleAssignment)
    {
        $vehicleAssignment->load('employee', 'vehicle');
        
        return view('vehicle-assignments.show', compact('vehicleAssignment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VehicleAssignment $vehicleAssignment)
    {
        $employees = Employee::orderBy('last_name')->get();
        $vehicles = Vehicle::orderBy('registration_number')->get();
        
        return view('vehicle-assignments.edit', compact('vehicleAssignment', 'employees', 'vehicles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVehicleAssignmentRequest $request, VehicleAssignment $vehicleAssignment)
    {
        try {
            $this->assignmentService->updateAssignment($vehicleAssignment, $request->validated());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withInput()
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('employees.vehicles.index', $vehicleAssignment->employee_id)
            ->with('success', 'Przypisanie pojazdu zostało zaktualizowane.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VehicleAssignment $vehicleAssignment)
    {
        $employeeId = $vehicleAssignment->employee_id;
        $vehicleAssignment->delete();

        return redirect()
            ->route('employees.vehicles.index', $employeeId)
            ->with('success', 'Przypisanie pojazdu zostało usunięte.');
    }
}
