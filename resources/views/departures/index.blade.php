<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Wyjazdy">
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('departures.create-v2') }}"
                    routeName="departures.create-v2"
                    action="create"
                >
                    Utwórz wyjazd
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card>
        <form method="GET" action="{{ route('departures.index') }}" class="mb-3 js-auto-submit">
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
                    <a href="{{ route('departures.index') }}" class="btn btn-sm btn-outline-secondary w-100">
                        Wyczyść
                    </a>
                </div>
            </div>
        </form>

        @php
            $sortHref = function (string $column) use ($sort, $dir) {
                $nextDir = ($sort === $column) ? ($dir === 'asc' ? 'desc' : 'asc') : 'desc';

                return route('departures.index', array_merge(request()->except('page'), ['sort' => $column, 'dir' => $nextDir]));
            };
        @endphp
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th class="text-start text-nowrap">
                            <a href="{{ $sortHref('id') }}" class="text-decoration-none text-reset">
                                ID
                                @if($sort === 'id')
                                    <i class="bi bi-sort-{{ $dir === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </a>
                        </th>
                        <th class="text-start text-nowrap">
                            <a href="{{ $sortHref('event_date') }}" class="text-decoration-none text-reset">
                                Daty
                                @if($sort === 'event_date')
                                    <i class="bi bi-sort-{{ $dir === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </a>
                        </th>
                        <th class="text-start">Trasa</th>
                        <th class="text-start">Pojazd</th>
                        <th class="text-start">Uczestnicy</th>
                        <th class="text-start">Status</th>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($departures as $departure)
                        <tr>
                            <td class="text-muted small">{{ $departure->id }}</td>
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
                                    <span class="text-muted">Transport publiczny</span>
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
                            colspan="7"
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
