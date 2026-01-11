<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 text-dark mb-0">
            Przygotowanie Zjazdu
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h3 class="fs-5 fw-bold mb-3">Podsumowanie przygotowania zjazdu</h3>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Data zjazdu:</strong> {{ $preparation->returnDate->format('d.m.Y') }}</p>
                            <p><strong>Pracownicy:</strong></p>
                            <ul>
                                @foreach($preparation->employeeIds as $employeeId)
                                    <li>{{ $employeeNames[$employeeId] ?? "ID: {$employeeId}" }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="col-md-6">
                            @if($returnVehicle)
                                <p><strong>Pojazd powrotny:</strong> {{ $returnVehicle->registration_number }} - {{ $returnVehicle->brand }} {{ $returnVehicle->model }}</p>
                            @else
                                <p><strong>Pojazd powrotny:</strong> Nie wybrano</p>
                            @endif
                        </div>
                    </div>

                    <!-- Przypisania do skrócenia -->
                    <div class="mb-4">
                        <h4 class="fs-6 fw-bold mb-3">Przypisania, które zostaną skrócone ({{ $preparation->assignmentsToShorten->count() }})</h4>
                        @if($preparation->assignmentsToShorten->isEmpty())
                            <p class="text-muted">Brak przypisań do skrócenia.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Osoba</th>
                                            <th>Typ przypisania</th>
                                            <th>Obecna data końcowa</th>
                                            <th>Nowa data końcowa</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($preparation->assignmentsToShorten as $assignmentToShorten)
                                            <tr>
                                                <td>{{ $assignmentToShorten->assignment->getEmployee()->full_name }}</td>
                                                <td>
                                                    @if($assignmentToShorten->assignment instanceof \App\Models\ProjectAssignment)
                                                        Projekt
                                                    @elseif($assignmentToShorten->assignment instanceof \App\Models\AccommodationAssignment)
                                                        Dom
                                                    @elseif($assignmentToShorten->assignment instanceof \App\Models\VehicleAssignment)
                                                        Auto
                                                    @else
                                                        {{ class_basename($assignmentToShorten->assignment) }}
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $assignmentToShorten->currentEndDate 
                                                        ? $assignmentToShorten->currentEndDate->format('d.m.Y') 
                                                        : 'Brak (bezterminowe)' }}
                                                </td>
                                                <td class="fw-bold text-primary">
                                                    {{ $assignmentToShorten->newEndDate->format('d.m.Y') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <!-- Konflikty -->
                    @if($preparation->conflicts->isNotEmpty())
                        <div class="mb-4">
                            <h4 class="fs-6 fw-bold mb-3 text-danger">
                                Konflikty ({{ $preparation->conflicts->count() }})
                            </h4>
                            <div class="alert alert-warning">
                                <p class="fw-bold mb-2">Uwaga! Wykryto konflikty, które mogą uniemożliwić wykonanie zjazdu:</p>
                                <ul class="mb-0">
                                    @foreach($preparation->conflicts as $conflict)
                                        <li>{{ $conflict->message }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <!-- Komunikat o konsekwencjach -->
                    <div class="alert alert-info mb-4">
                        <h5 class="fw-bold mb-2">Konsekwencje zjazdu:</h5>
                        <ul class="mb-0">
                            <li>Wszystkie aktywne przypisania wskazanych osób do projektów, domów i aut zostaną skrócone do daty zjazdu</li>
                            @if(isset($isEditMode) && $isEditMode)
                                <li>Zostanie zaktualizowane zdarzenie domenowe "Zjazd"</li>
                            @else
                                <li>Zostanie utworzone zdarzenie domenowe "Zjazd" jako fakt biznesowy</li>
                            @endif
                            @if($returnVehicle)
                                <li>Pojazd powrotny zostanie przypisany do osób z zjazdu</li>
                                <li>Pojazd powrotny nie może mieć aktywnych przypisań po dacie zjazdu dla osób NIE objętych zjazdem</li>
                            @endif
                        </ul>
                    </div>

                    <!-- Formularz akceptacji -->
                    @if($preparation->isValid)
                        <form method="POST" action="{{ route('return-trips.store') }}">
                            @csrf
                            <input type="hidden" name="notes" value="{{ $validated['notes'] ?? '' }}">
                            @if(isset($isEditMode) && $isEditMode && isset($returnTripId))
                                <input type="hidden" name="return_trip_id" value="{{ $returnTripId }}">
                            @endif
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="accept_consequences" id="accept_consequences" value="1" required>
                                    <label class="form-check-label" for="accept_consequences">
                                        <strong>Akceptuję konsekwencje zjazdu</strong> - rozumiem, że przypisania zostaną skrócone zgodnie z powyższym podsumowaniem
                                    </label>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                @if(isset($isEditMode) && $isEditMode && isset($returnTripId))
                                    <a href="{{ route('return-trips.edit', $returnTripId) }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left"></i> Wróć do edycji
                                    </a>
                                @else
                                    <a href="{{ route('return-trips.create') }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left"></i> Wróć do formularza
                                    </a>
                                @endif
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Zatwierdź Zjazd
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-danger">
                            <h5 class="fw-bold mb-2">Zjazd nie może zostać wykonany!</h5>
                            <p class="mb-0">Istnieją konflikty, które uniemożliwiają wykonanie zjazdu. Proszę je rozwiązać przed kontynuowaniem.</p>
                        </div>
                        @if(isset($isEditMode) && $isEditMode && isset($returnTripId))
                            <a href="{{ route('return-trips.edit', $returnTripId) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Wróć do edycji
                            </a>
                        @else
                            <a href="{{ route('return-trips.create') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Wróć do formularza
                            </a>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
