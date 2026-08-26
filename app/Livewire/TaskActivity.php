<?php

namespace App\Livewire;

use App\Models\ProjectTask;
use App\Services\SprintActivityFeed;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class TaskActivity extends Component
{
    public ProjectTask $task;

    public function render(): View
    {
        return view('livewire.task-activity', [
            'entries' => app(SprintActivityFeed::class)->forTask($this->task),
        ]);
    }
}
