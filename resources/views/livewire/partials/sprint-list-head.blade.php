@php
    $items = $items ?? collect();
    $done = $items->filter(fn ($item) => $item->isCompleted())->count();
    $total = $items->count();
    $pct = $total ? (int) round($done / $total * 100) : 0;
@endphp

<div class="sb-list-head">
    <p class="sb-list-title">
        <i class="bi bi-{{ $icon }}"></i>
        <span>{{ $title }}</span>
    </p>
    <p class="sb-list-hint">{{ $hint }}</p>
    <div class="sb-meter" title="{{ $done }} z {{ $total }}">
        <div class="sb-meter-track"><span style="width: {{ $pct }}%"></span></div>
        <span class="sb-meter-count font-mono">{{ $done }}/{{ $total }}</span>
    </div>
</div>
