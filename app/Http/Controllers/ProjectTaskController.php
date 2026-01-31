<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreProjectTaskRequest;
use App\Http\Requests\UpdateProjectTaskRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectTaskController extends Controller
{
    /**
     * Display all tasks (global view).
     */
    public function index(): View
    {
        // Dane są pobierane przez komponent Livewire TasksTable
        return view('tasks.index');
    }

    /**
     * Display the specified task.
     */
    public function show(Project $project, ProjectTask $task): View
    {
        if ($task->project_id !== $project->id) {
            abort(404);
        }

        $task->load(['assignedTo', 'createdBy', 'project', 'comments.user']);
        $users = \App\Models\User::orderBy('name')->get();
        
        return view('projects.tasks.show', compact('project', 'task', 'users'));
    }

    /**
     * Show the form for editing the specified task.
     */
    public function edit(Project $project, ProjectTask $task): View
    {
        if ($task->project_id !== $project->id) {
            abort(404);
        }

        $task->load(['assignedTo', 'createdBy', 'project']);
        $users = \App\Models\User::orderBy('name')->get();
        
        return view('projects.tasks.edit', compact('project', 'task', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectTaskRequest $request, Project $project): RedirectResponse
    {
        $status = $request->input('status') 
            ? TaskStatus::from($request->input('status')) 
            : TaskStatus::PENDING;

        $task = ProjectTask::create([
            'project_id' => $project->id,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'status' => $status,
            'assigned_to' => $request->input('assigned_to'),
            'due_date' => $request->input('due_date'),
            'created_by' => auth()->id(),
        ]);

        // Jeśli status to COMPLETED, ustaw completed_at
        if ($status === TaskStatus::COMPLETED && !$task->completed_at) {
            $task->update(['completed_at' => now()]);
        }

        return redirect()->back()->with('success', 'Zadanie zostało utworzone.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectTaskRequest $request, Project $project, ProjectTask $task): RedirectResponse
    {
        if ($task->project_id !== $project->id) {
            abort(404);
        }

        $oldStatus = $task->status;
        $newStatus = $request->input('status') 
            ? TaskStatus::from($request->input('status')) 
            : $task->status;

        // Aktualizuj podstawowe pola
        $task->update($request->only(['name', 'description', 'assigned_to', 'due_date']));

        // Jeśli status się zmienił, użyj metod domenowych lub zaktualizuj bezpośrednio
        if ($newStatus !== $oldStatus) {
            // Jeśli zmiana na COMPLETED, użyj metody domenowej
            if ($newStatus === TaskStatus::COMPLETED && $oldStatus !== TaskStatus::COMPLETED) {
                $task->markCompleted();
            }
            // Jeśli zmiana na CANCELLED, użyj metody domenowej
            elseif ($newStatus === TaskStatus::CANCELLED && $oldStatus !== TaskStatus::CANCELLED) {
                $task->cancel();
            }
            // Jeśli zmiana na IN_PROGRESS, użyj metody domenowej
            elseif ($newStatus === TaskStatus::IN_PROGRESS && $oldStatus === TaskStatus::PENDING) {
                $task->markInProgress();
            }
            // W innych przypadkach zaktualizuj bezpośrednio
            else {
                $updateData = ['status' => $newStatus];
                // Jeśli zmiana z COMPLETED na inny status, wyczyść completed_at
                if ($oldStatus === TaskStatus::COMPLETED && $newStatus !== TaskStatus::COMPLETED) {
                    $updateData['completed_at'] = null;
                }
                $task->update($updateData);
            }
        }

        return redirect()->back()->with('success', 'Zadanie zostało zaktualizowane.');
    }

    /**
     * Mark task as in progress.
     */
    public function markInProgress(Project $project, ProjectTask $task): RedirectResponse
    {
        if ($task->project_id !== $project->id) {
            abort(404);
        }

        // Autoryzacja przez Policy
        $this->authorize('markInProgress', $task);

        $task->markInProgress();

        return redirect()->back()->with('success', 'Zadanie zostało oznaczone jako w trakcie.');
    }

    /**
     * Mark task as completed.
     */
    public function markCompleted(Project $project, ProjectTask $task): RedirectResponse
    {
        if ($task->project_id !== $project->id) {
            abort(404);
        }

        // Autoryzacja przez Policy
        $this->authorize('markCompleted', $task);

        $task->markCompleted();

        return redirect()->back()->with('success', 'Zadanie zostało oznaczone jako zakończone.');
    }

    /**
     * Cancel the task.
     */
    public function cancel(Project $project, ProjectTask $task): RedirectResponse
    {
        if ($task->project_id !== $project->id) {
            abort(404);
        }

        // Autoryzacja przez Policy
        $this->authorize('cancel', $task);

        $task->cancel();

        return redirect()->back()->with('success', 'Zadanie zostało anulowane.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project, ProjectTask $task): RedirectResponse
    {
        if ($task->project_id !== $project->id) {
            abort(404);
        }

        $task->delete();

        return redirect()->back()->with('success', 'Zadanie zostało usunięte.');
    }
}
