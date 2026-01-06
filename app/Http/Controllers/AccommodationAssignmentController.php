<?php

namespace App\Http\Controllers;

use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\Accommodation;
use App\Http\Requests\StoreAccommodationAssignmentRequest;
use App\Http\Requests\UpdateAccommodationAssignmentRequest;
use Illuminate\Http\Request;

class AccommodationAssignmentController extends Controller
{
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

        // Check accommodation capacity
        $accommodation = Accommodation::findOrFail($validated['accommodation_id']);
        $endDate = $validated['end_date'] ?? now()->addYears(10);
        
        if (!$accommodation->hasAvailableSpace($validated['start_date'], $endDate)) {
            return back()
                ->withInput()
                ->withErrors(['accommodation_id' => 'Brak wolnych miejsc w tym mieszkaniu w wybranym okresie.']);
        }

        $assignment = $employee->accommodationAssignments()->create($validated);

        return redirect()
            ->route('employees.accommodations.index', $employee)
            ->with('success', 'Mieszkanie zostało przypisane do pracownika.');
    }

    /**
     * Display the specified resource.
     */
    public function show(AccommodationAssignment $accommodation)
    {
        $accommodation->load('employee', 'accommodation');
        
        return view('accommodation-assignments.show', compact('accommodation'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AccommodationAssignment $accommodation)
    {
        $employees = Employee::orderBy('last_name')->get();
        $accommodations = Accommodation::orderBy('name')->get();
        
        return view('accommodation-assignments.edit', compact('accommodation', 'employees', 'accommodations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAccommodationAssignmentRequest $request, AccommodationAssignment $accommodation)
    {
        $accommodation->update($request->validated());

        return redirect()
            ->route('employees.accommodations.index', $accommodation->employee_id)
            ->with('success', 'Przypisanie mieszkania zostało zaktualizowane.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AccommodationAssignment $accommodation)
    {
        $employeeId = $accommodation->employee_id;
        $accommodation->delete();

        return redirect()
            ->route('employees.accommodations.index', $employeeId)
            ->with('success', 'Przypisanie mieszkania zostało usunięte.');
    }
}
