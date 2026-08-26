@props([
    'entries',
    'intro',
    'empty',
    'keyPrefix' => 'act',
])

@php
    $tone = [
        'primary' => 'var(--primary, #60a5fa)',
        'success' => '#4ade80',
        'warning' => '#fbbf24',
        'danger' => '#f87171',
        'info' => '#38bdf8',
        'muted' => 'rgba(148, 163, 184, .85)',
    ];
@endphp

<x-ui.card label="Historia">
    <p class="small text-muted mb-3">{{ $intro }}</p>

    @forelse($entries as $entry)
        @php $color = $tone[$entry['tone']] ?? $tone['muted']; @endphp
        <div
            wire:key="{{ $keyPrefix }}-{{ $entry['kind'] }}-{{ $entry['at']->timestamp }}-{{ $loop->index }}"
            class="d-flex align-items-start gap-3 py-3"
            style="border-bottom:1px solid var(--glass-border, rgba(148,163,184,.18));"
        >
            <span
                class="d-inline-flex align-items-center justify-content-center flex-shrink-0"
                style="width:32px;height:32px;border-radius:999px;background:color-mix(in srgb, {{ $color }} 18%, transparent);color:{{ $color }};margin-top:.1rem;"
            >
                <i class="bi bi-{{ $entry['icon'] }}" aria-hidden="true"></i>
            </span>
            <div class="flex-grow-1" style="min-width:0;">
                <div>
                    <span class="fw-semibold">{{ $entry['actor'] }}</span>
                    <span>{{ $entry['verb'] }}</span>
                    @if($entry['subject'])
                        @if($entry['url'])
                            <a href="{{ $entry['url'] }}" class="fw-semibold text-decoration-none">{{ $entry['subject'] }}</a>
                        @else
                            <span class="fw-semibold">{{ $entry['subject'] }}</span>
                        @endif
                    @endif
                </div>
                @if($entry['detail'])
                    <div class="small text-muted mt-1" style="white-space:pre-wrap;">{{ $entry['detail'] }}</div>
                @endif
            </div>
            <time
                class="small text-muted flex-shrink-0"
                datetime="{{ $entry['at']->toIso8601String() }}"
                title="{{ $entry['at']->format('d.m.Y H:i:s') }}"
            >
                {{ $entry['at']->format('d.m.Y H:i') }}
            </time>
        </div>
    @empty
        <p class="text-muted mb-0">{{ $empty }}</p>
    @endforelse
</x-ui.card>
