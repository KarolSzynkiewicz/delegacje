<?php

namespace App\Livewire;

use App\Livewire\Concerns\PresentsAssignmentTimeline;
use App\Models\Employee;
use App\Services\AssignmentTimelineService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class EmployeeAssignmentTimeline extends Component
{
    use PresentsAssignmentTimeline;

    public Employee $employee;

    public function mount(Employee $employee): void
    {
        $this->employee = $employee;
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

        $options = $proposal['id'] || $lane === 'rotation'
            ? []
            : app(AssignmentTimelineService::class)->optionsFor($lane, $this->employee, $proposal['start'], (string) $proposal['end']);

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

        $lane = (string) $this->selection['lane'];
        $laneRow = collect($this->board()['lanes'])->firstWhere('key', $lane);
        if (! ($laneRow['can_delete'] ?? false)) {
            $this->error = 'Brak uprawnień do usunięcia tego paska.';

            return;
        }

        try {
            app(AssignmentTimelineService::class)->deleteEmployee(
                $this->employee,
                $lane,
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

        if ($this->proposal['id'] === null && $this->proposal['lane'] !== 'rotation' && ! $this->choice) {
            $this->error = 'Wybierz pozycję z listy.';

            return;
        }

        try {
            app(AssignmentTimelineService::class)->commitEmployee(
                $this->employee,
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

        return app(AssignmentTimelineService::class)->employeeBoard($this->employee, $start, $end, $this->permissions());
    }

    /**
     * @return array<string, array{view: bool, create: bool, update: bool, delete: bool}>
     */
    private function permissions(): array
    {
        $user = auth()->user();
        $can = fn (string $name): bool => (bool) $user?->hasPermission($name);

        return [
            'rotation' => [
                'view' => $can('rotations.view'),
                'create' => $can('rotations.create'),
                'update' => $can('rotations.update'),
                'delete' => $can('rotations.delete'),
            ],
            'project' => [
                'view' => $can('project-assignments.view'),
                'create' => $can('project-assignments.create'),
                'update' => $can('project-assignments.update'),
                'delete' => $can('project-assignments.delete'),
            ],
            'accommodation' => [
                'view' => $can('accommodation-assignments.view'),
                'create' => $can('accommodation-assignments.create'),
                'update' => $can('accommodation-assignments.update'),
                'delete' => $can('accommodation-assignments.delete'),
            ],
            'vehicle' => [
                'view' => $can('vehicle-assignments.view'),
                'create' => $can('vehicle-assignments.create'),
                'update' => $can('vehicle-assignments.update'),
                'delete' => $can('vehicle-assignments.delete'),
            ],
        ];
    }
}
