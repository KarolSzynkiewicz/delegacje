<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Przygotowanie rejestracji serwisu">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('vehicle-repairs.create', ['vehicle_id' => $draft['vehicle_id'] ?? null]) }}" action="back">
                    Wróć do formularza
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="py-2">
        <div class="container-xxl">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <x-ui.card label="Podsumowanie rejestracji serwisu">
                        <h3 class="fs-5 fw-bold mb-3">Dane serwisu</h3>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Pojazd:</strong> {{ $vehicle->registration_number }}
                                    @if($vehicle->brand)
                                        — {{ $vehicle->brand }} {{ $vehicle->model }}
                                    @endif
                                </p>
                                <p class="mb-2"><strong>Typ akcji serwisowej:</strong>
                                    <x-ui.badge variant="{{ $actionType->badgeVariant() }}">{{ $actionType->label() }}</x-ui.badge>
                                </p>
                                <p class="mb-2"><strong>Data oddania do warsztatu:</strong> {{ $repairStart->format('d.m.Y') }}</p>
                                @if(!empty($draft['end_date']))
                                    <p class="mb-2"><strong>Planowana data odbioru:</strong> {{ \Carbon\Carbon::parse($draft['end_date'])->format('d.m.Y') }}</p>
                                @endif
                                @if(!empty($draft['price']))
                                    <p class="mb-2"><strong>Koszt (wstępny):</strong> {{ number_format((float) $draft['price'], 2) }} {{ $draft['currency'] ?? '' }}</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Warsztat:</strong> {{ $workshopSummary }}</p>
                                @if(!empty($draft['notes']))
                                    <p class="mb-0"><strong>Notatki:</strong> {{ $draft['notes'] }}</p>
                                @endif
                            </div>
                        </div>

                        {{-- Przypisania do skrócenia / usunięcia --}}
                        <div class="mb-4">
                            <h4 class="fs-6 fw-bold mb-3">
                                Przypisania, które zostaną skrócone lub usunięte
                                <span class="text-muted small">({{ $assignmentPreview->count() }})</span>
                            </h4>
                            @if($assignmentPreview->isEmpty())
                                <p class="text-muted mb-0">Brak aktywnych przypisań kierowców/pasażerów do tego pojazdu w dniu oddania — nic nie zostanie zmienione w przypisaniach.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Pracownik</th>
                                                <th>Rola w pojeździe</th>
                                                <th>Data rozpoczęcia</th>
                                                <th>Obecna data końcowa</th>
                                                <th>Co się stanie</th>
                                                <th>Nowa data końcowa</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($assignmentPreview as $row)
                                                @php
                                                    $a = $row->assignment;
                                                    $pos = $a->position;
                                                @endphp
                                                <tr>
                                                    <td>{{ $a->employee?->full_name ?? '—' }}</td>
                                                    <td>
                                                        <x-ui.badge variant="{{ $pos === \App\Enums\VehiclePosition::DRIVER ? 'accent' : 'info' }}">
                                                            {{ $pos->label() }}
                                                        </x-ui.badge>
                                                    </td>
                                                    <td><small>{{ \Carbon\Carbon::parse($a->start_date)->format('d.m.Y') }}</small></td>
                                                    <td>
                                                        <small>
                                                            @if($a->end_date)
                                                                {{ \Carbon\Carbon::parse($a->end_date)->format('d.m.Y') }}
                                                            @else
                                                                <span class="text-muted">bezterminowe</span>
                                                            @endif
                                                        </small>
                                                    </td>
                                                    <td>
                                                        @if($row->action === 'delete')
                                                            <span class="badge bg-danger">Usunięcie przypisania</span>
                                                            <br><small class="text-muted">Zaczyna się w dniu oddania lub później</small>
                                                        @else
                                                            <span class="badge bg-primary">Skrócenie</span>
                                                            <br><small class="text-muted">Koniec ustawiony na dzień przed oddaniem</small>
                                                        @endif
                                                    </td>
                                                    <td class="fw-bold text-primary">
                                                        @if($row->action === 'delete')
                                                            <span class="text-danger">—</span>
                                                        @else
                                                            {{ $row->new_end_date->format('d.m.Y') }}
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        <div class="alert alert-info mb-4" style="background: rgba(59,130,246,0.1); border-color: rgba(59,130,246,0.3); color: var(--text-main);">
                            <h5 class="fw-bold mb-2">Konsekwencje rejestracji serwisu</h5>
                            <ul class="mb-0">
                                <li>Stan techniczny pojazdu zostanie ustawiony na <strong>Warsztat</strong>.</li>
                                <li>Powyższe przypisania osobom do pojazdu zostaną <strong>skrócone do dnia {{ $repairStart->copy()->subDay()->format('d.m.Y') }}</strong> (lub usuń, jeśli zaczynają się w dniu oddania lub później).</li>
                                <li>Nowy wpis w książce serwisowej zostanie zapisany z podanymi danymi.</li>
                            </ul>
                        </div>

                        <form method="POST" action="{{ route('vehicle-repairs.store') }}">
                            @csrf
                            <x-ui.errors />

                            <div class="mb-3">
                                <x-ui.input
                                    type="checkbox"
                                    name="accept_consequences"
                                    id="accept_consequences"
                                    value="1"
                                    label="<strong>Akceptuję konsekwencje serwisu</strong> — rozumiem, że przypisania zostaną skrócone lub usunięte zgodnie z powyższym podsumowaniem oraz że pojazd oznaczony zostanie jako Warsztat."
                                    required
                                />
                            </div>

                            <div class="d-flex gap-2 flex-wrap">
                                <x-ui.button variant="success" type="submit" action="save">
                                    <i class="bi bi-check-circle"></i> Zatwierdź rejestrację serwisu
                                </x-ui.button>
                                <x-ui.button variant="ghost" href="{{ route('vehicle-repairs.create', ['vehicle_id' => $draft['vehicle_id'] ?? null]) }}">
                                    <i class="bi bi-arrow-left"></i> Wróć do formularza
                                </x-ui.button>
                            </div>
                        </form>
                    </x-ui.card>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
