<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Rotation;
use App\Services\RotationService;
use App\Http\Requests\StoreRotationRequest;
use App\Http\Requests\UpdateRotationRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class RotationController extends Controller
{
    public function __construct(
        protected RotationService $rotationService
    ) {}
    /**
     * Display all rotations (global view).
     */
    public function all(Request $request): View
    {
        return view('rotations.index');
    }

    /**
     * Display a listing of the resource for a specific employee.
     */
    public function index(Request $request, Employee $employee): View
    {
        $query = $employee->rotations();

        // Filtrowanie po statusie (na podstawie dat)
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'scheduled':
                    $query->scheduled();
                    break;
                case 'active':
                    $query->active();
                    break;
                case 'completed':
                    $query->completed();
                    break;
            }
        }

        // Filtrowanie po dacie rozpoczęcia (od)
        if ($request->filled('start_date_from')) {
            $query->whereDate('start_date', '>=', $request->start_date_from);
        }

        // Filtrowanie po dacie rozpoczęcia (do)
        if ($request->filled('start_date_to')) {
            $query->whereDate('start_date', '<=', $request->start_date_to);
        }

        // Filtrowanie po dacie zakończenia (od)
        if ($request->filled('end_date_from')) {
            $query->whereDate('end_date', '>=', $request->end_date_from);
        }

        // Filtrowanie po dacie zakończenia (do)
        if ($request->filled('end_date_to')) {
            $query->whereDate('end_date', '<=', $request->end_date_to);
        }

        $rotations = $query->orderBy('start_date', 'desc')
            ->paginate(15)
            ->withQueryString(); // Zachowaj parametry w URL przy paginacji

        return view('employees.rotations.index', compact('employee', 'rotations'));
    }

    /**
     * Show the form for creating a new resource (global - with employee selection).
     */
    public function createGlobal(): View
    {
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        return view('rotations.create', compact('employees'));
    }

    /**
     * Show the form for creating a new resource for a specific employee.
     */
    public function create(Employee $employee): View
    {
        return view('employees.rotations.create', compact('employee'));
    }

    /**
     * Store a newly created resource in storage (global - with employee_id).
     */
    public function storeGlobal(StoreRotationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $employee = Employee::findOrFail($validated['employee_id']);
            $startDate = \Carbon\Carbon::parse($validated['start_date']);
            $endDate = \Carbon\Carbon::parse($validated['end_date']);
            
            $this->rotationService->createRotation(
                $employee,
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
            ->route('rotations.index')
            ->with('success', 'Rotacja została utworzona.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRotationRequest $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $startDate = \Carbon\Carbon::parse($validated['start_date']);
            $endDate = \Carbon\Carbon::parse($validated['end_date']);
            
            $this->rotationService->createRotation(
                $employee,
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
            ->route('employees.rotations.index', $employee)
            ->with('success', 'Rotacja została utworzona.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee, Rotation $rotation): View
    {
        $rotation->load('employee');
        return view('employees.rotations.show', compact('employee', 'rotation'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee, Rotation $rotation): View
    {
        return view('employees.rotations.edit', compact('employee', 'rotation'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRotationRequest $request, Employee $employee, Rotation $rotation): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $startDate = \Carbon\Carbon::parse($validated['start_date']);
            $endDate = \Carbon\Carbon::parse($validated['end_date']);
            
            $this->rotationService->updateRotation(
                $rotation,
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
            ->route('employees.rotations.index', $employee)
            ->with('success', 'Rotacja została zaktualizowana.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee, Rotation $rotation): RedirectResponse
    {
        $rotation->delete();

        return redirect()
            ->route('employees.rotations.index', $employee)
            ->with('success', 'Rotacja została usunięta.');
    }
}
