<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ $equipment->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('equipment.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('equipment.edit', $equipment) }}"
                    routeName="equipment.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card label="Informacje podstawowe" class="mb-3">
                    <h5 class="fw-bold text-dark mb-4">Informacje podstawowe</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Nazwa</h6>
                            <p class="fw-semibold">{{ $equipment->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Kategoria</h6>
                            <p class="fw-semibold">{{ $equipment->category ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Ilość w magazynie</h6>
                            <p class="fw-semibold">{{ $equipment->quantity_in_stock }} {{ $equipment->unit }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Dostępne</h6>
                            <p class="fw-semibold">{{ $equipment->available_quantity }} {{ $equipment->unit }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Minimalna ilość</h6>
                            <p class="fw-semibold">{{ $equipment->min_quantity }} {{ $equipment->unit }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Status</h6>
                            @if($equipment->isLowStock())
                                <x-ui.badge variant="danger">Niski stan</x-ui.badge>
                            @else
                                <x-ui.badge variant="success">OK</x-ui.badge>
                            @endif
                        </div>
                        @if($equipment->unit_cost)
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Koszt jednostkowy</h6>
                            <p class="fw-semibold">{{ number_format($equipment->unit_cost, 2) }} PLN</p>
                        </div>
                        @endif
                        @if($equipment->description)
                        <div class="col-12">
                            <h6 class="text-muted small mb-1">Opis</h6>
                            <p>{{ $equipment->description }}</p>
                        </div>
                        @endif
                    </div>
    </x-ui.card>

    <x-ui.card label="Wymagania dla ról" class="mb-3">
        @if($equipment->requirements->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Rola</th>
                            <th>Wymagana ilość</th>
                            <th>Obowiązkowe</th>
                        </tr>
                    </thead>
                                <tbody>
                                    @foreach($equipment->requirements as $requirement)
                                        <tr>
                                            <td>{{ $requirement->role->name }}</td>
                                            <td>{{ $requirement->required_quantity }} {{ $equipment->unit }}</td>
                                            <td>
                                                @if($requirement->is_mandatory)
                                                    <x-ui.badge variant="danger">Tak</x-ui.badge>
                                                @else
                                                    <x-ui.badge variant="accent">Nie</x-ui.badge>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
        @else
            <p class="text-muted">Brak wymagań</p>
        @endif
    </x-ui.card>

    <x-ui.card label="Ostatnie wydania">
        @if($equipment->issues->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Pracownik</th>
                            <th>Ilość</th>
                            <th>Data wydania</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                                <tbody>
                                    @foreach($equipment->issues->take(10) as $issue)
                                        <tr>
                                            <td>
                                                <x-employee-cell :employee="$issue->employee"  />
                                            </td>
                                            <td>{{ $issue->quantity_issued }} {{ $equipment->unit }}</td>
                                            <td>{{ $issue->issue_date->format('Y-m-d') }}</td>
                                            <td>
                                                @php
                                                    $badgeVariant = match($issue->status) {
                                                        'issued' => 'primary',
                                                        'returned' => 'success',
                                                        default => 'accent'
                                                    };
                                                @endphp
                                                <x-ui.badge variant="{{ $badgeVariant }}">{{ ucfirst($issue->status) }}</x-ui.badge>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
        @else
            <p class="text-muted">Brak wydań</p>
        @endif
    </x-ui.card>
</x-app-layout>
