<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Szczegóły transferu">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('transfers.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                @if(in_array($transfer->status, [\App\Enums\LogisticsEventStatus::PLANNED, \App\Enums\LogisticsEventStatus::COMPLETED]))
                    <form method="POST" action="{{ route('transfers.cancel', $transfer) }}" class="d-inline">
                        @csrf
                        <x-ui.button variant="danger" type="submit" onclick="return confirm('Anulować ten transfer?')">
                            <i class="bi bi-x-circle me-1"></i> Anuluj transfer
                        </x-ui.button>
                    </form>
                @endif
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-alert type="success" dismissible icon="check-circle">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert type="danger" dismissible icon="exclamation-triangle">{{ session('error') }}</x-alert>
    @endif

    <!-- Podstawowe informacje -->
    <x-ui.card label="Informacje podstawowe" class="mb-4">
        <div class="row g-4">
            <div class="col-md-4">
                <h6 class="text-muted small mb-1">Data i godzina</h6>
                <p class="fw-semibold mb-0">{{ $transfer->event_date->format('d.m.Y H:i') }}</p>
            </div>
            <div class="col-md-4">
                <h6 class="text-muted small mb-1">Status</h6>
                @php
                    $visualStatus = $transfer->getVisualStatus();
                    $badgeVariant = match($visualStatus) {
                        'oczekuje' => 'primary',
                        'w trakcie' => 'warning',
                        'zakończone' => 'success',
                        'anulowany' => 'danger',
                        default => 'accent'
                    };
                @endphp
                <x-ui.badge variant="{{ $badgeVariant }}">{{ ucfirst($visualStatus) }}</x-ui.badge>
            </div>
            <div class="col-md-4">
                <h6 class="text-muted small mb-1">Pojazd</h6>
                @if($transfer->vehicle)
                    <p class="fw-semibold mb-0">
                        {{ $transfer->vehicle->registration_number }}
                        @if($transfer->vehicle->brand || $transfer->vehicle->model)
                            <small class="text-muted">— {{ trim($transfer->vehicle->brand . ' ' . $transfer->vehicle->model) }}</small>
                        @endif
                    </p>
                @else
                    <span class="text-muted">Brak pojazdu / transport własny</span>
                @endif
            </div>
            <div class="col-md-6">
                <h6 class="text-muted small mb-1"><i class="bi bi-geo-alt text-danger me-1"></i>Skąd</h6>
                <p class="fw-semibold mb-0">{{ $transfer->fromLocation?->name ?? '—' }}</p>
                @if($transfer->fromLocation?->city)
                    <small class="text-muted">{{ $transfer->fromLocation->city }}</small>
                @endif
            </div>
            <div class="col-md-6">
                <h6 class="text-muted small mb-1"><i class="bi bi-geo-alt-fill text-success me-1"></i>Dokąd</h6>
                <p class="fw-semibold mb-0">{{ $transfer->toLocation?->name ?? '—' }}</p>
                @if($transfer->toLocation?->city)
                    <small class="text-muted">{{ $transfer->toLocation->city }}</small>
                @endif
            </div>
            @if($transfer->notes)
                <div class="col-12">
                    <h6 class="text-muted small mb-1">Notatki</h6>
                    <p class="mb-0">{{ $transfer->notes }}</p>
                </div>
            @endif
        </div>
    </x-ui.card>

    <!-- Trasa -->
    <x-ui.card label="Trasa" class="mb-4">
        <div class="row g-4">
            <div class="col-md-4">
                <h6 class="text-muted small mb-1">Dystans</h6>
                <p class="fw-semibold mb-0">{{ $transfer->getFormattedDistance() ?? '—' }}</p>
            </div>
            <div class="col-md-4">
                <h6 class="text-muted small mb-1">Czas</h6>
                <p class="fw-semibold mb-0">{{ $transfer->getFormattedDuration() ?? '—' }}</p>
            </div>
            <div class="col-md-4">
                <h6 class="text-muted small mb-1">Liczba przystanków</h6>
                <p class="fw-semibold mb-0">{{ max(0, ($routeStops->count() ?? 0) - 2) }}</p>
            </div>
        </div>

        <div class="mt-3">
            @if(isset($routeStops) && $routeStops->count() > 0)
                <div class="row g-2">
                    @foreach($routeStops as $i => $loc)
                        @php
                            $isStart = $i === 0;
                            $isEnd = $i === ($routeStops->count() - 1);
                            $badge = $isStart ? 'Start' : ($isEnd ? 'Cel' : 'Przystanek');
                            $badgeVariant = $isStart ? 'primary' : ($isEnd ? 'success' : 'accent');
                        @endphp
                        <div class="col-md-6 col-lg-4">
                            <div class="p-2 border rounded-3 d-flex align-items-start gap-2">
                                <x-ui.badge variant="{{ $badgeVariant }}">{{ $badge }}</x-ui.badge>
                                <div class="min-w-0">
                                    <div class="fw-semibold small text-truncate">{{ $loc->name }}</div>
                                    @if($loc->city)
                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $loc->city }}</div>
                                    @endif
                                    @if(!$loc->hasCoordinates())
                                        <div class="text-warning" style="font-size: 0.75rem;">
                                            <i class="bi bi-exclamation-triangle"></i> brak współrzędnych
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state icon="map" message="Brak zapisanej trasy (brak przystanków)" />
            @endif
        </div>
    </x-ui.card>

    <!-- Uczestnicy -->
    <x-ui.card label="Uczestnicy" class="mb-4">
        @if($transfer->participants->count() > 0)
            <div class="row g-2">
                @foreach($transfer->participants as $participant)
                    @if($participant->employee)
                        <div class="col-md-4 col-lg-3">
                            <div class="d-flex align-items-center gap-2 p-2 border rounded-3">
                                <i class="bi bi-person text-primary"></i>
                                <div>
                                    <div class="fw-semibold small">{{ $participant->employee->full_name }}</div>
                                    @if($participant->employee->phone)
                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $participant->employee->phone }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <p class="text-muted mb-0">Brak uczestników.</p>
        @endif
    </x-ui.card>

    <!-- Wynagrodzenie kierowcy -->
    @if($transfer->driverAdjustments->count() > 0)
        <x-ui.card label="Wynagrodzenie kierowcy" class="mb-4">
            @foreach($transfer->driverAdjustments as $adj)
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-person-badge fs-4 text-primary"></i>
                        <div>
                            <div class="fw-semibold">{{ $adj->employee?->full_name ?? '—' }}</div>
                            <div class="text-success fw-semibold fs-5">
                                {{ number_format($adj->amount, 2) }} {{ $adj->currency }}
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        @if($adj->payroll_id && $adj->payroll)
                            <div class="small text-muted">Payroll:</div>
                            <a href="{{ route('payrolls.show', $adj->payroll) }}" class="small">
                                {{ $adj->payroll->display_name }}
                            </a>
                        @else
                            <x-ui.badge variant="warning">Bez payrollu</x-ui.badge>
                            <div class="small text-muted mt-1">
                                Przypisz payroll w
                                <a href="{{ route('adjustments.edit', $adj) }}">edycji nagrody</a>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </x-ui.card>
    @endif

    <!-- Meta -->
    <x-ui.card label="Informacje systemowe">
        <div class="row g-3">
            <div class="col-md-4">
                <h6 class="text-muted small mb-1">ID</h6>
                <p class="mb-0 font-monospace">{{ $transfer->id }}</p>
            </div>
            <div class="col-md-4">
                <h6 class="text-muted small mb-1">Utworzony przez</h6>
                <p class="mb-0">{{ $transfer->creator?->name ?? '—' }}</p>
            </div>
            <div class="col-md-4">
                <h6 class="text-muted small mb-1">Data utworzenia</h6>
                <p class="mb-0">{{ $transfer->created_at->format('d.m.Y H:i') }}</p>
            </div>
        </div>
    </x-ui.card>
</x-app-layout>
