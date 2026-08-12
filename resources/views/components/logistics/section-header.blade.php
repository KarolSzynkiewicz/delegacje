@props([
    'title',
    'icon' => 'bi-suitcase-lg',
    'iconBg' => 'rgba(99,102,241,0.2)',
    'iconColor' => '#a5b4fc',
    'iconSize' => '0.9rem',
])
<div {{ $attributes->merge(['class' => 'd-flex align-items-center gap-2 mb-3']) }}>
    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
         style="width: 32px; height: 32px; background: {{ $iconBg }};">
        <i class="bi {{ $icon }}" style="font-size: {{ $iconSize }}; color: {{ $iconColor }};"></i>
    </div>
    <h6 class="mb-0 fw-bold flex-grow-1" style="letter-spacing: .02em;">{{ $title }}</h6>
    @if(isset($actions) && ! $actions->isEmpty())
        <div class="ms-auto flex-shrink-0 d-flex align-items-center gap-2" style="min-width: 0; max-width: min(100%, 22rem);">
            {{ $actions }}
        </div>
    @endif
</div>
