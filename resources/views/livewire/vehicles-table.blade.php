<div>
    <!-- Statystyki i Filtry -->
    <x-ui.card class="mb-4">
        <div class="mb-4 pb-3 border-top border-bottom">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h3 class="fs-5 fw-semibold mb-1">Pojazdy</h3>
                    <p class="small text-muted mb-0">
                        @if($search || $conditionFilter || $statusFilter || $locationFilter || $statusDate)
                            Znaleziono: <span class="fw-semibold">{{ $vehicles->total() }}</span> pojazdów
                            @if($statusDate)
                                <span class="text-primary">(stan na {{ \Carbon\Carbon::parse($statusDate)->format('d.m.Y') }})</span>
                            @endif
                        @else
                            Łącznie: <span class="fw-semibold">{{ $vehicles->total() }}</span> pojazdów
                        @endif
                    </p>
                </div>
                @if($search || $conditionFilter || $statusFilter || $locationFilter || $statusDate)
                    <x-ui.button variant="ghost" wire:click="clearFilters" class="btn-sm">
                        <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                    </x-ui.button>
                @endif
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-2">
                <label class="form-label small">
                    <i class="bi bi-search me-1"></i> Szukaj
                </label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Nr rej., marka, model..." class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label small">
                    <i class="bi bi-calendar me-1"></i> Stan na dzień
                </label>
                <input type="date" wire:model.live="statusDate" 
                    placeholder="Dzisiaj"
                    class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Stan techniczny</label>
                <select wire:model.live="conditionFilter" class="form-control">
                    <option value="">Wszystkie</option>
                    @foreach(\App\Enums\VehicleCondition::cases() as $condition)
                        <option value="{{ $condition->value }}">{{ $condition->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Status</label>
                <select wire:model.live="statusFilter" class="form-control">
                    <option value="">Wszystkie</option>
                    <option value="occupied">Zajęty</option>
                    <option value="available">Wolny</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">
                    <i class="bi bi-geo-alt me-1"></i> Lokalizacja
                </label>
                <select wire:model.live="locationFilter" class="form-control">
                    <option value="">Wszystkie</option>
                    <option value="base">Baza</option>
                    <option value="field">W terenie</option>
                    <option value="transit">W podróży</option>
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
                        <x-livewire.sortable-header field="registration_number" :sortField="$sortField" :sortDirection="$sortDirection">
                            Nr Rejestracyjny
                        </x-livewire.sortable-header>
                        <th class="text-start d-none d-md-table-cell">Marka i Model</th>
                        <th class="text-start">Stan</th>
                        <th class="text-center" style="min-width: 120px;">Status</th>
                        <th class="text-center d-none d-lg-table-cell" style="min-width: 140px;">Projekty</th>
                        <th class="text-center d-none d-lg-table-cell" style="min-width: 140px;">Domy</th>
                        <th class="text-center d-none d-xl-table-cell" style="min-width: 120px;">Stacjonowanie</th>
                        <th class="text-center" style="min-width: 100px;">Zapełnienie</th>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vehicles as $vehicle)
                        @php
                            $locationTracker = app(\App\Services\LocationTrackingService::class);
                            $locationStatus = $locationTracker->getVehicleLocationStatus($vehicle, $checkDate);
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center justify-content-center">
                                    <x-ui.avatar 
                                        :image-url="$vehicle->image_path ? $vehicle->image_url : null"
                                        :alt="($vehicle->brand ?? '') . ' ' . ($vehicle->model ?? '')"
                                        :initials="substr($vehicle->registration_number, 0, 2)"
                                        size="50px"
                                        shape="rounded"
                                    />
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
                                    $condition = \App\Enums\VehicleCondition::tryFrom($vehicle->technical_condition);
                                    $colorType = \App\Services\StatusColorService::getVehicleConditionColor($vehicle->technical_condition);
                                    $badgeVariant = match($colorType) {
                                        'success' => 'success',
                                        'danger' => 'danger',
                                        'warning' => 'warning',
                                        'info' => 'info',
                                        default => 'info'
                                    };
                                @endphp
                                <x-ui.badge variant="{{ $badgeVariant }}">{{ $condition?->label() ?? $vehicle->technical_condition }}</x-ui.badge>
                            </td>
                            
                            <!-- Status (W podróży / Poza bazą / W bazie) -->
                            <td class="text-center">
                                @if($locationStatus['in_transit'])
                                    <x-ui.badge variant="warning">🚗 W podróży</x-ui.badge>
                                @elseif(!$locationStatus['outside_base'])
                                    <x-ui.badge variant="success">🏠 Baza</x-ui.badge>
                                @else
                                    <x-ui.badge variant="info">📍 Poza bazą</x-ui.badge>
                                @endif
                            </td>
                            
                            <!-- Projekty -->
                            <td class="text-center d-none d-lg-table-cell">
                                @if($locationStatus['in_transit'] || !$locationStatus['outside_base'])
                                    <span class="text-muted">─</span>
                                @elseif($locationStatus['project_names']->isNotEmpty())
                                    <x-ui.badge variant="info">
                                        🏢 {{ $locationStatus['project_names']->join(', ') }}
                                    </x-ui.badge>
                                @else
                                    <x-ui.badge variant="danger">❌ Brak</x-ui.badge>
                                @endif
                            </td>
                            
                            <!-- Domy -->
                            <td class="text-center d-none d-lg-table-cell">
                                @if($locationStatus['in_transit'] || !$locationStatus['outside_base'])
                                    <span class="text-muted">─</span>
                                @elseif($locationStatus['accommodation_names']->isNotEmpty())
                                    <x-ui.badge variant="info">
                                        🏡 {{ $locationStatus['accommodation_names']->join(', ') }}
                                    </x-ui.badge>
                                @else
                                    <x-ui.badge variant="danger">❌ Brak</x-ui.badge>
                                @endif
                            </td>
                            
                            <!-- Stacjonowanie -->
                            <td class="text-center d-none d-xl-table-cell">
                                @if($locationStatus['in_transit'])
                                    <span class="text-muted">─</span>
                                @elseif($locationStatus['stationing_location'])
                                    <x-ui.badge variant="info">{{ $locationStatus['stationing_location'] }}</x-ui.badge>
                                @else
                                    <span class="text-muted">─</span>
                                @endif
                            </td>
                            
                            <!-- Zapełnienie -->
                            <td class="text-center">
                                @if($locationStatus['in_transit'])
                                    <span class="text-muted">─</span>
                                @elseif($locationStatus['capacity'])
                                    @php
                                        $occupancy = $locationStatus['occupancy'];
                                        $capacity = $locationStatus['capacity'];
                                        $percentage = $locationStatus['occupancy_percentage'];
                                        $occupancyText = "{$occupancy}/{$capacity}";
                                        $badgeVariant = match(true) {
                                            $percentage >= 100 => 'danger',
                                            $percentage >= 80 => 'warning',
                                            default => 'success'
                                        };
                                    @endphp
                                    <x-tooltip title="{{ $occupancy }} z {{ $capacity }} miejsc ({{ $percentage }}%)">
                                        <x-ui.badge variant="{{ $badgeVariant }}">{{ $occupancyText }}</x-ui.badge>
                                    </x-tooltip>
                                @else
                                    <x-ui.badge variant="info">{{ $locationStatus['occupancy'] }}</x-ui.badge>
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
                        <x-ui.empty-state 
                            icon="car-front"
                            :message="$search || $conditionFilter || $statusFilter || $locationFilter || $statusDate ? 'Brak pojazdów spełniających kryteria' : 'Brak pojazdów'"
                            :has-filters="$search || $conditionFilter || $statusFilter || $locationFilter || $statusDate"
                            clear-filters-action="wire:clearFilters"
                            :in-table="true"
                            colspan="10"
                        />
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

