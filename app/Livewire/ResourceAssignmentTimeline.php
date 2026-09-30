<?php

namespace App\Livewire;

use App\Livewire\Concerns\PresentsAssignmentTimeline;
use App\Services\AssignmentTimelineService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ResourceAssignmentTimeline extends Component
{
    use PresentsAssignmentTimeline;

    public string $resourceType;

    public int $resourceId;

    public function mount(string $resourceType, int $resourceId): void
    {
        abort_unless(in_array($resourceType, ['project', 'vehicle', 'accommodation'], true), 404);
        $this->resourceType = $resourceType;
        $this->resourceId = $resourceId;
        $this->focus = now()->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    public function propose(string $lane, mixed $id, string $start, mixed $end = null, bool $keepOpen = false): void
    {
        $board = $this->board();
        $laneRow = collect($board['lanes'])->firstWhere('key', $lane);
        if (! $laneRow) {
            $this->error = 'Ten tor jest niedostępny.';
            $this->proposal = null;

            return;
        }

        $proposal = $this->stageProposal($lane, $id, $start, $end, $keepOpen, $laneRow);
        if (! $proposal) {
            $this->proposal = null;
            $this->options = [];

            return;
        }

        $options = $proposal['id']
            ? []
            : app(AssignmentTimelineService::class)->resourceOptions($this->resourceType, $this->resourceId, $proposal['start'], (string) $proposal['end']);

        $this->rememberProposal($proposal, $options);
    }

    public function confirm(): void
    {
        if (! $this->proposal) {
            return;
        }

        if ($this->proposal['id'] === null && ! $this->choice) {
            $this->error = 'Wybierz osobę.';

            return;
        }

        try {
            app(AssignmentTimelineService::class)->commitResource(
                $this->resourceType,
                $this->resourceId,
                $this->proposal,
                $this->choice,
                $this->seat,
            );
            $this->cancel();
        } catch (ValidationException $exception) {
            $this->error = $this->timelineError($exception);
        }
    }

    public function render()
    {
        return view('livewire.assignment-timeline', [
            'board' => $this->paintProposal($this->board()),
            'heading' => 'Oś przypisań',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function board(): array
    {
        [$start, $end] = app(AssignmentTimelineService::class)->window($this->focusDate());

        return app(AssignmentTimelineService::class)->resourceBoard(
            $this->resourceType,
            $this->resourceId,
            $start,
            $end,
            $this->permissions(),
        );
    }

    /**
     * @return array{view: bool, create: bool, update: bool}
     */
    private function permissions(): array
    {
        $user = auth()->user();
        $can = fn (string $name): bool => (bool) $user?->hasPermission($name);
        [$view, $create, $update] = match ($this->resourceType) {
            'project' => ['project-assignments.view', 'project-assignments.create', 'project-assignments.update'],
            'vehicle' => ['vehicle-assignments.view', 'vehicle-assignments.create', 'vehicle-assignments.update'],
            default => ['accommodation-assignments.view', 'accommodation-assignments.create', 'accommodation-assignments.update'],
        };

        $visible = $can($view) || ($this->resourceType === 'project' && $can('assignments.view'));

        return [
            'view' => $visible,
            'create' => $visible && $can($create),
            'update' => $visible && $can($update),
        ];
    }
}
