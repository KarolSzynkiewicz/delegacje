{{--
  Warsztat — huddle 4 botów, dwie warstwy:
  tył: Impek (okrąg) + Edi (wizjer), przód: Chrono (kwadrat) + Argus (soczewka).
  Układ: ( Impek ( Chrono )( Argus ) Edi )
--}}
@props([
    'size' => 36,
])

@php
    $uid = 'acw-'.substr(bin2hex(random_bytes(4)), 0, 8);
    $grad = $uid.'-g';
    $style = '--ac-size: '.(int) $size.'px;';
@endphp

<span {{ $attributes->merge(['class' => 'ac-workshop', 'style' => $style]) }}>
    <svg class="ac-workshop__svg" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
        <defs>
            <linearGradient id="{{ $grad }}" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#3b82f6"/>
                <stop offset="100%" stop-color="#a855f7"/>
            </linearGradient>
        </defs>

        {{-- WARSTWA TYLNA — wystają z boków --}}

        {{-- Impek (kurier): okrągła tarcza, lewy bark --}}
        <g class="ac-workshop__back">
            <path d="M9 4.5 L9 7" stroke="url(#{{ $grad }})" stroke-width="1.3" stroke-linecap="round"/>
            <circle cx="9" cy="3.4" r="1.4" fill="url(#{{ $grad }})"/>
            <circle cx="9" cy="15" r="8.2" fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.4"/>
            <circle cx="9" cy="15" r="5.4" fill="#050912"/>
            <ellipse cx="6.4" cy="13.8" rx="1.35" ry="1.7" fill="#7dd3fc"/>
            <ellipse cx="11.6" cy="13.8" rx="1.35" ry="1.7" fill="#7dd3fc"/>
        </g>

        {{-- Edi (redaktor): wizjer, prawy bark --}}
        <g class="ac-workshop__back">
            <path d="M31 4 L31 6.5" stroke="url(#{{ $grad }})" stroke-width="1.3" stroke-linecap="round"/>
            <circle cx="31" cy="3" r="1.4" fill="url(#{{ $grad }})"/>
            <rect x="22.5" y="6.5" width="16" height="13.5" rx="6"
                  fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.4"/>
            <rect x="24.5" y="9" width="12" height="7.5" rx="3.6" fill="#050912"/>
            <ellipse cx="27.4" cy="12.6" rx="1.4" ry="1.7" fill="#7dd3fc"/>
            <ellipse cx="33.8" cy="12.6" rx="1.4" ry="1.7" fill="#7dd3fc"/>
        </g>

        {{-- WARSTWA PRZEDNIA — Chrono + Argus, trochę więksi, nachodzą na tył --}}

        {{-- Chrono (twórca): kwadratowa głowa-moduł --}}
        <g class="ac-workshop__front">
            <path d="M12 12.5 L12 10" stroke="url(#{{ $grad }})" stroke-width="1.3" stroke-linecap="round"/>
            <g stroke="url(#{{ $grad }})" stroke-width="1.4" stroke-linecap="round">
                <path d="M12 6.6 v4"/>
                <path d="M10 8.6 h4"/>
            </g>
            <rect x="3" y="14.5" width="18" height="17.5" rx="5"
                  fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.55"/>
            <rect x="5.4" y="17.2" width="13.2" height="9.2" rx="2.4" fill="#050912"/>
            <path d="M10 17.2 v9.2 M13.8 17.2 v9.2 M5.4 21.8 h13.2" stroke="rgba(59,130,246,.35)" stroke-width=".7"/>
            <rect x="6.6" y="19.4" width="3.4" height="4.2" rx="1" fill="#93c5fd"/>
            <rect x="13.8" y="19.4" width="3.4" height="4.2" rx="1" fill="#93c5fd"/>
        </g>

        {{-- Argus (analityk): hełm + jedno oko-soczewka --}}
        <g class="ac-workshop__front">
            <path d="M28.5 12 L28.5 9.5" stroke="url(#{{ $grad }})" stroke-width="1.3" stroke-linecap="round"/>
            <circle cx="28.5" cy="8.3" r="1.45" fill="url(#{{ $grad }})"/>
            <rect x="18.8" y="14" width="18.4" height="18.2" rx="7.2"
                  fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.55"/>
            <rect x="21.2" y="17.2" width="13.6" height="9.6" rx="4.6" fill="#050912"/>
            <circle cx="28" cy="22" r="4.1" fill="#0a1628" stroke="url(#{{ $grad }})" stroke-width="1.15"/>
            <circle cx="28" cy="22" r="2.45" fill="#7dd3fc"/>
            <circle cx="28.9" cy="21.1" r=".7" fill="#e0f2fe" opacity=".9"/>
        </g>
    </svg>
</span>
