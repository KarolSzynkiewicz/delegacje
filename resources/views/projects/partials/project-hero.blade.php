@php
    $start = $project->start_date?->copy()?->startOfDay();
    $end = $project->end_date?->copy()?->startOfDay();
    $today = now()->startOfDay();

    $totalDays = ($start && $end) ? ($start->diffInDays($end) + 1) : null;
    $effectiveEnd = $end ? $end->min($today) : $today;
    $elapsedDays = $start ? max(0, $start->diffInDays($effectiveEnd) + ($today->gte($start) ? 1 : 0)) : 0;

    if ($totalDays !== null) {
        $elapsedDays = min($elapsedDays, $totalDays);
        $progressPct = $totalDays > 0 ? (int) round(($elapsedDays / $totalDays) * 100) : 0;
        $progressPct = max(0, min(100, $progressPct));
    } else {
        $progressPct = null;
    }

    $statusValue = $project->status instanceof \App\Enums\ProjectStatus
        ? $project->status->value
        : (string) $project->status;
    $badgeVariant = match ($statusValue) {
        'active' => 'success',
        'on_hold' => 'warning',
        'completed' => 'info',
        'cancelled' => 'danger',
        default => 'info',
    };
@endphp

<div class="pj-hero cl-reveal">
    <div class="pj-hero__top">
        <div class="min-w-0">
            <div class="pj-hero__kicker">Kartoteka projektu</div>
            <h3 class="pj-hero__title">{{ $project->name }}</h3>
            <div class="pj-hero__chips">
                <span class="pj-chip pj-chip--strong">
                    <x-ui.badge variant="{{ $badgeVariant }}">{{ ucfirst($statusValue) }}</x-ui.badge>
                </span>
                @if($project->type)
                    <span class="pj-chip">
                        <i class="bi bi-tag"></i> {{ $project->type->label() }}
                    </span>
                @endif
                @if($project->client || $project->client_name)
                    <span class="pj-chip">
                        <i class="bi bi-building"></i>
                        @if($project->client)
                            <a href="{{ route('clients.show', $project->client) }}">{{ $project->client->displayName() }}</a>
                        @else
                            {{ $project->client_name }}
                        @endif
                    </span>
                @endif
                @if($project->clientContact)
                    <span class="pj-chip">
                        <i class="bi bi-person-lines-fill"></i>
                        {{ $project->clientContact->name }}
                    </span>
                @endif
                @if($project->location)
                    <span class="pj-chip">
                        <i class="bi bi-geo-alt"></i> {{ $project->location->name }}
                    </span>
                @endif
            </div>
        </div>

        <div class="pj-hero__stats">
            <div class="pj-stat">
                <div class="pj-stat__label">Start</div>
                <div class="pj-stat__value font-mono">{{ $start ? $start->format('d.m.Y') : '—' }}</div>
            </div>
            <div class="pj-stat">
                <div class="pj-stat__label">Koniec</div>
                <div class="pj-stat__value font-mono">{{ $end ? $end->format('d.m.Y') : '—' }}</div>
            </div>
            <div class="pj-stat">
                <div class="pj-stat__label">Postęp</div>
                <div class="pj-stat__value font-mono">
                    @if($totalDays !== null)
                        {{ $progressPct }}%
                    @elseif($start)
                        dzień {{ $elapsedDays }}
                    @else
                        —
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="pj-progress">
        <div class="pj-progress__meta">
            <span>Harmonogram</span>
            <span>
                @if($totalDays !== null)
                    <strong>{{ $elapsedDays }}</strong> / {{ $totalDays }} dni
                @elseif($start)
                    <strong>{{ $elapsedDays }}</strong> dni od startu · bez daty końca
                @else
                    brak dat
                @endif
            </span>
        </div>
        <div class="pj-progress__track" role="progressbar" aria-label="Postęp projektu" @if($progressPct !== null) aria-valuenow="{{ $progressPct }}" aria-valuemin="0" aria-valuemax="100" @endif>
            @if($totalDays !== null)
                <div class="pj-progress__fill" style="width: {{ $progressPct }}%"></div>
            @else
                <div class="pj-progress__fill pj-progress__fill--open"></div>
            @endif
        </div>
    </div>
</div>
