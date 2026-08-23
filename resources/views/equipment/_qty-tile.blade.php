@php
    $value = (int) ($value ?? 0);
    $tone = $tone ?? null;
    $label = $label ?? '';
    $classes = 'eq-stock-tile';
    if ($value === 0) {
        $classes .= ' eq-qty--zero';
    } elseif ($tone) {
        $classes .= ' eq-qty--'.$tone;
    }
@endphp
<div class="{{ $classes }}">
    <span class="eq-stock-tile__label">{{ $label }}</span>
    <span class="eq-stock-tile__value font-mono">{{ $value }}</span>
</div>
