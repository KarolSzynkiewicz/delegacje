@props([
    'variant' => 'category',
    'href' => null,
    'icon' => null,
    'tone' => null,
])

@php
    $icon = $icon ?? match ($variant) {
        'sprint' => 'flag',
        'priority' => 'fire',
        default => 'tag',
    };
    $classes = 'tg-col-chip tg-col-chip--'.$variant;
    if (is_string($tone) && $tone !== '') {
        $classes .= ' tg-col-chip--'.$tone;
    }
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
        <span class="tg-col-chip__label">{{ $slot }}</span>
        <i class="bi bi-chevron-right tg-col-chip__go" aria-hidden="true"></i>
    </a>
@else
    <button type="button" {{ $attributes->class($classes) }}>
        <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
        <span class="tg-col-chip__label">{{ $slot }}</span>
        <i class="bi bi-chevron-right tg-col-chip__go" aria-hidden="true"></i>
    </button>
@endif
