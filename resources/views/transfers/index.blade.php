<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Transfery">
            <x-slot name="right">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('transfers.create') }}"
                    routeName="transfers.create"
                    action="create"
                >
                    <i class="bi bi-plus-lg me-1"></i> Utwórz transfer
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-alert type="success" dismissible icon="check-circle">{{ session('success') }}</x-alert>
    @endif

    <x-ui.card>
        <form method="GET" action="{{ route('transfers.index') }}" class="mb-3 js-auto-submit">
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
                    <a href="{{ route('transfers.index') }}" class="btn btn-sm btn-outline-secondary w-100">
                        Wyczyść
                    </a>
                </div>
            </div>
        </form>

        @php
            $sortHref = function (string $column) use ($sort, $dir) {
                $nextDir = ($sort === $column) ? ($dir === 'asc' ? 'desc' : 'asc') : 'desc';

                return route('transfers.index', array_merge(request()->except('page'), ['sort' => $column, 'dir' => $nextDir]));
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
                                Data
                                @if($sort === 'event_date')
                                    <i class="bi bi-sort-{{ $dir === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </a>
                        </th>
                        <th class="text-start">Trasa</th>
                        <th class="text-start">Rodzaj</th>
                        <th class="text-start">Pojazd</th>
                        <th class="text-start">Uczestnicy</th>
                        <th class="text-start">Kierowca / Wynagrodzenie</th>
                        <th class="text-start">Status</th>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $transfer)
                        <tr>
                            <td class="text-muted small">{{ $transfer->id }}</td>
                            <td>
                                <div class="fw-semibold">{{ $transfer->event_date->format('d.m.Y') }}</div>
                                <small class="text-muted">{{ $transfer->event_date->format('H:i') }}</small>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <div>
                                        <small class="text-muted d-block">Z:</small>
                                        <div>{{ $transfer->fromLocation?->name ?? '—' }}</div>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">Do:</small>
                                        <div>{{ $transfer->toLocation?->name ?? '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($transfer->has_reassignment)
                                    <x-ui.badge variant="info">
                                        <i class="bi bi-arrow-left-right me-1"></i> Przeniesienie
                                    </x-ui.badge>
                                @else
                                    <x-ui.badge variant="secondary">
                                        <i class="bi bi-truck-front me-1"></i> Przejazd
                                    </x-ui.badge>
                                @endif
                            </td>
                            <td>
                                @if($transfer->vehicle)
                                    <div class="fw-semibold">{{ $transfer->vehicle->registration_number }}</div>
                                    @if($transfer->vehicle->brand || $transfer->vehicle->model)
                                        <small class="text-muted">{{ trim($transfer->vehicle->brand . ' ' . $transfer->vehicle->model) }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $uniqueParticipants = $transfer->participants
                                        ->filter(fn ($p) => $p->employee)
                                        ->unique('employee_id')
                                        ->values();
                                    $uniqueParticipantsCount = $uniqueParticipants->count();
                                @endphp

                                @if($uniqueParticipantsCount > 0)
                                    <div class="d-flex flex-column gap-1">
                                        @foreach($uniqueParticipants as $participant)
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-person text-primary"></i>
                                                <x-employee-cell
                                                    :employee="$participant->employee"
                                                    :avatar-size="'24px'"
                                                    :show-phone="false"
                                                    :name-class="'small'"
                                                />
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $driverAdj = $transfer->driverAdjustments->first();
                                @endphp
                                @if($driverAdj)
                                    <div class="small">
                                        <div class="fw-semibold">{{ $driverAdj->employee?->full_name }}</div>
                                        <div class="text-success">{{ number_format($driverAdj->amount, 2) }} {{ $driverAdj->currency }}</div>
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $visualStatus = $transfer->getVisualStatus();
                                    $badgeVariant = match($visualStatus) {
                                        'oczekuje' => 'primary',
                                        'w trakcie' => 'warning',
                                        'zakończone' => 'success',
                                        'anulowany' => 'danger',
                                        default => 'accent'
                                    };
                                @endphp
                                <x-ui.badge variant="{{ $badgeVariant }}">{{ ucfirst($visualStatus) }}</x-ui.badge>
                                @if($transfer->has_reassignment)
                                    <x-ui.badge variant="info"><i class="bi bi-arrow-left-right"></i> Przeniesienie</x-ui.badge>
                                @endif
                            </td>
                            <td class="text-end">
                                <x-ui.button variant="ghost" href="{{ route('transfers.show', $transfer) }}" class="btn-sm">
                                    <i class="bi bi-eye"></i>
                                    <span class="d-none d-sm-inline ms-1">Zobacz</span>
                                </x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-state
                            icon="arrow-left-right"
                            message="Brak transferów"
                            :in-table="true"
                            colspan="7"
                        />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transfers->hasPages())
            <div class="mt-3 pt-3 border-top">
                {{ $transfers->links('pagination::bootstrap-5') }}
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
