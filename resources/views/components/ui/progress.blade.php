@props([
    'value' => 0,
    'max' => 100,
    'showLabel' => false,
    'variant' => 'default', // default, success, danger, warning
])

@php
    $percentage = min(100, max(0, ($value / $max) * 100));
    $style = 'width: ' . $percentage . '%;';
    if ($variant === 'success') {
        $style .= ' background: linear-gradient(90deg, var(--success), #34d399); box-shadow: 0 0 10px rgba(16, 185, 129, 0.5);';
    } elseif ($variant === 'danger') {
        $style .= ' background: linear-gradient(90deg, var(--danger), #f87171); box-shadow: 0 0 10px rgba(239, 68, 68, 0.5);';
    } elseif ($variant === 'warning') {
        $style .= ' background: linear-gradient(90deg, var(--warning), #fbbf24); box-shadow: 0 0 10px rgba(245, 158, 11, 0.5);';
    }
@endphp

<div class="progress-ui">
    <div class="progress-bar-ui" style="{{ $style }}"></div>
</div>

@if($showLabel)
    <div class="d-flex justify-content-between align-items-center mt-2">
        <span class="small text-muted">{{ $value }}/{{ $max }}</span>
        <span class="small fw-bold">{{ round($percentage) }}%</span>
    </div>
@endif
