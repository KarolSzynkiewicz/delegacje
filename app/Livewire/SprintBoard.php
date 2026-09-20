<?php

namespace App\Livewire;

use App\Livewire\Concerns\ClosesAndParksSprints;
use App\Models\Attachment;
use App\Models\Sprint;
use App\Models\SprintDodItem;
use App\Models\SprintMilestone;
use App\Models\SprintReadinessItem;
use App\Services\SprintInsights;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class SprintBoard extends Component
{
    use ClosesAndParksSprints;
    use WithFileUploads;

    public Sprint $sprint;

    public string $newMilestoneName = '';

    public string $newMilestoneDue = '';

    public string $newReadinessName = '';

    public string $newDoneName = '';

    /** @var array<int, mixed> */
    public array $uploads = [];

    public ?string $flash = null;

    public function mount(Sprint $sprint): void
    {
        $this->sprint = $sprint;
        $this->newMilestoneDue = $sprint->end_date?->format('Y-m-d') ?? now()->toDateString();
    }

    #[On('sprint-lifecycle-changed')]
    public function refreshFromLifecycle(string $action = ''): void
    {
        $this->sprint->refresh();
        $this->loadBoard();
        $this->flash = match ($action) {
            'closed' => 'Sprint zakończony.',
            'parked' => 'Sprint odstawiony na później.',
            'unparked' => 'Sprint wrócił z później.',
            default => $this->flash,
        };
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

    public function addReadinessItem(): void
    {
        $this->authorizeMutate();
        $this->validate([
            'newReadinessName' => 'required|string|max:255',
        ]);

        SprintReadinessItem::query()->create([
            'sprint_id' => $this->sprint->id,
            'name' => trim($this->newReadinessName),
            'position' => $this->sprint->nextReadinessPosition(),
            'created_by' => auth()->id(),
        ]);

        $this->newReadinessName = '';
        $this->refreshSprint();
    }

    public function toggleReadinessItem(int $itemId): void
    {
        $this->toggleItem($this->sprint->readinessItems(), $itemId);
    }

    public function deleteReadinessItem(int $itemId): void
    {
        $this->authorizeMutate();
        $this->sprint->readinessItems()->whereKey($itemId)->delete();
        $this->refreshSprint();
    }

    public function addDoneItem(): void
    {
        $this->authorizeMutate();
        $this->validate([
            'newDoneName' => 'required|string|max:255',
        ]);

        SprintDodItem::query()->create([
            'sprint_id' => $this->sprint->id,
            'name' => trim($this->newDoneName),
            'position' => $this->sprint->nextDonePosition(),
            'created_by' => auth()->id(),
        ]);

        $this->newDoneName = '';
        $this->refreshSprint();
    }

    public function toggleDoneItem(int $itemId): void
    {
        $this->toggleItem($this->sprint->doneItems(), $itemId);
    }

    public function deleteDoneItem(int $itemId): void
    {
        $this->authorizeMutate();
        $this->sprint->doneItems()->whereKey($itemId)->delete();
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
        $this->loadBoard();

        $insights = app(SprintInsights::class)->for($this->sprint);

        return view('livewire.sprint-board', [
            'insights' => $insights,
            'canMutate' => $this->canMutate(),
        ]);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Relations\HasMany  $relation
     */
    private function toggleItem($relation, int $itemId): void
    {
        $this->authorizeMutate();
        $item = $relation->whereKey($itemId)->first();
        if (! $item) {
            return;
        }

        $item->toggleCompleted();
        $this->refreshSprint();
    }

    private function authorizeMutate(): void
    {
        $this->assertCanMutateSprints();
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
        $this->flash = match ($action) {
            'closed' => 'Sprint zakończony.',
            'parked' => 'Sprint odstawiony na później.',
            'unparked' => 'Sprint wrócił z później.',
            default => null,
        };
        $this->loadBoard();
    }

    private function refreshSprint(): void
    {
        $this->sprint->refresh();
        $this->loadBoard();
    }

    private function loadBoard(): void
    {
        $this->sprint->load([
            'createdBy',
            'attachments.uploader',
            'readinessItems',
            'doneItems',
            'milestones',
            'orderedTasks.assignedTo',
        ]);
        $this->sprint->setRelation('tasks', $this->sprint->orderedTasks);
    }
}
