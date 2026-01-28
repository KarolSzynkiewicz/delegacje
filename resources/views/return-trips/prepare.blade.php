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
                        @php
                            // Grupuj przypisania po osobie i typie przypisania, aby uniknąć duplikatów
                            $groupedAssignments = $preparation->assignmentsToShorten->groupBy(function($item) {
                                $employeeId = $item->assignment->employee->id;
                                $type = match(true) {
                                    $item->assignment instanceof \App\Models\ProjectAssignment => 'Projekt',
                                    $item->assignment instanceof \App\Models\AccommodationAssignment => 'Dom',
                                    $item->assignment instanceof \App\Models\VehicleAssignment => 'Auto',
                                    default => class_basename($item->assignment),
                                };
                                return "{$employeeId}_{$type}";
                            });
                            
                            // Dla każdej grupy weź pierwsze przypisanie (reprezentatywne)
                            $uniqueAssignments = $groupedAssignments->map(function($group) {
                                return $group->first();
                            });
                        @endphp
                        <h4 class="fs-6 fw-bold mb-3">
                            Przypisania, które zostaną skrócone 
                            <span class="text-muted small">({{ $preparation->assignmentsToShorten->count() }} przypisań, {{ $uniqueAssignments->count() }} unikalnych kombinacji osoba+typ)</span>
                        </h4>
                        @if($preparation->assignmentsToShorten->isEmpty())
                            <p class="text-muted">Brak przypisań do skrócenia.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Osoba</th>
                                            <th>Typ przypisania</th>
                                            <th>Obecna data końcowa</th>
                                            <th>Nowa data końcowa</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($uniqueAssignments as $assignmentToShorten)
                                            @php
                                                $employee = $assignmentToShorten->assignment->employee;
                                                $type = match(true) {
                                                    $assignmentToShorten->assignment instanceof \App\Models\ProjectAssignment => 'Projekt',
                                                    $assignmentToShorten->assignment instanceof \App\Models\AccommodationAssignment => 'Dom',
                                                    $assignmentToShorten->assignment instanceof \App\Models\VehicleAssignment => 'Auto',
                                                    default => class_basename($assignmentToShorten->assignment),
                                                };
                                                
                                                // Policz ile przypisań tego typu ma ta osoba
                                                $countForThisType = $preparation->assignmentsToShorten->filter(function($item) use ($employee, $type) {
                                                    $itemType = match(true) {
                                                        $item->assignment instanceof \App\Models\ProjectAssignment => 'Projekt',
                                                        $item->assignment instanceof \App\Models\AccommodationAssignment => 'Dom',
                                                        $item->assignment instanceof \App\Models\VehicleAssignment => 'Auto',
                                                        default => class_basename($item->assignment),
                                                    };
                                                    return $item->assignment->employee->id === $employee->id && $itemType === $type;
                                                })->count();
                                            @endphp
                                            <tr>
                                                <td>
                                                    {{ $employee->full_name }}
                                                    @if($countForThisType > 1)
                                                        <span class="badge bg-secondary ms-2" title="{{ $countForThisType }} przypisań tego typu">{{ $countForThisType }}x</span>
                                                    @endif
                                                </td>
                                                <td>{{ $type }}</td>
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
                        @php
                            $blockingConflicts = $preparation->conflicts->where('isBlocking', true);
                            $nonBlockingConflicts = $preparation->conflicts->where('isBlocking', false);
                        @endphp
                        
                        @if($blockingConflicts->isNotEmpty())
                            <div class="mb-4">
                                <h4 class="fs-6 fw-bold mb-3 text-danger">
                                    Konflikty blokujące ({{ $blockingConflicts->count() }})
                                </h4>
                                <div class="alert alert-danger">
                                    <p class="fw-bold mb-2">Uwaga! Wykryto konflikty, które uniemożliwiają wykonanie zjazdu:</p>
                                    <ul class="mb-0">
                                        @foreach($blockingConflicts as $conflict)
                                            <li>{{ $conflict->message }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif
                        
                        @if($nonBlockingConflicts->isNotEmpty())
                            <div class="mb-4">
                                <h4 class="fs-6 fw-bold mb-3 text-warning">
                                    Ostrzeżenia ({{ $nonBlockingConflicts->count() }})
                                </h4>
                                <div class="alert alert-warning">
                                    <p class="fw-bold mb-2">Uwaga! Wykryto sytuacje wymagające uwagi:</p>
                                    <ul class="mb-0">
                                        @foreach($nonBlockingConflicts as $conflict)
                                            <li>{{ $conflict->message }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif
                    @endif

                    <!-- Komunikat o konsekwencjach -->
                    <div class="alert alert-info mb-4">
                        <h5 class="fw-bold mb-2">
                            @if(isset($isEditMode) && $isEditMode)
                                Konsekwencje edycji zjazdu:
                            @else
                                Konsekwencje zjazdu:
                            @endif
                        </h5>
                        <ul class="mb-0">
                            @if(isset($isEditMode) && $isEditMode)
                                <li><strong>Poprzednie zmiany zostaną cofnięte</strong> - przypisania wrócą do poprzednich dat końcowych</li>
                                <li>Nowe zmiany zostaną zastosowane zgodnie z wybranymi danymi</li>
                            @endif
                            <li>Wszystkie aktywne przypisania wskazanych osób do projektów, domów i aut zostaną skrócone do daty zjazdu</li>
                            @if(isset($isEditMode) && $isEditMode)
                                <li>Zostanie zaktualizowane zdarzenie domenowe "Zjazd"</li>
                            @else
                                <li>Zostanie utworzone zdarzenie domenowe "Zjazd" jako fakt biznesowy</li>
                            @endif
                            @if($returnVehicle)
                                <li>Pojazd powrotny zostanie przypisany do osób z zjazdu</li>
                                @php
                                    $nonBlockingConflicts = $preparation->conflicts->where('isBlocking', false);
                                @endphp
                                @if($nonBlockingConflicts->isNotEmpty())
                                    <li><strong>Przypisania do auta powrotnego dla osób NIE objętych zjazdem zostaną anulowane</strong> - te osoby zostaną bez auta</li>
                                @else
                                    <li>Pojazd powrotny nie może mieć aktywnych przypisań po dacie zjazdu dla osób NIE objętych zjazdem</li>
                                @endif
                            @endif
                        </ul>
                    </div>

                    <!-- Formularz akceptacji -->
                    @if($preparation->isValid)
                        <form method="POST" action="{{ route('return-trips.store') }}">
                            @csrf
                            <input type="hidden" name="notes" value="{{ $validated['notes'] ?? '' }}">
                            @if(isset($validated['status']))
                                <input type="hidden" name="status" value="{{ $validated['status'] }}">
                            @endif
                            @if(isset($isEditMode) && $isEditMode && isset($returnTripId))
                                <input type="hidden" name="return_trip_id" value="{{ $returnTripId }}">
                            @endif
                            
                            <div class="mb-3">
                                <x-ui.input 
                                    type="checkbox" 
                                    name="accept_consequences" 
                                    id="accept_consequences"
                                    label="<strong>Akceptuję konsekwencje zjazdu</strong> - rozumiem, że przypisania zostaną skrócone zgodnie z powyższym podsumowaniem"
                                    required
                                />
                            </div>

                            <div class="d-flex gap-2">
                                @if(isset($isEditMode) && $isEditMode && isset($returnTripId))
                                    <x-ui.button variant="ghost" href="{{ route('return-trips.edit', $returnTripId) }}">
                                        <i class="bi bi-arrow-left"></i> Wróć do edycji
                                    </x-ui.button>
                                @else
                                    <x-ui.button variant="ghost" href="{{ route('return-trips.create') }}">
                                        <i class="bi bi-arrow-left"></i> Wróć do formularza
                                    </x-ui.button>
                                @endif
                                <x-ui.button variant="success" type="submit">
                                    <i class="bi bi-check-circle"></i> Zatwierdź Zjazd
                                </x-ui.button>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-danger">
                            <h5 class="fw-bold mb-2">Zjazd nie może zostać wykonany!</h5>
                            <p class="mb-0">Istnieją konflikty, które uniemożliwiają wykonanie zjazdu. Proszę je rozwiązać przed kontynuowaniem.</p>
                        </div>
                        @if(isset($isEditMode) && $isEditMode && isset($returnTripId))
                            <x-ui.button variant="ghost" href="{{ route('return-trips.edit', $returnTripId) }}">
                                <i class="bi bi-arrow-left"></i> Wróć do edycji
                            </x-ui.button>
                        @else
                            <x-ui.button variant="ghost" href="{{ route('return-trips.create') }}">
                                <i class="bi bi-arrow-left"></i> Wróć do formularza
                            </x-ui.button>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
