@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\ProjectDemand> $demands */
    $demands = $demands ?? collect();
    $project = $project ?? null;

    $axisStart = $demands
        ->map(fn ($d) => $d->start_date?->copy()->startOfDay())
        ->filter()
        ->min();
    $axisEnd = $demands
        ->map(fn ($d) => ($d->end_date ?? $d->start_date)?->copy()->startOfDay())
        ->filter()
        ->max();

    if (! $axisStart && $project?->start_date) {
        $axisStart = $project->start_date->copy()->startOfDay();
    }
    if (! $axisEnd && $project?->end_date) {
        $axisEnd = $project->end_date->copy()->startOfDay();
    }
    if ($axisStart && ! $axisEnd) {
        $axisEnd = $axisStart->copy()->addDays(30);
    }
    if ($axisStart && $axisEnd && $axisEnd->lt($axisStart)) {
        $axisEnd = $axisStart->copy();
    }

    $totalDays = $axisStart && $axisEnd
        ? max(1, $axisStart->diffInDays($axisEnd) + 1)
        : 1;

    $tracks = $demands
        ->groupBy('role_id')
        ->map(function ($roleDemands) use ($axisStart, $totalDays) {
            $role = $roleDemands->first()?->role;
            $bars = $roleDemands->map(function ($demand) use ($axisStart, $totalDays) {
                $start = $demand->start_date?->copy()->startOfDay() ?? $axisStart;
                $end = ($demand->end_date ?? $demand->start_date)?->copy()->startOfDay() ?? $start;
                if ($end->lt($start)) {
                    $end = $start->copy();
                }
                $offset = max(0, $axisStart->diffInDays($start));
                if ($start->lt($axisStart)) {
                    $offset = 0;
                }
                $span = max(1, $start->diffInDays($end) + 1);

                return [
                    'left' => min(100, ($offset / $totalDays) * 100),
                    'width' => min(100 - min(100, ($offset / $totalDays) * 100), max(2.5, ($span / $totalDays) * 100)),
                    'count' => (int) $demand->required_count,
                    'label' => $start->format('d.m').'–'.($demand->end_date ? $end->format('d.m') : '…'),
                ];
            })->values();

            return [
                'role' => $role?->name ?? 'Bez profesji',
                'total' => (int) $roleDemands->sum('required_count'),
                'bars' => $bars,
            ];
        })
        ->sortBy('role')
        ->values();
@endphp

@if($tracks->isEmpty())
    <div class="pda-empty">
        <div class="pda-empty__ghost" aria-hidden="true">
            <div class="pda-empty__row">
                <div class="pda-empty__lab"></div>
                <div class="pda-empty__lane"><div class="pda-empty__bar" style="left:12%;width:38%"></div></div>
            </div>
            <div class="pda-empty__row">
                <div class="pda-empty__lab"></div>
                <div class="pda-empty__lane"><div class="pda-empty__bar" style="left:28%;width:48%"></div></div>
            </div>
            <div class="pda-empty__row">
                <div class="pda-empty__lab"></div>
                <div class="pda-empty__lane"><div class="pda-empty__bar" style="left:8%;width:55%"></div></div>
            </div>
        </div>
        <div class="small">Brak zapotrzebowania — dodaj tory per profesja.</div>
    </div>
@else
    <div class="pda">
        <div class="pda__meta small text-muted">
            <span>
                Oś
                <span class="font-mono text-light">{{ $axisStart?->format('d.m.Y') ?? '—' }}</span>
                –
                <span class="font-mono text-light">{{ $axisEnd?->format('d.m.Y') ?? '—' }}</span>
            </span>
            <span class="font-mono">{{ $tracks->count() }} profesji</span>
        </div>

        <div class="pda__tracks">
            @foreach($tracks as $track)
                <div class="pda__track">
                    <div class="pda__label">
                        <div class="pda__role">{{ $track['role'] }}</div>
                        <div class="pda__total">{{ $track['total'] }} os.</div>
                    </div>
                    <div class="pda__lane">
                        @foreach($track['bars'] as $bar)
                            <div
                                class="pda__bar"
                                style="left: {{ number_format($bar['left'], 2, '.', '') }}%; width: {{ number_format($bar['width'], 2, '.', '') }}%;"
                                title="{{ $bar['label'] }} · {{ $bar['count'] }} os."
                            >
                                <span class="pda__bar-count">{{ $bar['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
