<?php

namespace App\Http\Controllers;

use App\Enums\ProcedureSubjectType;
use App\Models\ProcedureTemplate;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProcedureTemplateController extends Controller
{
    public function index(): View
    {
        return view('procedures.index');
    }

    public function show(ProcedureTemplate $procedureTemplate): View
    {
        $procedureTemplate->loadCount('runs');
        $procedureTemplate->load('createdBy');

        $runs = $procedureTemplate->runs()
            ->with(['startedBy', 'subject'])
            ->latest('started_at')
            ->paginate(10);

        $runsByStatus = $procedureTemplate->runs()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->status instanceof \App\Enums\ProcedureRunStatus ? $row->status->value : (string) $row->status => $row->aggregate,
            ]);

        return view('procedures.show', [
            'template' => $procedureTemplate,
            'runs' => $runs,
            'runsByStatus' => $runsByStatus,
        ]);
    }

    public function editor(ProcedureTemplate $procedureTemplate): View
    {
        return view('procedures.editor', [
            'template' => $procedureTemplate,
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'subject_type' => ['nullable', 'string', Rule::in(ProcedureSubjectType::values())],
            'description' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable', 'array'],
            'definition' => ['nullable', 'array'],
        ]);

        $template = ProcedureTemplate::create([
            ...$data,
            'definition' => $data['definition'] ?? ['nodes' => [], 'edges' => []],
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('procedure-templates.editor', $template)
            ->with('success', 'Szablon "'.$template->name.'" został utworzony.');
    }

    public function update(Request $request, ProcedureTemplate $procedureTemplate): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'subject_type' => ['nullable', 'string', Rule::in(ProcedureSubjectType::values())],
            'description' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable', 'array'],
            'definition' => ['required', 'array'],
        ]);

        $procedureTemplate->update($data);

        return response()->json(['ok' => true]);
    }

    public function destroy(ProcedureTemplate $procedureTemplate): RedirectResponse
    {
        $procedureTemplate->delete();

        return redirect()->route('procedure-templates.index')
            ->with('success', 'Szablon został usunięty.');
    }
}
