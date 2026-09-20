<?php

namespace App\Livewire\Concerns;

use App\Models\Sprint;
use App\Services\SprintCloseService;
use InvalidArgumentException;

trait ClosesAndParksSprints
{
    public bool $showCloseModal = false;

    public string $closeNote = '';

    public bool $unpinOpen = true;

    public ?int $closingSprintId = null;

    public string $closingSprintName = '';

    public int $closeOpenCount = 0;

    public function startClose(int $sprintId): void
    {
        $this->assertCanMutateSprints();

        $sprint = Sprint::query()->with('tasks')->findOrFail($sprintId);
        if ($sprint->isClosed()) {
            return;
        }

        $this->closingSprintId = $sprint->id;
        $this->closingSprintName = $sprint->name;
        $this->closeOpenCount = $sprint->openTasksCount();
        $this->closeNote = '';
        $this->unpinOpen = true;
        $this->showCloseModal = true;
        $this->resetErrorBag();
    }

    public function cancelClose(): void
    {
        $this->showCloseModal = false;
        $this->closingSprintId = null;
        $this->closingSprintName = '';
        $this->closeOpenCount = 0;
        $this->closeNote = '';
        $this->unpinOpen = true;
    }

    public function confirmClose(): void
    {
        $this->assertCanMutateSprints();
        $this->validate([
            'closeNote' => ['nullable', 'string', 'max:2000'],
            'unpinOpen' => ['boolean'],
        ]);

        if (! $this->closingSprintId) {
            return;
        }

        $sprint = Sprint::query()->findOrFail($this->closingSprintId);

        try {
            app(SprintCloseService::class)->close(
                $sprint,
                auth()->user(),
                $this->unpinOpen,
                $this->closeNote !== '' ? $this->closeNote : null,
            );
        } catch (InvalidArgumentException $e) {
            $this->addError('close', $e->getMessage());

            return;
        }

        $this->cancelClose();
        $this->onSprintLifecycleChanged($sprint->fresh(), 'closed');
    }

    public function parkSprint(int $sprintId): void
    {
        $this->assertCanMutateSprints();
        $sprint = Sprint::query()->findOrFail($sprintId);

        try {
            app(SprintCloseService::class)->park($sprint);
        } catch (InvalidArgumentException $e) {
            $this->addError('close', $e->getMessage());

            return;
        }

        $this->onSprintLifecycleChanged($sprint->fresh(), 'parked');
    }

    public function unparkSprint(int $sprintId): void
    {
        $this->assertCanMutateSprints();
        $sprint = Sprint::query()->findOrFail($sprintId);
        app(SprintCloseService::class)->unpark($sprint);
        $this->onSprintLifecycleChanged($sprint->fresh(), 'unparked');
    }

    abstract protected function assertCanMutateSprints(): void;

    abstract protected function onSprintLifecycleChanged(Sprint $sprint, string $action): void;
}
