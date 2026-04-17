{{--
    Karta „segment transferu” — wspólna obudowa dla: transfer przed lotem, po locie, zjazdy itd.

    Zachowanie (badge po zatwierdzeniu, przyciski, modale) zostaje w komponencie Livewire nadrzędnym;
    ten klocek tylko ujednolica layout (numer, tytuł, podtytuł, obramowanie, tryb „wymaga uwagi”).

    Sloty:
    - subtitle — opis trasy / kontekstu (HTML)
    - aside — np. przycisk „Usuń” (HTML)
    - default — treść karty (picker, empty state, badge, przyciski)

    @props accent: info | primary | success — kolor numerka i domyślnej obwódki (gdy needsAttention = false)
--}}
@props([
    'index' => '1',
    'title' => '',
    'accent' => 'info',
    'needsAttention' => false,
])
@php
    $accents = [
        'info' => ['circle' => 'rgba(14,165,233,0.35)', 'border' => 'rgba(14,165,233,0.28)'],
        'primary' => ['circle' => 'rgba(59,130,246,0.35)', 'border' => 'rgba(59,130,246,0.28)'],
        'success' => ['circle' => 'rgba(34,197,94,0.35)', 'border' => 'rgba(34,197,94,0.28)'],
    ];
    $a = $accents[$accent] ?? $accents['info'];
    if ($needsAttention) {
        $cardBg = 'rgba(239,68,68,0.07)';
        $cardBorder = 'rgba(239,68,68,0.42)';
    } else {
        $cardBg = 'var(--bg-card)';
        $cardBorder = $a['border'];
    }
@endphp
<div {{ $attributes->merge(['class' => 'rtp-card transfer-segment-card rounded-3 p-3 mb-3']) }}
     style="background: {{ $cardBg }}; border: 1px solid {{ $cardBorder }};">
    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
        <div class="d-flex align-items-center gap-2 min-w-0">
            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                 style="width: 32px; height: 32px; font-size: 0.85rem; background: {{ $a['circle'] }};">{{ $index }}</div>
            <div class="min-w-0">
                <h6 class="mb-0 fw-semibold" style="font-size: 0.92rem;">{{ $title }}</h6>
                @isset($subtitle)
                    <div class="transfer-segment-card__subtitle">{{ $subtitle }}</div>
                @endisset
            </div>
        </div>
        @isset($aside)
            <div class="flex-shrink-0">{{ $aside }}</div>
        @endisset
    </div>
    {{ $slot }}
</div>
