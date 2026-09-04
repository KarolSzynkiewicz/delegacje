@props(['assignment', 'hideEmployee' => false])

<tr wire:key="accommodation-assignment-{{ $assignment->id }}">
    @unless($hideEmployee)
        <td><x-employee-cell :employee="$assignment->employee" /></td>
    @endunless
    <td class="fw-semibold">
        {{ $assignment->accommodation->name }}
        @if($assignment->accommodation->city)
            <span class="text-muted small">({{ $assignment->accommodation->city }})</span>
        @endif
    </td>
    <td>
        <small class="text-muted">
            {{ $assignment->start_date->format('Y-m-d') }}
            – {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '...' }}
        </small>
    </td>
    <td>
        @if($assignment->isScheduled())
            <x-ui.badge variant="info">Przyszłe</x-ui.badge>
        @elseif($assignment->isActive())
            <x-ui.badge variant="success">Aktywne</x-ui.badge>
        @else
            <x-ui.badge variant="accent">Zakończone</x-ui.badge>
        @endif
    </td>
    <td class="text-end">
        <x-ui.action-buttons
            viewRoute="{{ route('accommodation-assignments.show', $assignment) }}"
            editRoute="{{ route('accommodation-assignments.edit', $assignment) }}"
            deleteRoute="{{ route('accommodation-assignments.destroy', $assignment) }}"
            deleteMessage="Czy na pewno chcesz usunąć to przypisanie mieszkania?"
        />
    </td>
</tr>
