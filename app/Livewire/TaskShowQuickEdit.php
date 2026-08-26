<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithTaskQuickEdit;
use App\Models\ProjectTask;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class TaskShowQuickEdit extends Component
{
    use WithTaskQuickEdit;

    public ProjectTask $task;

    public string $descriptionDraft = '';

    public function mount(ProjectTask $task): void
    {
        $this->task = $task->loadMissing(['assignedTo', 'createdBy', 'sprint']);
        $this->descriptionDraft = $task->plainDescription();
    }

    public function saveDescription(): void
    {
        if (! $this->canQuickEditTask($this->task)) {
            abort(403);
        }

        Validator::make(
            ['descriptionDraft' => $this->descriptionDraft],
            ['descriptionDraft' => ['nullable', 'string', 'max:20000']],
        )->validate();

        $this->task->update([
            'description' => $this->descriptionDraft === '' ? null : $this->descriptionDraft,
        ]);

        $this->task = $this->task->fresh(['assignedTo', 'createdBy', 'sprint', 'attachments.uploader']);
        $this->descriptionDraft = $this->task->plainDescription();
        $this->quickEditFlash = 'Zapisano opis.';
    }

    protected function afterTaskQuickEditSaved(ProjectTask $task): void
    {
        $this->task = $task->loadMissing(['assignedTo', 'createdBy', 'sprint', 'attachments.uploader']);
        $this->descriptionDraft = $task->plainDescription();
    }

    public function render()
    {
        return view('livewire.task-show-quick-edit', [
            'allUsers' => User::orderBy('name')->get(),
            'sprints' => Sprint::query()
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->limit(40)
                ->get(),
            'sourceCard' => $this->task->sourceCard(),
        ]);
    }
}
