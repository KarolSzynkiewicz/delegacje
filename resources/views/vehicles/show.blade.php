<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Pojazd: {{ $vehicle->registration_number }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('vehicles.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('vehicles.edit', $vehicle) }}"
                    routeName="vehicles.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @php
        $today = \Carbon\Carbon::today();
        $overduePrzeglad = $vehicle->inspection_valid_to && $vehicle->inspection_valid_to->lt($today);
        $overdueOc = $vehicle->insurance_valid_to && $vehicle->insurance_valid_to->lt($today);
        $overdueAc = $vehicle->ac_wazne_do && $vehicle->ac_wazne_do->lt($today);
    @endphp

    @if($overduePrzeglad || $overdueOc || $overdueAc)
        <x-ui.alert variant="danger" title="Dokumenty po terminie" class="mb-4">
            <ul class="mb-0 ps-3 small">
                @if($overduePrzeglad)
                    <li><strong>Przegląd</strong> — ważny był do {{ $vehicle->inspection_valid_to->format('d.m.Y') }}</li>
                @endif
                @if($overdueOc)
                    <li><strong>OC</strong> — ważne było do {{ $vehicle->insurance_valid_to->format('d.m.Y') }}</li>
                @endif
                @if($overdueAc)
                    <li><strong>AC</strong> — ważne było do {{ $vehicle->ac_wazne_do->format('d.m.Y') }}</li>
                @endif
            </ul>
        </x-ui.alert>
    @endif

    <x-ui.card label="Szczegóły Pojazdu">
        @if($vehicle->image_path)
            <div class="mb-4 text-center">
                <img src="{{ $vehicle->image_url }}" alt="{{ $vehicle->registration_number }}" class="img-fluid rounded">
            </div>
        @endif

        <div class="row mb-3">
                    <div class="col-md-6">
                        <h5>Numer Rejestracyjny</h5>
                        <p><strong>{{ $vehicle->registration_number }}</strong></p>
                    </div>
                    <div class="col-md-6">
                        <h5>Stan Techniczny</h5>
                        <p>
                            @php
                                $condition = \App\Enums\VehicleCondition::tryFrom($vehicle->technical_condition);
                                $badgeVariant = match($vehicle->technical_condition) {
                                    'excellent' => 'success',
                                    'good' => 'info',
                                    'fair' => 'warning',
                                    'poor' => 'warning',
                                    'workshop' => 'danger',
                                    default => 'info'
                                };
                            @endphp
                            <x-ui.badge variant="{{ $badgeVariant }}">{{ $condition?->label() ?? ucfirst($vehicle->technical_condition) }}</x-ui.badge>
                        </p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <h5>Marka</h5>
                        <p>{{ $vehicle->brand ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <h5>Model</h5>
                        <p>{{ $vehicle->model ?? '-' }}</p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <h5>Pojemność</h5>
                        <p>{{ $vehicle->capacity ?? '-' }} osób</p>
                    </div>
                    <div class="col-md-6">
                        <h5>Przegląd Ważny Do</h5>
                        <p>
                            @if ($vehicle->inspection_valid_to)
                                <x-ui.badge variant="{{ $vehicle->inspection_valid_to->lt($today) ? 'danger' : 'success' }}">
                                    {{ $vehicle->inspection_valid_to->format('Y-m-d') }}
                                </x-ui.badge>
                            @else
                                -
                            @endif
                        </p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <h5>OC Ważne Do</h5>
                        <p>
                            @if ($vehicle->insurance_valid_to)
                                <x-ui.badge variant="{{ $vehicle->insurance_valid_to->lt($today) ? 'danger' : 'success' }}">
                                    {{ $vehicle->insurance_valid_to->format('Y-m-d') }}
                                </x-ui.badge>
                            @else
                                -
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h5>AC Ważne Do</h5>
                        <p>
                            @if ($vehicle->ac_wazne_do)
                                <x-ui.badge variant="{{ $vehicle->ac_wazne_do->lt($today) ? 'danger' : 'success' }}">
                                    {{ $vehicle->ac_wazne_do->format('Y-m-d') }}
                                </x-ui.badge>
                            @else
                                -
                            @endif
                        </p>
                    </div>
                </div>

                @if ($vehicle->notes)
                    <div class="mb-3">
                        <h5>Notatki</h5>
                        <p>{{ $vehicle->notes }}</p>
                    </div>
                @endif

                <div class="d-flex gap-2 mt-4 pt-3 border-top">
                    <x-ui.button variant="primary" href="{{ route('vehicles.edit', $vehicle) }}">Edytuj</x-ui.button>
                    <x-ui.button variant="ghost" href="{{ route('vehicles.index') }}">Wróć do Listy</x-ui.button>
                </div>
            </x-ui.card>

            {{-- Repairs (książka serwisowa) — nad przypisaniami --}}
            <x-ui.card class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Książka serwisowa</h5>
                    <x-ui.button
                        variant="primary"
                        href="{{ route('vehicle-repairs.create', ['vehicle_id' => $vehicle->id]) }}"
                        class="btn-sm"
                    >
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

            <x-ui.card class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Przypisania do pojazdu</h5>
                    <div>
                        <a href="{{ route('vehicles.show', ['vehicle' => $vehicle->id, 'filter' => $filter === 'active' ? 'all' : 'active']) }}" 
                           class="btn btn-sm {{ $filter === 'active' ? 'btn-primary' : 'btn-outline-primary' }}">
                            {{ $filter === 'active' ? 'Aktywne' : 'Wszystkie' }}
                        </a>
                    </div>
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
                                        <td>
                                            <x-employee-cell :employee="$assignment->employee"  />
                                        </td>
                                        <td>
                                            @php
                                                $position = $assignment->position ?? \App\Enums\VehiclePosition::PASSENGER;
                                                $positionValue = $position instanceof \App\Enums\VehiclePosition ? $position->value : $position;
                                                $positionLabel = $position instanceof \App\Enums\VehiclePosition ? $position->label() : ucfirst($position);
                                            @endphp
                                            <x-ui.badge variant="{{ $positionValue === 'driver' ? 'accent' : 'info' }}">
                                                {{ $positionLabel }}
                                            </x-ui.badge>
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
                    <x-ui.empty-state 
                        icon="inbox"
                        message="Brak przypisań do tego pojazdu."
                    />
                @endif
            </x-ui.card>

    <x-ui.card class="mt-4">
        <x-comments :commentable="$vehicle" />
    </x-ui.card>
</x-app-layout>
