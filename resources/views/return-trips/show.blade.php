<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Szczegóły Zjazdu">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('return-trips.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                @if($returnTrip->status === \App\Enums\LogisticsEventStatus::PLANNED)
                    <x-ui.button 
                        variant="ghost" 
                        href="{{ route('return-trips.edit', $returnTrip) }}"
                        routeName="return-trips.edit"
                        action="edit"
                    >
                        Edytuj
                    </x-ui.button>
                    <form method="POST" action="{{ route('return-trips.cancel', $returnTrip) }}" class="d-inline" onsubmit="return confirm('Czy na pewno chcesz anulować ten zjazd?');">
                        @csrf
                        <x-ui.button 
                            variant="danger" 
                            type="submit"
                            action="cancel"
                        >
                            Anuluj Zjazd
                        </x-ui.button>
                    </form>
                @endif
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif
    @if(session('error'))
        <x-alert type="danger" dismissible icon="exclamation-triangle">
            {{ session('error') }}
        </x-alert>
    @endif

    <x-ui.card label="Informacje podstawowe">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Data zjazdu</h6>
                            <p class="fw-semibold">{{ $returnTrip->event_date->format('Y-m-d H:i') }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Status</h6>
                            @php
                                $badgeVariant = match($returnTrip->status->value) {
                                    'planned' => 'primary',
                                    'in_progress' => 'info',
                                    'completed' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'accent'
                                };
                            @endphp
                            <x-ui.badge variant="{{ $badgeVariant }}">{{ $returnTrip->status->label() }}</x-ui.badge>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Pojazd</h6>
                            <p class="fw-semibold">
                                {{ $returnTrip->vehicle ? $returnTrip->vehicle->registration_number . ' - ' . $returnTrip->vehicle->brand . ' ' . $returnTrip->vehicle->model : '-' }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Z</h6>
                            <p class="fw-semibold">{{ $returnTrip->fromLocation->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Do</h6>
                            <p class="fw-semibold">{{ $returnTrip->toLocation->name }}</p>
                        </div>
                        @if($returnTrip->notes)
                        <div class="col-12">
                            <h6 class="text-muted small mb-1">Notatki</h6>
                            <p>{{ $returnTrip->notes }}</p>
                        </div>
                        @endif
                    </div>

                    <div class="border-top pt-4">
                        <h5 class="fw-bold text-dark mb-4">Uczestnicy ({{ $returnTrip->participants->count() }})</h5>
                        @if($returnTrip->participants->count() > 0)
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th class="text-start">Pracownik</th>
                                            <th class="text-start">Przypisanie</th>
                                            <th class="text-start">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($returnTrip->participants as $participant)
                                            <tr>
                                                <td>{{ $participant->employee->full_name }}</td>
                                                <td>
                                                    @if($participant->assignment)
                                                        {{ class_basename($participant->assignment) }} #{{ $participant->assignment_id }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    <x-ui.badge variant="accent">{{ ucfirst($participant->status) }}</x-ui.badge>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak uczestników</p>
                        @endif
                    </div>
    </x-ui.card>
</x-app-layout>
