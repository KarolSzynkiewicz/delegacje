@props([
    'variant' => 'category',
    'href' => null,
    'icon' => null,
    'tone' => null,
    'side' => 'none',
    'sideClick' => null,
    'sideTip' => null,
    'mainClick' => null,
    'mainTip' => null,
    'excludeClick' => null,
    'excludeTip' => null,
    'static' => false,
])

@php
    $icon = $icon ?? match ($variant) {
        'sprint' => 'flag',
        'priority' => 'fire',
        'assignee' => 'person',
        default => 'tag',
    };
    $classes = 'tg-col-chip tg-col-chip--'.$variant;
    if (is_string($tone) && $tone !== '') {
        $classes .= ' tg-col-chip--'.$tone;
    }
@endphp

<x-tasks.chip
    {{ $attributes->class($classes) }}
    :href="$href"
    :main-click="$mainClick"
    :main-tip="$mainTip"
    :side="$side"
    :side-click="$sideClick"
    :side-tip="$sideTip"
    :exclude-click="$excludeClick"
    :exclude-tip="$excludeTip"
    :static="$static"
>
    <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
    <span class="tg-col-chip__label">{{ $slot }}</span>
</x-tasks.chip>
