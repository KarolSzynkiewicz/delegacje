<?php

namespace App\Livewire;

use App\Enums\OfficeWorkLocation;
use App\Models\OfficeProfile;
use App\Services\OfficeHoursService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class OfficeHoursGrid extends Component
{
    public string $month;

    /** Poniedziałek tygodnia (Y-m-d). */
    public string $weekStart;

    /** week | month */
    public string $viewMode = 'week';

    public bool $onlyMine = false;

    /** @var array<string, array{starts_at: string, ends_at: string, location: string}> */
    public array $cells = [];

    public string $flash = '';

    public function mount(?string $month = null, ?string $week = null, ?string $viewMode = null, bool $onlyMine = false): void
    {
        $this->onlyMine = $onlyMine;
        $this->viewMode = in_array($viewMode, ['week', 'month'], true) ? $viewMode : 'week';

        if (is_string($week) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $week)) {
            $this->weekStart = Carbon::parse($week)->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        } elseif (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $anchor = now()->format('Y-m') === $month
                ? now()
                : Carbon::parse($month.'-01');
            $this->weekStart = $anchor->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        } else {
            $this->weekStart = now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        }

        $this->month = (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month))
            ? $month
            : Carbon::parse($this->weekStart)->format('Y-m');

        $this->loadCells();
    }

    public function setViewMode(string $mode): void
    {
        if (! in_array($mode, ['week', 'month'], true)) {
            return;
        }
        $this->viewMode = $mode;
        $this->flash = '';
        if ($mode === 'month') {
            $this->month = Carbon::parse($this->weekStart)->format('Y-m');
        }
        $this->loadCells();
    }

    public function previousPeriod(): void
    {
        $this->flash = '';
        if ($this->viewMode === 'week') {
            $this->weekStart = Carbon::parse($this->weekStart)->subWeek()->format('Y-m-d');
            $this->month = Carbon::parse($this->weekStart)->format('Y-m');
        } else {
            $this->month = Carbon::parse($this->month.'-01')->subMonth()->format('Y-m');
            $this->weekStart = Carbon::parse($this->month.'-01')->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        }
        $this->loadCells();
    }

    public function nextPeriod(): void
    {
        $this->flash = '';
        if ($this->viewMode === 'week') {
            $this->weekStart = Carbon::parse($this->weekStart)->addWeek()->format('Y-m-d');
            $this->month = Carbon::parse($this->weekStart)->format('Y-m');
        } else {
            $this->month = Carbon::parse($this->month.'-01')->addMonth()->format('Y-m');
            $this->weekStart = Carbon::parse($this->month.'-01')->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        }
        $this->loadCells();
    }

    /** Alias dla starych wywołań / testów. */
    public function previousMonth(): void
    {
        $this->previousPeriod();
    }

    public function nextMonth(): void
    {
        $this->nextPeriod();
    }

    public function updatedCells(mixed $value, string $key): void
    {
        if (! str_ends_with($key, '.starts_at') && ! str_ends_with($key, '.ends_at')) {
            return;
        }

        [$cellKey, $field] = array_pad(explode('.', $key, 2), 2, null);
        if ($cellKey === null || $field === null || ! isset($this->cells[$cellKey])) {
            return;
        }

        $this->cells[$cellKey][$field] = $this->formatTimeInput(is_string($value) ? $value : '');
    }

    public function toggleLocation(string $key): void
    {
        if (! isset($this->cells[$key])) {
            $this->cells[$key] = [
                'starts_at' => '',
                'ends_at' => '',
                'location' => OfficeWorkLocation::Office->value,
            ];
        }

        $current = OfficeWorkLocation::tryFrom($this->cells[$key]['location'] ?? '')
            ?? OfficeWorkLocation::Office;
        $next = $current->next();
        $this->cells[$key]['location'] = $next->value;

        if ($next === OfficeWorkLocation::Leave) {
            $this->cells[$key]['starts_at'] = '';
            $this->cells[$key]['ends_at'] = '';
        }
    }

    /**
     * Skopiuj poprzedni dzień roboczy na wskazaną komórkę.
     */
    public function copyPreviousDay(string $key): void
    {
        $service = app(OfficeHoursService::class);
        try {
            [$profileId, $date] = (function () use ($service, $key) {
                if (! preg_match('/^(\d+)_(\d{8})$/', $key, $m)) {
                    return [null, null];
                }
                $ymd = $m[2];

                return [
                    (int) $m[1],
                    substr($ymd, 0, 4).'-'.substr($ymd, 4, 2).'-'.substr($ymd, 6, 2),
                ];
            })();
        } catch (\Throwable) {
            return;
        }

        if (! $profileId || ! $date) {
            return;
        }

        $cursor = Carbon::parse($date)->subDay();
        for ($i = 0; $i < 14; $i++) {
            if ($cursor->isWeekend()) {
                $cursor->subDay();

                continue;
            }
            $prevKey = $service->cellKey($profileId, $cursor->format('Y-m-d'));
            $prev = $this->cells[$prevKey] ?? null;
            if (! $prev) {
                $cursor->subDay();

                continue;
            }
            $loc = $prev['location'] ?? OfficeWorkLocation::Office->value;
            $hasTimes = ($prev['starts_at'] ?? '') !== '' && ($prev['ends_at'] ?? '') !== '';
            $isLeave = $loc === OfficeWorkLocation::Leave->value;
            if ($hasTimes || $isLeave) {
                $this->cells[$key] = [
                    'starts_at' => $prev['starts_at'] ?? '',
                    'ends_at' => $prev['ends_at'] ?? '',
                    'location' => $loc,
                ];

                return;
            }
            $cursor->subDay();
        }
    }

    /**
     * Skopiuj wpis z pierwszego dnia miesiąca (z danymi) na pozostałe dni robocze.
     */
    public function copyFromFirstDay(int $profileId): void
    {
        $this->resetErrorBag();
        $this->flash = '';

        $service = app(OfficeHoursService::class);
        $monthStart = Carbon::parse($this->month.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $template = null;
        $templateKey = null;
        for ($d = $monthStart->copy(); $d->lte($monthEnd); $d->addDay()) {
            $key = $service->cellKey($profileId, $d->format('Y-m-d'));
            $cell = $this->cells[$key] ?? null;
            if (! $cell) {
                continue;
            }
            $loc = $cell['location'] ?? OfficeWorkLocation::Office->value;
            $hasTimes = ($cell['starts_at'] ?? '') !== '' && ($cell['ends_at'] ?? '') !== '';
            $isLeave = $loc === OfficeWorkLocation::Leave->value;
            if ($hasTimes || $isLeave) {
                $template = $cell;
                $templateKey = $key;
                break;
            }
        }

        if ($template === null || $templateKey === null) {
            $this->addError('cells', 'Brak wpisu do skopiowania — uzupełnij najpierw pierwszy dzień.');

            return;
        }

        $copied = 0;
        for ($d = $monthStart->copy(); $d->lte($monthEnd); $d->addDay()) {
            if ($d->isWeekend()) {
                continue;
            }
            $key = $service->cellKey($profileId, $d->format('Y-m-d'));
            if ($key === $templateKey) {
                continue;
            }
            $this->cells[$key] = [
                'starts_at' => $template['starts_at'] ?? '',
                'ends_at' => $template['ends_at'] ?? '',
                'location' => $template['location'] ?? OfficeWorkLocation::Office->value,
            ];
            $copied++;
        }

        $this->flash = $copied > 0
            ? "Skopiowano wzorzec na {$copied} dni robocze. Zapisz, żeby utrwalić."
            : 'Nie było dni do uzupełnienia.';
    }

    public function save(OfficeHoursService $service): void
    {
        $this->resetErrorBag();
        $this->flash = '';

        $allowedProfileId = null;
        if ($this->onlyMine) {
            $profile = auth()->user()?->officeProfile;
            if (! $profile) {
                $this->addError('cells', 'Nie masz profilu biurowego.');

                return;
            }
            $allowedProfileId = (int) $profile->id;
        }

        foreach ($this->cells as $cellKey => $cell) {
            foreach (['starts_at', 'ends_at'] as $field) {
                $this->cells[$cellKey][$field] = $this->formatTimeInput((string) ($cell[$field] ?? ''));
            }
        }

        try {
            $service->saveMonthCells($this->cells, $allowedProfileId);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $attribute => $messages) {
                foreach ($messages as $message) {
                    $this->addError($attribute, $message);
                }
            }
            $this->dispatch('office-hours-highlight-errors');

            return;
        }

        $this->flash = 'Zapisano godziny.';
        $this->loadCells();
    }

    public function render(OfficeHoursService $service)
    {
        $onlyUserId = $this->onlyMine ? auth()->id() : null;
        $monthGrid = $service->getMonthlyGrid($this->month, $onlyUserId);
        $displayDays = $this->displayDays();
        $built = $this->buildViewModel($service, $monthGrid, $displayDays);

        $weekStartDate = Carbon::parse($this->weekStart);
        $weekEndDate = $weekStartDate->copy()->endOfWeek(Carbon::SUNDAY);

        return view('livewire.office-hours-grid', [
            'monthStart' => $monthGrid['monthStart'],
            'monthEnd' => $monthGrid['monthEnd'],
            'weekStartDate' => $weekStartDate,
            'weekEndDate' => $weekEndDate,
            'days' => $displayDays,
            'profiles' => $monthGrid['profiles'],
            'kpis' => $built['kpis'],
            'rowMeta' => $built['rowMeta'],
            'dayTotals' => $built['dayTotals'],
            'cellHours' => $built['cellHours'],
            'today' => now()->startOfDay(),
            'isWeekView' => $this->viewMode === 'week',
        ]);
    }

    /**
     * @return list<Carbon>
     */
    private function displayDays(): array
    {
        if ($this->viewMode === 'month') {
            $start = Carbon::parse($this->month.'-01')->startOfMonth();
            $end = $start->copy()->endOfMonth();
        } else {
            $start = Carbon::parse($this->weekStart)->startOfWeek(Carbon::MONDAY);
            $end = $start->copy()->endOfWeek(Carbon::SUNDAY);
        }

        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days[] = $d->copy();
        }

        return $days;
    }

    /**
     * @param  array{monthStart: Carbon, monthEnd: Carbon, days: list<Carbon>, profiles: \Illuminate\Support\Collection, cells: array}  $monthGrid
     * @param  list<Carbon>  $displayDays
     * @return array{kpis: array, rowMeta: array<int, array>, dayTotals: array<string, float>, cellHours: array<string, float>}
     */
    private function buildViewModel(OfficeHoursService $service, array $monthGrid, array $displayDays): array
    {
        $monthStart = $monthGrid['monthStart'];
        $monthEnd = $monthGrid['monthEnd'];
        $today = now()->startOfDay();
        $normUntil = $today->lt($monthStart)
            ? $monthStart->copy()->subDay()
            : ($today->gt($monthEnd) ? $monthEnd->copy() : $today->copy());

        $cellHours = [];
        $dayTotals = [];
        $rowMeta = [];
        $totalHours = 0.0;
        $remoteHours = 0.0;
        $leaveDays = 0;
        $workedCells = 0;

        foreach ($displayDays as $day) {
            $dayTotals[$day->format('Y-m-d')] = 0.0;
        }

        foreach ($monthGrid['profiles'] as $profile) {
            /** @var OfficeProfile $profile */
            $fte = (float) $profile->fte;
            $rowTotal = 0.0;
            $rowToToday = 0.0;

            // Suma miesiąca z pełnego zakresu komórek miesiąca
            for ($d = $monthStart->copy(); $d->lte($monthEnd); $d->addDay()) {
                $date = $d->format('Y-m-d');
                $key = $service->cellKey((int) $profile->id, $date);
                $cell = $this->cells[$key] ?? ['starts_at' => '', 'ends_at' => '', 'location' => 'office'];
                $loc = $cell['location'] ?? 'office';
                $hours = $service->resolveCellHours(
                    $cell['starts_at'] ?? null,
                    $cell['ends_at'] ?? null,
                    $loc,
                    $fte
                );
                $cellHours[$key] = $hours;
                $rowTotal += $hours;

                if ($hours > 0) {
                    $workedCells++;
                    $totalHours += $hours;
                    if ($loc === OfficeWorkLocation::Remote->value) {
                        $remoteHours += $hours;
                    }
                    if ($loc === OfficeWorkLocation::Leave->value) {
                        $leaveDays++;
                    }
                }

                if ($d->lte($normUntil) && ! $d->isWeekend()) {
                    $rowToToday += $hours;
                }
            }

            $weekHasEntries = false;
            foreach ($displayDays as $day) {
                $date = $day->format('Y-m-d');
                $key = $service->cellKey((int) $profile->id, $date);
                $dayTotals[$date] = ($dayTotals[$date] ?? 0) + ($cellHours[$key] ?? 0);

                $cell = $this->cells[$key] ?? ['starts_at' => '', 'ends_at' => '', 'location' => 'office'];
                $loc = $cell['location'] ?? 'office';
                if (
                    ($cellHours[$key] ?? 0) > 0
                    || $loc !== OfficeWorkLocation::Office->value
                    || ($cell['starts_at'] ?? '') !== ''
                    || ($cell['ends_at'] ?? '') !== ''
                ) {
                    $weekHasEntries = true;
                }
            }

            $normMonth = $service->monthNormHours($monthStart, $monthEnd, $fte);
            $normToToday = $normUntil->lt($monthStart)
                ? 0.0
                : round($service->weekdayCount($monthStart, $normUntil) * OfficeHoursService::STANDARD_DAY_HOURS * $fte, 2);
            $diff = round($rowToToday - $normToToday, 2);
            $progress = $normMonth > 0 ? (int) round(($rowTotal / $normMonth) * 100) : 0;

            $rowMeta[$profile->id] = [
                'total' => $rowTotal,
                'norm' => $normMonth,
                'diff' => $diff,
                'progress' => $progress,
                'initials' => $this->initials($profile->user?->name ?? '?'),
                'week_has_entries' => $weekHasEntries,
            ];
        }

        $kpis = [
            'total_hours' => $totalHours,
            'avg_day' => $workedCells > 0 ? round($totalHours / $workedCells, 1) : 0.0,
            'remote_pct' => $totalHours > 0 ? (int) round(($remoteHours / $totalHours) * 100) : 0,
            'leave_days' => $leaveDays,
        ];

        return compact('kpis', 'rowMeta', 'dayTotals', 'cellHours');
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $letters !== '' ? $letters : '?';
    }

    private function formatTimeInput(string $value): string
    {
        $raw = trim(str_replace([',', '.'], ':', $value));
        if ($raw === '') {
            return '';
        }

        if (preg_match('/^\d{1,2}$/', $raw)) {
            $h = (int) $raw;

            return $h <= 23 ? sprintf('%02d:00', $h) : $raw;
        }

        if (preg_match('/^\d{3,4}$/', $raw)) {
            $raw = str_pad($raw, 4, '0', STR_PAD_LEFT);

            return substr($raw, 0, 2).':'.substr($raw, 2, 2);
        }

        if (preg_match('/^(\d{1,2}):(\d{1,2})$/', $raw, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }

        return $raw;
    }

    private function loadCells(): void
    {
        $onlyUserId = $this->onlyMine ? auth()->id() : null;
        $service = app(OfficeHoursService::class);

        // Zawsze ładuj cały miesiąc (KPI + kopiuj + zapis bez kasowania innych dni).
        // W widoku tygodnia doklej też dni z sąsiedniego miesiąca jeśli tydzień nachodzi.
        $monthGrid = $service->getMonthlyGrid($this->month, $onlyUserId);
        $cells = [];

        foreach ($monthGrid['profiles'] as $profile) {
            foreach ($monthGrid['days'] as $day) {
                $key = $service->cellKey((int) $profile->id, $day->format('Y-m-d'));
                $existing = $monthGrid['cells'][$key] ?? null;
                $cells[$key] = [
                    'starts_at' => $existing['starts_at'] ?? '',
                    'ends_at' => $existing['ends_at'] ?? '',
                    'location' => $existing['location'] ?? OfficeWorkLocation::Office->value,
                ];
            }
        }

        if ($this->viewMode === 'week') {
            $weekStart = Carbon::parse($this->weekStart)->startOfWeek(Carbon::MONDAY);
            $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
            $extra = $service->getGridForRange($weekStart, $weekEnd, $onlyUserId);
            foreach ($extra['profiles'] as $profile) {
                foreach ($extra['days'] as $day) {
                    $key = $service->cellKey((int) $profile->id, $day->format('Y-m-d'));
                    if (isset($cells[$key])) {
                        continue;
                    }
                    $existing = $extra['cells'][$key] ?? null;
                    $cells[$key] = [
                        'starts_at' => $existing['starts_at'] ?? '',
                        'ends_at' => $existing['ends_at'] ?? '',
                        'location' => $existing['location'] ?? OfficeWorkLocation::Office->value,
                    ];
                }
            }
        }

        $this->cells = $cells;
    }
}
