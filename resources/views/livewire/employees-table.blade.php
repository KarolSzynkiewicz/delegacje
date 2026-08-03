<div>
    <!-- Statystyki i Filtry -->
    <x-ui.card class="mb-4">
        <!-- Statystyki -->
        <div class="mb-4 pb-3 border-top border-bottom">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h3 class="fs-5 fw-semibold mb-1">Pracownicy</h3>
                <p class="small text-muted mb-0">
                    @if($search || $roleFilter || $locationFilter || $rotationFilter || $statusDate || $companyFilter)
                        Znaleziono: <span class="fw-semibold">{{ $employees->total() }}</span> pracowników
                        @if($statusDate)
                            <span class="text-primary">(stan na {{ \Carbon\Carbon::parse($statusDate)->format('d.m.Y') }})</span>
                        @endif
                    @else
                        Łącznie: <span class="fw-semibold">{{ $employees->total() }}</span> pracowników
                    @endif
                </p>
                </div>
                @if($search || $roleFilter || $locationFilter || $rotationFilter || $statusDate || $companyFilter)
                    <x-ui.button variant="ghost" wire:click="clearFilters" class="btn-sm">
                        <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                    </x-ui.button>
                @endif
            </div>
        </div>

        <!-- Filtry -->
        <div class="row g-3">
            <!-- Wyszukiwanie -->
            <div class="col-md-2">
                <label class="form-label small">
                    <i class="bi bi-search me-1"></i> Szukaj
                </label>
                <input type="text" wire:model.live.debounce.300ms="search" 
                    placeholder="Imię, nazwisko, telefon..."
                    class="form-control">
            </div>

            <!-- Data sprawdzenia statusu -->
            <div class="col-md-2">
                <label class="form-label small">
                    <i class="bi bi-calendar me-1"></i> Stan na dzień
                </label>
                <input type="date" wire:model.live="statusDate" 
                    placeholder="Dzisiaj"
                    class="form-control">
            </div>

            <!-- Rola -->
            <div class="col-md-2">
                <label class="form-label small">
                    <i class="bi bi-person-badge me-1"></i> Rola
                </label>
                <select wire:model.live="roleFilter" class="form-control">
                    <option value="">Wszystkie role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Lokalizacja -->
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

            <!-- Rotacja -->
            <div class="col-md-2">
                <label class="form-label small">
                    <i class="bi bi-arrow-repeat me-1"></i> Rotacja
                </label>
                <select wire:model.live="rotationFilter" class="form-control">
                    <option value="">Wszystkie</option>
                    <option value="active">Aktywna</option>
                    <option value="inactive">Nieaktywna</option>
                </select>
            </div>

            <!-- Spółka -->
            <div class="col-md-2">
                <label class="form-label small">
                    <i class="bi bi-building me-1"></i> Spółka
                </label>
                <select wire:model.live="companyFilter" class="form-control">
                    <option value="">Wszystkie spółki</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-ui.card>

    <!-- Tabela -->
    <x-ui.card>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <x-livewire.sortable-header field="id" :sortField="$sortField" :sortDirection="$sortDirection" class="text-start" style="width: 70px;">
                            ID
                        </x-livewire.sortable-header>
                        <x-livewire.sortable-header field="name" :sortField="$sortField" :sortDirection="$sortDirection">
                            Pracownik
                        </x-livewire.sortable-header>
                        <th class="text-center" style="min-width: 120px;">Status</th>
                        <th class="text-center" style="min-width: 140px;">Dom</th>
                        <th class="text-center" style="min-width: 120px;">Auto</th>
                        <th class="text-center" style="min-width: 140px;">Projekt</th>
                        <th class="text-center" style="min-width: 100px;">Rotacja</th>
                        <th class="text-center" style="min-width: 110px;">Spółka</th>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        @php
                            $locationTracker = app(\App\Services\LocationTrackingService::class);
                            $locationStatus = $locationTracker->getLocationStatus($employee, $checkDate);
                            
                            $hasActiveRotation = $employee->rotations->filter(function($rotation) use ($checkDate) {
                                $startDate = $rotation->start_date ? \Carbon\Carbon::parse($rotation->start_date) : null;
                                $endDate = $rotation->end_date ? \Carbon\Carbon::parse($rotation->end_date) : null;
                                
                                if (!$startDate) {
                                    return false;
                                }
                                
                                return $startDate->lte($checkDate) 
                                    && ($endDate === null || $endDate->gte($checkDate));
                            })->isNotEmpty();
                        @endphp
                        <tr>
                            <td class="text-muted small">
                                {{ $employee->id }}
                            </td>
                            <td>
                                <x-employee-cell :employee="$employee"  />
                            </td>

                            <!-- Status (Baza/W podróży/Poza bazą) -->
                            <td class="text-center">
                                @if($locationStatus['state'] === \App\Enums\EmployeeLocationState::IN_TRANSIT)
                                    <x-ui.badge variant="warning">🚗 W podróży</x-ui.badge>
                                @elseif($locationStatus['state'] === \App\Enums\EmployeeLocationState::IN_BASE)
                                    <x-ui.badge variant="success">🏠 Baza</x-ui.badge>
                                @else
                                    <x-ui.badge variant="info">📍 Poza bazą</x-ui.badge>
                                @endif
                            </td>
                            
                            <!-- Dom (Accommodation) — wszystkie aktywne; nakładanie = ostrzeżenie -->
                            <td class="text-center">
                                @if($locationStatus['state'] === \App\Enums\EmployeeLocationState::IN_BASE || $locationStatus['state'] === \App\Enums\EmployeeLocationState::IN_TRANSIT)
                                    <span class="text-muted">─</span>
                                @elseif(!empty($locationStatus['accommodation_names']))
                                    <div class="d-flex flex-wrap gap-1 justify-content-center align-items-center">
                                        @foreach($locationStatus['accommodation_names'] as $accName)
                                            <x-ui.badge :variant="($locationStatus['has_assignment_overlap'] ?? false) ? 'warning' : 'info'" title="{{ $accName }}">
                                                🏡 {{ \Illuminate\Support\Str::limit($accName, 5, '…') }}
                                            </x-ui.badge>
                                        @endforeach
                                    </div>
                                @else
                                    <x-ui.badge variant="danger">
                                        ❌ Brak
                                    </x-ui.badge>
                                @endif
                            </td>

                            <!-- Auto -->
                            <td class="text-center">
                                @if($locationStatus['state'] === \App\Enums\EmployeeLocationState::IN_BASE || $locationStatus['state'] === \App\Enums\EmployeeLocationState::IN_TRANSIT)
                                    <span class="text-muted">─</span>
                                @elseif(!empty($locationStatus['vehicle_labels']))
                                    <div class="d-flex flex-wrap gap-1 justify-content-center align-items-center">
                                        @foreach($locationStatus['vehicle_labels'] as $reg)
                                            <x-ui.badge :variant="($locationStatus['has_assignment_overlap'] ?? false) ? 'warning' : 'info'" title="{{ $reg }}">
                                                🚗 {{ \Illuminate\Support\Str::limit($reg, 5, '…') }}
                                            </x-ui.badge>
                                        @endforeach
                                    </div>
                                @else
                                    <x-ui.badge variant="danger">
                                        ❌ Brak
                                    </x-ui.badge>
                                @endif
                            </td>
                            
                            <!-- Projekt -->
                            <td class="text-center">
                                @if($locationStatus['state'] === \App\Enums\EmployeeLocationState::IN_BASE || $locationStatus['state'] === \App\Enums\EmployeeLocationState::IN_TRANSIT)
                                    <span class="text-muted">─</span>
                                @elseif(!empty($locationStatus['project_names']))
                                    <div class="d-flex flex-wrap gap-1 justify-content-center align-items-center">
                                        @foreach($locationStatus['project_names'] as $pname)
                                            <x-ui.badge :variant="($locationStatus['has_assignment_overlap'] ?? false) ? 'warning' : 'info'" title="{{ $pname }}">
                                                🏢 {{ \Illuminate\Support\Str::limit($pname, 5, '…') }}
                                            </x-ui.badge>
                                        @endforeach
                                    </div>
                                @else
                                    <x-ui.badge variant="danger">
                                        ❌ Brak
                                    </x-ui.badge>
                                @endif
                            </td>
                            
                            <!-- Rotacja -->
                            <td class="text-center">
                                @if($hasActiveRotation)
                                    <x-ui.badge variant="success">✓ Tak</x-ui.badge>
                                @else
                                    <x-ui.badge variant="danger">✗ Nie</x-ui.badge>
                                @endif
                            </td>

                            <!-- Spółka (aktywne przypisanie) -->
                            <td class="text-center text-nowrap">
                                @php $companyAssignment = $employee->companyAssignments->first(); @endphp
                                @if($companyAssignment && $companyAssignment->company)
                                    @php $companyName = $companyAssignment->company->name; @endphp
                                    <x-ui.badge variant="secondary" title="{{ $companyName }}">
                                        🏢 {{ \Illuminate\Support\Str::limit($companyName, 5, '…') }}
                                    </x-ui.badge>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td class="text-end">
                                <x-ui.action-buttons>
                                    <x-ui.button variant="ghost" href="{{ route('employees.show', $employee) }}" class="btn-sm">
                                        <i class="bi bi-eye"></i>
                                    </x-ui.button>
                                    <x-ui.button variant="ghost" href="{{ route('employees.edit', $employee) }}" class="btn-sm">
                                        <i class="bi bi-pencil"></i>
                                    </x-ui.button>
                                </x-ui.action-buttons>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <x-ui.empty-state 
                                    icon="people"
                                    message="Brak pracowników do wyświetlenia"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginacja -->
        @if($employees->hasPages())
            <div class="mt-3 pt-3 border-top">
                {{ $employees->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
