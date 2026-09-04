@props(['rotation', 'hideEmployee' => false])

@php
    $status = $rotation->status;
    $today = now()->toDateString();

    if (empty($status) || $status !== 'cancelled') {
        if ($rotation->start_date->toDateString() > $today) {
            $status = 'scheduled';
        } elseif ($rotation->end_date->toDateString() < $today) {
            $status = 'completed';
        } else {
            $status = 'active';
        }
    }

    $badgeVariant = match($status) {
        'active' => 'success',
        'scheduled' => 'info',
        'completed' => 'accent',
        'cancelled' => 'danger',
        default => 'accent'
    };

    $badgeLabel = match($status) {
        'active' => 'Aktywna',
        'scheduled' => 'Zaplanowana',
        'completed' => 'Zakończona',
        'cancelled' => 'Anulowana',
        default => '-'
    };

    $days = $rotation->duration_days;
@endphp

<tr wire:key="rotation-{{ $rotation->id }}">
    @unless($hideEmployee)
        <td>
            <x-employee-cell :employee="$rotation->employee" />
        </td>
    @endunless
    <td><small class="text-muted">{{ $rotation->start_date->format('Y-m-d') }}</small></td>
    <td><small class="text-muted">{{ $rotation->end_date->format('Y-m-d') }}</small></td>
    <td class="text-end text-nowrap">
        @if($days !== null)
            <span class="fw-semibold">{{ $days }}</span>
            <span class="text-muted small">{{ $days === 1 ? 'dzień' : 'dni' }}</span>
        @else
            <span class="text-muted">—</span>
        @endif
    </td>
    <td><x-ui.badge variant="{{ $badgeVariant }}">{{ $badgeLabel }}</x-ui.badge></td>
    <td><small class="text-muted">{{ $rotation->notes ? Str::limit($rotation->notes, 50) : '-' }}</small></td>
    <td>
        <x-ui.action-buttons
            viewRoute="{{ route('employees.rotations.show', [$rotation->employee, $rotation]) }}"
            editRoute="{{ route('employees.rotations.edit', [$rotation->employee, $rotation]) }}"
            deleteRoute="{{ route('employees.rotations.destroy', [$rotation->employee, $rotation]) }}"
            deleteMessage="Czy na pewno chcesz usunąć tę rotację?"
        />
    </td>
</tr>
