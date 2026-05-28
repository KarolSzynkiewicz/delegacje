<?php

namespace App\Http\Controllers;

use App\Services\PromptEngine\AssignmentPromptBundleService;
use App\Services\PromptEngine\CostPromptBundleService;
use App\Services\PromptEngine\TaskPromptBundleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromptEngineController extends Controller
{
    public function index(): View
    {
        return view('prompts.index');
    }

    public function exportTasks(Request $request, TaskPromptBundleService $bundleService): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);

        return response()->json($bundleService->build($start, $end));
    }

    public function exportAssignments(Request $request, AssignmentPromptBundleService $bundleService): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
        ]);

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $employeeIds = array_map('intval', $validated['employee_ids'] ?? []);

        return response()->json($bundleService->build($start, $end, $employeeIds));
    }

    public function exportCosts(Request $request, CostPromptBundleService $bundleService): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'project_ids' => ['nullable', 'array'],
            'project_ids.*' => ['integer', 'exists:projects,id'],
            'include' => ['nullable', 'array'],
            'include.*' => ['string', 'in:fixed,variable,transport,accommodation,labor,adjustments,advances,vehicle_repairs'],
        ]);

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $projectIds = array_map('intval', $validated['project_ids'] ?? []);
        $include = $validated['include'] ?? null;

        return response()->json($bundleService->build($start, $end, $projectIds, $include));
    }
}
