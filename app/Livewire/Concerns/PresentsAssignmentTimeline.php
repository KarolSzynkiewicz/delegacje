<?php

namespace App\Livewire\Concerns;

use App\Services\AssignmentTimelineService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

trait PresentsAssignmentTimeline
{
    public string $focus = '';

    public ?array $proposal = null;

    public array $options = [];

    public ?string $choice = null;

    public string $seat = 'passenger';

    public ?string $error = null;

    public function shift(int $weeks): void
    {
        $this->cancel();
        $this->focus = Carbon::parse($this->focus === '' ? now() : $this->focus)
            ->addWeeks($weeks)
            ->startOfWeek(Carbon::MONDAY)
            ->toDateString();
    }

    public function cancel(): void
    {
        $this->proposal = null;
        $this->options = [];
        $this->choice = null;
        $this->seat = 'passenger';
        $this->error = null;
    }

    /**
     * @param  array<string, mixed>  $laneRow
     * @return array{lane: string, id: ?int, start: string, end: ?string, keep_open: bool}|null
     */
    protected function stageProposal(string $lane, mixed $id, string $start, mixed $end, bool $keepOpen, array $laneRow): ?array
    {
        $barId = (int) $id > 0 ? (int) $id : null;
        $startDate = Carbon::parse($start)->toDateString();
        $endDate = ($keepOpen || $end === null || $end === '') ? null : Carbon::parse((string) $end)->toDateString();
        if ($endDate !== null && $endDate < $startDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $creating = $barId === null;
        if ($creating && ! ($laneRow['can_create'] ?? false)) {
            $this->error = 'Brak uprawnień do nowego zakresu.';

            return null;
        }
        if (! $creating && ! ($laneRow['can_update'] ?? false)) {
            $this->error = 'Brak uprawnień do zmiany tego paska.';

            return null;
        }

        try {
            if ($creating) {
                if ($endDate === null) {
                    throw ValidationException::withMessages(['end_date' => 'Nowy zakres potrzebuje daty końca.']);
                }
                app(AssignmentTimelineService::class)->assertRangeInGaps($startDate, $endDate, $laneRow['gaps'] ?? []);
            } else {
                $bar = collect($laneRow['bars'] ?? [])->firstWhere('id', $barId);
                if (! $bar || ! empty($bar['locked'])) {
                    $this->error = 'Tego paska nie da się zmienić.';

                    return null;
                }
                app(AssignmentTimelineService::class)->assertResizeInside(
                    $startDate,
                    $endDate,
                    $keepOpen,
                    ['min' => $bar['min'], 'max' => $bar['max']],
                    $bar['start'],
                    $bar['end'],
                );
            }
        } catch (ValidationException $exception) {
            $this->error = $this->timelineError($exception);

            return null;
        }

        return [
            'lane' => $lane,
            'id' => $barId,
            'start' => $startDate,
            'end' => $keepOpen ? null : $endDate,
            'keep_open' => $creating ? false : $keepOpen,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $options
     */
    protected function rememberProposal(array $proposal, array $options): void
    {
        $this->error = null;
        $this->proposal = $proposal;
        $this->options = $options;
        $enabled = collect($options)->first(fn (array $option): bool => (bool) $option['enabled']);
        $this->choice = $enabled['key'] ?? null;
        $this->seat = 'passenger';
    }

    /**
     * @param  array<string, mixed>  $board
     * @return array<string, mixed>
     */
    protected function paintProposal(array $board): array
    {
        if (! $this->proposal) {
            return $board;
        }

        $service = app(AssignmentTimelineService::class);
        $bucket = array_key_exists('lanes', $board) ? 'lanes' : 'rows';
        $dayWidth = (int) ($board['day_width'] ?? AssignmentTimelineService::DAY_WIDTH);
        foreach ($board[$bucket] as $index => $lane) {
            if (($lane['key'] ?? null) !== $this->proposal['lane']) {
                continue;
            }
            if (isset($this->proposal['employee_id']) && (int) $lane['id'] !== (int) $this->proposal['employee_id']) {
                continue;
            }
            if ($this->proposal['id']) {
                foreach ($lane['bars'] as $barIndex => $bar) {
                    if ((int) $bar['id'] !== (int) $this->proposal['id']) {
                        continue;
                    }
                    $pixels = $service->pixels(
                        $board['start'],
                        $board['end'],
                        $this->proposal['start'],
                        $this->proposal['keep_open'] ? null : $this->proposal['end'],
                        $dayWidth,
                    );
                    $board[$bucket][$index]['bars'][$barIndex] = array_merge($bar, $pixels, [
                        'start' => $this->proposal['start'],
                        'end' => $this->proposal['keep_open'] ? null : $this->proposal['end'],
                        'open' => (bool) $this->proposal['keep_open'],
                        'pending' => true,
                    ]);
                }
            } else {
                $pixels = $service->pixels($board['start'], $board['end'], $this->proposal['start'], $this->proposal['end'], $dayWidth);
                $board[$bucket][$index]['bars'][] = array_merge($pixels, [
                    'id' => 0,
                    'start' => $this->proposal['start'],
                    'end' => $this->proposal['end'],
                    'open' => false,
                    'label' => 'Nowy zakres',
                    'min' => $this->proposal['start'],
                    'max' => $this->proposal['end'],
                    'locked' => true,
                    'pending' => true,
                ]);
            }
        }

        return $board;
    }

    protected function timelineError(ValidationException $exception): string
    {
        return collect($exception->validator->errors()->all())->first() ?: 'Nie udało się zapisać.';
    }

    protected function focusDate(): Carbon
    {
        return Carbon::parse($this->focus === '' ? now() : $this->focus)->startOfDay();
    }
}
