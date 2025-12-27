<?php

namespace App\Http\Controllers;

use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\Accommodation;
use Illuminate\Http\Request;

class AccommodationAssignmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $assignments = AccommodationAssignment::with('employee', 'accommodation')
            ->orderBy('start_date', 'desc')
            ->paginate(20);
        
        return view('accommodation-assignments.index', compact('assignments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::with('role')->orderBy('last_name')->get();
        $accommodations = Accommodation::orderBy('name')->get();
        
        return view('accommodation-assignments.create', compact('employees', 'accommodations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'accommodation_id' => 'required|exists:accommodations,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        // Check accommodation capacity
        $accommodation = Accommodation::findOrFail($validated['accommodation_id']);
        $endDate = $validated['end_date'] ?? now()->addYears(10);
        
        if (!$accommodation->hasAvailableSpace($validated['start_date'], $endDate)) {
            return back()
                ->withInput()
                ->withErrors(['accommodation_id' => 'Brak wolnych miejsc w tym mieszkaniu w wybranym okresie.']);
        }

        $assignment = AccommodationAssignment::create($validated);

        return redirect()
            ->route('accommodation-assignments.index')
            ->with('success', 'Przypisanie mieszkania zostało utworzone.');
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
        $employees = Employee::with('role')->orderBy('last_name')->get();
        $accommodations = Accommodation::orderBy('name')->get();
        
        return view('accommodation-assignments.edit', compact('accommodationAssignment', 'employees', 'accommodations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AccommodationAssignment $accommodationAssignment)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'accommodation_id' => 'required|exists:accommodations,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        $accommodationAssignment->update($validated);

        return redirect()
            ->route('accommodation-assignments.index')
            ->with('success', 'Przypisanie mieszkania zostało zaktualizowane.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AccommodationAssignment $accommodationAssignment)
    {
        $accommodationAssignment->delete();

        return redirect()
            ->route('accommodation-assignments.index')
            ->with('success', 'Przypisanie mieszkania zostało usunięte.');
    }
}
