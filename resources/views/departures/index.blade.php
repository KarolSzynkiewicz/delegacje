<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Wyjazdy">
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('departures.create-v2') }}"
                    routeName="departures.create-v2"
                    action="create"
                    class="me-2"
                >
                    Utwórz Wyjazd (V2)
                </x-ui.button>
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('departures.create') }}"
                    routeName="departures.create"
                    action="create"
                >
                    Utwórz Wyjazd (Stary)
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th class="text-start">Daty</th>
                        <th class="text-start">Trasa</th>
                        <th class="text-start">Pojazd</th>
                        <th class="text-start">Uczestnicy i Lokalizacje</th>
                        <th class="text-start">Status</th>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($departures as $departure)
                        <tr>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <div>
                                        <small class="text-muted d-block">Wyjazd:</small>
                                        <div class="fw-semibold">{{ $departure->event_date->format('d.m.Y') }}</div>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">Dojazd:</small>
                                        <div class="fw-semibold">
                                            @if($departure->end_date)
                                                {{ $departure->end_date->format('d.m.Y') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
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
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-2">
                                    <div>
                                        <small class="text-muted d-block mb-1">
                                            <i class="bi bi-people"></i> Uczestnicy:
                                        </small>
                                        @if($departure->participants->count() > 0)
                                            <div class="d-flex flex-column gap-1">
                                                @foreach($departure->participants as $participant)
                                                    @if($participant->employee)
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="bi bi-person text-primary"></i>
                                                            <x-employee-cell 
                                                                :employee="$participant->employee" 
                                                                :avatar-size="'32px'"
                                                                :show-phone="false"
                                                                :name-class="'small'"
                                                            />
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
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
                                <x-ui.badge variant="{{ $badgeVariant }}">{{ ucfirst($visualStatus) }}</x-ui.badge>
                            </td>
                            <td class="text-end">
                                <x-ui.button variant="ghost" href="{{ route('departures.show', $departure) }}" class="btn-sm">
                                    <i class="bi bi-eye"></i>
                                    <span class="d-none d-sm-inline ms-1">Zobacz</span>
                                </x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-state 
                            icon="airplane"
                            message="Brak wyjazdów"
                            :in-table="true"
                            colspan="6"
                        />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($departures->hasPages())
            <div class="mt-3 pt-3 border-top">
                {{ $departures->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </x-ui.card>
</x-app-layout>
