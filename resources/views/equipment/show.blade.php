<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">{{ $equipment->name }}</h2>
            <div class="d-flex gap-2">
                <x-edit-button href="{{ route('equipment.edit', $equipment) }}" />
                <a href="{{ route('equipment.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Powrót
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
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
                                <span class="badge bg-danger">Niski stan</span>
                            @else
                                <span class="badge bg-success">OK</span>
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
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <h5 class="fw-bold text-dark mb-4">Wymagania dla ról</h5>
                    @if($equipment->requirements->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start">Rola</th>
                                        <th class="text-start">Wymagana ilość</th>
                                        <th class="text-start">Obowiązkowe</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($equipment->requirements as $requirement)
                                        <tr>
                                            <td>{{ $requirement->role->name }}</td>
                                            <td>{{ $requirement->required_quantity }} {{ $equipment->unit }}</td>
                                            <td>
                                                @if($requirement->is_mandatory)
                                                    <span class="badge bg-danger">Tak</span>
                                                @else
                                                    <span class="badge bg-secondary">Nie</span>
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
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="fw-bold text-dark mb-4">Ostatnie wydania</h5>
                    @if($equipment->issues->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start">Pracownik</th>
                                        <th class="text-start">Ilość</th>
                                        <th class="text-start">Data wydania</th>
                                        <th class="text-start">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($equipment->issues->take(10) as $issue)
                                        <tr>
                                            <td>{{ $issue->employee->full_name }}</td>
                                            <td>{{ $issue->quantity_issued }} {{ $equipment->unit }}</td>
                                            <td>{{ $issue->issue_date->format('Y-m-d') }}</td>
                                            <td>
                                                @php
                                                    $badgeClass = match($issue->status) {
                                                        'issued' => 'bg-primary',
                                                        'returned' => 'bg-success',
                                                        default => 'bg-secondary'
                                                    };
                                                @endphp
                                                <span class="badge {{ $badgeClass }}">{{ ucfirst($issue->status) }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">Brak wydań</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
