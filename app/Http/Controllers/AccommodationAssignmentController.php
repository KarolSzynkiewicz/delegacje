<?php

namespace App\Http\Controllers;

use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\Accommodation;
use App\Services\AccommodationAssignmentService;
use App\Http\Requests\StoreAccommodationAssignmentRequest;
use App\Http\Requests\UpdateAccommodationAssignmentRequest;
use Illuminate\Http\Request;

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
     */
    public function create(Employee $employee, Request $request)
    {
        $accommodations = Accommodation::orderBy('name')->get();
        
        // Pobierz daty z query string jeśli są przekazane (z widoku tygodniowego)
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        
        return view('accommodation-assignments.create', compact('employee', 'accommodations', 'dateFrom', 'dateTo'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAccommodationAssignmentRequest $request, Employee $employee)
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
            ->route('employees.accommodations.index', $employee)
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
            $this->assignmentService->updateAssignment($accommodationAssignment, $request->validated());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withInput()
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('employees.accommodations.index', $accommodationAssignment->employee_id)
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
            ->route('employees.accommodations.index', $employeeId)
            ->with('success', 'Przypisanie mieszkania zostało usunięte.');
    }
}
