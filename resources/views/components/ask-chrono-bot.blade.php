@props([
    'state' => 'idle',   // idle | thinking | done
    'variant' => 'clock', // clock | visor | orb | spark | lens | ferry | nib | forge | radar
    'size' => null,       // px; null = dziedziczy --ac-size z rodzica (domyślnie 40px)
])

@php
    $variants = ['clock', 'visor', 'orb', 'spark', 'lens', 'ferry', 'nib', 'forge', 'radar'];
    $variant = in_array($variant, $variants, true) ? $variant : 'clock';

    // Unikalne id gradientów: kilka botów na stronie nie może współdzielić <defs>.
    $uid = 'acb-'.substr(bin2hex(random_bytes(4)), 0, 8);
    $grad = $uid.'-g';
    $glow = $uid.'-glow';

    // Oś obrotu wskazówek jest inna w każdym wariancie — CSS czyta ją z --ac-pivot.
    [$viewBox, $pivot] = match ($variant) {
        'visor', 'lens', 'ferry', 'nib', 'forge', 'radar', 'orb' => ['0 0 72 82', match ($variant) {
            'lens' => '36px 28px',
            'ferry' => '36px 65.5px',
            'nib' => '36px 64px',
            'forge' => '36px 61px',
            'radar' => '36px 67px',
            'orb' => '36px 33px',
            default => '36px 65.5px', // visor
        }],
        'spark' => ['0 0 64 64', '32px 32px'],
        default => ['0 0 72 82', '36px 35px'],
    };

    $classes = 'ac-bot ac-bot--v-'.$variant.($state !== 'idle' ? ' ac-bot--'.$state : '');

    $style = '--ac-pivot: '.$pivot.';';

    if ($size !== null) {
        $style .= ' --ac-size: '.(int) $size.'px;';
    }
@endphp

<span {{ $attributes->merge(['style' => $style])->class($classes) }}>
    <svg class="ac-bot__svg" viewBox="{{ $viewBox }}" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
        <defs>
            <linearGradient id="{{ $grad }}" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#3b82f6"/>
                <stop offset="100%" stop-color="#a855f7"/>
            </linearGradient>
            <radialGradient id="{{ $glow }}" cx="50%" cy="50%" r="50%">
                <stop offset="0%" stop-color="#a855f7" stop-opacity=".35"/>
                <stop offset="100%" stop-color="#a855f7" stop-opacity="0"/>
            </radialGradient>
        </defs>

        @include('partials.ask-chrono.'.$variant, ['grad' => $grad, 'glow' => $glow])
    </svg>
</span>
