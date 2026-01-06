<?php

namespace App\Http\Controllers;

use App\Models\VehicleAssignment;
use App\Models\Employee;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleAssignmentController extends Controller
{
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
    public function store(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        // Check vehicle availability
        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
        $endDate = $validated['end_date'] ?? now()->addYears(10);
        
        if (!$vehicle->isAvailableInDateRange($validated['start_date'], $endDate)) {
            return back()
                ->withInput()
                ->withErrors(['vehicle_id' => 'Pojazd jest już przypisany w tym okresie.']);
        }

        $assignment = $employee->vehicleAssignments()->create($validated);

        return redirect()
            ->route('employees.vehicles.index', $employee)
            ->with('success', 'Pojazd został przypisany do pracownika.');
    }

    /**
     * Display the specified resource.
     */
    public function show(VehicleAssignment $vehicle)
    {
        $vehicle->load('employee', 'vehicle');
        
        return view('vehicle-assignments.show', compact('vehicle'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VehicleAssignment $vehicle)
    {
        $employees = Employee::orderBy('last_name')->get();
        $vehicles = Vehicle::orderBy('registration_number')->get();
        
        return view('vehicle-assignments.edit', compact('vehicle', 'employees', 'vehicles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, VehicleAssignment $vehicle)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        $vehicle->update($validated);

        return redirect()
            ->route('employees.vehicles.index', $vehicle->employee_id)
            ->with('success', 'Przypisanie pojazdu zostało zaktualizowane.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VehicleAssignment $vehicle)
    {
        $employeeId = $vehicle->employee_id;
        $vehicle->delete();

        return redirect()
            ->route('employees.vehicles.index', $employeeId)
            ->with('success', 'Przypisanie pojazdu zostało usunięte.');
    }
}
