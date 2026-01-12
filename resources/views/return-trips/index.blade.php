<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">Zjazdy</h2>
            <x-ui.button variant="primary" href="{{ route('return-trips.create') }}">
                <i class="bi bi-plus-circle"></i> Utwórz Zjazd
            </x-ui.button>
        </div>
    </x-slot>

    <x-ui.card>
        @if($returnTrips->count() > 0)
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="text-start">Data</th>
                            <th class="text-start">Pojazd</th>
                            <th class="text-start">Z</th>
                            <th class="text-start">Do</th>
                            <th class="text-start">Uczestnicy</th>
                            <th class="text-start">Status</th>
                            <th class="text-end">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($returnTrips as $trip)
                            <tr>
                                <td>{{ $trip->event_date->format('Y-m-d H:i') }}</td>
                                <td>{{ $trip->vehicle ? $trip->vehicle->registration_number : '-' }}</td>
                                <td>{{ $trip->fromLocation->name }}</td>
                                <td>{{ $trip->toLocation->name }}</td>
                                <td>{{ $trip->participants->count() }} osób</td>
                                <td>
                                    @php
                                        $badgeVariant = match($trip->status->value) {
                                            'scheduled' => 'accent',
                                            'in_progress' => 'info',
                                            'completed' => 'success',
                                            'cancelled' => 'danger',
                                            default => 'info'
                                        };
                                    @endphp
                                    <x-ui.badge variant="{{ $badgeVariant }}">{{ $trip->status->label() }}</x-ui.badge>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <x-ui.button variant="ghost" href="{{ route('return-trips.show', $trip) }}">
                                            <i class="bi bi-eye"></i>
                                        </x-ui.button>
                                        @if($trip->status === \App\Enums\LogisticsEventStatus::PLANNED)
                                            <x-ui.button variant="ghost" href="{{ route('return-trips.edit', $trip) }}">
                                                <i class="bi bi-pencil"></i>
                                            </x-ui.button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($returnTrips->hasPages())
                <div class="mt-3">
                    {{ $returnTrips->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                <p class="text-muted mb-3">Brak zjazdów w systemie.</p>
                <x-ui.button variant="primary" href="{{ route('return-trips.create') }}">
                    <i class="bi bi-plus-circle"></i> Utwórz pierwszy zjazd
                </x-ui.button>
            </div>
        @endif
    </x-ui.card>
</x-app-layout>
