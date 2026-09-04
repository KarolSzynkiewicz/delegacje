@props(['timeLog', 'hideEmployee' => false])

<tr wire:key="time-log-{{ $timeLog->id }}">
    <td>{{ $timeLog->start_time->format('Y-m-d') }}</td>
    @unless($hideEmployee)
        <td>
            @if($timeLog->projectAssignment?->employee)
                <x-employee-cell :employee="$timeLog->projectAssignment->employee" />
            @else
                <span class="text-muted">—</span>
            @endif
        </td>
    @endunless
    <td class="fw-semibold">{{ $timeLog->projectAssignment?->project?->name ?? '—' }}</td>
    <td class="fw-semibold font-mono">{{ number_format($timeLog->hours_worked, 2, ',', ' ') }}h</td>
    <td>{{ $timeLog->notes ? Str::limit($timeLog->notes, 50) : '—' }}</td>
    <td class="text-end">
        <x-ui.action-buttons
            viewRoute="{{ route('time-logs.show', $timeLog) }}"
            editRoute="{{ route('time-logs.edit', $timeLog) }}"
            :deleteRoute="auth()->user()->hasPermission('time-logs.delete') ? route('time-logs.destroy', $timeLog) : null"
            deleteMessage="Czy na pewno chcesz usunąć ten wpis?"
        />
    </td>
</tr>
