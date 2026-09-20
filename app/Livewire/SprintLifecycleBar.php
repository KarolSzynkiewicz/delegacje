<?php

namespace App\Livewire;

use App\Livewire\Concerns\ClosesAndParksSprints;
use App\Models\Sprint;
use Livewire\Component;

class SprintLifecycleBar extends Component
{
    use ClosesAndParksSprints;

    public Sprint $sprint;

    public function canMutate(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->isAdmin() || $user->hasPermission('tasks.update');
    }

    public function render()
    {
        return view('livewire.sprint-lifecycle-bar', [
            'canMutate' => $this->canMutate(),
        ]);
    }

    protected function assertCanMutateSprints(): void
    {
        if (! $this->canMutate()) {
            abort(403);
        }
    }

    protected function onSprintLifecycleChanged(Sprint $sprint, string $action): void
    {
        $this->sprint = $sprint;
        $this->dispatch('sprint-lifecycle-changed', action: $action);
    }
}
