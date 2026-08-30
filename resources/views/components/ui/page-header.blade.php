@props([
    'title' => '',
    'phone' => false,
])

@php
    $hasSlot = isset($slot) && trim($slot);
    $hasLeft = isset($left);
    $hasRight = isset($right) || $hasSlot;
    $hasActions = $hasLeft || $hasRight;
@endphp

{{--
  Desktop: tytuł na górze, akcje pod spodem.
  Mobile / phone: tytuł + hamburger po prawej; opcje z przycisków
  rozwijają się pod spodem (drugie menu, obok hamburgera navbaru).
--}}
<div
    @class([
        'ui-page-header',
        'ui-page-header--has-left' => $hasLeft,
        'ui-page-header--has-right' => $hasRight,
        'ui-page-header--phone' => $phone,
    ])
    @if($hasActions)
        x-data="{ optsOpen: false }"
        @keydown.escape.window="optsOpen = false"
        @click.outside="optsOpen = false"
    @endif
>
    <div class="ui-page-header__bar">
        <div class="ui-page-header__title">
            <h2 class="ui-page-header__heading mb-0">{{ $title }}</h2>
        </div>

        @if($hasActions)
            <button
                type="button"
                class="ui-page-header__menu-btn"
                @click="optsOpen = !optsOpen"
                :aria-expanded="optsOpen"
                aria-label="Opcje strony"
            >
                <span class="navbar-toggler-icon"></span>
            </button>
        @endif
    </div>

    @if($hasActions)
        <div
            class="ui-page-header__actions"
            :class="{ 'is-open': optsOpen }"
        >
            @isset($left)
                <div class="ui-page-header__left">{{ $left }}</div>
            @endisset

            @if($hasRight)
                <div class="ui-page-header__right">
                    @isset($right)
                        {{ $right }}
                    @endisset
                    @if($hasSlot)
                        {{ $slot }}
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>

{{-- Vite bywa wyłączony — hamburger i panel muszą działać bez rebuildu. --}}
<style>
    .ui-page-header__menu-btn { display: none; }
    .ui-page-header--phone .ui-page-header__menu-btn { display: inline-flex; }
    .ui-page-header--phone .ui-page-header__actions { display: none; }
    .ui-page-header--phone .ui-page-header__actions.is-open { display: flex; }
    @media (max-width: 767.98px) {
        .ui-page-header__menu-btn { display: inline-flex; }
        .ui-page-header__actions { display: none; }
        .ui-page-header__actions.is-open { display: flex; }
    }
</style>
