<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Zjazdy">
            <x-slot name="right">
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('return-trips.create') }}"
                    routeName="return-trips.create"
                    action="create"
                >
                    Utwórz Zjazd
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card>
        @if($returnTrips->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Pojazd</th>
                            <th>Z</th>
                            <th>Do</th>
                            <th>Uczestnicy</th>
                            <th>Status</th>
                            <th>Akcje</th>
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
                                <td>
                                    @if($trip->status === \App\Enums\LogisticsEventStatus::PLANNED)
                                        <x-action-buttons
                                            viewRoute="{{ route('return-trips.show', $trip) }}"
                                            editRoute="{{ route('return-trips.edit', $trip) }}"
                                        />
                                    @else
                                        <x-action-buttons
                                            viewRoute="{{ route('return-trips.show', $trip) }}"
                                        />
                                    @endif
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
            <x-ui.empty-state 
                icon="inbox" 
                message="Brak zjazdów w systemie."
            >
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('return-trips.create') }}"
                    routeName="return-trips.create"
                    action="create"
                >
                    Utwórz pierwszy zjazd
                </x-ui.button>
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</x-app-layout>
