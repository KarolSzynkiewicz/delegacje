@props([
    'ctaLabel' => 'Zaloguj się',
    'ctaHref' => null,
])
@php
    $ctaHref = $ctaHref ?? route('login');
@endphp
<nav class="cl-landing-nav">
    <div class="cl-landing-wrap cl-landing-nav__inner">
        <a href="{{ route('home') }}" class="cl-landing-logo">
            <x-application-logo />
            <span class="navbar-brand-name">Chrono<span class="navbar-brand-name__accent">Logic</span></span>
        </a>
        <div class="cl-landing-nav__links">
            @if ($slot->isNotEmpty())
                <div class="cl-landing-nav__menu">{{ $slot }}</div>
            @endif
            @if ($ctaLabel)
                <a href="{{ $ctaHref }}" class="btn btn-primary">{{ $ctaLabel }}</a>
            @endif
        </div>
    </div>
</nav>
