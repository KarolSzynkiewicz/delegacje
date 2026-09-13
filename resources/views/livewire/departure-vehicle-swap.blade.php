<div class="d-flex flex-column gap-4">
    @if($blockers !== [])
        <x-ui.alert variant="danger" title="Nie można zmienić auta" icon="bi-slash-circle">
            <ul class="mb-0 ps-3">
                @foreach($blockers as $blocker)
                    <li>{{ $blocker }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @else
        <x-ui.card label="Nowe auto" class="mb-0">
            <p class="small text-muted mb-3">
                Data wyjazdu ({{ $departure->event_date->format('d.m.Y') }}) i daty uczestników zostają.
                Kierowca i rozkład foteli wyjazdu bez zmian. Lista zawiera tylko pojazdy, które w dniu wyjazdu są w bazie.
            </p>

            <label class="form-label" for="swap-vehicle">Pojazd w bazie na dzień wyjazdu</label>
            @php
                $vehicleSelectClass = $errors->has('newVehicleId') ? 'form-select is-invalid' : 'form-select';
            @endphp
            <select id="swap-vehicle"
                    class="{{ $vehicleSelectClass }}"
                    wire:model.live="newVehicleId">
                <option value="">— wybierz —</option>
                @foreach($candidates as $vehicle)
                    <option value="{{ $vehicle->id }}">
                        {{ $vehicle->registration_number }}
                        — {{ $vehicle->brand }} {{ $vehicle->model }}
                        ({{ $vehicle->capacity ? $vehicle->capacity.' os.' : 'brak pojemności' }})
                    </option>
                @endforeach
            </select>
            @error('newVehicleId')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            @if($candidates->isEmpty())
                <p class="small text-warning mt-2 mb-0">Brak innych pojazdów w bazie wolnych na odcinek przejazdu.</p>
            @endif
        </x-ui.card>

        @if($preview['from'])
            <x-ui.card label="Podsumowanie" class="mb-0">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="text-muted small text-uppercase mb-2">Było</h6>
                        <p class="fw-semibold mb-1">{{ $preview['from']['label'] }}</p>
                        <p class="small text-muted mb-0">
                            {{ $preview['participant_count'] }} os.
                            · kierowca:
                            {{ $preview['driver']['type'] === 'internal' ? ($preview['driver']['name'] ?? '—') : 'zewnętrzny' }}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted small text-uppercase mb-2">Będzie</h6>
                        @if($preview['to'])
                            <p class="fw-semibold mb-1">{{ $preview['to']['label'] }}</p>
                            <p class="small text-muted mb-0">
                                {{ $preview['participant_count'] }} os.
                                · pojemność {{ $preview['to']['capacity'] ?? '—' }}
                                · kierowca bez zmian
                            </p>
                        @else
                            <p class="text-muted mb-0">Wybierz pojazd, żeby zobaczyć skutki.</p>
                        @endif
                    </div>
                </div>
                <p class="small text-muted mt-3 mb-0">Data wyjazdu: bez zmian ({{ $departure->event_date->format('d.m.Y H:i') }}).</p>
            </x-ui.card>
        @endif

        @if($preview['participant_assignments'] !== [])
            <x-ui.card label="Przypisania uczestników (zostaną przepisane)" class="mb-0">
                <p class="small text-muted mb-3">
                    Jadą tym wyjazdem i po przyjeździe mają auto ekipy. Daty bez zmian — zmienia się tylko pojazd.
                </p>
                <div class="table-responsive rounded-3 border" style="border-color: rgba(255,255,255,0.08) !important;">
                    <table class="table table-hover departure-participants-table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="text-uppercase small text-muted fw-semibold py-3 ps-3">Osoba</th>
                                <th class="text-uppercase small text-muted fw-semibold py-3">Okres</th>
                                <th class="text-uppercase small text-muted fw-semibold py-3">Było</th>
                                <th class="text-uppercase small text-muted fw-semibold py-3 pe-3">Będzie</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preview['participant_assignments'] as $row)
                                <tr>
                                    <td class="ps-3 py-3" data-label="Osoba">
                                        <span class="fw-semibold">{{ $row['name'] }}</span>
                                        <span class="d-block small text-muted">{{ $row['position'] }}</span>
                                    </td>
                                    <td class="py-3 font-mono small" data-label="Okres">{{ $row['start'] }} – {{ $row['end'] }}</td>
                                    <td class="py-3" data-label="Było">{{ $preview['from']['label'] }}</td>
                                    <td class="py-3 pe-3" data-label="Będzie">
                                        @if($preview['to'])
                                            {{ $preview['to']['label'] }}
                                        @else
                                            <span class="text-muted">wybierz auto</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        @endif

        @if($preview['external_assignments'] !== [])
            <x-ui.card label="A co z nimi?" class="mb-0">
                <x-ui.alert variant="warning" title="Też siedzą na poprzednim aucie" icon="bi-person-exclamation" class="mb-3">
                    Nie jadą tym wyjazdem, ale mają przypisanie do tego samego auta co ekipa (dopisane ręcznie).
                    Zaznacz, kto ma przejść na nowe auto. Niezaznaczeni zostają na starym.
                </x-ui.alert>
                <div class="table-responsive rounded-3 border" style="border-color: rgba(255,255,255,0.08) !important;">
                    <table class="table table-hover departure-participants-table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="text-uppercase small text-muted fw-semibold py-3 ps-3" style="width: 2.5rem;"></th>
                                <th class="text-uppercase small text-muted fw-semibold py-3">Osoba</th>
                                <th class="text-uppercase small text-muted fw-semibold py-3">Okres</th>
                                <th class="text-uppercase small text-muted fw-semibold py-3">Było</th>
                                <th class="text-uppercase small text-muted fw-semibold py-3 pe-3">Będzie</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preview['external_assignments'] as $row)
                                @php
                                    $moveThis = in_array((string) $row['id'], array_map('strval', $confirmedExternalAssignmentIds), true);
                                @endphp
                                <tr>
                                    <td class="ps-3 py-3" data-label="Przepnij">
                                        <input type="checkbox"
                                               class="form-check-input"
                                               value="{{ $row['id'] }}"
                                               wire:model.live="confirmedExternalAssignmentIds"
                                               aria-label="Przepnij {{ $row['name'] }} na nowe auto">
                                    </td>
                                    <td class="py-3" data-label="Osoba">
                                        <span class="fw-semibold">{{ $row['name'] }}</span>
                                        <span class="d-block small text-muted">{{ $row['position'] }}</span>
                                    </td>
                                    <td class="py-3 font-mono small" data-label="Okres">{{ $row['start'] }} – {{ $row['end'] }}</td>
                                    <td class="py-3" data-label="Było">{{ $preview['from']['label'] }}</td>
                                    <td class="py-3 pe-3" data-label="Będzie">
                                        @if(! $preview['to'])
                                            <span class="text-muted">wybierz auto</span>
                                        @elseif($moveThis && $row['wider'] && $row['overlap_start'])
                                            {{ $preview['to']['label'] }}
                                            <span class="d-block small text-warning">
                                                tylko {{ $row['overlap_start'] }} – {{ $row['overlap_end'] }};
                                                reszta zostaje na {{ $preview['from']['label'] }}
                                            </span>
                                        @elseif($moveThis)
                                            {{ $preview['to']['label'] }}
                                        @else
                                            zostaje na {{ $preview['from']['label'] }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        @endif

        @if($preview['warnings'] !== [])
            <x-ui.alert variant="warning" title="Sprawdź przed zatwierdzeniem">
                <ul class="mb-0 ps-3">
                    @foreach($preview['warnings'] as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        @if($preview['blockers'] !== [] && $newVehicleId)
            <x-ui.alert variant="danger" title="Podmiana zablokowana">
                <ul class="mb-0 ps-3">
                    @foreach($preview['blockers'] as $blocker)
                        <li>{{ $blocker }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <div class="d-flex flex-wrap gap-2">
            <button
                type="button"
                class="btn btn-primary"
                wire:click="confirm"
                wire:loading.attr="disabled"
                @if(! $canConfirm)
                    disabled
                @endif
            >
                <i class="bi bi-check-circle me-1"></i>
                Zatwierdź podmianę
            </button>
            <x-ui.button variant="ghost" href="{{ route('departures.show', $departure) }}" action="back">
                Anuluj
            </x-ui.button>
        </div>
    @endif
</div>
