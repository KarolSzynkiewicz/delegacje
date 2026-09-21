@props([
    'href' => null,
    'mainClick' => null,
    'mainTip' => null,
    'side' => 'none',
    'sideClick' => null,
    'sideTip' => null,
    'openMenu' => null,
    'static' => false,
])

@php
    $href = is_string($href) && $href !== '' ? $href : null;
    $mainClick = is_string($mainClick) && $mainClick !== '' ? $mainClick : null;
    $sideClick = is_string($sideClick) && $sideClick !== '' ? $sideClick : null;
    $openMenu = is_string($openMenu) && $openMenu !== '' ? $openMenu : null;
    $side = in_array($side, ['down', 'edit'], true) ? $side : 'none';
    if ($openMenu && $side === 'none') {
        $side = 'down';
    }
    $split = $side !== 'none' && ($href || $mainClick || $sideClick || $openMenu);
    $sideIcon = $side === 'edit' ? 'pencil' : 'chevron-down';
    $classes = $split ? 'tg-col-chip--split' : '';
    $mainTip = $mainTip ?: null;
    $sideTip = $sideTip ?: null;
@endphp

@if($static)
    <span {{ $attributes->class($classes) }}>
        @if($split)
            <span class="tg-col-chip__main">{{ $slot }}</span>
        @else
            {{ $slot }}
        @endif
    </span>
@elseif($split)
    <div {{ $attributes->except(['wire:click', 'wire:click.stop'])->class($classes) }}>
        @if($href)
            <a href="{{ $href }}" class="tg-col-chip__main" @if($mainTip) data-tip="{{ $mainTip }}" aria-label="{{ $mainTip }}" @endif>
                {{ $slot }}
            </a>
        @elseif($mainClick)
            <button type="button" class="tg-col-chip__main" wire:click.stop="{!! $mainClick !!}" @if($mainTip) data-tip="{{ $mainTip }}" aria-label="{{ $mainTip }}" @endif>
                {{ $slot }}
            </button>
        @else
            <span class="tg-col-chip__main">{{ $slot }}</span>
        @endif
        <button
            type="button"
            class="tg-col-chip__side"
            @if($openMenu) @click.stop="{{ $openMenu }}"
            @elseif($sideClick) wire:click.stop="{!! $sideClick !!}" @endif
            @if($sideTip) data-tip="{{ $sideTip }}" aria-label="{{ $sideTip }}" @endif
        >
            <i class="bi bi-{{ $sideIcon }}" aria-hidden="true"></i>
        </button>
    </div>
@elseif($href)
    <a href="{{ $href }}" {{ $attributes->except(['wire:click', 'wire:click.stop']) }} @if($mainTip) data-tip="{{ $mainTip }}" @endif>
        {{ $slot }}
    </a>
@elseif($mainClick)
    <button type="button" {{ $attributes->except(['wire:click', 'wire:click.stop']) }} wire:click.stop="{!! $mainClick !!}" @if($mainTip) data-tip="{{ $mainTip }}" aria-label="{{ $mainTip }}" @endif>
        {{ $slot }}
    </button>
@else
    <span {{ $attributes->except(['wire:click', 'wire:click.stop']) }}>
        {{ $slot }}
    </span>
@endif
