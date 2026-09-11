<?php

namespace App\Livewire;

use App\Models\ProjectTask;
use App\Services\SprintActivityFeed;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class TaskActivity extends Component
{
    public ProjectTask $task;

    #[On('task-history-changed')]
    public function refreshHistory(): void
    {
        $this->task = $this->task->fresh() ?? $this->task;
    }

    public function render(): View
    {
        return view('livewire.task-activity', [
            'entries' => app(SprintActivityFeed::class)->forTask($this->task),
        ]);
    }
}
