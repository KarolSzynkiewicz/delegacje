<div>
    <!-- Statystyki i Filtry -->
    <x-ui.card class="mb-4">
        <div class="mb-4 pb-3 border-top border-bottom">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h3 class="fs-5 fw-semibold mb-1">Pojazdy</h3>
                    <p class="small text-muted mb-0">
                        @if($search || $conditionFilter || $statusFilter)
                            Znaleziono: <span class="fw-semibold">{{ $vehicles->total() }}</span> pojazdów
                        @else
                            Łącznie: <span class="fw-semibold">{{ $vehicles->total() }}</span> pojazdów
                        @endif
                    </p>
                </div>
                @if($search || $conditionFilter || $statusFilter)
                    <x-ui.button variant="ghost" wire:click="clearFilters" class="btn-sm">
                        <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                    </x-ui.button>
                @endif
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label small">
                    <i class="bi bi-search me-1"></i> Szukaj
                </label>
                <div class="position-relative">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Nr rej., marka, model..." class="form-control ps-5">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Stan techniczny</label>
                <select wire:model.live="conditionFilter" class="form-control">
                    <option value="">Wszystkie</option>
                    <option value="excellent">Doskonały</option>
                    <option value="good">Dobry</option>
                    <option value="fair">Zadowalający</option>
                    <option value="poor">Słaby</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Status</label>
                <select wire:model.live="statusFilter" class="form-control">
                    <option value="">Wszystkie</option>
                    <option value="occupied">Zajęty</option>
                    <option value="available">Wolny</option>
                </select>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th class="text-start">Zdjęcie</th>
                        <th class="text-start">
                            <button wire:click="sortBy('registration_number')" class="btn-link text-decoration-none p-0 fw-semibold d-flex align-items-center gap-1" style="background: none; border: none; color: var(--text-main);">
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
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vehicles as $vehicle)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center justify-content-center">
                                    @if($vehicle->image_path)
                                        <img src="{{ $vehicle->image_url }}" alt="{{ $vehicle->brand }} {{ $vehicle->model }}" class="rounded border border-2 vehicle-image">
                                    @else
                                        <div class="avatar-ui vehicle-image">
                                            <span>{{ substr($vehicle->registration_number, 0, 2) }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium">{{ $vehicle->registration_number }}</div>
                                <div class="d-md-none small text-muted mt-1">{{ ($vehicle->brand ?? '') . ' ' . ($vehicle->model ?? '') }}</div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div>{{ ($vehicle->brand ?? '') . ' ' . ($vehicle->model ?? '') }}</div>
                            </td>
                            <td>
                                @php
                                    $labels = ['excellent' => 'Doskonały', 'good' => 'Dobry', 'fair' => 'Zadowalający', 'poor' => 'Słaby'];
                                    $colorType = \App\Services\StatusColorService::getVehicleConditionColor($vehicle->technical_condition);
                                    $badgeVariant = match($colorType) {
                                        'success' => 'success',
                                        'danger' => 'danger',
                                        'warning' => 'warning',
                                        'info' => 'info',
                                        default => 'info'
                                    };
                                @endphp
                                <x-ui.badge variant="{{ $badgeVariant }}">{{ $labels[$vehicle->technical_condition] ?? $vehicle->technical_condition }}</x-ui.badge>
                            </td>
                            <td class="d-none d-lg-table-cell">
                                <small class="text-muted">{{ $vehicle->capacity ?? '-' }} osób</small>
                            </td>
                            <td>
                                @if($vehicle->currentAssignment())
                                    <x-ui.badge variant="danger">Zajęty</x-ui.badge>
                                @else
                                    <x-ui.badge variant="success">Wolny</x-ui.badge>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-2 justify-content-end">
                                    <x-ui.button variant="ghost" href="{{ route('vehicles.show', $vehicle) }}" class="btn-sm">
                                        <i class="bi bi-eye"></i>
                                        <span class="d-none d-sm-inline ms-1">Zobacz</span>
                                    </x-ui.button>
                                    <x-ui.button variant="ghost" href="{{ route('vehicles.edit', $vehicle) }}" class="btn-sm">
                                        <i class="bi bi-pencil"></i>
                                        <span class="d-none d-sm-inline ms-1">Edytuj</span>
                                    </x-ui.button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-car-front text-muted fs-1 d-block mb-2"></i>
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
            <div class="mt-3 pt-3 border-top">
                {{ $vehicles->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
