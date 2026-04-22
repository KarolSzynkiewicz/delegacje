<?php

namespace App\Http\Controllers;

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
}
