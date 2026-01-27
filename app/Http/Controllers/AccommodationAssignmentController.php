<?php

namespace App\Http\Controllers;

use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\Accommodation;
use App\Services\AccommodationAssignmentService;
use App\Http\Requests\StoreAccommodationAssignmentRequest;
use App\Http\Requests\UpdateAccommodationAssignmentRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccommodationAssignmentController extends Controller
{
    public function __construct(
        protected AccommodationAssignmentService $assignmentService
    ) {}
    /**
     * Display all accommodation assignments (global view).
     */
    public function all()
    {
        $assignments = AccommodationAssignment::with('employee', 'accommodation')
            ->orderBy('start_date', 'desc')
            ->paginate(20);
        
        return view('accommodation-assignments.index', compact('assignments'));
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Employee $employee)
    {
        $assignments = $employee->accommodationAssignments()
            ->with('accommodation')
            ->orderBy('start_date', 'desc')
            ->paginate(20);
        
        return view('accommodation-assignments.index', compact('employee', 'assignments'));
    }

    /**
     * Show the form for creating a new resource.
     * //review
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
        
        $accommodations = Accommodation::orderBy('name')->get();
        
        // Pobierz daty z query string jeśli są przekazane (z widoku tygodniowego)
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        
        // If employee_id is provided, set it in old input for pre-selection
        if ($employeeId) {
            session()->flash('_old_input.employee_id', $employeeId);
        }
        
        return view('accommodation-assignments.create', compact('employee', 'employees', 'accommodations', 'dateFrom', 'dateTo'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAccommodationAssignmentRequest $request)
    {
        $validated = $request->validated();
        
        $employeeId = $validated['employee_id'] ?? $request->input('employee_id');
        if (!$employeeId) {
            return redirect()->route('employees.index')
                ->with('error', 'Musisz wybrać pracownika');
        }
        
        $employee = Employee::findOrFail($employeeId);

        try {
            $accommodation = Accommodation::findOrFail($validated['accommodation_id']);
            $startDate = \Carbon\Carbon::parse($validated['start_date']);
            $endDate = isset($validated['end_date']) ? \Carbon\Carbon::parse($validated['end_date']) : null;
            
            $this->assignmentService->createAssignment(
                $employee,
                $accommodation,
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
            ->with('success', 'Mieszkanie zostało przypisane do pracownika.');
    }

    /**
     * Display the specified resource.
     */
    public function show(AccommodationAssignment $accommodationAssignment)
    {
        $accommodationAssignment->load('employee', 'accommodation');
        
        return view('accommodation-assignments.show', compact('accommodationAssignment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AccommodationAssignment $accommodationAssignment)
    {
        $employees = Employee::orderBy('last_name')->get();
        $accommodations = Accommodation::orderBy('name')->get();
        
        return view('accommodation-assignments.edit', compact('accommodationAssignment', 'employees', 'accommodations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAccommodationAssignmentRequest $request, AccommodationAssignment $accommodationAssignment)
    {
        try {
            $validated = $request->validated();
            
            $accommodation = Accommodation::findOrFail($validated['accommodation_id']);
            $startDate = \Carbon\Carbon::parse($validated['start_date']);
            $endDate = isset($validated['end_date']) ? \Carbon\Carbon::parse($validated['end_date']) : null;
            
            $this->assignmentService->updateAssignment(
                $accommodationAssignment,
                $accommodation,
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
            ->route('employees.show', $accommodationAssignment->employee_id)
            ->with('success', 'Przypisanie mieszkania zostało zaktualizowane.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AccommodationAssignment $accommodationAssignment)
    {
        $employeeId = $accommodationAssignment->employee_id;
        $accommodationAssignment->delete();

        return redirect()
            ->route('employees.show', $employeeId)
            ->with('success', 'Przypisanie mieszkania zostało usunięte.');
    }
}
