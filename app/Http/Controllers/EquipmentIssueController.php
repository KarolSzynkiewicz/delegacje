<?php

namespace App\Http\Controllers;

use App\Services\EquipmentService;
use App\Models\Equipment;
use App\Models\Employee;
use App\Models\EquipmentIssue;
use Illuminate\Http\Request;

class EquipmentIssueController extends Controller
{
    protected $equipmentService;

    public function __construct(EquipmentService $equipmentService)
    {
        $this->equipmentService = $equipmentService;
    }

    /**
     * Display a listing of equipment issues.
     */
    public function index(Request $request)
    {
        $query = EquipmentIssue::with('equipment', 'employee', 'projectAssignment');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by equipment
        if ($request->filled('equipment_id')) {
            $query->where('equipment_id', $request->equipment_id);
        }

        $issues = $query->orderBy('issue_date', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Get filter options
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        $equipmentList = Equipment::orderBy('name')->get();
        $statuses = ['issued', 'returned', 'lost', 'damaged'];

        return view('equipment-issues.index', compact('issues', 'employees', 'equipmentList', 'statuses'));
    }

    /**
     * Show the form for creating a new equipment issue.
     */
    public function create()
    {
        $equipment = Equipment::orderBy('name')->get();
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        $assignments = \App\Models\ProjectAssignment::active()
            ->where('is_cancelled', false)
            ->with('employee', 'project')
            ->get();

        return view('equipment-issues.create', compact('equipment', 'employees', 'assignments'));
    }

    /**
     * Store a newly created equipment issue.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'employee_id' => 'required|exists:employees,id',
            'project_assignment_id' => 'nullable|exists:project_assignments,id',
            'quantity_issued' => 'required|integer|min:1',
            'issue_date' => 'required|date',
            'expected_return_date' => 'nullable|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
        ]);

        try {
            $equipment = Equipment::findOrFail($validated['equipment_id']);
            $employee = Employee::findOrFail($validated['employee_id']);
            $projectAssignment = isset($validated['project_assignment_id']) 
                ? \App\Models\ProjectAssignment::findOrFail($validated['project_assignment_id'])
                : null;
            $quantityIssued = (int) ($validated['quantity_issued'] ?? 1);
            $issueDate = \Carbon\Carbon::parse($validated['issue_date']);
            $expectedReturnDate = isset($validated['expected_return_date'])
                ? \Carbon\Carbon::parse($validated['expected_return_date'])
                : null;

            $issue = $this->equipmentService->issueEquipment(
                $equipment,
                $employee,
                $quantityIssued,
                $issueDate,
                $expectedReturnDate,
                $projectAssignment,
                $validated['notes'] ?? null
            );

            return redirect()
                ->route('equipment-issues.show', $issue)
                ->with('success', 'Sprzęt został wydany.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    /**
     * Display the specified equipment issue.
     */
    public function show(EquipmentIssue $equipmentIssue)
    {
        $equipmentIssue->load('equipment', 'employee', 'projectAssignment', 'issuer', 'returner');

        return view('equipment-issues.show', compact('equipmentIssue'));
    }

    /**
     * Show the form for returning equipment.
     */
    public function returnForm(EquipmentIssue $equipmentIssue)
    {
        if ($equipmentIssue->status !== 'issued') {
            return redirect()
                ->route('equipment-issues.show', $equipmentIssue)
                ->with('error', 'Sprzęt został już zwrócony, zgłoszony jako uszkodzony lub zgubiony.');
        }

        // Check if equipment is returnable
        if (!$equipmentIssue->equipment->returnable) {
            return redirect()
                ->route('equipment-issues.show', $equipmentIssue)
                ->with('error', 'Ten sprzęt nie może być zwracany, zgłaszany jako uszkodzony lub zgubiony.');
        }

        return view('equipment-issues.return', compact('equipmentIssue'));
    }

    /**
     * Return equipment.
     */
    public function return(Request $request, EquipmentIssue $equipmentIssue)
    {
        $validated = $request->validate([
            'return_date' => 'required|date|after_or_equal:' . $equipmentIssue->issue_date->format('Y-m-d'),
            'status' => 'required|in:returned,damaged,lost',
            'notes' => 'nullable|string',
        ]);

        try {
            $returnDate = \Carbon\Carbon::parse($validated['return_date']);
            
            $this->equipmentService->returnEquipment(
                $equipmentIssue,
                $returnDate,
                $validated['status'],
                $validated['notes'] ?? null
            );

            $statusMessages = [
                'returned' => 'Sprzęt został zwrócony.',
                'damaged' => 'Sprzęt został zgłoszony jako uszkodzony.',
                'lost' => 'Sprzęt został zgłoszony jako zgubiony.',
            ];

            return redirect()
                ->route('equipment-issues.show', $equipmentIssue)
                ->with('success', $statusMessages[$validated['status']]);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Wystąpił błąd: ' . $e->getMessage())
                ->withInput();
        }
    }
}
