<?php

namespace App\Livewire;

use App\Models\Attachment;
use App\Models\Sprint;
use App\Models\SprintMilestone;
use App\Services\SprintInsights;
use Livewire\Component;
use Livewire\WithFileUploads;

class SprintBoard extends Component
{
    use WithFileUploads;

    public Sprint $sprint;

    public string $newMilestoneName = '';

    public string $newMilestoneDue = '';

    /** @var array<int, mixed> */
    public array $uploads = [];

    public ?string $flash = null;

    public function mount(Sprint $sprint): void
    {
        $this->sprint = $sprint;
        $this->newMilestoneDue = $sprint->end_date?->format('Y-m-d') ?? now()->toDateString();
    }

    public function addMilestone(): void
    {
        $this->authorizeMutate();
        $this->validate([
            'newMilestoneName' => 'required|string|max:255',
            'newMilestoneDue' => 'required|date',
        ]);

        SprintMilestone::query()->create([
            'sprint_id' => $this->sprint->id,
            'name' => trim($this->newMilestoneName),
            'due_date' => $this->newMilestoneDue,
            'position' => $this->sprint->nextMilestonePosition(),
            'created_by' => auth()->id(),
        ]);

        $this->newMilestoneName = '';
        $this->flash = 'Kamień milowy dodany.';
        $this->refreshSprint();
    }

    public function toggleMilestone(int $milestoneId): void
    {
        $this->authorizeMutate();

        $milestone = $this->sprint->milestones()->whereKey($milestoneId)->first();
        if (! $milestone) {
            return;
        }

        $milestone->update([
            'completed_at' => $milestone->completed_at ? null : now(),
        ]);

        $this->refreshSprint();
    }

    public function deleteMilestone(int $milestoneId): void
    {
        $this->authorizeMutate();
        $this->sprint->milestones()->whereKey($milestoneId)->delete();
        $this->refreshSprint();
    }

    public function saveUploads(): void
    {
        $this->authorizeMutate();
        $this->validate([
            'uploads' => ['nullable', 'array', 'max:15'],
            'uploads.*' => ['file', 'max:15360', 'mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,txt,zip'],
        ]);

        Attachment::storeManyFor($this->sprint, $this->uploads, auth()->id(), 'sprints');
        $this->uploads = [];
        $this->flash = 'Załączniki zapisane.';
        $this->refreshSprint();
    }

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
        $this->sprint->load([
            'createdBy',
            'attachments.uploader',
            'milestones',
            'orderedTasks.assignedTo',
        ]);
        $this->sprint->setRelation('tasks', $this->sprint->orderedTasks);

        $insights = app(SprintInsights::class)->for($this->sprint);

        return view('livewire.sprint-board', [
            'insights' => $insights,
            'canMutate' => $this->canMutate(),
        ]);
    }

    private function authorizeMutate(): void
    {
        if (! $this->canMutate()) {
            abort(403);
        }
    }

    private function refreshSprint(): void
    {
        $this->sprint->refresh();
        $this->sprint->load([
            'createdBy',
            'attachments.uploader',
            'milestones',
            'orderedTasks.assignedTo',
        ]);
        $this->sprint->setRelation('tasks', $this->sprint->orderedTasks);
    }
}
