<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Zjazdy (Return Trips)</h2>
            <a href="{{ route('return-trips.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Utwórz Zjazd
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    @if($returnTrips->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start">Data</th>
                                        <th class="text-start">Pojazd</th>
                                        <th class="text-start">Z</th>
                                        <th class="text-start">Do</th>
                                        <th class="text-start">Uczestnicy</th>
                                        <th class="text-start">Status</th>
                                        <th class="text-start">Akcje</th>
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
                                                    $badgeClass = match($trip->status->value) {
                                                        'scheduled' => 'bg-primary',
                                                        'in_progress' => 'bg-info',
                                                        'completed' => 'bg-success',
                                                        'cancelled' => 'bg-danger',
                                                        default => 'bg-secondary'
                                                    };
                                                @endphp
                                                <span class="badge {{ $badgeClass }}">{{ $trip->status->label() }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <x-view-button href="{{ route('return-trips.show', $trip) }}" />
                                                    @if($trip->status === \App\Enums\LogisticsEventStatus::PLANNED)
                                                        <a href="{{ route('return-trips.edit', $trip) }}" 
                                                           class="btn btn-sm btn-outline-primary" 
                                                           title="Edytuj">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
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
                            <a href="{{ route('return-trips.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Utwórz pierwszy zjazd
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
