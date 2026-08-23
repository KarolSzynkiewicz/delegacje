@php
    $gradId = 'chronoLogoGrad-'.substr(bin2hex(random_bytes(4)), 0, 8);
@endphp
<svg xmlns="http://www.w3.org/2000/svg"
     viewBox="0 0 40 40"
     width="40"
     height="40"
     aria-label="ChronoLogic logo">
    {{-- 4:00 — minuta (12) w gradiencie primary→accent, godzina (4) w --warning. --}}
    <defs>
        <linearGradient id="{{ $gradId }}" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#3b82f6"/>
            <stop offset="100%" stop-color="#a855f7"/>
        </linearGradient>
    </defs>
    <circle cx="20" cy="20" r="16.5" fill="none" stroke="#334155" stroke-width="1.8"/>
    {{-- Minuta, 12:00 — gradient marki --}}
    <path d="M20 20 L20 7.5" stroke="url(#{{ $gradId }})" stroke-width="1.9" stroke-linecap="round"/>
    {{-- Godzina, 4:00 — warning --}}
    <path d="M20 20 L28 24.6" stroke="#f59e0b" stroke-width="2.7" stroke-linecap="round"/>
    <circle cx="20" cy="20" r="2.3" fill="url(#{{ $gradId }})"/>
</svg>
