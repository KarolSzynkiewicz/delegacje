@props([
    'href' => null,
    'mainClick' => null,
    'mainTip' => null,
    'side' => 'none',
    'sideClick' => null,
    'sideHref' => null,
    'sideTip' => null,
    'openMenu' => null,
    'excludeClick' => null,
    'excludeTip' => null,
    'static' => false,
])

@php
    $href = is_string($href) && $href !== '' ? $href : null;
    $mainClick = is_string($mainClick) && $mainClick !== '' ? $mainClick : null;
    $sideClick = is_string($sideClick) && $sideClick !== '' ? $sideClick : null;
    $sideHref = is_string($sideHref) && $sideHref !== '' ? $sideHref : null;
    $openMenu = is_string($openMenu) && $openMenu !== '' ? $openMenu : null;
    $excludeClick = is_string($excludeClick) && $excludeClick !== '' ? $excludeClick : null;
    $side = in_array($side, ['down', 'edit', 'go'], true) ? $side : 'none';
    if ($openMenu && $side === 'none') {
        $side = 'down';
    }
    $hasSide = $side !== 'none' && ($href || $mainClick || $sideClick || $sideHref || $openMenu);
    $hasExclude = $excludeClick !== null;
    $compound = $hasSide || $hasExclude;
    $sideIcon = match ($side) {
        'edit' => 'pencil',
        'go' => 'chevron-right',
        default => 'chevron-down',
    };
    $classes = trim(($compound ? 'tg-col-chip--split' : '').($hasExclude ? ' tg-col-chip--has-exclude' : ''));
    $mainTip = $mainTip ?: null;
    $sideTip = $sideTip ?: null;
    $excludeTip = $excludeTip ?: 'Odfiltruj';
@endphp

@if($static)
    <span {{ $attributes->class($classes) }}>
        @if($compound)
            <span class="tg-col-chip__main">{{ $slot }}</span>
        @else
            {{ $slot }}
        @endif
    </span>
@elseif($compound)
    <div {{ $attributes->except(['wire:click', 'wire:click.stop'])->class($classes) }}>
        @if($hasExclude)
            <button
                type="button"
                class="tg-col-chip__exclude"
                wire:click.stop="{!! $excludeClick !!}"
                data-tip="{{ $excludeTip }}"
                aria-label="{{ $excludeTip }}"
            >
                <i class="bi bi-x" aria-hidden="true"></i>
            </button>
        @endif
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
        @if($hasSide)
            @if($sideHref && ! $sideClick && ! $openMenu)
                <a
                    href="{{ $sideHref }}"
                    class="tg-col-chip__side"
                    @click.stop
                    @if($sideTip) data-tip="{{ $sideTip }}" aria-label="{{ $sideTip }}" @endif
                >
                    <i class="bi bi-{{ $sideIcon }}" aria-hidden="true"></i>
                </a>
            @else
                <button
                    type="button"
                    class="tg-col-chip__side"
                    @if($openMenu) @click.stop="{{ $openMenu }}"
                    @elseif($sideClick) wire:click.stop="{!! $sideClick !!}" @endif
                    @if($sideTip) data-tip="{{ $sideTip }}" aria-label="{{ $sideTip }}" @endif
                >
                    <i class="bi bi-{{ $sideIcon }}" aria-hidden="true"></i>
                </button>
            @endif
        @endif
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
