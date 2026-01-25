<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Szczegóły Wyjazdu">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('departures.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                @if($departure->status !== \App\Enums\LogisticsEventStatus::CANCELLED)
                    <x-ui.button 
                        variant="ghost" 
                        href="{{ route('departures.edit', $departure) }}"
                        routeName="departures.edit"
                        action="edit"
                    >
                        Edytuj
                    </x-ui.button>
                @endif
                @if($departure->status === \App\Enums\LogisticsEventStatus::PLANNED)
                    <form method="POST" action="{{ route('departures.cancel', $departure) }}" class="d-inline" onsubmit="return confirm('Czy na pewno chcesz anulować ten wyjazd?');">
                        @csrf
                        <x-ui.button 
                            variant="danger" 
                            type="submit"
                            action="cancel"
                        >
                            Anuluj Wyjazd
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
                <h6 class="text-muted small mb-1">Data wyjazdu</h6>
                <p class="fw-semibold">{{ $departure->event_date->format('Y-m-d H:i') }}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted small mb-1">Status</h6>
                @php
                    $badgeVariant = match($departure->status->value) {
                        'planned' => 'primary',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'accent'
                    };
                @endphp
                <x-ui.badge variant="{{ $badgeVariant }}">{{ $departure->status->label() }}</x-ui.badge>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted small mb-1">Pojazd</h6>
                <p class="fw-semibold">
                    {{ $departure->vehicle ? $departure->vehicle->registration_number . ' - ' . $departure->vehicle->brand . ' ' . $departure->vehicle->model : '-' }}
                </p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted small mb-1">Z</h6>
                <p class="fw-semibold">{{ $departure->fromLocation->name }}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted small mb-1">Do</h6>
                <p class="fw-semibold">{{ $departure->toLocation->name }}</p>
            </div>
            @if($departure->notes)
            <div class="col-12">
                <h6 class="text-muted small mb-1">Notatki</h6>
                <p>{{ $departure->notes }}</p>
            </div>
            @endif
        </div>

        <div class="border-top pt-4">
            <h5 class="fw-bold text-dark mb-4">Uczestnicy ({{ $departure->participants->count() }})</h5>
            @if($departure->participants->count() > 0)
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th class="text-start">Pracownik</th>
                                <th class="text-start">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($departure->participants as $participant)
                                <tr>
                                    <td>
                                        <x-employee-cell :employee="$participant->employee" />
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
