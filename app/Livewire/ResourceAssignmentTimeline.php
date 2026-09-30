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

    public function propose(
        string $lane,
        mixed $id,
        string $start,
        mixed $end = null,
        bool $keepOpen = false,
        mixed $x = null,
        mixed $y = null,
        bool $above = true,
    ): void {
        $this->placeMenu($x, $y, $above);
        $this->selection = null;
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

    public function selectBar(string $lane, int $barId, mixed $x = null, mixed $y = null, bool $above = true): void
    {
        $this->placeMenu($x, $y, $above);
        $laneRow = collect($this->board()['lanes'])->firstWhere('key', $lane);
        if (! $laneRow || ! ($laneRow['can_delete'] ?? false)) {
            $this->selection = null;
            $this->error = 'Brak uprawnień do usunięcia tego paska.';

            return;
        }

        if ($this->selection
            && ($this->selection['lane'] ?? null) === $lane
            && (int) ($this->selection['id'] ?? 0) === $barId) {
            $this->cancel();

            return;
        }

        $bar = collect($laneRow['bars'])->first(
            fn (array $bar): bool => (int) $bar['id'] === $barId && empty($bar['pending']) && empty($bar['locked'])
        );
        if (! $bar) {
            $this->selection = null;
            $this->error = 'Tego paska nie da się usunąć.';

            return;
        }

        $this->proposal = null;
        $this->options = [];
        $this->choice = null;
        $this->error = null;
        $this->selection = [
            'lane' => $lane,
            'id' => $barId,
            'start' => $bar['start'],
            'end' => $bar['end'],
            'label' => $bar['label'] ?? null,
        ];
    }

    public function deleteSelected(): void
    {
        if (! $this->selection) {
            return;
        }

        $laneRow = collect($this->board()['lanes'])->firstWhere('key', $this->selection['lane']);
        if (! ($laneRow['can_delete'] ?? false)) {
            $this->error = 'Brak uprawnień do usunięcia tego paska.';

            return;
        }

        try {
            app(AssignmentTimelineService::class)->deleteResource(
                $this->resourceType,
                $this->resourceId,
                (int) $this->selection['id'],
            );
            $this->cancel();
        } catch (ValidationException $exception) {
            $this->error = $this->timelineError($exception);
        }
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
     * @return array{view: bool, create: bool, update: bool, delete: bool}
     */
    private function permissions(): array
    {
        $user = auth()->user();
        $can = fn (string $name): bool => (bool) $user?->hasPermission($name);
        [$view, $create, $update, $delete] = match ($this->resourceType) {
            'project' => ['project-assignments.view', 'project-assignments.create', 'project-assignments.update', 'project-assignments.delete'],
            'vehicle' => ['vehicle-assignments.view', 'vehicle-assignments.create', 'vehicle-assignments.update', 'vehicle-assignments.delete'],
            default => ['accommodation-assignments.view', 'accommodation-assignments.create', 'accommodation-assignments.update', 'accommodation-assignments.delete'],
        };

        $visible = $can($view) || ($this->resourceType === 'project' && $can('assignments.view'));

        return [
            'view' => $visible,
            'create' => $visible && $can($create),
            'update' => $visible && $can($update),
            'delete' => $visible && $can($delete),
        ];
    }
}
