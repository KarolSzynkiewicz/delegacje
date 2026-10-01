@php
    $state = $cell['state'] ?? 'empty';
    $before = $cell['before'] ?? null;
    $after = $cell['after'] ?? null;
    $current = $cell['current'] ?? null;
    $emptyLabel = $emptyLabel ?? '—';
@endphp

@if($state === 'changed')
    <div class="transfer-assignment-cell transfer-assignment-cell--changed">
        <div>
            <span class="badge bg-success me-1">✓</span>
            <span
                class="transfer-change-hotspot"
                x-data="{ open: false }"
                @keydown.escape.window="open = false"
                @click.outside="open = false"
            >
                <button
                    type="button"
                    class="badge transfer-change-pill"
                    @click.stop="open = !open"
                    :aria-expanded="open"
                    title="Pokaż poprzednie przypisanie"
                >
                    zmiana
                </button>
                <div
                    class="transfer-change-popover"
                    x-show="open"
                    x-cloak
                    x-transition.opacity.duration.150ms
                    @click.stop
                    role="dialog"
                    aria-label="Poprzednie przypisanie"
                >
                    <div class="transfer-change-popover__label">Było</div>
                    @if($before)
                        @if(! empty($before['url']))
                            <a href="{{ $before['url'] }}" class="transfer-change-popover__title text-decoration-none">{{ $before['title'] }}</a>
                        @else
                            <div class="transfer-change-popover__title">{{ $before['title'] }}</div>
                        @endif
                        @if(! empty($before['subtitle']))
                            <div class="transfer-change-popover__meta">{{ $before['subtitle'] }}</div>
                        @endif
                        <div class="transfer-change-popover__meta font-mono">{{ $before['dates'] }}</div>
                    @else
                        <div class="transfer-change-popover__title text-muted">—</div>
                    @endif
                </div>
            </span>
            @if($after)
                @if(! empty($after['url']))
                    <a href="{{ $after['url'] }}" class="text-decoration-none fw-semibold">{{ $after['title'] }}</a>
                @else
                    <span class="fw-semibold">{{ $after['title'] }}</span>
                @endif
            @else
                <span class="text-muted">—</span>
            @endif
        </div>
        @if($after)
            @if(! empty($after['subtitle']))
                <small class="text-muted d-block mt-1">{{ $after['subtitle'] }}</small>
            @endif
            <small class="text-muted d-block mt-1 font-mono">{{ $after['dates'] }}</small>
        @endif
    </div>
@elseif(in_array($state, ['unchanged', 'plain'], true) && $current)
    <div @class([
        'transfer-assignment-cell',
        'transfer-assignment-cell--unchanged' => $state === 'unchanged',
    ])>
        <div>
            <span class="badge bg-success me-1">✓</span>
            @if($state === 'unchanged')
                <span class="badge transfer-keep-pill me-1">bez zmian</span>
            @endif
            @if(! empty($current['url']))
                <a href="{{ $current['url'] }}" class="text-decoration-none">{{ $current['title'] }}</a>
            @else
                <span class="fw-semibold">{{ $current['title'] }}</span>
            @endif
        </div>
        @if(! empty($current['subtitle']))
            <small class="text-muted d-block mt-1">{{ $current['subtitle'] }}</small>
        @endif
        <small class="text-muted d-block mt-1 font-mono">{{ $current['dates'] }}</small>
    </div>
@else
    <span class="text-muted">
        @if($emptyLabel === 'Nie przypisany')
            <i class="bi bi-dash-circle"></i>
        @endif
        {{ $emptyLabel }}
    </span>
@endif
