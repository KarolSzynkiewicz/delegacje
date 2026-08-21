<?php

namespace App\Livewire;

use App\Models\Sprint;
use App\Services\SprintActivityFeed;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SprintActivity extends Component
{
    public Sprint $sprint;

    public function render(): View
    {
        return view('livewire.sprint-activity', [
            'entries' => app(SprintActivityFeed::class)->for($this->sprint),
        ]);
    }
}
