<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Rotation;
use App\Rules\RotationDoesNotOverlap;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class RotationController extends Controller
{
    /**
     * Display all rotations (global view).
     */
    public function all(Request $request): View
    {
        $query = Rotation::with('employee');

        // Filtrowanie po pracowniku
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filtrowanie po statusie (na podstawie dat)
        if ($request->filled('status')) {
            $today = now()->toDateString();
            switch ($request->status) {
                case 'scheduled':
                    $query->whereDate('start_date', '>', $today)
                        ->where(function ($q) {
                            $q->whereNull('status')
                              ->orWhere('status', '!=', 'cancelled');
                        });
                    break;
                case 'active':
                    $query->whereDate('start_date', '<=', $today)
                        ->whereDate('end_date', '>=', $today)
                        ->where(function ($q) {
                            $q->whereNull('status')
                              ->orWhere('status', '!=', 'cancelled');
                        });
                    break;
                case 'completed':
                    $query->whereDate('end_date', '<', $today)
                        ->where(function ($q) {
                            $q->whereNull('status')
                              ->orWhere('status', '!=', 'cancelled');
                        });
                    break;
                case 'cancelled':
                    $query->where('status', 'cancelled');
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
            ->paginate(20)
            ->withQueryString(); // Zachowaj parametry w URL przy paginacji

        $employees = \App\Models\Employee::orderBy('last_name')->orderBy('first_name')->get();
        
        return view('rotations.index', compact('rotations', 'employees'));
    }

    /**
     * Display a listing of the resource for a specific employee.
     */
    public function index(Request $request, Employee $employee): View
    {
        $query = $employee->rotations();

        // Filtrowanie po statusie (na podstawie dat)
        if ($request->filled('status')) {
            $today = now()->toDateString();
            switch ($request->status) {
                case 'scheduled':
                    $query->whereDate('start_date', '>', $today)
                        ->where(function ($q) {
                            $q->whereNull('status')
                              ->orWhere('status', '!=', 'cancelled');
                        });
                    break;
                case 'active':
                    $query->whereDate('start_date', '<=', $today)
                        ->whereDate('end_date', '>=', $today)
                        ->where(function ($q) {
                            $q->whereNull('status')
                              ->orWhere('status', '!=', 'cancelled');
                        });
                    break;
                case 'completed':
                    $query->whereDate('end_date', '<', $today)
                        ->where(function ($q) {
                            $q->whereNull('status')
                              ->orWhere('status', '!=', 'cancelled');
                        });
                    break;
                case 'cancelled':
                    $query->where('status', 'cancelled');
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
    public function storeGlobal(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
                new RotationDoesNotOverlap($request->input('employee_id'))
            ],
            'status' => 'nullable|in:cancelled', // Tylko cancelled można ustawić ręcznie
            'notes' => 'nullable|string',
        ]);

        // Status jest automatyczny - nie zapisujemy go (chyba że cancelled)
        if (!isset($validated['status']) || $validated['status'] !== 'cancelled') {
            unset($validated['status']);
        }

        $employee = Employee::findOrFail($validated['employee_id']);
        $employee->rotations()->create($validated);

        return redirect()
            ->route('rotations.index')
            ->with('success', 'Rotacja została utworzona.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
                new RotationDoesNotOverlap($employee->id)
            ],
            'status' => 'nullable|in:cancelled', // Tylko cancelled można ustawić ręcznie
            'notes' => 'nullable|string',
        ]);

        // Status jest automatyczny - nie zapisujemy go (chyba że cancelled)
        if (!isset($validated['status']) || $validated['status'] !== 'cancelled') {
            unset($validated['status']);
        }

        $employee->rotations()->create($validated);

        return redirect()
            ->route('employees.rotations.index', $employee)
            ->with('success', 'Rotacja została utworzona.');
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
    public function update(Request $request, Employee $employee, Rotation $rotation): RedirectResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
                new RotationDoesNotOverlap($employee->id, $rotation->id)
            ],
            'status' => 'nullable|in:cancelled', // Tylko cancelled można ustawić ręcznie
            'notes' => 'nullable|string',
        ]);

        // Status jest automatyczny - nie zapisujemy go (chyba że cancelled)
        // Jeśli nie ma statusu cancelled, ustawiamy null (będzie obliczony automatycznie)
        if (!isset($validated['status']) || $validated['status'] !== 'cancelled') {
            $validated['status'] = null;
        }

        $rotation->update($validated);

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
