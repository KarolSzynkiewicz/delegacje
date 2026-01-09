<div>
    <!-- Statystyki i Filtry -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="mb-4 pb-3 border-bottom">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h3 class="fs-5 fw-semibold text-dark mb-1">Pojazdy</h3>
                        <p class="small text-muted mb-0">
                            @if($search || $conditionFilter || $statusFilter)
                                Znaleziono: <span class="fw-semibold text-dark">{{ $vehicles->total() }}</span> pojazdów
                            @else
                                Łącznie: <span class="fw-semibold text-dark">{{ $vehicles->total() }}</span> pojazdów
                            @endif
                        </p>
                    </div>
                    @if($search || $conditionFilter || $statusFilter)
                        <button wire:click="clearFilters" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                        </button>
                    @endif
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">
                        <i class="bi bi-search me-1"></i> Szukaj
                    </label>
                    <div class="position-relative">
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Nr rej., marka, model..." class="form-control form-control-sm ps-5">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Stan techniczny</label>
                    <select wire:model.live="conditionFilter" class="form-select form-select-sm">
                        <option value="">Wszystkie</option>
                        <option value="excellent">Doskonały</option>
                        <option value="good">Dobry</option>
                        <option value="fair">Zadowalający</option>
                        <option value="poor">Słaby</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Status</label>
                    <select wire:model.live="statusFilter" class="form-select form-select-sm">
                        <option value="">Wszystkie</option>
                        <option value="occupied">Zajęty</option>
                        <option value="available">Wolny</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 position-relative">
        <div wire:loading.delay class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-90 d-flex align-items-center justify-content-center rounded z-3">
            <div class="text-center">
                <div class="spinner-border text-primary mb-2" role="status">
                    <span class="visually-hidden">Ładowanie...</span>
                </div>
                <div class="small text-muted fw-medium">Ładowanie...</div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-start">Zdjęcie</th>
                        <th class="text-start">
                            <button wire:click="sortBy('registration_number')" class="btn btn-link text-decoration-none p-0 fw-semibold text-dark d-flex align-items-center gap-1">
                                <span>Nr Rejestracyjny</span>
                                @if($sortField === 'registration_number')
                                    @if($sortDirection === 'asc')
                                        <i class="bi bi-chevron-up"></i>
                                    @else
                                        <i class="bi bi-chevron-down"></i>
                                    @endif
                                @endif
                            </button>
                        </th>
                        <th class="text-start">Marka i Model</th>
                        <th class="text-start">Stan</th>
                        <th class="text-start d-none d-lg-table-cell">Pojemność</th>
                        <th class="text-start">Status</th>
                        <th class="text-start">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vehicles as $vehicle)
                        <tr>
                            <td>
                                @if($vehicle->image_path)
                                    <img src="{{ $vehicle->image_url }}" alt="{{ $vehicle->brand }} {{ $vehicle->model }}" class="rounded border border-2" style="width: 50px; height: 50px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-warning bg-opacity-75 d-flex align-items-center justify-content-center border border-2" style="width: 50px; height: 50px;">
                                        <span class="text-white small fw-semibold">{{ substr($vehicle->registration_number, 0, 2) }}</span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-medium text-dark">{{ $vehicle->registration_number }}</div>
                                <div class="d-md-none small text-muted mt-1">{{ ($vehicle->brand ?? '') . ' ' . ($vehicle->model ?? '') }}</div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div class="text-dark">{{ ($vehicle->brand ?? '') . ' ' . ($vehicle->model ?? '') }}</div>
                            </td>
                            <td>
                                @php
                                    $labels = ['excellent' => 'Doskonały', 'good' => 'Dobry', 'fair' => 'Zadowalający', 'poor' => 'Słaby'];
                                    $colorType = \App\Services\StatusColorService::getVehicleConditionColor($vehicle->technical_condition);
                                @endphp
                                <x-badge type="{{ $colorType }}">{{ $labels[$vehicle->technical_condition] ?? $vehicle->technical_condition }}</x-badge>
                            </td>
                            <td class="d-none d-lg-table-cell">
                                <small class="text-muted">{{ $vehicle->capacity ?? '-' }} osób</small>
                            </td>
                            <td>
                                @if($vehicle->currentAssignment())
                                    <x-badge type="danger">Zajęty</x-badge>
                                @else
                                    <x-badge type="success">Wolny</x-badge>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                        <span class="d-none d-sm-inline ms-1">Zobacz</span>
                                    </a>
                                    <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                        <span class="d-none d-sm-inline ms-1">Edytuj</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-car-front"></i>
                                    <p class="text-muted small fw-medium mb-0">
                                        @if($search || $conditionFilter || $statusFilter)
                                            Brak pojazdów spełniających kryteria
                                        @else
                                            Brak pojazdów
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($vehicles->hasPages())
            <div class="card-footer bg-light">
                {{ $vehicles->links() }}
            </div>
        @endif
    </div>
</div>
