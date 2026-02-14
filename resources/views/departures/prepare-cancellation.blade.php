<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Przygotowanie Anulacji Wyjazdu">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('departures.show', $departure) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <x-ui.card>
                <h3 class="fs-5 fw-bold mb-3">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                    Konsekwencje anulacji wyjazdu
                </h3>
                
                <!-- Informacje o wyjeździe -->
                <div class="alert alert-info mb-4">
                    <h5 class="fw-semibold mb-2">Informacje o wyjeździe</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Data wyjazdu:</strong> {{ $departure->event_date->format('d.m.Y') }}</p>
                            <p class="mb-1"><strong>Data przybycia:</strong> {{ $departure->end_date?->format('d.m.Y') ?? 'Brak' }}</p>
                            <p class="mb-1"><strong>Z:</strong> {{ $departure->fromLocation->name }}</p>
                            <p class="mb-0"><strong>Do:</strong> {{ $departure->toLocation->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Uczestnicy:</strong></p>
                            <ul class="mb-0">
                                @foreach($departure->participants as $participant)
                                    <li>{{ $participant->employee->full_name }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

                @if($affectedProjectAssignments->isEmpty() && $affectedVehicleAssignments->isEmpty() && $affectedAccommodationAssignments->isEmpty())
                    <!-- Brak przypisań do anulowania -->
                    <x-ui.alert type="success" icon="check-circle">
                        <strong>Brak powiązanych przypisań</strong><br>
                        Ten wyjazd nie ma żadnych powiązanych przypisań. Możesz bezpiecznie anulować wyjazd.
                    </x-ui.alert>

                    <form method="POST" action="{{ route('departures.cancel', $departure) }}">
                        @csrf
                        <div class="d-flex gap-2">
                            <x-ui.button 
                                variant="danger" 
                                type="submit"
                            >
                                <i class="bi bi-x-circle me-1"></i>
                                Anuluj Wyjazd
                            </x-ui.button>
                            <x-ui.button 
                                variant="ghost" 
                                href="{{ route('departures.show', $departure) }}"
                            >
                                Rezygnuj
                            </x-ui.button>
                        </div>
                    </form>
                @else
                    <!-- Przypisania do usunięcia -->
                    <x-ui.alert type="warning" icon="exclamation-triangle">
                        <strong>Uwaga!</strong> Anulowanie tego wyjazdu spowoduje automatyczne usunięcie poniższych przypisań:
                    </x-ui.alert>

                    @if($affectedProjectAssignments->isNotEmpty())
                        <h5 class="fw-semibold mb-3 mt-4">
                            <i class="bi bi-briefcase text-primary me-2"></i>
                            Przypisania do projektów ({{ $affectedProjectAssignments->count() }})
                        </h5>
                        <div class="table-responsive mb-4">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Pracownik</th>
                                        <th>Projekt</th>
                                        <th>Rola</th>
                                        <th>Data rozpoczęcia</th>
                                        <th>Data zakończenia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($affectedProjectAssignments as $assignment)
                                        <tr>
                                            <td>
                                                <strong>{{ $assignment->employee->full_name }}</strong>
                                            </td>
                                            <td>
                                                {{ $assignment->project->name }}
                                                <small class="text-muted d-block">
                                                    {{ $assignment->project->location->name }}
                                                </small>
                                            </td>
                                            <td>
                                                <x-ui.badge variant="accent">
                                                    {{ $assignment->role->name }}
                                                </x-ui.badge>
                                            </td>
                                            <td>{{ $assignment->start_date->format('d.m.Y') }}</td>
                                            <td>{{ $assignment->end_date ? $assignment->end_date->format('d.m.Y') : 'Bezterminowo' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if($affectedVehicleAssignments->isNotEmpty())
                        <h5 class="fw-semibold mb-3 mt-4">
                            <i class="bi bi-truck text-warning me-2"></i>
                            Przypisania pojazdów ({{ $affectedVehicleAssignments->count() }})
                        </h5>
                        <div class="table-responsive mb-4">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Pracownik</th>
                                        <th>Pojazd</th>
                                        <th>Data rozpoczęcia</th>
                                        <th>Data zakończenia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($affectedVehicleAssignments as $assignment)
                                        <tr>
                                            <td>
                                                <strong>{{ $assignment->employee->full_name }}</strong>
                                            </td>
                                            <td>
                                                {{ $assignment->vehicle->registration_number }}
                                                <small class="text-muted d-block">
                                                    {{ $assignment->vehicle->brand }} {{ $assignment->vehicle->model }}
                                                </small>
                                            </td>
                                            <td>{{ $assignment->start_date->format('d.m.Y') }}</td>
                                            <td>{{ $assignment->end_date ? $assignment->end_date->format('d.m.Y') : 'Bezterminowo' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if($affectedAccommodationAssignments->isNotEmpty())
                        <h5 class="fw-semibold mb-3 mt-4">
                            <i class="bi bi-house text-success me-2"></i>
                            Przypisania domów ({{ $affectedAccommodationAssignments->count() }})
                        </h5>
                        <div class="table-responsive mb-4">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Pracownik</th>
                                        <th>Dom</th>
                                        <th>Data rozpoczęcia</th>
                                        <th>Data zakończenia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($affectedAccommodationAssignments as $assignment)
                                        <tr>
                                            <td>
                                                <strong>{{ $assignment->employee->full_name }}</strong>
                                            </td>
                                            <td>
                                                {{ $assignment->accommodation->name }}
                                                @if($assignment->accommodation->location)
                                                    <small class="text-muted d-block">
                                                        {{ $assignment->accommodation->location->name }}
                                                    </small>
                                                @endif
                                            </td>
                                            <td>{{ $assignment->start_date->format('d.m.Y') }}</td>
                                            <td>{{ $assignment->end_date ? $assignment->end_date->format('d.m.Y') : 'Bezterminowo' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="alert alert-danger mb-4">
                        <h6 class="fw-bold mb-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Dlaczego te przypisania zostaną usunięte?
                        </h6>
                        <p class="mb-0 small">
                            Anulowanie wyjazdu oznacza, że pracownicy nie dotrą do lokalizacji docelowej ({{ $departure->toLocation->name }}) 
                            w planowanym terminie. Wszystkie powiązane przypisania (projekty, pojazdy, zakwaterowanie) zostaną <strong>trwale usunięte</strong>, 
                            ponieważ pracownicy nie będą fizycznie dostępni do ich realizacji.
                        </p>
                    </div>

                    @php
                        $totalAffected = $affectedProjectAssignments->count() + 
                                       $affectedVehicleAssignments->count() + 
                                       $affectedAccommodationAssignments->count();
                    @endphp

                    <form method="POST" action="{{ route('departures.cancel', $departure) }}">
                        @csrf
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input 
                                    class="form-check-input" 
                                    type="checkbox" 
                                    id="accept_consequences" 
                                    name="accept_consequences" 
                                    required
                                >
                                <label class="form-check-label" for="accept_consequences">
                                    <strong>Akceptuję konsekwencje anulacji</strong> - 
                                    rozumiem, że wyjazd zostanie anulowany, a <strong>{{ $totalAffected }}</strong> 
                                    {{ $totalAffected === 1 ? 'przypisanie zostanie trwale usunięte' : 'przypisania zostaną trwale usunięte' }}
                                </label>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <x-ui.button 
                                variant="danger" 
                                type="submit"
                            >
                                <i class="bi bi-x-circle me-1"></i>
                                Anuluj Wyjazd i Usuń Przypisania ({{ $totalAffected }})
                            </x-ui.button>
                            <x-ui.button 
                                variant="ghost" 
                                href="{{ route('departures.show', $departure) }}"
                            >
                                Rezygnuj
                            </x-ui.button>
                        </div>
                    </form>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
