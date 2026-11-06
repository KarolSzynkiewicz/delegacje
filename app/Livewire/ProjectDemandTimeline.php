<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\ProjectDemand;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ProjectDemandTimeline extends Component
{
    public const WEEK_WIDTH = 64;

    /** Puste tygodnie przed/po projekcie — miejsce na przeciąganie start/końca. */
    public const AXIS_PAD_WEEKS = 4;

    public Project $project;

    public ?array $proposal = null;

    public ?array $selection = null;

    /** Propozycja przesunięcia daty start/końca projektu (agregat). */
    public ?array $boundProposal = null;

    public int $draftCount = 1;

    public ?string $error = null;

    public ?float $menuX = null;

    public ?float $menuY = null;

    public bool $menuAbove = true;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function canManage(): bool
    {
        $user = Auth::user();

        return $user && ($user->isAdmin() || $user->hasPermission('projects.update'));
    }

    public function propose(
        int $id,
        string $start,
        mixed $end = null,
        mixed $x = null,
        mixed $y = null,
        bool $above = true,
    ): void {
        $this->placeMenu($x, $y, $above);
        $this->selection = null;
        $this->error = null;

        if (! $this->canManage()) {
            $this->error = 'Brak uprawnień do zmiany zapotrzebowania.';
            $this->proposal = null;

            return;
        }

        $demand = $this->project->demands()->with('role')->whereKey($id)->first();
        if (! $demand) {
            $this->error = 'Nie znaleziono zapotrzebowania.';
            $this->proposal = null;

            return;
        }

        try {
            [$startDate, $endDate] = $this->normalizeRange($start, $end);
            $this->assertWithinProject($startDate, $endDate);
            $this->assertNoOverlapForRole((int) $demand->role_id, $startDate, $endDate, $demand->id);
        } catch (ValidationException $e) {
            $this->error = collect($e->errors())->flatten()->first() ?: 'Nieprawidłowy zakres.';
            $this->proposal = null;

            return;
        }

        $this->draftCount = max(1, (int) $demand->required_count);
        $this->proposal = [
            'mode' => 'move',
            'id' => $demand->id,
            'role_id' => $demand->role_id,
            'label' => $demand->role?->name ?? 'Rola',
            'count' => $this->draftCount,
            'start' => $startDate->toDateString(),
            'end' => $endDate?->toDateString(),
        ];
    }

    /**
     * Rysowanie nowego zakresu na wolnym fragmencie toru profesji.
     */
    public function proposeDraw(
        string $laneKey,
        string $start,
        mixed $end = null,
        mixed $x = null,
        mixed $y = null,
        bool $above = true,
    ): void {
        $this->placeMenu($x, $y, $above);
        $this->selection = null;
        $this->error = null;

        if (! $this->canManage()) {
            $this->error = 'Brak uprawnień do zmiany zapotrzebowania.';
            $this->proposal = null;

            return;
        }

        if (! preg_match('/^role-(\d+)$/', $laneKey, $matches)) {
            $this->error = 'Nieznany tor profesji.';
            $this->proposal = null;

            return;
        }

        $roleId = (int) $matches[1];
        $role = Role::query()->find($roleId);
        if (! $role) {
            $this->error = 'Nie znaleziono profesji.';
            $this->proposal = null;

            return;
        }

        try {
            [$startDate, $endDate] = $this->normalizeRange($start, $end);
            $this->assertWithinProject($startDate, $endDate);
            $this->assertNoOverlapForRole($roleId, $startDate, $endDate);
        } catch (ValidationException $e) {
            $this->error = collect($e->errors())->flatten()->first() ?: 'Nieprawidłowy zakres.';
            $this->proposal = null;

            return;
        }

        $this->draftCount = 1;
        $this->proposal = [
            'mode' => 'create',
            'id' => null,
            'role_id' => $roleId,
            'label' => $role->name,
            'count' => 1,
            'start' => $startDate->toDateString(),
            'end' => $endDate?->toDateString(),
        ];
    }

    public function confirm(): void
    {
        if (! $this->proposal || ! $this->canManage()) {
            return;
        }

        $count = max(1, min(999, (int) $this->draftCount));
        $mode = $this->proposal['mode'] ?? 'move';

        try {
            [$startDate, $endDate] = $this->normalizeRange(
                $this->proposal['start'],
                $this->proposal['end'] ?? null
            );
            $this->assertWithinProject($startDate, $endDate);

            if ($mode === 'create') {
                $roleId = (int) ($this->proposal['role_id'] ?? 0);
                $this->assertNoOverlapForRole($roleId, $startDate, $endDate);
                $this->project->demands()->create([
                    'role_id' => $roleId,
                    'required_count' => $count,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]);
            } else {
                $demand = $this->project->demands()->whereKey($this->proposal['id'])->first();
                if (! $demand) {
                    $this->error = 'Nie znaleziono zapotrzebowania.';

                    return;
                }
                $this->assertNoOverlapForRole((int) $demand->role_id, $startDate, $endDate, $demand->id);
                $demand->update([
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'required_count' => $count,
                ]);
            }
        } catch (ValidationException $e) {
            $this->error = collect($e->errors())->flatten()->first() ?: 'Nieprawidłowy zakres.';

            return;
        }

        $this->cancel();
        $this->project->unsetRelation('demands');
    }

    public function selectBar(int $id, mixed $x = null, mixed $y = null, bool $above = true): void
    {
        $this->placeMenu($x, $y, $above);
        $this->proposal = null;
        $this->error = null;

        if (! $this->canManage()) {
            $this->error = 'Brak uprawnień.';
            $this->selection = null;

            return;
        }

        $demand = $this->project->demands()->with('role')->whereKey($id)->first();
        if (! $demand) {
            $this->selection = null;

            return;
        }

        if ($this->selection && (int) ($this->selection['id'] ?? 0) === $id) {
            $this->cancel();

            return;
        }

        $this->selection = [
            'id' => $demand->id,
            'label' => ($demand->role?->name ?? 'Rola').' · '.$demand->required_count.' os.',
            'start' => $demand->start_date?->toDateString(),
            'end' => $demand->end_date?->toDateString(),
        ];
    }

    public function deleteSelected(): void
    {
        if (! $this->selection || ! $this->canManage()) {
            return;
        }

        $this->project->demands()->whereKey($this->selection['id'])->delete();
        $this->cancel();
        $this->project->unsetRelation('demands');
    }

    public function cancel(): void
    {
        $this->proposal = null;
        $this->selection = null;
        $this->boundProposal = null;
        $this->error = null;
        $this->draftCount = 1;
        $this->menuX = null;
        $this->menuY = null;
    }

    /**
     * Przeciągnięcie markera start/koniec na wykresie agregatu.
     *
     * @param  'start'|'end'  $edge
     */
    public function proposeBound(
        string $edge,
        string $date,
        mixed $x = null,
        mixed $y = null,
        bool $above = true,
    ): void {
        $this->placeMenu($x, $y, $above);
        $this->proposal = null;
        $this->selection = null;
        $this->error = null;
        $this->boundProposal = null;

        if (! $this->canManage()) {
            $this->error = 'Brak uprawnień do zmiany dat projektu.';

            return;
        }

        if (! in_array($edge, ['start', 'end'], true)) {
            $this->error = 'Nieznana krawędź projektu.';

            return;
        }

        $projectStart = $this->project->start_date?->copy()->startOfDay();
        $projectEnd = $this->project->end_date?->copy()->startOfDay();
        if (! $projectStart || ! $projectEnd) {
            $this->error = 'Projekt musi mieć ustawiony start i koniec.';

            return;
        }

        try {
            if ($edge === 'start') {
                $newStart = Carbon::parse($date)->startOfDay()->startOfWeek(Carbon::MONDAY);
                $endCap = $projectEnd->copy()->startOfWeek(Carbon::MONDAY);
                if ($newStart->gte($endCap)) {
                    throw ValidationException::withMessages([
                        'range' => 'Start musi być przed końcem projektu (min. 1 tydzień).',
                    ]);
                }
                $oldMonday = $projectStart->copy()->startOfWeek(Carbon::MONDAY);
                $deltaWeeks = (int) $oldMonday->diffInWeeks($newStart, false);
                if ($deltaWeeks === 0 && $newStart->equalTo($oldMonday)) {
                    return;
                }
                $this->boundProposal = [
                    'edge' => 'start',
                    'date' => $newStart->toDateString(),
                    'old' => $projectStart->toDateString(),
                    'delta_weeks' => $deltaWeeks,
                    'label' => 'Nowy start projektu',
                ];
            } else {
                $newEnd = Carbon::parse($date)->startOfDay()->endOfWeek(Carbon::SUNDAY)->startOfDay();
                $startCap = $projectStart->copy()->startOfWeek(Carbon::MONDAY);
                if ($newEnd->lte($startCap)) {
                    throw ValidationException::withMessages([
                        'range' => 'Koniec musi być po starcie projektu (min. 1 tydzień).',
                    ]);
                }
                $oldSunday = $projectEnd->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();
                if ($newEnd->equalTo($oldSunday)) {
                    return;
                }
                $this->boundProposal = [
                    'edge' => 'end',
                    'date' => $newEnd->toDateString(),
                    'old' => $projectEnd->toDateString(),
                    'delta_weeks' => 0,
                    'label' => 'Nowy koniec projektu',
                ];
            }
        } catch (ValidationException $e) {
            $this->error = collect($e->errors())->flatten()->first() ?: 'Nieprawidłowa data.';
        }
    }

    public function confirmBound(bool $shiftDemands = false): void
    {
        if (! $this->boundProposal || ! $this->canManage()) {
            return;
        }

        $edge = $this->boundProposal['edge'] ?? null;
        $date = $this->boundProposal['date'] ?? null;
        if (! $edge || ! $date) {
            return;
        }

        if ($edge === 'start') {
            $newStart = Carbon::parse($date)->startOfDay();
            $oldStart = $this->project->start_date?->copy()->startOfDay();
            $this->project->update(['start_date' => $newStart]);

            if ($shiftDemands && $oldStart) {
                $deltaWeeks = (int) ($this->boundProposal['delta_weeks'] ?? 0);
                if ($deltaWeeks !== 0) {
                    $this->shiftAllDemandsByWeeks($deltaWeeks);
                }
            }
        } else {
            $this->project->update(['end_date' => Carbon::parse($date)->startOfDay()]);
        }

        $this->project->refresh();
        $this->project->unsetRelation('demands');
        $this->cancel();
    }

    protected function shiftAllDemandsByWeeks(int $deltaWeeks): void
    {
        if ($deltaWeeks === 0) {
            return;
        }

        foreach ($this->project->demands()->get() as $demand) {
            $payload = [];
            if ($demand->start_date) {
                $payload['start_date'] = $demand->start_date->copy()->addWeeks($deltaWeeks);
            }
            if ($demand->end_date) {
                $payload['end_date'] = $demand->end_date->copy()->addWeeks($deltaWeeks);
            }
            if ($payload !== []) {
                $demand->update($payload);
            }
        }
    }

    protected function placeMenu(mixed $x, mixed $y, bool $above): void
    {
        $this->menuX = is_numeric($x) ? (float) $x : null;
        $this->menuY = is_numeric($y) ? (float) $y : null;
        $this->menuAbove = $above;
    }

    /**
     * @return array{0: Carbon, 1: ?Carbon}
     */
    protected function normalizeRange(string $start, mixed $end): array
    {
        $startDate = Carbon::parse($start)->startOfDay()->startOfWeek(Carbon::MONDAY);
        $endDate = $end
            ? Carbon::parse((string) $end)->startOfDay()->endOfWeek(Carbon::SUNDAY)->startOfDay()
            : null;

        if ($endDate && $endDate->lt($startDate)) {
            throw ValidationException::withMessages([
                'range' => 'Data końca nie może być wcześniejsza niż start.',
            ]);
        }

        return [$startDate, $endDate];
    }

    protected function assertWithinProject(Carbon $start, ?Carbon $end): void
    {
        $projectStart = $this->project->start_date?->copy()->startOfDay();
        $projectEnd = $this->project->end_date?->copy()->startOfDay();

        if ($projectStart && $start->lt($projectStart->copy()->startOfWeek(Carbon::MONDAY))) {
            throw ValidationException::withMessages([
                'range' => 'Zapotrzebowanie nie może zaczynać się przed startem projektu.',
            ]);
        }

        if ($projectEnd && $end && $end->gt($projectEnd->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay())) {
            throw ValidationException::withMessages([
                'range' => 'Zapotrzebowanie nie może kończyć się po końcu projektu.',
            ]);
        }
    }

    protected function assertNoOverlapForRole(int $roleId, Carbon $start, ?Carbon $end, ?int $exceptId = null): void
    {
        $siblings = $this->project->demands()
            ->where('role_id', $roleId)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->get(['id', 'start_date', 'end_date']);

        foreach ($siblings as $sibling) {
            $sStart = $sibling->start_date?->copy()->startOfDay();
            $sEnd = $sibling->end_date?->copy()->startOfDay();
            if (! $sStart) {
                continue;
            }

            $aEnd = $end ?? Carbon::parse('9999-12-31');
            $bEnd = $sEnd ?? Carbon::parse('9999-12-31');

            if ($start->lte($bEnd) && $aEnd->gte($sStart)) {
                throw ValidationException::withMessages([
                    'range' => 'Zakres nachodzi na inne zapotrzebowanie tej profesji.',
                ]);
            }
        }
    }

    /**
     * Wolne tygodnie na torze (poniedziałek–niedziela ISO).
     *
     * @param  Collection<int, ProjectDemand>  $demands
     * @return list<array{start: string, end: string}>
     */
    protected function gapsForDemands(Collection $demands, Carbon $axisStart, Carbon $axisEnd): array
    {
        $occupied = $demands
            ->map(function (ProjectDemand $demand) {
                $start = $demand->start_date?->copy()->startOfDay();
                if (! $start) {
                    return null;
                }
                $end = $demand->end_date?->copy()->startOfDay() ?? $start->copy();
                if ($end->lt($start)) {
                    $end = $start->copy();
                }

                return [
                    $start->copy()->startOfWeek(Carbon::MONDAY),
                    $end->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay(),
                ];
            })
            ->filter()
            ->sortBy(fn ($range) => $range[0]->timestamp)
            ->values();

        $cursor = $axisStart->copy();
        $gaps = [];

        foreach ($occupied as [$occStart, $occEnd]) {
            if ($occStart->gt($cursor)) {
                $gapEnd = $occStart->copy()->subDay()->startOfDay();
                if ($gapEnd->gte($cursor)) {
                    $gaps[] = [
                        'start' => $cursor->toDateString(),
                        'end' => $gapEnd->toDateString(),
                    ];
                }
            }
            $next = $occEnd->copy()->addDay()->startOfWeek(Carbon::MONDAY);
            if ($next->gt($cursor)) {
                $cursor = $next;
            }
        }

        if ($cursor->lte($axisEnd)) {
            $gaps[] = [
                'start' => $cursor->toDateString(),
                'end' => $axisEnd->toDateString(),
            ];
        }

        return $gaps;
    }

    /**
     * @return array<string, mixed>
     */
    public function board(): array
    {
        $this->project->loadMissing(['demands.role']);

        $projectStart = $this->project->start_date?->copy()->startOfDay()
            ?? $this->project->demands->min(fn ($d) => $d->start_date)?->copy()->startOfDay()
            ?? now()->startOfWeek(Carbon::MONDAY);

        $projectEnd = $this->project->end_date?->copy()->startOfDay()
            ?? $this->project->demands
                ->map(fn ($d) => $d->end_date ?? $d->start_date)
                ->filter()
                ->max()
                ?->copy()
                ->startOfDay()
            ?? $projectStart->copy()->addWeeks(12);

        if ($projectEnd->lt($projectStart)) {
            $projectEnd = $projectStart->copy();
        }

        $projectWeekStart = $projectStart->copy()->startOfWeek(Carbon::MONDAY);
        $projectWeekEnd = $projectEnd->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();

        // Oś widoku = projekt ± padding (żeby dało się przeciągnąć start/koniec).
        $axisStart = $projectWeekStart->copy()->subWeeks(self::AXIS_PAD_WEEKS);
        $axisEnd = $projectWeekEnd->copy()->addWeeks(self::AXIS_PAD_WEEKS)->endOfWeek(Carbon::SUNDAY)->startOfDay();
        $weekCount = max(1, (int) floor($axisStart->diffInDays($axisEnd) / 7) + 1);

        $todayWeek = now()->startOfWeek(Carbon::MONDAY);
        $ticks = [];
        for ($i = 0; $i < $weekCount; $i++) {
            $week = $axisStart->copy()->addWeeks($i);
            $weekEnd = $week->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();
            $iso = $week->isoWeek();
            $ticks[] = [
                'left' => $i * self::WEEK_WIDTH,
                'number' => $iso,
                'label' => 'T'.$iso,
                'sub' => $week->format('j.m'),
                'title' => 'Tydzień '.$iso.' · '.$week->format('d.m').'–'.$weekEnd->format('d.m.Y'),
                'is_current' => $week->equalTo($todayWeek),
                'in_project' => $week->betweenIncluded($projectWeekStart, $projectWeekEnd),
            ];
        }

        $startLeft = $this->weekOffsetPx($axisStart, $projectStart);
        $endLeft = $this->weekOffsetPx($axisStart, $projectEnd) + self::WEEK_WIDTH - 2;

        // Pending bound drag preview.
        if ($this->boundProposal) {
            $pending = Carbon::parse($this->boundProposal['date']);
            if (($this->boundProposal['edge'] ?? '') === 'start') {
                $startLeft = $this->weekOffsetPx($axisStart, $pending);
            } else {
                $endLeft = $this->weekOffsetPx($axisStart, $pending) + self::WEEK_WIDTH - 2;
            }
        }

        $markers = [];
        $markers[] = [
            'kind' => 'project-start',
            'edge' => 'start',
            'left' => $startLeft,
            'date' => ($this->boundProposal['edge'] ?? null) === 'start'
                ? $this->boundProposal['date']
                : $projectStart->toDateString(),
            'label' => 'Start '.(
                ($this->boundProposal['edge'] ?? null) === 'start'
                    ? Carbon::parse($this->boundProposal['date'])->format('j.m')
                    : $projectStart->format('j.m')
            ),
            'draggable' => $this->canManage(),
        ];
        $markers[] = [
            'kind' => 'project-end',
            'edge' => 'end',
            'left' => $endLeft,
            'date' => ($this->boundProposal['edge'] ?? null) === 'end'
                ? $this->boundProposal['date']
                : $projectEnd->toDateString(),
            'label' => 'Koniec '.(
                ($this->boundProposal['edge'] ?? null) === 'end'
                    ? Carbon::parse($this->boundProposal['date'])->format('j.m')
                    : $projectEnd->format('j.m')
            ),
            'draggable' => $this->canManage(),
        ];

        $today = now()->startOfDay();
        if ($today->betweenIncluded($axisStart, $axisEnd)) {
            $markers[] = [
                'kind' => 'today',
                'edge' => null,
                'left' => $this->weekOffsetPx($axisStart, $today) + (int) round(self::WEEK_WIDTH / 2),
                'date' => $today->toDateString(),
                'label' => 'Dziś',
                'draggable' => false,
            ];
        }

        $canManage = $this->canManage();
        // Paski zapotrzebowania tylko w zakresie projektu (nie w paddingu).
        $minBound = $projectWeekStart->toDateString();
        $maxBound = $projectWeekEnd->toDateString();
        $demandsByRole = $this->project->demands->groupBy('role_id');

        // Tory: profesje z zapotrzebowaniem + puste (do rysowania nowych zakresów).
        $roles = Role::query()->orderBy('name')->get(['id', 'name']);
        if ($roles->isEmpty()) {
            $roles = $demandsByRole
                ->map(fn ($demands, $roleId) => (object) [
                    'id' => (int) $roleId,
                    'name' => $demands->first()?->role?->name ?? ('Rola #'.$roleId),
                ])
                ->values();
        }

        $lanes = $roles->map(function ($role) use ($demandsByRole, $axisStart, $projectWeekStart, $projectWeekEnd, $canManage, $minBound, $maxBound) {
            /** @var Collection<int, ProjectDemand> $demands */
            $demands = $demandsByRole->get($role->id, collect());
            $bars = $demands->values()->map(function (ProjectDemand $demand) use ($axisStart, $minBound, $maxBound) {
                $start = $demand->start_date?->copy()->startOfDay() ?? $axisStart->copy();
                $end = $demand->end_date?->copy()->startOfDay() ?? $start->copy();
                if ($end->lt($start)) {
                    $end = $start->copy();
                }

                $startWeek = $start->copy()->startOfWeek(Carbon::MONDAY);
                $endWeek = $end->copy()->startOfWeek(Carbon::MONDAY);
                $weekStartIdx = max(0, (int) floor($axisStart->diffInDays($startWeek) / 7));
                $weekEndIdx = max($weekStartIdx, (int) floor($axisStart->diffInDays($endWeek) / 7));
                $widthWeeks = max(1, $weekEndIdx - $weekStartIdx + 1);

                return [
                    'id' => $demand->id,
                    'row' => 0,
                    'start' => $startWeek->toDateString(),
                    'end' => $endWeek->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay()->toDateString(),
                    'open' => false,
                    'min' => $minBound,
                    'max' => $maxBound,
                    'left' => $weekStartIdx * self::WEEK_WIDTH,
                    'width' => $widthWeeks * self::WEEK_WIDTH,
                    'label' => $demand->required_count.' os.',
                ];
            })->all();

            $gaps = $canManage
                ? $this->gapsForDemands($demands, $projectWeekStart, $projectWeekEnd)
                : [];

            return [
                'key' => 'role-'.$role->id,
                'role_id' => (int) $role->id,
                'label' => $role->name,
                'rows' => 1,
                'empty' => $bars === [],
                'can_create' => $canManage && $gaps !== [],
                'can_update' => $canManage,
                'can_delete' => $canManage,
                'gaps' => $gaps,
                'bars' => $bars,
            ];
        })
            // Puste tory na dole, zajęte u góry — czytelniej przy wielu profesjach.
            ->sortBy([
                fn ($lane) => count($lane['bars']) === 0 ? 1 : 0,
                fn ($lane) => mb_strtolower($lane['label']),
            ])
            ->values()
            ->all();

        $aggregate = $this->buildAggregate($axisStart, $weekCount, $this->project->demands);

        return [
            'start' => $axisStart->toDateString(),
            'end' => $axisEnd->toDateString(),
            'project_start' => $projectStart->toDateString(),
            'project_end' => $projectEnd->toDateString(),
            'project_start_left' => $startLeft,
            'project_end_left' => $endLeft,
            'week_count' => $weekCount,
            'week_width' => self::WEEK_WIDTH,
            'ticks' => $ticks,
            'markers' => $markers,
            'lanes' => $lanes,
            'aggregate' => $aggregate,
        ];
    }

    /**
     * Suma osób (wszystkie profesje) per tydzień osi.
     *
     * @param  Collection<int, ProjectDemand>  $demands
     * @return array{max: int, columns: list<array{left: int, count: int, height: float, label: string}>}
     */
    protected function buildAggregate(Carbon $axisStart, int $weekCount, Collection $demands): array
    {
        $columns = [];
        $max = 0;

        for ($i = 0; $i < $weekCount; $i++) {
            $weekMonday = $axisStart->copy()->addWeeks($i);
            $weekSunday = $weekMonday->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();
            $count = 0;

            foreach ($demands as $demand) {
                $start = $demand->start_date?->copy()->startOfDay();
                if (! $start) {
                    continue;
                }
                $end = $demand->end_date?->copy()->startOfDay() ?? $start->copy();
                if ($end->lt($start)) {
                    $end = $start->copy();
                }
                if ($start->lte($weekSunday) && $end->gte($weekMonday)) {
                    $count += (int) $demand->required_count;
                }
            }

            $max = max($max, $count);
            $iso = $weekMonday->isoWeek();
            $columns[] = [
                'left' => $i * self::WEEK_WIDTH,
                'count' => $count,
                'height' => 0.0,
                'label' => 'T'.$iso.' · '.$weekMonday->format('d.m').'–'.$weekSunday->format('d.m'),
                'week' => 'T'.$iso,
            ];
        }

        foreach ($columns as &$column) {
            $column['height'] = $max > 0
                ? max($column['count'] > 0 ? 12.0 : 0.0, ($column['count'] / $max) * 100)
                : 0.0;
        }
        unset($column);

        return [
            'max' => $max,
            'columns' => $columns,
        ];
    }

    protected function weekOffsetPx(Carbon $axisStart, Carbon $date): int
    {
        $week = $date->copy()->startOfWeek(Carbon::MONDAY);
        $idx = max(0, (int) floor($axisStart->diffInDays($week) / 7));

        return $idx * self::WEEK_WIDTH;
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

        $start = Carbon::parse($this->proposal['start'])->startOfWeek(Carbon::MONDAY);
        $end = Carbon::parse($this->proposal['end'] ?? $this->proposal['start'])->startOfWeek(Carbon::MONDAY);
        $axisStart = Carbon::parse($board['start']);
        $weekStartIdx = max(0, (int) floor($axisStart->diffInDays($start) / 7));
        $weekEndIdx = max($weekStartIdx, (int) floor($axisStart->diffInDays($end) / 7));
        $left = $weekStartIdx * self::WEEK_WIDTH;
        $width = max(1, $weekEndIdx - $weekStartIdx + 1) * self::WEEK_WIDTH;
        $endIso = $end->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay()->toDateString();
        $count = max(1, (int) $this->draftCount);
        $mode = $this->proposal['mode'] ?? 'move';
        $roleId = (int) ($this->proposal['role_id'] ?? 0);

        foreach ($board['lanes'] as &$lane) {
            if ($mode === 'create' && (int) $lane['role_id'] === $roleId) {
                $lane['bars'][] = [
                    'id' => 'pending',
                    'row' => 0,
                    'start' => $start->toDateString(),
                    'end' => $endIso,
                    'open' => false,
                    'min' => $board['start'],
                    'max' => $board['end'],
                    'left' => $left,
                    'width' => $width,
                    'label' => $count.' os.',
                    'pending' => true,
                ];
                break;
            }

            if ($mode !== 'create') {
                foreach ($lane['bars'] as &$bar) {
                    if ((int) $bar['id'] === (int) $this->proposal['id']) {
                        $bar['start'] = $start->toDateString();
                        $bar['end'] = $endIso;
                        $bar['left'] = $left;
                        $bar['width'] = $width;
                        $bar['label'] = $count.' os.';
                        $bar['pending'] = true;
                    }
                }
                unset($bar);
            }
        }
        unset($lane);

        return $board;
    }

    public function render()
    {
        return view('livewire.project-demand-timeline', [
            'board' => $this->paintProposal($this->board()),
            'canManage' => $this->canManage(),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
