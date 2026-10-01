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
    use PresentsAssignmentTimeline {
        cancel as protected timelineCancel;
    }

    public string $search = '';

    public string $place = 'base';

    /** @var list<string> */
    public array $tones = ['past', 'active', 'soon', 'future'];

    /** 0 = domyślne okno (~6 tyg.); ±1 = przesunięcie o 3 tygodnie (połowa okna). */
    public int $periodOffset = 0;

    /** ends_soon | starts_soon | name */
    public string $sort = 'ends_soon';

    /** Edycja notatki w popoverze zaznaczonej rotacji. */
    public string $selectionNotes = '';

    public bool $notesSaved = false;

    public function setPlace(string $place): void
    {
        if (! in_array($place, ['base', 'away'], true)) {
            return;
        }

        $this->cancel();
        $this->place = $place;
    }

    public function setSort(string $sort): void
    {
        if (! in_array($sort, ['ends_soon', 'starts_soon', 'name'], true)) {
            return;
        }

        $this->sort = $sort;
    }

    public function updatedSort(): void
    {
        if (! in_array($this->sort, ['ends_soon', 'starts_soon', 'name'], true)) {
            $this->sort = 'ends_soon';
        }
    }

    public function previousPeriod(): void
    {
        $this->cancel();
        $this->periodOffset--;
    }

    public function nextPeriod(): void
    {
        $this->cancel();
        $this->periodOffset++;
    }

    public function resetPeriod(): void
    {
        $this->cancel();
        $this->periodOffset = 0;
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

    public function cancel(): void
    {
        $this->selectionNotes = '';
        $this->notesSaved = false;
        $this->timelineCancel();
    }

    public function updatedSelectionNotes(): void
    {
        $this->notesSaved = false;
    }

    public function selectBar(int $employeeId, int $barId, mixed $x = null, mixed $y = null, bool $above = true): void
    {
        $this->placeMenu($x, $y, $above);
        $this->notesSaved = false;

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

        $notes = $bar['notes'] ?? null;
        $showUrl = $bar['show_url'] ?? null;
        if ($notes === null || $showUrl === null) {
            $rotation = Rotation::query()
                ->where('employee_id', $employeeId)
                ->find($barId);
            if ($rotation) {
                $notes = $notes ?? $rotation->notes;
                $showUrl = $showUrl ?? route('employees.rotations.show', [$employeeId, $rotation]);
            }
        }

        $this->proposal = null;
        $this->options = [];
        $this->choice = null;
        $this->error = null;
        $this->selectionNotes = (string) ($notes ?? '');
        $this->selection = [
            'employee_id' => $employeeId,
            'id' => $barId,
            'start' => $bar['start'],
            'end' => $bar['end'],
            'notes' => $notes,
            'show_url' => $showUrl,
            'employee_name' => $row['name'] ?? null,
            'can_delete' => (bool) auth()->user()?->hasPermission('rotations.delete'),
            'can_update' => (bool) auth()->user()?->hasPermission('rotations.update'),
        ];
    }

    public function saveSelectionNotes(): void
    {
        if (! $this->selection) {
            return;
        }
        if (! auth()->user()?->hasPermission('rotations.update')) {
            $this->error = 'Brak uprawnień do edycji notatki.';

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

        $notes = trim($this->selectionNotes);
        $notes = $notes === '' ? null : $notes;

        try {
            app(RotationService::class)->updateRotation(
                $rotation,
                $rotation->start_date->copy()->startOfDay(),
                $rotation->end_date->copy()->startOfDay(),
                $notes,
            );
        } catch (ValidationException $exception) {
            $this->error = $this->timelineError($exception);

            return;
        }

        $this->selection['notes'] = $notes;
        $this->selectionNotes = (string) ($notes ?? '');
        $this->notesSaved = true;
        $this->error = null;
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
        $existing = collect($row['bars'])->first(
            fn (array $bar) => (int) ($bar['id'] ?? 0) === (int) ($proposal['id'] ?? 0) && empty($bar['pending'])
        );
        $proposal['notes'] = $existing['notes'] ?? null;
        $proposal['show_url'] = $existing['show_url'] ?? null;
        $proposal['employee_name'] = $row['name'] ?? null;
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
        $board = $this->paintProposal($this->withPermissions($axis->board($this->search, $this->place, $this->periodOffset)));

        return view('livewire.rotation-axis', [
            'board' => $this->sortRows($this->filterTones($this->paintTones($board))),
            'tones' => $this->tones,
            'sort' => $this->sort,
            'periodOffset' => $this->periodOffset,
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
        // „Aktywne” obejmuje też trwające rotacje, które wygasają w ≤7 dni (tone = soon).
        if (isset($allowed['active'])) {
            $allowed['soon'] = true;
        }

        foreach ($board['rows'] as $index => $row) {
            $board['rows'][$index]['bars'] = array_values(array_filter(
                $row['bars'],
                fn (array $bar): bool => isset($allowed[$bar['tone'] ?? ''])
            ));
        }

        // Przy zawężonym filtrze tonów chowaj też puste wiersze (nie tylko paski).
        $board['rows'] = array_values(array_filter(
            $board['rows'],
            fn (array $row): bool => count($row['bars']) > 0
        ));

        return $board;
    }

    /**
     * @param  array<string, mixed>  $board
     * @return array<string, mixed>
     */
    private function sortRows(array $board): array
    {
        $rows = collect($board['rows']);

        $board['rows'] = match ($this->sort) {
            'starts_soon' => $rows
                ->sortBy([
                    fn (array $row) => collect($row['bars'])->min('start') ?? '9999-99-99',
                    fn (array $row) => mb_strtolower($row['name']),
                ])
                ->values()
                ->all(),
            'name' => $rows
                ->sortBy(fn (array $row) => mb_strtolower($row['name']))
                ->values()
                ->all(),
            default => $rows
                ->sortBy([
                    fn (array $row) => collect($row['bars'])->min('end') ?? '9999-99-99',
                    fn (array $row) => mb_strtolower($row['name']),
                ])
                ->values()
                ->all(),
        };

        return $board;
    }

    private function preparedBoard(): array
    {
        return $this->withPermissions(app(RotationAxisService::class)->board($this->search, $this->place, $this->periodOffset));
    }
}
