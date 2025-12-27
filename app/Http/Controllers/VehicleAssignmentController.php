<?php

namespace App\Http\Controllers;

use App\Models\VehicleAssignment;
use App\Models\Employee;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleAssignmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $assignments = VehicleAssignment::with('employee', 'vehicle')
            ->orderBy('start_date', 'desc')
            ->paginate(20);
        
        return view('vehicle-assignments.index', compact('assignments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::with('role')->orderBy('last_name')->get();
        $vehicles = Vehicle::orderBy('registration_number')->get();
        
        return view('vehicle-assignments.create', compact('employees', 'vehicles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
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

        $assignment = VehicleAssignment::create($validated);

        return redirect()
            ->route('vehicle-assignments.index')
            ->with('success', 'Przypisanie pojazdu zostało utworzone.');
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
        $employees = Employee::with('role')->orderBy('last_name')->get();
        $vehicles = Vehicle::orderBy('registration_number')->get();
        
        return view('vehicle-assignments.edit', compact('vehicleAssignment', 'employees', 'vehicles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, VehicleAssignment $vehicleAssignment)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        $vehicleAssignment->update($validated);

        return redirect()
            ->route('vehicle-assignments.index')
            ->with('success', 'Przypisanie pojazdu zostało zaktualizowane.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VehicleAssignment $vehicleAssignment)
    {
        $vehicleAssignment->delete();

        return redirect()
            ->route('vehicle-assignments.index')
            ->with('success', 'Przypisanie pojazdu zostało usunięte.');
    }
}
