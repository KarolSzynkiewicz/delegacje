<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\StoreProjectTaskRequest;
use App\Http\Requests\UpdateProjectTaskRequest;
use App\Models\Attachment;
use App\Models\ProjectTask;
use App\Models\TaskGridView;
use App\Models\User;
use App\Notifications\TaskAssigned;
use App\Support\TasksGridUrlParams;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     * Wejście z menu — przekierowanie do domyślnego widoku użytkownika.
     */
    public function home(): RedirectResponse
    {
        return redirect()->to(auth()->user()->tasksHomeUrl());
    }

    public function setDefaultView(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'view' => ['required', 'in:cards,grid'],
            'grid_query' => ['nullable', 'array'],
        ]);

        $user = auth()->user();

        if ($validated['view'] === 'cards') {
            $user->update([
                'default_tasks_view' => 'cards',
                'default_tasks_grid_view_slug' => null,
                'default_tasks_grid_query' => null,
            ]);

            return back()->with('success', 'Domyślny widok zadań został zapisany.');
        }

        $query = TasksGridUrlParams::fromRequestQuery($validated['grid_query'] ?? $request->query());

        $slug = isset($query['view'])
            ? TaskGridView::findVisibleTo($user, $query['view'])?->slug
            : null;

        if ($slug === null && isset($query['view'])) {
            unset($query['view']);
        }

        $user->update([
            'default_tasks_view' => 'grid',
            'default_tasks_grid_view_slug' => $slug,
            'default_tasks_grid_query' => $query !== [] ? $query : null,
        ]);

        return back()->with('success', 'Domyślny widok zadań został zapisany.');
    }

    public function grid(): View
    {
        return view('tasks.grid');
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

            $taskData = [
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'status' => $status,
                'priority' => $request->input('priority') ?: null,
                'category' => $request->input('category') ?: null,
                'sprint_id' => $request->input('sprint_id') ?: null,
                'assigned_to' => $request->input('assigned_to') ?: null,
                'due_date' => $request->input('due_date') ?: null,
                'created_by' => auth()->id(),
            ];

            \Log::info('Creating task', ['data' => $taskData]);

            $task = ProjectTask::create($taskData);

            \Log::info('Task created', ['task_id' => $task->id]);

            // Powiadomienie dla przypisanego użytkownika (jeśli inny niż tworzący)
            if ($task->assigned_to && $task->assigned_to !== auth()->id()) {
                $assignee = User::find($task->assigned_to);
                $assignee?->notify(new TaskAssigned($task, auth()->user()));
            }

            // Jeśli status to COMPLETED, ustaw completed_at
            if ($status === TaskStatus::COMPLETED && ! $task->completed_at) {
                $task->update(['completed_at' => now()]);
            }

            $message = 'Zadanie "'.$task->name.'" zostało utworzone.';

            return redirect()->route('tasks.index')
                ->with('success', $message)
                ->with('task_created', true); // Flag to close modal via JS
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('tasks.index')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Task creation error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'input' => $request->except(['_token']),
            ]);

            return redirect()->route('tasks.index')
                ->with('error', 'Wystąpił błąd podczas tworzenia zadania: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified task.
     */
    public function show(ProjectTask $task): View|RedirectResponse
    {
        if ($redirect = $this->redirectLegacyMention($task)) {
            return $redirect;
        }

        $task->load(['assignedTo', 'createdBy', 'sprint', 'comments.user', 'subtasks', 'attachments.uploader', 'procedureRun.template', 'procedureRun.startedBy', 'procedureRun.steps', 'procedureRun.comments.user', 'procedureRun.subject', 'recruitmentProcess.candidate', 'subject.user', 'subject.commentable', 'subject.parent']);
        $users = \App\Models\User::orderBy('name')->get();

        return view('tasks.show', compact('task', 'users'));
    }

    /**
     * Show the form for editing the specified task.
     */
    public function edit(ProjectTask $task): View|RedirectResponse
    {
        if ($redirect = $this->redirectLegacyMention($task)) {
            return $redirect;
        }

        $task->load(['assignedTo', 'createdBy', 'sprint', 'attachments.uploader']);
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

        $previousAssignee = $task->assigned_to;

        // Aktualizuj podstawowe pola
        $task->update($request->only(['name', 'description', 'assigned_to', 'due_date', 'priority', 'category', 'sprint_id']));

        // Powiadomienie jeśli przypisano do nowego użytkownika
        $newAssignee = (int) $request->input('assigned_to');
        if ($newAssignee && $newAssignee !== $previousAssignee && $newAssignee !== auth()->id()) {
            $assignee = User::find($newAssignee);
            $assignee?->notify(new TaskAssigned($task->fresh(), auth()->user()));
        }

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

        $uploads = $request->file('attachments', []);
        if (! is_array($uploads)) {
            $uploads = $uploads ? [$uploads] : [];
        }
        Attachment::storeManyFor($task, $uploads, auth()->id(), 'tasks');

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

        return redirect()->back()->with('success', 'Zadanie zostało oznaczone jako w trakcie.')->withFragment('task-'.$task->id);
    }

    /**
     * Mark task as completed.
     */
    public function markCompleted(ProjectTask $task): RedirectResponse
    {
        // Autoryzacja przez Policy
        $this->authorize('markCompleted', $task);

        $task->markCompleted();

        return redirect()->back()->with('success', 'Zadanie zostało oznaczone jako zakończone.')->withFragment('task-'.$task->id);
    }

    public function toggleDone(ProjectTask $task): RedirectResponse
    {
        $this->authorize('markCompleted', $task);

        if ($task->status === TaskStatus::COMPLETED) {
            $task->reopen();

            return redirect()->back()->with('success', 'Ponownie otwarte.');
        }

        $task->markCompleted();

        return redirect()->back()->with('success', 'Oznaczone jako zrobione.');
    }

    /**
     * Cancel the task.
     */
    public function cancel(ProjectTask $task): RedirectResponse
    {
        // Autoryzacja przez Policy
        $this->authorize('cancel', $task);

        $task->cancel();

        return redirect()->back()->with('success', 'Zadanie zostało anulowane.')->withFragment('task-'.$task->id);
    }

    private function redirectLegacyMention(ProjectTask $task): ?RedirectResponse
    {
        if (! $task->isMention()) {
            return null;
        }

        $url = $task->mentionSourceComment()?->urlWithCommentAnchor();

        return $url ? redirect()->to($url) : redirect()->to(url('/tasks2'));
    }
}
