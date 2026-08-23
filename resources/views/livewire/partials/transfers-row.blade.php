@props(['transfer'])

@php
    $uniqueParticipants = $transfer->participants
        ->filter(fn ($p) => $p->employee)
        ->unique('employee_id')
        ->values();

    $visualStatus = $transfer->getVisualStatus();
    $badgeVariant = match($visualStatus) {
        'oczekuje' => 'primary',
        'w trakcie' => 'warning',
        'zakończone' => 'success',
        'anulowany' => 'danger',
        default => 'accent'
    };

    $driverAdj = $transfer->driverAdjustments->first();
@endphp

<tr wire:key="transfer-{{ $transfer->id }}">
    <td class="text-muted small">{{ $transfer->id }}</td>
    <td>
        <div class="fw-semibold">{{ $transfer->event_date->format('d.m.Y') }}</div>
        <small class="text-muted">{{ $transfer->event_date->format('H:i') }}</small>
    </td>
    <td>
        <div class="d-flex flex-column gap-1">
            <div>
                <small class="text-muted d-block">Z:</small>
                <div>{{ $transfer->fromLocation?->name ?? '—' }}</div>
            </div>
            <div>
                <small class="text-muted d-block">Do:</small>
                <div>{{ $transfer->toLocation?->name ?? '—' }}</div>
            </div>
        </div>
    </td>
    <td>
        @if($transfer->has_reassignment)
            <x-ui.badge variant="info"><i class="bi bi-arrow-left-right me-1"></i> Przeniesienie</x-ui.badge>
        @else
            <x-ui.badge variant="secondary"><i class="bi bi-truck-front me-1"></i> Przejazd</x-ui.badge>
        @endif
    </td>
    <td>
        @if($transfer->vehicle)
            <div class="fw-semibold">{{ $transfer->vehicle->registration_number }}</div>
            @if($transfer->vehicle->brand || $transfer->vehicle->model)
                <small class="text-muted">{{ trim($transfer->vehicle->brand.' '.$transfer->vehicle->model) }}</small>
            @endif
        @else
            <span class="text-muted">—</span>
        @endif
    </td>
    <td>
        <x-ui.avatar-stack :employees="$uniqueParticipants->pluck('employee')" />
    </td>
    <td>
        @if($driverAdj)
            <div class="small">
                <div class="fw-semibold">{{ $driverAdj->employee?->full_name }}</div>
                <div class="text-success">{{ number_format($driverAdj->amount, 2) }} {{ $driverAdj->currency }}</div>
            </div>
        @else
            <span class="text-muted">—</span>
        @endif
    </td>
    <td>
        <x-ui.badge variant="{{ $badgeVariant }}">{{ ucfirst($visualStatus) }}</x-ui.badge>
        @if($transfer->has_reassignment)
            <x-ui.badge variant="info"><i class="bi bi-arrow-left-right"></i></x-ui.badge>
        @endif
    </td>
    <td class="text-end">
        <x-ui.button variant="ghost" href="{{ route('transfers.show', $transfer) }}" class="btn-sm">
            <i class="bi bi-eye"></i>
            <span class="d-none d-sm-inline ms-1">Zobacz</span>
        </x-ui.button>
    </td>
</tr>
