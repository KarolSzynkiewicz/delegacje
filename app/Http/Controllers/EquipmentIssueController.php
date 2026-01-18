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
    public function index()
    {
        $issues = EquipmentIssue::with('equipment', 'employee', 'projectAssignment')
            ->orderBy('issue_date', 'desc')
            ->paginate(20);

        return view('equipment-issues.index', compact('issues'));
    }

    /**
     * Show the form for creating a new equipment issue.
     */
    public function create()
    {
        $equipment = Equipment::orderBy('name')->get();
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        $assignments = \App\Models\ProjectAssignment::where('status', 'active')
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
        if ($equipmentIssue->status === 'returned') {
            return redirect()
                ->route('equipment-issues.show', $equipmentIssue)
                ->with('error', 'Sprzęt został już zwrócony.');
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
            'notes' => 'nullable|string',
        ]);

        try {
            $returnDate = \Carbon\Carbon::parse($validated['return_date']);
            
            $this->equipmentService->returnEquipment(
                $equipmentIssue,
                $returnDate,
                $validated['notes'] ?? null
            );

            return redirect()
                ->route('equipment-issues.show', $equipmentIssue)
                ->with('success', 'Sprzęt został zwrócony.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Wystąpił błąd: ' . $e->getMessage())
                ->withInput();
        }
    }
}
