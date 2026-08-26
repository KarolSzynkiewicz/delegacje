@props([
    'variant' => 'dial', // dial | aperture | bot | monogram | pulse | timer
    'size' => 40,
])

@php
    $variants = ['dial', 'aperture', 'bot', 'monogram', 'pulse', 'timer'];
    $variant = in_array($variant, $variants, true) ? $variant : 'dial';

    // Unikalny id gradientu: nav i stopka renderują znak dwa razy na jednej stronie.
    $grad = 'clm-'.substr(bin2hex(random_bytes(4)), 0, 8);

    // Oś obrotu wskazówek — warianty z przesuniętą tarczą mają własny środek.
    $pivot = match ($variant) {
        'bot' => '20px 24.5px',
        'timer' => '20px 21.5px',
        default => '20px 20px',
    };
@endphp

<svg
    {{ $attributes->merge([
        'class' => 'cl-mark cl-mark--'.$variant,
        'style' => '--cl-mark-pivot: '.$pivot.';',
    ]) }}
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 40 40"
    width="{{ (int) $size }}"
    height="{{ (int) $size }}"
    role="img"
    aria-label="ChronoLogic"
>
    <defs>
        <linearGradient id="{{ $grad }}" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#3b82f6"/>
            <stop offset="100%" stop-color="#a855f7"/>
        </linearGradient>
    </defs>

    @include('partials.brand-mark.'.$variant, ['grad' => $grad])
</svg>
