<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\StoreSprintRequest;
use App\Http\Requests\UpdateSprintRequest;
use App\Models\Attachment;
use App\Models\Sprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SprintController extends Controller
{
    public function index(): View
    {
        $sprints = Sprint::query()
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn ($q) => $q->where('status', TaskStatus::COMPLETED),
            ])
            ->orderByDesc('start_date')
            ->get();

        return view('sprints.index', compact('sprints'));
    }

    public function create(): View
    {
        return view('sprints.create');
    }

    public function store(StoreSprintRequest $request): RedirectResponse
    {
        $sprint = Sprint::query()->create([
            ...$request->safe()->except('attachments'),
            'created_by' => auth()->id(),
        ]);

        $uploads = $request->file('attachments', []);
        if (! is_array($uploads)) {
            $uploads = $uploads ? [$uploads] : [];
        }
        Attachment::storeManyFor($sprint, $uploads, auth()->id(), 'sprints');

        return redirect()
            ->route('sprints.show', $sprint)
            ->with('success', 'Sprint „'.$sprint->name.'” został utworzony.');
    }

    public function show(Sprint $sprint): View
    {
        return view('sprints.show', compact('sprint'));
    }

    public function edit(Sprint $sprint): View
    {
        $sprint->load('attachments.uploader');

        return view('sprints.edit', compact('sprint'));
    }

    public function update(UpdateSprintRequest $request, Sprint $sprint): RedirectResponse
    {
        $sprint->update($request->safe()->except('attachments'));

        $uploads = $request->file('attachments', []);
        if (! is_array($uploads)) {
            $uploads = $uploads ? [$uploads] : [];
        }
        Attachment::storeManyFor($sprint, $uploads, auth()->id(), 'sprints');

        return redirect()
            ->route('sprints.show', $sprint)
            ->with('success', 'Sprint został zaktualizowany.');
    }

    public function destroy(Sprint $sprint): RedirectResponse
    {
        $name = $sprint->name;
        $sprint->delete();

        return redirect()
            ->route('sprints.index')
            ->with('success', 'Sprint „'.$name.'” został usunięty. Zadania zostały odpięte od sprintu.');
    }
}
