<?php

namespace App\Http\Controllers;

use App\Models\ProcedureRun;
use App\Models\ProcedureRunComment;
use App\Models\ProcedureTemplate;
use App\Services\ProcedureRunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProcedureRunController extends Controller
{
    public function __construct(private ProcedureRunService $service)
    {
    }

    public function store(Request $request, ProcedureTemplate $procedureTemplate): RedirectResponse
    {
        $data = $request->validate([
            'task_name'    => ['required', 'string', 'max:255'],
            'assigned_to'  => ['nullable', 'integer', 'exists:users,id'],
            'due_date'     => ['nullable', 'date'],
            'subject_type' => ['nullable', 'string', 'max:100'],
            'subject_id'   => ['nullable', 'integer'],
        ]);

        $run = $this->service->startRun($procedureTemplate, $data);

        return redirect()->route('tasks.show', $run->task)
            ->with('success', 'Procedura "' . $procedureTemplate->name . '" została uruchomiona.');
    }

    public function advance(Request $request, ProcedureRun $procedureRun): RedirectResponse
    {
        $data = $request->validate([
            'node_id'   => ['required', 'string'],
            'edge_id'   => ['nullable', 'string'],
            'step_data' => ['nullable', 'array'],
        ]);

        $this->service->advanceNode(
            $procedureRun,
            $data['node_id'],
            $data['edge_id'] ?? null,
            $data['step_data'] ?? []
        );

        return back();
    }

    public function back(Request $request, ProcedureRun $procedureRun): RedirectResponse
    {
        $data = $request->validate([
            'node_id' => ['required', 'string'],
        ]);

        $this->service->goBackNode($procedureRun, $data['node_id']);

        return back();
    }

    public function abandon(ProcedureRun $procedureRun): RedirectResponse
    {
        $this->service->abandon($procedureRun);

        return redirect()->route('tasks.show', $procedureRun->task)
            ->with('success', 'Procedura została porzucona.');
    }

    public function storeComment(Request $request, ProcedureRun $procedureRun): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        ProcedureRunComment::create([
            'procedure_run_id' => $procedureRun->id,
            'user_id'          => auth()->id(),
            'body'             => $data['body'],
        ]);

        return back();
    }
}
