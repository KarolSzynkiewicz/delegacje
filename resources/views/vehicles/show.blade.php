<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">Pojazd</h2>
            <x-ui.button variant="ghost" href="{{ route('vehicles.index') }}">Wróć do listy</x-ui.button>
        </div>
    </x-slot>

    <div class="container-xxl veh-page">
        @if (session('success'))
            <x-ui.alert variant="success" title="Sukces" dismissible class="mb-3">
                {{ session('success') }}
            </x-ui.alert>
        @endif

        @if ($errors->any())
            <x-ui.alert variant="danger" title="Błąd" dismissible class="mb-3">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        @php
            $canEditVehicle = auth()->user()?->hasPermission('vehicles.update');
            $condition = \App\Enums\VehicleCondition::tryFrom($vehicle->technical_condition);
            $today = \Carbon\Carbon::today();
            $overduePrzeglad = $vehicle->inspection_valid_to && $vehicle->inspection_valid_to->lt($today);
            $overdueOc = $vehicle->insurance_valid_to && $vehicle->insurance_valid_to->lt($today);
            $overdueAc = $vehicle->ac_wazne_do && $vehicle->ac_wazne_do->lt($today);
            $stationing = $placement['in_transit']
                ? 'W podróży'
                : ($placement['stationing_location'] ?: '—');
            $brandLine = trim(($vehicle->brand ?? '').' '.($vehicle->model ?? ''));
            $emptySeats = max(0, $seatCapacity - $todayCrew->count());
            $tabs = [
                'info' => ['label' => 'Informacje', 'icon' => 'bi bi-info-circle', 'group' => 'Pojazd', 'href' => route('vehicles.show', ['vehicle' => $vehicle, 'tab' => 'info'])],
                'repairs' => ['label' => 'Książka serwisowa', 'icon' => 'bi bi-tools', 'group' => 'Pojazd', 'href' => route('vehicles.show', ['vehicle' => $vehicle, 'tab' => 'repairs'])],
                'assignments' => ['label' => 'Przypisania do pojazdu', 'icon' => 'bi bi-people', 'group' => 'Pojazd', 'href' => route('vehicles.show', ['vehicle' => $vehicle, 'tab' => 'assignments'])],
                'consumptions' => ['label' => 'Rozchody z magazynów', 'icon' => 'bi bi-box-seam', 'group' => 'Pojazd', 'href' => route('vehicles.show', ['vehicle' => $vehicle, 'tab' => 'consumptions'])],
                'logistics' => ['label' => 'Zdarzenia logistyczne', 'icon' => 'bi bi-signpost-split', 'group' => 'Pojazd', 'href' => route('vehicles.show', ['vehicle' => $vehicle, 'tab' => 'logistics'])],
                'lifecycle' => ['label' => 'Cykl życia pojazdu', 'icon' => 'bi bi-arrow-repeat', 'group' => 'Pojazd', 'href' => route('vehicles.show', ['vehicle' => $vehicle, 'tab' => 'lifecycle'])],
            ];
        @endphp

        <div class="emp-shell">
            <article class="card emp-hero {{ $vehicle->isRetired() ? 'emp-hero--out' : 'emp-hero--in' }}">
                <div class="emp-hero__photo-frame">
                    @if($vehicle->image_path)
                        <img src="{{ $vehicle->image_url }}" alt="{{ $vehicle->registration_number }}" class="emp-hero__photo">
                    @else
                        <div class="emp-hero__photo emp-hero__photo--empty emp-hero__photo--plate">{{ $vehicle->registration_number }}</div>
                    @endif
                </div>

                <div class="emp-hero__body">
                    <div class="emp-hero__bar">
                        <div class="emp-hero__status">
                            @if($vehicle->isRetired())
                                <x-ui.badge variant="danger">Wycofany</x-ui.badge>
                                <span class="emp-hero__status-meta">
                                    {{ $vehicle->retired_at->format('Y-m-d') }}
                                    · {{ $vehicle->retirement_reason?->label() }}
                                </span>
                                @if($vehicle->retirement_note)
                                    <span class="emp-hero__status-note">{{ $vehicle->retirement_note }}</span>
                                @endif
                            @else
                                <x-ui.badge variant="success">W flocie</x-ui.badge>
                            @endif
                            <x-ui.badge variant="accent">Zapełnienie {{ $occupancyCount }}/{{ $seatCapacity }}</x-ui.badge>
                        </div>

                        @if($canEditVehicle)
                            <div class="emp-hero__actions">
                                <x-ui.button variant="ghost" href="{{ route('vehicles.edit', $vehicle) }}" class="btn-sm">
                                    <i class="bi bi-pencil me-1"></i>Edytuj
                                </x-ui.button>
                                <x-ui.button variant="ghost" type="button" class="btn-sm" data-bs-toggle="collapse" data-bs-target="#veh-place-panel" aria-expanded="{{ $errors->has('placement') ? 'true' : 'false' }}">
                                    <i class="bi bi-geo-alt me-1"></i>Popraw lokalizację
                                </x-ui.button>
                                @if($vehicle->isRetired())
                                    <x-ui.button variant="ghost" type="submit" form="veh-reinstate" class="btn-sm">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Przywróć do floty
                                    </x-ui.button>
                                @else
                                    <x-ui.button variant="danger" type="button" class="btn-sm" data-bs-toggle="collapse" data-bs-target="#veh-retire-panel" aria-expanded="{{ $errors->has('reason') ? 'true' : 'false' }}">
                                        <i class="bi bi-archive me-1"></i>Wycofaj
                                    </x-ui.button>
                                @endif
                            </div>
                        @endif
                    </div>

                    <h1 class="emp-hero__name font-mono">{{ $vehicle->registration_number }}</h1>
                    @if($brandLine !== '')
                        <p class="text-muted mb-0">{{ $brandLine }}</p>
                    @endif

                    <div class="veh-crew">
                        @foreach($todayCrew as $seat)
                            @php
                                $person = $seat['employee'];
                                $initials = mb_strtoupper(mb_substr($person->first_name, 0, 1).mb_substr($person->last_name, 0, 1));
                            @endphp
                            <a href="{{ route('employees.show', $person) }}" class="veh-crew__person">
                                <x-ui.avatar :image-url="$person->image_url" :alt="$person->full_name" :initials="$initials" size="48px" :border="false" />
                                <div class="veh-crew__role">{{ $seat['role'] }}</div>
                                <div class="veh-crew__name">{{ $person->full_name }}</div>
                            </a>
                        @endforeach
                        @for($empty = 0; $empty < $emptySeats; $empty++)
                            <div class="veh-crew__person">
                                <div class="veh-seat-empty"></div>
                                <div class="veh-crew__role">Wolne miejsce</div>
                            </div>
                        @endfor
                    </div>

                    @if($canEditVehicle)
                        <div class="collapse {{ $errors->has('placement') ? 'show' : '' }}" id="veh-place-panel">
                            <div class="border rounded-3 p-3 mt-2" style="border-color: var(--glass-border) !important;">
                                <p class="small text-muted">Zapisuje stan od razu, na dzisiaj. Auto przestaje być w podróży.</p>
                                @if($assignmentsToRelease->isNotEmpty())
                                    <p class="small text-warning">W bazie zdejmie przypisania na dziś i później: {{ $assignmentsToRelease->pluck('employee.full_name')->filter()->unique()->implode(', ') }}. Poza bazą ich nie rusza.</p>
                                @endif
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="placement" id="placement-base" value="base" form="veh-place" @checked(old('placement') === 'base')>
                                    <label class="form-check-label" for="placement-base">W bazie</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="placement" id="placement-field" value="field" form="veh-place" @checked(old('placement') === 'field')>
                                    <label class="form-check-label" for="placement-field">Poza bazą</label>
                                </div>
                                <x-ui.input
                                    type="textarea"
                                    name="notes"
                                    label="Dlaczego"
                                    form="veh-place"
                                    rows="2"
                                    maxlength="1000"
                                    required
                                    :value="old('_method') === 'PUT' ? '' : old('notes', '')"
                                />
                                <x-ui.button variant="primary" type="submit" form="veh-place" class="mt-2 btn-sm">Zapisz położenie</x-ui.button>
                            </div>
                        </div>
                    @endif

                    @if($canEditVehicle && ! $vehicle->isRetired())
                        <div class="collapse {{ $errors->has('reason') ? 'show' : '' }}" id="veh-retire-panel">
                            <div class="border rounded-3 p-3 mt-2" style="border-color: var(--glass-border) !important;">
                                <p class="small text-muted">Znika z planowania nowych wyjazdów. Starsze wyjazdy zostają.</p>
                                @if($assignmentsToRelease->isNotEmpty())
                                    <p class="small text-warning">Ktoś jest w tym aucie: {{ $assignmentsToRelease->pluck('employee.full_name')->filter()->unique()->implode(', ') }}. Wycofanie zdejmie przypisania na dziś i później. Od dziś te osoby nie mają tego auta.</p>
                                @endif
                                <x-ui.input type="select" name="reason" label="Powód" form="veh-retire" required>
                                    <option value="">— wybierz —</option>
                                    @foreach(\App\Enums\VehicleRetirementReason::options() as $value => $label)
                                        <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                                    @endforeach
                                </x-ui.input>
                                <div class="mt-2">
                                    <x-ui.input type="textarea" name="note" label="Notatka" form="veh-retire" rows="2" maxlength="1000" :value="old('_method') === 'PUT' ? '' : old('note', '')" />
                                </div>
                                <x-ui.button variant="danger" type="submit" form="veh-retire" class="mt-2">Przenieś do archiwum</x-ui.button>
                            </div>
                        </div>
                    @endif
                </div>
            </article>

            <div class="emp-body">
                <nav class="card emp-rail d-none d-lg-flex" aria-label="Sekcje karty pojazdu">
                    <div class="emp-rail__group">
                        <div class="emp-rail__group-label">Pojazd</div>
                        @foreach($tabs as $tabKey => $tabItem)
                            <a
                                href="{{ $tabItem['href'] }}"
                                class="emp-rail__item {{ $tab === $tabKey ? 'is-active' : '' }}"
                            >
                                <i class="{{ $tabItem['icon'] }}" aria-hidden="true"></i>
                                <span class="emp-rail__label">{{ $tabItem['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </nav>

                <div class="emp-main">
                    <x-ui.tabs
                        :tabs="$tabs"
                        :activeTab="$tab"
                        id="vehicleTabs"
                        :compact-mobile="true"
                        :hide-strip="true"
                        mobile-label="Sekcja"
                    />

                    @if($tab === 'info')
                        <div class="card">
                            <div class="card-body emp-dossier">
                                <div class="emp-facts">
                                    <div class="emp-fact">
                                        <div class="emp-fact__label">W projektach</div>
                                        <p class="emp-fact__value mb-0">
                                            @forelse($crewProjects as $project)
                                                <x-ui.badge variant="accent">{{ $project->name }}</x-ui.badge>
                                            @empty
                                                —
                                            @endforelse
                                        </p>
                                    </div>
                                    <div class="emp-fact">
                                        <div class="emp-fact__label">W domach</div>
                                        <p class="emp-fact__value">
                                            {{ $crewHomes->pluck('name')->filter()->implode(', ') ?: '—' }}
                                        </p>
                                    </div>
                                    <div class="emp-fact">
                                        <div class="emp-fact__label">Lokalizacja</div>
                                        <p class="emp-fact__value mb-1">{{ $stationing }}</p>
                                        @if($placement['in_transit'])
                                            <x-ui.badge variant="warning">W podróży</x-ui.badge>
                                        @elseif(! $placement['outside_base'])
                                            <x-ui.badge variant="success">W bazie</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="info">Poza bazą</x-ui.badge>
                                        @endif
                                    </div>
                                    <div class="emp-fact">
                                        <div class="emp-fact__label">Kierowca</div>
                                        <p class="emp-fact__value">
                                            @if($driverAssignment?->employee)
                                                <a href="{{ route('employees.show', $driverAssignment->employee) }}">{{ $driverAssignment->employee->full_name }}</a>
                                            @else
                                                —
                                            @endif
                                        </p>
                                    </div>
                                    <div class="emp-fact">
                                        <div class="emp-fact__label">Stan</div>
                                        <p class="emp-fact__value">{{ $condition?->label() ?? $vehicle->technical_condition }}</p>
                                    </div>
                                    <div class="emp-fact">
                                        <div class="emp-fact__label">OC</div>
                                        <p class="emp-fact__value font-mono {{ $overdueOc ? 'text-danger' : '' }}">{{ $vehicle->insurance_valid_to?->format('Y-m-d') ?? '—' }}</p>
                                    </div>
                                    <div class="emp-fact">
                                        <div class="emp-fact__label">Przegląd</div>
                                        <p class="emp-fact__value font-mono {{ $overduePrzeglad ? 'text-danger' : '' }}">{{ $vehicle->inspection_valid_to?->format('Y-m-d') ?? '—' }}</p>
                                    </div>
                                    <div class="emp-fact">
                                        <div class="emp-fact__label">AC</div>
                                        <p class="emp-fact__value font-mono {{ $overdueAc ? 'text-danger' : '' }}">{{ $vehicle->ac_wazne_do?->format('Y-m-d') ?? '—' }}</p>
                                    </div>
                                    <div class="emp-fact emp-fact--wide">
                                        <div class="emp-fact__label">Notatki</div>
                                        <p class="emp-fact__value emp-fact__value--notes">{{ $vehicle->notes ?: '—' }}</p>
                                    </div>
                                </div>

                                @if(auth()->user()?->hasPermission('comments.view'))
                                    <div class="emp-dossier__comments">
                                        <x-comments embedded :commentable="$vehicle" />
                                    </div>
                                @endif
                            </div>
                        </div>
                    @elseif($tab === 'repairs')
                        <x-ui.card>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Książka serwisowa</h5>
                                <x-ui.button variant="primary" href="{{ route('vehicle-repairs.create', ['vehicle_id' => $vehicle->id]) }}" class="btn-sm">
                                    + Nowa akcja serwisowa
                                </x-ui.button>
                            </div>
                            @php $repairs = $vehicle->repairs()->with('location')->orderBy('start_date', 'desc')->limit(10)->get(); @endphp
                            @if($repairs->count() > 0)
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0 table-sm">
                                        <thead>
                                            <tr>
                                                <th>Typ</th>
                                                <th>Okres</th>
                                                <th>Warsztat</th>
                                                <th>Koszt</th>
                                                <th>Status</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($repairs as $repair)
                                                <tr>
                                                    <td>
                                                        <x-ui.badge variant="{{ $repair->action_type->badgeVariant() }}">
                                                            {{ $repair->action_type->label() }}
                                                        </x-ui.badge>
                                                    </td>
                                                    <td>
                                                        <small>
                                                            {{ $repair->start_date->format('Y-m-d') }}
                                                            @if($repair->end_date) → {{ $repair->end_date->format('Y-m-d') }} @else → <em class="text-muted">trwa</em> @endif
                                                        </small>
                                                    </td>
                                                    <td><small>{{ $repair->location?->name ?? '–' }}</small></td>
                                                    <td><small>{{ $repair->price ? number_format($repair->price, 2) . ' ' . $repair->currency : '–' }}</small></td>
                                                    <td>
                                                        <x-ui.badge variant="{{ $repair->status_badge_variant }}">{{ $repair->status_label }}</x-ui.badge>
                                                    </td>
                                                    <td>
                                                        <x-ui.button variant="ghost" href="{{ route('vehicle-repairs.show', $repair) }}" class="btn-sm">Szczegóły</x-ui.button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-2">
                                    <a href="{{ route('vehicle-repairs.index', ['vehicle_id' => $vehicle->id]) }}" class="btn btn-sm btn-outline-secondary">
                                        Wszystkie serwisy pojazdu →
                                    </a>
                                </div>
                            @else
                                <x-ui.empty-state icon="tools" message="Brak wpisów serwisowych dla tego pojazdu." />
                            @endif
                        </x-ui.card>
                    @elseif($tab === 'assignments')
                        <x-ui.card>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Przypisania do pojazdu</h5>
                                <a href="{{ route('vehicles.show', ['vehicle' => $vehicle->id, 'tab' => 'assignments', 'filter' => $filter === 'active' ? 'all' : 'active']) }}"
                                   class="btn btn-sm {{ $filter === 'active' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    {{ $filter === 'active' ? 'Aktywne' : 'Wszystkie' }}
                                </a>
                            </div>
                            @if($assignments->count() > 0)
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Pracownik</th>
                                                <th>Rola</th>
                                                <th>Okres</th>
                                                <th>Status</th>
                                                <th class="text-end">Akcje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($assignments as $assignment)
                                                <tr>
                                                    <td><x-employee-cell :employee="$assignment->employee" /></td>
                                                    <td>
                                                        @php
                                                            $position = $assignment->position ?? \App\Enums\VehiclePosition::PASSENGER;
                                                            $positionValue = $position instanceof \App\Enums\VehiclePosition ? $position->value : $position;
                                                            $positionLabel = $position instanceof \App\Enums\VehiclePosition ? $position->label() : ucfirst($position);
                                                        @endphp
                                                        <x-ui.badge variant="{{ $positionValue === 'driver' ? 'accent' : 'info' }}">{{ $positionLabel }}</x-ui.badge>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            {{ $assignment->start_date->format('Y-m-d') }}
                                                            @if($assignment->end_date)
                                                                - {{ $assignment->end_date->format('Y-m-d') }}
                                                            @else
                                                                - ...
                                                            @endif
                                                        </small>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $status = $assignment->status ?? \App\Enums\AssignmentStatus::ACTIVE;
                                                            $statusValue = $status instanceof \App\Enums\AssignmentStatus ? $status->value : $status;
                                                            $statusLabel = $status instanceof \App\Enums\AssignmentStatus ? $status->label() : ucfirst($status);
                                                            $badgeVariant = match($statusValue) {
                                                                'active' => 'success',
                                                                'completed' => 'info',
                                                                'cancelled' => 'danger',
                                                                'in_transit' => 'warning',
                                                                'at_base' => 'info',
                                                                default => 'info'
                                                            };
                                                        @endphp
                                                        <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
                                                    </td>
                                                    <td class="text-end">
                                                        <x-ui.button variant="ghost" href="{{ route('vehicle-assignments.show', $assignment) }}" class="btn-sm">Szczegóły</x-ui.button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if($assignments->hasPages())
                                    <div class="mt-3 pt-3 border-top">
                                        {{ $assignments->links('pagination::bootstrap-5') }}
                                    </div>
                                @endif
                            @else
                                <x-ui.empty-state icon="inbox" message="Brak przypisań do tego pojazdu." />
                            @endif
                        </x-ui.card>
                    @elseif($tab === 'consumptions')
                        <x-equipment.destination-consumptions :consumable="$vehicle" empty="Brak rozchodów z magazynu na ten pojazd." />
                    @elseif($tab === 'logistics')
                        <div class="card">
                            <div class="card-body emp-dossier">
                                <div class="emp-fact__label">Zdarzenia logistyczne</div>
                                @if($recentLogistics->isEmpty())
                                    <p class="text-muted mb-0 mt-2">Brak wyjazdów, zjazdów i transferów.</p>
                                @else
                                    <ol class="emp-lifecycle__list">
                                        @foreach($recentLogistics as $event)
                                            @php
                                                $operationUrl = match ($event->type) {
                                                    \App\Enums\LogisticsEventType::DEPARTURE => route('departures.show', $event),
                                                    \App\Enums\LogisticsEventType::RETURN => route('return-trips.show', $event),
                                                    \App\Enums\LogisticsEventType::TRANSFER => route('transfers.show', $event),
                                                    default => null,
                                                };
                                                $routeLabel = trim(($event->fromLocation?->name ?? '').' → '.($event->toLocation?->name ?? ''), ' →');
                                            @endphp
                                            <li class="emp-lifecycle__item">
                                                @if($operationUrl)
                                                    <a href="{{ $operationUrl }}" class="fw-semibold">{{ $event->type->label() }}</a>
                                                @else
                                                    <span class="fw-semibold">{{ $event->type->label() }}</span>
                                                @endif
                                                <span class="font-mono">{{ $event->event_date->format('Y-m-d H:i') }}</span>
                                                @if($routeLabel !== '')
                                                    <span class="text-muted">{{ $routeLabel }}</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ol>
                                @endif
                            </div>
                        </div>
                    @elseif($tab === 'lifecycle')
                        <div class="card">
                            <div class="card-body emp-dossier">
                                <div class="emp-fact__label">Cykl życia pojazdu</div>
                                @if($vehicleJournal->isEmpty())
                                    <p class="text-muted mb-0 mt-2">Brak korekt położenia i zmian cyklu życia.</p>
                                @else
                                    <ol class="emp-lifecycle__list">
                                        @foreach($vehicleJournal as $entry)
                                            <li class="emp-lifecycle__item">
                                                <span class="fw-semibold">{{ $entry['title'] }}</span>
                                                <span class="font-mono text-muted">{{ $entry['meta'] }}</span>
                                                @if($entry['aside'] !== '')
                                                    <span class="text-muted">{{ $entry['aside'] }}</span>
                                                @endif
                                                @if($entry['note'])
                                                    <span class="text-muted">{{ $entry['note'] }}</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ol>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if($canEditVehicle)
            <form id="veh-place" method="POST" action="{{ route('vehicles.placement-correction', $vehicle) }}" hidden>@csrf</form>
            <form id="veh-retire" method="POST" action="{{ route('vehicles.retire', $vehicle) }}" hidden>@csrf</form>
            <form id="veh-reinstate" method="POST" action="{{ route('vehicles.reinstate', $vehicle) }}" hidden>@csrf</form>
        @endif
    </div>
</x-app-layout>
