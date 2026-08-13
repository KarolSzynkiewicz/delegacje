@php
    $value = (int) ($value ?? 0);
    $compact = (bool) ($compact ?? false);
    $padding = $compact ? '.45rem .75rem' : '.65rem .75rem';
@endphp
<td class="text-end" style="padding:{{ $padding }};font-variant-numeric:tabular-nums;{{ $value === 0 ? 'color:var(--text-muted);' : '' }}">
    {{ $value }}
</td>
