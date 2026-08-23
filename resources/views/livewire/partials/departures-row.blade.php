@props(['departure'])

@php
    $visualStatus = $departure->getVisualStatus();
    $badgeVariant = match($visualStatus) {
        'oczekuje' => 'primary',
        'w trakcie' => 'warning',
        'zakończone' => 'success',
        'anulowany' => 'danger',
        default => 'accent'
    };
@endphp

<tr wire:key="departure-{{ $departure->id }}">
    <td class="text-muted small">{{ $departure->id }}</td>
    <td>
        <div class="d-flex flex-column gap-1">
            <div>
                <small class="text-muted d-block">Wyjazd:</small>
                <div class="fw-semibold">{{ $departure->event_date->format('d.m.Y') }}</div>
            </div>
            <div>
                <small class="text-muted d-block">Dojazd:</small>
                <div class="fw-semibold">
                    {{ $departure->end_date?->format('d.m.Y') ?? '—' }}
                </div>
            </div>
        </div>
    </td>
    <td>
        <div class="d-flex flex-column gap-1">
            <div>
                <small class="text-muted d-block">Z:</small>
                <div>{{ $departure->fromLocation->name }}</div>
            </div>
            <div>
                <small class="text-muted d-block">Do:</small>
                <div>{{ $departure->toLocation->name }}</div>
            </div>
        </div>
    </td>
    <td>
        @if($departure->vehicle)
            <div class="d-flex align-items-center gap-2">
                @if($departure->vehicle->image_path)
                    <img
                        src="{{ $departure->vehicle->image_url }}"
                        alt="{{ $departure->vehicle->registration_number }}"
                        class="rounded"
                        style="width: 40px; height: 40px; object-fit: cover;"
                    >
                @else
                    <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-truck text-white"></i>
                    </div>
                @endif
                <div>
                    <div class="fw-semibold">{{ $departure->vehicle->registration_number }}</div>
                    @if($departure->vehicle->brand && $departure->vehicle->model)
                        <small class="text-muted">{{ $departure->vehicle->brand }} {{ $departure->vehicle->model }}</small>
                    @endif
                </div>
            </div>
        @else
            <span class="text-muted">Transport publiczny</span>
        @endif
    </td>
    <td>
        <x-ui.avatar-stack :employees="$departure->participants->pluck('employee')" />
    </td>
    <td>
        <x-ui.badge variant="{{ $badgeVariant }}">{{ ucfirst($visualStatus) }}</x-ui.badge>
    </td>
    <td class="text-end">
        <x-ui.button variant="ghost" href="{{ route('departures.show', $departure) }}" class="btn-sm">
            <i class="bi bi-eye"></i>
            <span class="d-none d-sm-inline ms-1">Zobacz</span>
        </x-ui.button>
    </td>
</tr>
