<?php
//review 
namespace App\Http\Controllers;

use App\Models\VehicleAssignment;
use App\Models\Employee;
use App\Models\Vehicle;
use App\Services\VehicleAssignmentService;
use App\Http\Requests\StoreVehicleAssignmentRequest;
use App\Http\Requests\UpdateVehicleAssignmentRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
    public function create(Request $request): View
    {
        $employeeId = $request->query('employee_id');
        $employee = null;
        
        if ($employeeId) {
            $employee = Employee::findOrFail($employeeId);
        }
        
        // Jeśli nie ma pracownika, pobierz listę pracowników do wyboru
        $employees = $employee ? collect([$employee]) : Employee::orderBy('last_name')->orderBy('first_name')->get();
        
        $vehicles = Vehicle::orderBy('registration_number')->get();
        
        // Pobierz daty z query string jeśli są przekazane (z widoku tygodniowego)
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        
        // If employee_id is provided, set it in old input for pre-selection
        if ($employeeId) {
            session()->flash('_old_input.employee_id', $employeeId);
        }
        
        return view('vehicle-assignments.create', compact('employee', 'employees', 'vehicles', 'dateFrom', 'dateTo'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVehicleAssignmentRequest $request)
    {
        $validated = $request->validated();
        
        $employeeId = $validated['employee_id'] ?? $request->input('employee_id');
        if (!$employeeId) {
            return redirect()->route('employees.index')
                ->with('error', 'Musisz wybrać pracownika');
        }
        
        $employee = Employee::findOrFail($employeeId);

        try {
            $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
            $position = \App\Enums\VehiclePosition::from($validated['position']);
            $startDate = \Carbon\Carbon::parse($validated['start_date']);
            $endDate = isset($validated['end_date']) ? \Carbon\Carbon::parse($validated['end_date']) : null;
            
            $this->assignmentService->createAssignment(
                $employee,
                $vehicle,
                $position,
                $startDate,
                $endDate,
                $validated['notes'] ?? null
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withInput()
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('employees.show', $employee)
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
            $validated = $request->validated();
            
            $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
            $position = \App\Enums\VehiclePosition::from($validated['position']);
            $startDate = \Carbon\Carbon::parse($validated['start_date']);
            $endDate = isset($validated['end_date']) ? \Carbon\Carbon::parse($validated['end_date']) : null;
            
            $this->assignmentService->updateAssignment(
                $vehicleAssignment,
                $vehicle,
                $position,
                $startDate,
                $endDate,
                $validated['notes'] ?? null
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withInput()
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('employees.show', $vehicleAssignment->employee_id)
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
            ->route('employees.show', $employeeId)
            ->with('success', 'Przypisanie pojazdu zostało usunięte.');
    }
}
