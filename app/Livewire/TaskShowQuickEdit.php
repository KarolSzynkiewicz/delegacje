<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithTaskQuickEdit;
use App\Models\ProjectTask;
use App\Models\User;
use Livewire\Component;

class TaskShowQuickEdit extends Component
{
    use WithTaskQuickEdit;

    public ProjectTask $task;

    protected function afterTaskQuickEditSaved(ProjectTask $task): void
    {
        $this->task = $task;
    }

    public function render()
    {
        return view('livewire.task-show-quick-edit', [
            'allUsers' => User::orderBy('name')->get(),
        ]);
    }
}
