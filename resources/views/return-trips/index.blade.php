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
        <form method="GET" action="{{ route('return-trips.index') }}" class="mb-3 js-auto-submit">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Uczestnik</label>
                    <input
                        type="text"
                        name="employee_search"
                        value="{{ $employeeSearch ?? '' }}"
                        class="form-control js-debounced"
                        placeholder="Wpisz imię/nazwisko/telefon..."
                        autocomplete="off"
                    >
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Typ transportu</label>
                    <select name="transport" class="form-select">
                        <option value="">— dowolny —</option>
                        <option value="vehicle" @selected(($transport ?? '') === 'vehicle')>Pojazd firmowy</option>
                        <option value="no_vehicle" @selected(($transport ?? '') === 'no_vehicle')>Bez pojazdu</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Pojazd</label>
                    <select name="vehicle_id" class="form-select">
                        <option value="">— dowolny —</option>
                        <option value="none" @selected(($vehicleFilter ?? '') === 'none')>Bez pojazdu</option>
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}" @selected((string)$v->id === (string)($vehicleFilter ?? ''))>
                                {{ $v->registration_number }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <input type="hidden" name="dir" value="{{ $dir }}">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        Filtruj
                    </button>
                    <a href="{{ route('return-trips.index') }}" class="btn btn-sm btn-outline-secondary w-100">
                        Wyczyść
                    </a>
                </div>
            </div>
        </form>

        @php
            $sortHref = function (string $column) use ($sort, $dir) {
                $nextDir = ($sort === $column) ? ($dir === 'asc' ? 'desc' : 'asc') : 'desc';

                return route('return-trips.index', array_merge(request()->except('page'), ['sort' => $column, 'dir' => $nextDir]));
            };
        @endphp
        @if($returnTrips->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th class="text-nowrap">
                                <a href="{{ $sortHref('id') }}" class="text-decoration-none text-reset">
                                    ID
                                    @if($sort === 'id')
                                        <i class="bi bi-sort-{{ $dir === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="text-nowrap">
                                <a href="{{ $sortHref('event_date') }}" class="text-decoration-none text-reset">
                                    Data
                                    @if($sort === 'event_date')
                                        <i class="bi bi-sort-{{ $dir === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th>Pojazd</th>
                            <th>Z</th>
                            <th>Do</th>
                            <th>Uczestnicy</th>
                            <th>Status</th>
                            <th class="text-nowrap">
                                <a href="{{ $sortHref('created_at') }}" class="text-decoration-none text-reset">
                                    Utworzono
                                    @if($sort === 'created_at')
                                        <i class="bi bi-sort-{{ $dir === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($returnTrips as $trip)
                            @php
                                $uniqueParticipants = $trip->participants
                                    ->filter(fn ($p) => $p->employee)
                                    ->unique('employee_id')
                                    ->values();
                                $uniqueParticipantsCount = $uniqueParticipants->count();
                            @endphp
                            <tr>
                                <td class="text-muted small">{{ $trip->id }}</td>
                                <td>{{ $trip->event_date->format('Y-m-d') }}</td>
                                <td>{{ $trip->vehicle ? $trip->vehicle->registration_number : '-' }}</td>
                                <td>
                                    @php
                                        $fromAccommodationLocations = $trip->participants
                                            ->filter(fn ($p) => $p->assignment_type === 'accommodation_assignment' && $p->assignment?->accommodation?->location)
                                            ->map(fn ($p) => $p->assignment->accommodation->location)
                                            ->unique('id')
                                            ->values();
                                    @endphp

                                    @if($fromAccommodationLocations->isNotEmpty())
                                        <div class="d-flex flex-column gap-1">
                                            @foreach($fromAccommodationLocations->take(3) as $loc)
                                                <div class="small">
                                                    <span class="fw-semibold">{{ $loc->name }}</span>
                                                    @if(!empty($loc->city))
                                                        <span class="text-muted">({{ $loc->city }})</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                            @if($fromAccommodationLocations->count() > 3)
                                                <small class="text-muted">+{{ $fromAccommodationLocations->count() - 3 }} więcej</small>
                                            @endif
                                        </div>
                                    @else
                                        {{ $trip->fromLocation?->name ?? '—' }}
                                    @endif
                                </td>
                                <td>{{ $trip->toLocation->name }}</td>
                                <td>
                                    @if($uniqueParticipantsCount > 0)
                                        <div class="d-flex flex-column gap-1">
                                            @foreach($uniqueParticipants as $participant)
                                                @if($participant->employee)
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="bi bi-person text-primary"></i>
                                                        <x-employee-cell
                                                            :employee="$participant->employee"
                                                            :avatar-size="'24px'"
                                                            :show-phone="false"
                                                            :name-class="'small'"
                                                        />
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        if ($trip->status === \App\Enums\LogisticsEventStatus::CANCELLED) {
                                            $statusLabel = 'Anulowany';
                                            $badgeVariant = 'danger';
                                        } else {
                                            // Sprawdź czy data zjazdu jest w przeszłości czy przyszłości
                                            $endDate = $trip->end_date ?? $trip->event_date;
                                            if ($endDate->isPast()) {
                                                $statusLabel = 'Zakończony';
                                                $badgeVariant = 'secondary';
                                            } else {
                                                $statusLabel = 'Zaplanowany';
                                                $badgeVariant = 'info';
                                            }
                                        }
                                    @endphp
                                    <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $trip->created_at?->format('d.m.Y H:i') ?? '—' }}</small>
                                </td>
                                <td>
                                    <x-action-buttons
                                        viewRoute="{{ route('return-trips.show', $trip) }}"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($returnTrips->hasPages())
                <div class="mt-3">
                    <x-ui.pagination :paginator="$returnTrips" />
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

    @push('scripts')
    <script>
        (function () {
            const form = document.querySelector('form.js-auto-submit');
            if (!form) return;
            let t = null;
            form.querySelectorAll('.js-debounced').forEach((el) => {
                el.addEventListener('input', () => {
                    if (t) clearTimeout(t);
                    t = setTimeout(() => form.submit(), 300);
                });
            });
        })();
    </script>
    @endpush
</x-app-layout>
