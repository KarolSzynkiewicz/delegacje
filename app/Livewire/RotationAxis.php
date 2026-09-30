<?php

namespace App\Livewire;

use App\Livewire\Concerns\PresentsAssignmentTimeline;
use App\Models\Employee;
use App\Models\Rotation;
use App\Services\RotationAxisService;
use App\Services\RotationService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class RotationAxis extends Component
{
    use PresentsAssignmentTimeline;

    public string $search = '';

    public string $place = 'base';

    /** @var list<string> */
    public array $tones = ['past', 'active', 'soon', 'future'];

    public function setPlace(string $place): void
    {
        if (! in_array($place, ['base', 'away'], true)) {
            return;
        }

        $this->cancel();
        $this->place = $place;
    }

    public function toggleTone(string $tone): void
    {
        if (! in_array($tone, ['past', 'active', 'soon', 'future'], true)) {
            return;
        }

        if (in_array($tone, $this->tones, true)) {
            if (count($this->tones) === 1) {
                return;
            }
            $this->tones = array_values(array_filter($this->tones, fn (string $item): bool => $item !== $tone));
        } else {
            $this->tones[] = $tone;
        }
    }

    public function updatedSearch(): void
    {
        $this->cancel();
    }

    public function selectBar(int $employeeId, int $barId, mixed $x = null, mixed $y = null, bool $above = true): void
    {
        $this->placeMenu($x, $y, $above);
        if (! auth()->user()?->hasPermission('rotations.delete')) {
            $this->selection = null;
            $this->error = 'Brak uprawnień do usunięcia rotacji.';

            return;
        }

        if ($this->selection
            && (int) $this->selection['id'] === $barId
            && (int) $this->selection['employee_id'] === $employeeId) {
            $this->cancel();

            return;
        }

        $row = collect($this->preparedBoard()['rows'])->firstWhere('id', $employeeId);
        $bar = $row
            ? collect($row['bars'])->first(fn (array $bar) => (int) $bar['id'] === $barId && empty($bar['pending']))
            : null;
        if (! $row || ! $bar) {
            $this->selection = null;
            $this->error = 'Nie ma tej rotacji na liście.';

            return;
        }

        $this->proposal = null;
        $this->options = [];
        $this->choice = null;
        $this->error = null;
        $this->selection = [
            'employee_id' => $employeeId,
            'id' => $barId,
            'start' => $bar['start'],
            'end' => $bar['end'],
        ];
    }

    public function deleteSelected(): void
    {
        if (! $this->selection) {
            return;
        }
        if (! auth()->user()?->hasPermission('rotations.delete')) {
            $this->error = 'Brak uprawnień do usunięcia rotacji.';

            return;
        }

        $rotation = Rotation::query()
            ->where('employee_id', $this->selection['employee_id'])
            ->find($this->selection['id']);
        if (! $rotation) {
            $this->selection = null;
            $this->error = 'Nie ma tej rotacji.';

            return;
        }

        $rotation->delete();
        $this->cancel();
    }

    public function propose(int $employeeId, mixed $id, string $start, mixed $end = null, mixed $x = null, mixed $y = null, bool $above = true): void
    {
        $this->placeMenu($x, $y, $above);
        $this->selection = null;
        $board = $this->preparedBoard();
        $row = collect($board['rows'])->firstWhere('id', $employeeId);
        if (! $row) {
            $this->error = 'Ten pracownik nie jest na liście.';
            $this->proposal = null;

            return;
        }

        $proposal = $this->stageProposal('rotation', $id, $start, $end, false, $row);
        if (! $proposal || $proposal['end'] === null) {
            $this->proposal = null;
            if ($proposal && $proposal['end'] === null) {
                $this->error = 'Rotacja potrzebuje daty końca.';
            }

            return;
        }

        $proposal['employee_id'] = $employeeId;
        $this->rememberProposal($proposal, []);
    }

    public function confirm(): void
    {
        if (! $this->proposal || $this->proposal['end'] === null) {
            return;
        }

        $employee = Employee::query()->find($this->proposal['employee_id'] ?? 0);
        if (! $employee || $employee->terminated_at !== null) {
            $this->error = 'Ten pracownik jest zwolniony.';

            return;
        }

        $start = Carbon::parse($this->proposal['start'])->startOfDay();
        $end = Carbon::parse($this->proposal['end'])->startOfDay();

        try {
            if ($this->proposal['id']) {
                $rotation = Rotation::query()
                    ->where('employee_id', $employee->id)
                    ->findOrFail($this->proposal['id']);
                app(RotationService::class)->updateRotation($rotation, $start, $end, $rotation->notes);
            } else {
                app(RotationService::class)->createRotation($employee, $start, $end);
            }
            $this->cancel();
        } catch (ValidationException $exception) {
            $this->error = $this->timelineError($exception);
        }
    }

    public function render(RotationAxisService $axis)
    {
        $board = $this->paintProposal($this->withPermissions($axis->board($this->search, $this->place)));

        return view('livewire.rotation-axis', [
            'board' => $this->filterTones($this->paintTones($board)),
            'tones' => $this->tones,
        ]);
    }

    /**
     * @param  array<string, mixed>  $board
     * @return array<string, mixed>
     */
    private function withPermissions(array $board): array
    {
        $user = auth()->user();
        $board['can_create'] = (bool) $user?->hasPermission('rotations.create');
        $board['can_update'] = (bool) $user?->hasPermission('rotations.update');
        $board['can_delete'] = (bool) $user?->hasPermission('rotations.delete');
        foreach ($board['rows'] as $index => $row) {
            $board['rows'][$index]['can_create'] = $board['can_create'];
            $board['rows'][$index]['can_update'] = $board['can_update'];
        }

        return $board;
    }

    /**
     * @param  array<string, mixed>  $board
     * @return array<string, mixed>
     */
    private function paintTones(array $board): array
    {
        foreach ($board['rows'] as $index => $row) {
            foreach ($row['bars'] as $barIndex => $bar) {
                if (empty($bar['pending']) || empty($bar['end'])) {
                    continue;
                }
                $board['rows'][$index]['bars'][$barIndex]['tone'] = RotationAxisService::tone(
                    $bar['start'],
                    $bar['end'],
                    $board['today'],
                    $board['soon'],
                );
                $board['rows'][$index]['bars'][$barIndex]['title'] = Carbon::parse($bar['start'])->format('j.m.Y')
                    .' – '.Carbon::parse($bar['end'])->format('j.m.Y');
            }
        }

        return $board;
    }

    /**
     * @param  array<string, mixed>  $board
     * @return array<string, mixed>
     */
    private function filterTones(array $board): array
    {
        if (count($this->tones) === 4) {
            return $board;
        }

        $allowed = array_fill_keys($this->tones, true);
        foreach ($board['rows'] as $index => $row) {
            $board['rows'][$index]['bars'] = array_values(array_filter(
                $row['bars'],
                fn (array $bar): bool => isset($allowed[$bar['tone'] ?? ''])
            ));
        }

        return $board;
    }

    private function preparedBoard(): array
    {
        return $this->withPermissions(app(RotationAxisService::class)->board($this->search, $this->place));
    }
}
