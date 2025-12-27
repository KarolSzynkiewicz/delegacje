<?php

namespace App\Http\Controllers;

use App\Models\ProjectAssignment;
use App\Models\Project;
use App\Models\Employee;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Display a listing of assignment reports.
     */
    public function index()
    {
        // Display a list of available reports
        return view('reports.index');
    }

    /**
     * Show the form for creating a new report.
     */
    public function create()
    {
        $projects = Project::all();
        $employees = Employee::all();
        
        return view('reports.create', compact('projects', 'employees'));
    }

    /**
     * Store a newly created report in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|string|in:assignment_summary,employee_hours,project_status,demand_fulfillment',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|string|in:pdf,excel,html',
            'project_id' => 'nullable|exists:projects,id',
            'employee_id' => 'nullable|exists:employees,id',
        ]);

        // Generate report based on type
        switch ($validated['report_type']) {
            case 'assignment_summary':
                return $this->generateAssignmentSummary($validated);
            case 'employee_hours':
                return $this->generateEmployeeHours($validated);
            case 'project_status':
                return $this->generateProjectStatus($validated);
            case 'demand_fulfillment':
                return $this->generateDemandFulfillment($validated);
            default:
                return back()->withErrors(['error' => 'Nieznany typ raportu.']);
        }
    }

    /**
     * Display the specified report.
     */
    public function show(string $id)
    {
        return view('reports.show', ['reportId' => $id]);
    }

    /**
     * Download the specified report.
     */
    public function download(string $id)
    {
        // TODO: Implement report download logic
        // Support multiple formats: PDF, Excel, CSV
    }

    /**
     * Generate assignment summary report.
     */
    private function generateAssignmentSummary(array $filters)
    {
        $query = ProjectAssignment::with('employee', 'project', 'role')
            ->whereBetween('start_date', [$filters['start_date'], $filters['end_date']]);

        if (isset($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        $assignments = $query->get();

        // Return view with report data
        return view('reports.assignment-summary', [
            'assignments' => $assignments,
            'filters' => $filters,
        ]);
    }

    /**
     * Generate employee hours report.
     */
    private function generateEmployeeHours(array $filters)
    {
        // TODO: Implement employee hours report generation
        // Include: total hours per employee, overtime, attendance
        // This would require TimeLog data aggregation
    }

    /**
     * Generate project status report.
     */
    private function generateProjectStatus(array $filters)
    {
        $query = Project::with(['assignments' => function ($q) use ($filters) {
            $q->whereBetween('start_date', [$filters['start_date'], $filters['end_date']]);
        }, 'demand']);

        if (isset($filters['project_id'])) {
            $query->where('id', $filters['project_id']);
        }

        $projects = $query->get();

        return view('reports.project-status', [
            'projects' => $projects,
            'filters' => $filters,
        ]);
    }

    /**
     * Generate demand fulfillment report.
     */
    private function generateDemandFulfillment(array $filters)
    {
        // Compare project demands with actual assignments
        $query = Project::with(['demand.demandRoles.role', 'assignments' => function ($q) use ($filters) {
            $q->whereBetween('start_date', [$filters['start_date'], $filters['end_date']])
              ->where('status', 'active');
        }]);

        if (isset($filters['project_id'])) {
            $query->where('id', $filters['project_id']);
        }

        $projects = $query->get();

        return view('reports.demand-fulfillment', [
            'projects' => $projects,
            'filters' => $filters,
        ]);
    }
}
