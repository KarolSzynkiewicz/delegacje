@php
    $value = (int) ($value ?? 0);
    $compact = (bool) ($compact ?? false);
    $tone = $tone ?? null;
    $classes = 'text-end eq-qty';
    if ($compact) {
        $classes .= ' eq-qty--compact';
    }
    if ($value === 0) {
        $classes .= ' eq-qty--zero';
    } elseif ($tone) {
        $classes .= ' eq-qty--'.$tone;
    }
@endphp
<td class="{{ $classes }}">
    {{ $value }}
</td>
