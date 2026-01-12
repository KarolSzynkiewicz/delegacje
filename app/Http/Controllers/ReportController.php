<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Employee;
use App\Services\ReportService;
use App\Http\Requests\StoreReportRequest;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}
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
        // Use orderBy for better UX and limit if needed for large datasets
        $projects = Project::orderBy('name')->get();
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        
        return view('reports.create', compact('projects', 'employees'));
    }

    /**
     * Store a newly created report in storage.
     */
    public function store(StoreReportRequest $request)
    {
        $validated = $request->validated();

        // Generate report based on type
        switch ($validated['report_type']) {
            case 'assignment_summary':
                $data = $this->reportService->generateAssignmentSummary($validated);
                return view('reports.assignment-summary', [
                    'assignments' => $data,
                    'filters' => $validated,
                ]);
            case 'employee_hours':
                $data = $this->reportService->generateEmployeeHours($validated);
                return view('reports.employee-hours', [
                    'data' => $data,
                    'filters' => $validated,
                ]);
            case 'project_status':
                $data = $this->reportService->generateProjectStatus($validated);
                return view('reports.project-status', [
                    'projects' => $data,
                    'filters' => $validated,
                ]);
            case 'demand_fulfillment':
                $data = $this->reportService->generateDemandFulfillment($validated);
                return view('reports.demand-fulfillment', [
                    'projects' => $data,
                    'filters' => $validated,
                ]);
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

}
