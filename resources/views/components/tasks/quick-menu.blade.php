@props([
    'disabled' => false,
    'minWidth' => 200,
    'href' => null,
    'openLabel' => 'Otwórz',
    'changeLabel' => 'Zmień',
])

@php
    $minWidth = (int) $minWidth;
    $href = is_string($href) && $href !== '' ? $href : null;
    $openJs = 'if (open) { open = false; return } const r = $el.getBoundingClientRect(); const w = Math.min('.$minWidth.', window.innerWidth - 16); top = r.bottom + 4; left = Math.max(8, Math.min(r.left, window.innerWidth - w - 8)); open = true';
@endphp

@if($disabled)
    <span {{ $attributes }}>
        {{ $trigger }}
    </span>
@else
    <div class="tg-quick-menu" x-data="{ open: false, top: 0, left: 0 }">
        @if($href)
            <div {{ $attributes->class('tg-col-chip--split') }}>
                <a href="{{ $href }}" class="tg-col-chip__main" data-tip="{{ $openLabel }}" aria-label="{{ $openLabel }}">
                    {{ $trigger }}
                </a>
                <button
                    type="button"
                    class="tg-col-chip__side"
                    @click.stop="{{ $openJs }}"
                    data-tip="{{ $changeLabel }}"
                    aria-label="{{ $changeLabel }}"
                >
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </button>
            </div>
        @else
            <button
                type="button"
                {{ $attributes->class('tg-col-chip--split') }}
                @click.stop="{{ $openJs }}"
            >
                <span class="tg-col-chip__main">{{ $trigger }}</span>
                <span class="tg-col-chip__side" aria-hidden="true">
                    <i class="bi bi-chevron-down"></i>
                </span>
            </button>
        @endif
        <template x-teleport="body">
            <ul
                x-show="open"
                x-cloak
                @click.outside="open = false"
                :style="`position:fixed;top:${top}px;left:${left}px;z-index:999990;min-width:{{ $minWidth }}px;max-height:16rem;overflow:auto;font-size:0.84rem`"
                class="dropdown-menu show py-1 shadow-lg tg-teleport-menu"
            >
                {{ $menu }}
            </ul>
        </template>
    </div>
@endif
