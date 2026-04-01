<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Transfery">
            <x-slot name="right">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('transfers.create') }}"
                    routeName="transfers.create"
                    action="create"
                >
                    <i class="bi bi-plus-lg me-1"></i> Utwórz transfer
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-alert type="success" dismissible icon="check-circle">{{ session('success') }}</x-alert>
    @endif

    <x-ui.card>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th class="text-start">Data</th>
                        <th class="text-start">Trasa</th>
                        <th class="text-start">Pojazd</th>
                        <th class="text-start">Uczestnicy</th>
                        <th class="text-start">Kierowca / Wynagrodzenie</th>
                        <th class="text-start">Status</th>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $transfer)
                        <tr>
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
                                @if($transfer->vehicle)
                                    <div class="fw-semibold">{{ $transfer->vehicle->registration_number }}</div>
                                    @if($transfer->vehicle->brand || $transfer->vehicle->model)
                                        <small class="text-muted">{{ trim($transfer->vehicle->brand . ' ' . $transfer->vehicle->model) }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($transfer->participants->count() > 0)
                                    <div class="d-flex flex-column gap-1">
                                        @foreach($transfer->participants->take(3) as $participant)
                                            @if($participant->employee)
                                                <small>{{ $participant->employee->full_name }}</small>
                                            @endif
                                        @endforeach
                                        @if($transfer->participants->count() > 3)
                                            <small class="text-muted">+{{ $transfer->participants->count() - 3 }} więcej</small>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $driverAdj = $transfer->driverAdjustments->first();
                                @endphp
                                @if($driverAdj)
                                    <div class="small">
                                        <div class="fw-semibold">{{ $driverAdj->employee?->full_name }}</div>
                                        <div class="text-success">{{ number_format($driverAdj->amount, 2) }} {{ $driverAdj->currency }}</div>
                                        @if(!$driverAdj->payroll_id)
                                            <span class="badge bg-warning text-dark">bez payrollu</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
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
                            </td>
                            <td class="text-end">
                                <x-ui.button variant="ghost" href="{{ route('transfers.show', $transfer) }}" class="btn-sm">
                                    <i class="bi bi-eye"></i>
                                    <span class="d-none d-sm-inline ms-1">Zobacz</span>
                                </x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-state
                            icon="arrow-left-right"
                            message="Brak transferów"
                            :in-table="true"
                            colspan="7"
                        />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transfers->hasPages())
            <div class="mt-3 pt-3 border-top">
                {{ $transfers->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </x-ui.card>
</x-app-layout>
