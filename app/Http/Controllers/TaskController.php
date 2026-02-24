<?php

namespace App\Http\Controllers;

use App\Models\ProjectTask;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreProjectTaskRequest;
use App\Http\Requests\UpdateProjectTaskRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaskController extends Controller
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
     * Store a newly created task.
     */
    public function store(StoreProjectTaskRequest $request): RedirectResponse
    {
        try {
            $status = $request->input('status') 
                ? TaskStatus::from($request->input('status')) 
                : TaskStatus::PENDING;

            $projectId = $request->input('project_id');
            // Konwertuj pusty string na null
            if ($projectId === '' || $projectId === null) {
                $projectId = null;
            }

            $taskData = [
                'project_id' => $projectId, // nullable
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'status' => $status,
                'assigned_to' => $request->input('assigned_to') ?: null,
                'due_date' => $request->input('due_date') ?: null,
                'created_by' => auth()->id(),
            ];
            
            \Log::info('Creating task', ['data' => $taskData]);
            
            $task = ProjectTask::create($taskData);
            
            \Log::info('Task created', ['task_id' => $task->id, 'project_id' => $task->project_id]);

            // Jeśli status to COMPLETED, ustaw completed_at
            if ($status === TaskStatus::COMPLETED && !$task->completed_at) {
                $task->update(['completed_at' => now()]);
            }

            $message = 'Zadanie "' . $task->name . '" zostało utworzone.';
            if ($task->project) {
                $message .= ' Przypisane do projektu: ' . $task->project->name;
            } else {
                $message .= ' (bez projektu)';
            }

            return redirect()->route('tasks.index')
                ->with('success', $message)
                ->with('task_created', true); // Flag to close modal via JS
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('tasks.index')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Task creation error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'input' => $request->except(['_token'])
            ]);
            
            return redirect()->route('tasks.index')
                ->with('error', 'Wystąpił błąd podczas tworzenia zadania: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified task.
     */
    public function show(ProjectTask $task): View
    {
        $task->load(['assignedTo', 'createdBy', 'project', 'comments.user', 'subtasks']);
        $users = \App\Models\User::orderBy('name')->get();
        
        return view('tasks.show', compact('task', 'users'));
    }

    /**
     * Show the form for editing the specified task.
     */
    public function edit(ProjectTask $task): View
    {
        $task->load(['assignedTo', 'createdBy', 'project']);
        $users = \App\Models\User::orderBy('name')->get();
        
        return view('tasks.edit', compact('task', 'users'));
    }

    /**
     * Update the specified task.
     */
    public function update(UpdateProjectTaskRequest $request, ProjectTask $task): RedirectResponse
    {
        $oldStatus = $task->status;
        $newStatus = $request->input('status') 
            ? TaskStatus::from($request->input('status')) 
            : $task->status;

        // Aktualizuj podstawowe pola
        $task->update($request->only(['name', 'description', 'assigned_to', 'due_date', 'project_id']));

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
            elseif ($newStatus === TaskStatus::IN_PROGRESS && $oldStatus !== TaskStatus::PENDING) {
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

        return redirect()->route('tasks.show', $task)->with('success', 'Zadanie zostało zaktualizowane.');
    }

    /**
     * Remove the specified task from storage.
     */
    public function destroy(ProjectTask $task): RedirectResponse
    {
        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Zadanie zostało usunięte.');
    }

    /**
     * Mark task as in progress.
     */
    public function markInProgress(ProjectTask $task): RedirectResponse
    {
        // Autoryzacja przez Policy
        $this->authorize('markInProgress', $task);

        $task->markInProgress();

        return redirect()->back()->with('success', 'Zadanie zostało oznaczone jako w trakcie.')->withFragment('task-' . $task->id);
    }

    /**
     * Mark task as completed.
     */
    public function markCompleted(ProjectTask $task): RedirectResponse
    {
        // Autoryzacja przez Policy
        $this->authorize('markCompleted', $task);

        $task->markCompleted();

        return redirect()->back()->with('success', 'Zadanie zostało oznaczone jako zakończone.')->withFragment('task-' . $task->id);
    }

    /**
     * Cancel the task.
     */
    public function cancel(ProjectTask $task): RedirectResponse
    {
        // Autoryzacja przez Policy
        $this->authorize('cancel', $task);

        $task->cancel();

        return redirect()->back()->with('success', 'Zadanie zostało anulowane.')->withFragment('task-' . $task->id);
    }
}
