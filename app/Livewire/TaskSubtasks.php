<?php

namespace App\Livewire;

use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use Livewire\Component;

class TaskSubtasks extends Component
{
    public ProjectTask $task;
    public string $newSubtaskName = '';

    public function mount(ProjectTask $task): void
    {
        $this->task = $task;
        $this->task->load('subtasks');
    }

    public function addSubtask(): void
    {
        $this->validate([
            'newSubtaskName' => 'required|string|max:255',
        ], [
            'newSubtaskName.required' => 'Nazwa podzadania jest wymagana.',
            'newSubtaskName.max' => 'Nazwa podzadania nie może przekraczać 255 znaków.',
        ]);

        TaskSubtask::create([
            'task_id' => $this->task->id,
            'name' => trim($this->newSubtaskName),
            'is_completed' => false,
        ]);

        $this->newSubtaskName = '';
        $this->task->refresh();
        $this->task->load('subtasks');
    }

    public function toggleSubtask($subtaskId): void
    {
        $subtask = TaskSubtask::findOrFail($subtaskId);
        
        if ($subtask->task_id !== $this->task->id) {
            abort(403, 'Nieprawidłowe podzadanie.');
        }

        if ($subtask->is_completed) {
            $subtask->markIncomplete();
        } else {
            $subtask->markCompleted();
        }

        $this->task->refresh();
        $this->task->load('subtasks');
    }

    public function getCompletedSubtasksProperty()
    {
        if (!$this->task->relationLoaded('subtasks')) {
            $this->task->load('subtasks');
        }
        return $this->task->subtasks->where('is_completed', true)->sortByDesc('completed_at')->values();
    }

    public function getPendingSubtasksProperty()
    {
        if (!$this->task->relationLoaded('subtasks')) {
            $this->task->load('subtasks');
        }
        return $this->task->subtasks->where('is_completed', false)->sortBy('created_at')->values();
    }

    public function getProgressPercentageProperty(): float
    {
        if (!$this->task->relationLoaded('subtasks')) {
            $this->task->load('subtasks');
        }
        $progress = $this->task->subtasks_progress;
        return is_numeric($progress) ? (float)$progress : 0.0;
    }

    public function render()
    {
        return view('livewire.task-subtasks');
    }
}
