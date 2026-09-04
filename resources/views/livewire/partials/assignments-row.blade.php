@props(['assignment', 'hideEmployee' => false])

@php
    $status = $assignment->status ?? \App\Enums\AssignmentStatus::ACTIVE;
    $statusLabel = $status instanceof \App\Enums\AssignmentStatus ? $status->label() : ucfirst($status);
    $colorType = \App\Services\StatusColorService::getAssignmentStatusColor($status);
    $badgeVariant = match ($colorType) {
        'success' => 'success',
        'danger' => 'danger',
        'warning' => 'warning',
        'info' => 'info',
        default => 'info',
    };
@endphp

<tr wire:key="assignment-{{ $assignment->id }}">
    <td class="fw-semibold">{{ $assignment->project->name }}</td>
    @unless($hideEmployee)
        <td>
            <x-employee-cell :employee="$assignment->employee" />
        </td>
    @endunless
    <td>
        <x-ui.badge variant="info">{{ $assignment->role->name }}</x-ui.badge>
    </td>
    <td>
        <small class="text-muted">
            {{ $assignment->start_date->format('Y-m-d') }}
            –
            {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '...' }}
        </small>
    </td>
    <td>
        <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
    </td>
    <td class="text-end">
        <x-ui.action-buttons
            viewRoute="{{ route('project-assignments.show', $assignment) }}"
            editRoute="{{ route('project-assignments.edit', $assignment) }}"
            deleteRoute="{{ route('project-assignments.destroy', $assignment) }}"
            deleteMessage="Czy na pewno chcesz usunąć to przypisanie?"
        />
    </td>
</tr>
