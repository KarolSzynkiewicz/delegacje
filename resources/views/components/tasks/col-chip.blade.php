@props([
    'variant' => 'category',
    'href' => null,
    'icon' => null,
    'tone' => null,
    'side' => 'none',
    'sideTaskId' => null,
    'sideTip' => null,
    'sideLabel' => null,
    'filterCategory' => null,
    'editCategory' => false,
])

@php
    $icon = $icon ?? match ($variant) {
        'sprint' => 'flag',
        'priority' => 'fire',
        default => 'tag',
    };
    $side = in_array($side, ['down', 'edit'], true) ? $side : 'none';
    $filterCategory = is_string($filterCategory) && trim($filterCategory) !== '' ? $filterCategory : null;
    $split = $side === 'edit' && $sideTaskId;
    $classes = 'tg-col-chip tg-col-chip--'.$variant;
    if (is_string($tone) && $tone !== '') {
        $classes .= ' tg-col-chip--'.$tone;
    }
    if ($split) {
        $classes .= ' tg-col-chip--split';
    }
    $tip = $attributes->get('data-tip') ?? $attributes->get('title');
    $attributes = $attributes->except(['title', 'data-tip', 'wire:click', 'wire:click.stop']);
    $sideIcon = $side === 'edit' ? 'pencil' : 'chevron-down';
@endphp

@if($split)
    <div {{ $attributes->class($classes) }}>
        @if($filterCategory)
            <button
                type="button"
                class="tg-col-chip__main"
                wire:click.stop="filterByCategory({{ \Illuminate\Support\Js::from($filterCategory) }})"
                data-tip="Pokaż tę kategorię"
                aria-label="Pokaż tę kategorię"
            >
                <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
                <span class="tg-col-chip__label">{{ $slot }}</span>
            </button>
        @elseif($editCategory)
            <button
                type="button"
                class="tg-col-chip__main"
                wire:click.stop="startEdit({{ (int) $sideTaskId }}, 'category')"
                data-tip="Wpisz kategorię"
                aria-label="Wpisz kategorię"
            >
                <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
                <span class="tg-col-chip__label">{{ $slot }}</span>
            </button>
        @else
            <span class="tg-col-chip__main">
                <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
                <span class="tg-col-chip__label">{{ $slot }}</span>
            </span>
        @endif
        <button
            type="button"
            class="tg-col-chip__side"
            wire:click.stop="startEdit({{ (int) $sideTaskId }}, 'category')"
            @if(filled($sideTip)) data-tip="{{ $sideTip }}" @endif
            aria-label="{{ $sideLabel ?: $sideTip ?: 'Edytuj' }}"
        >
            <i class="bi bi-{{ $sideIcon }}" aria-hidden="true"></i>
        </button>
    </div>
@elseif($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }} @if(filled($tip)) data-tip="{{ $tip }}" @endif>
        <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
        <span class="tg-col-chip__label">{{ $slot }}</span>
    </a>
@elseif($filterCategory)
    <button
        type="button"
        {{ $attributes->class($classes) }}
        wire:click.stop="filterByCategory({{ \Illuminate\Support\Js::from($filterCategory) }})"
        data-tip="Pokaż tę kategorię"
        aria-label="Pokaż tę kategorię"
    >
        <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
        <span class="tg-col-chip__label">{{ $slot }}</span>
    </button>
@else
    <button type="button" {{ $attributes->class($classes) }} @if(filled($tip)) data-tip="{{ $tip }}" @endif>
        <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
        <span class="tg-col-chip__label">{{ $slot }}</span>
    </button>
@endif
