<div>
    <x-data-table-filters
        :count="$employees->total()"
        :has-filters="(bool) ($search || $roleFilter || $locationFilter || $rotationFilter || $statusDate || $companyFilter || $showTerminated)"
        item-label="pracowników"
    >
        @if($statusDate || $showTerminated)
            <x-slot:note>
                @if($statusDate){{ 'stan na '.\Carbon\Carbon::parse($statusDate)->format('d.m.Y') }}@endif
                @if($statusDate && $showTerminated), @endif
                @if($showTerminated)z uwzględnieniem zwolnionych @endif
            </x-slot:note>
        @endif

        <x-slot:actions>
            <div class="form-check mb-0 d-none d-md-flex">
                <input type="checkbox" class="form-check-input" id="showTerminated" wire:model.live="showTerminated">
                <label class="form-check-label small" for="showTerminated">Pokaż zwolnionych</label>
            </div>
        </x-slot:actions>

        <div class="dt-filter-field d-md-none">
            <div class="form-check mb-0">
                <input type="checkbox" class="form-check-input" id="showTerminatedMobile" wire:model.live="showTerminated">
                <label class="form-check-label small" for="showTerminatedMobile">Pokaż zwolnionych</label>
            </div>
        </div>
        <div class="dt-filter-field dt-filter-field--wide">
            <label class="form-label small"><i class="bi bi-search me-1"></i> Szukaj</label>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Imię, nazwisko, telefon..." class="form-control">
        </div>
        <div class="dt-filter-field">
            <label class="form-label small"><i class="bi bi-calendar me-1"></i> Stan na dzień</label>
            <input type="date" wire:model.live="statusDate" placeholder="Dzisiaj" class="form-control">
        </div>
        <div class="dt-filter-field">
            <label class="form-label small"><i class="bi bi-person-badge me-1"></i> Rola</label>
            <select wire:model.live="roleFilter" class="form-select">
                <option value="">Wszystkie role</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="dt-filter-field">
            <label class="form-label small"><i class="bi bi-geo-alt me-1"></i> Lokalizacja</label>
            <select wire:model.live="locationFilter" class="form-select">
                <option value="">Wszystkie</option>
                <option value="base">Baza</option>
                <option value="field">W terenie</option>
                <option value="transit">W podróży</option>
            </select>
        </div>
        <div class="dt-filter-field">
            <label class="form-label small"><i class="bi bi-arrow-repeat me-1"></i> Rotacja</label>
            <select wire:model.live="rotationFilter" class="form-select">
                <option value="">Wszystkie</option>
                <option value="active">Aktywna</option>
                <option value="inactive">Nieaktywna</option>
            </select>
        </div>
        <div class="dt-filter-field">
            <label class="form-label small"><i class="bi bi-building me-1"></i> Spółka</label>
            <select wire:model.live="companyFilter" class="form-select">
                <option value="">Wszystkie spółki</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </div>
    </x-data-table-filters>

    <!-- Karty na mobile: 8 kolumn tabeli (status/dom/auto/projekt/rotacja/spółka) nie
         mieszczą się na wąskim ekranie nawet ze scrollem — czytelniejszy jest jeden
         pracownik na kartę z tymi samymi odznakami ułożonymi w siatkę. -->
    <div class="d-md-none">
        @forelse ($employees as $employee)
            @php
                $locationTracker = app(\App\Services\LocationTrackingService::class);
                $locationStatus = $locationTracker->getLocationStatus($employee, $checkDate);
                $hasActiveRotation = $employee->rotations->filter(function ($rotation) use ($checkDate) {
                    $startDate = $rotation->start_date ? \Carbon\Carbon::parse($rotation->start_date) : null;
                    $endDate = $rotation->end_date ? \Carbon\Carbon::parse($rotation->end_date) : null;
                    if (! $startDate) {
                        return false;
                    }

                    return $startDate->lte($checkDate) && ($endDate === null || $endDate->gte($checkDate));
                })->isNotEmpty();
                $companyAssignment = $employee->companyAssignments->first();
                $inBaseOrTransit = in_array($locationStatus['state'], [
                    \App\Enums\EmployeeLocationState::IN_BASE,
                    \App\Enums\EmployeeLocationState::IN_TRANSIT,
                ], true);
            @endphp
            <x-ui.card class="mb-2 py-3">
                <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <x-employee-cell :employee="$employee" />
                        @if($employee->isTerminated())
                            <x-ui.badge variant="danger">Zwolniony</x-ui.badge>
                        @endif
                    </div>
                    <x-ui.action-buttons>
                        <x-ui.button variant="ghost" href="{{ route('employees.show', $employee) }}" class="btn-sm">
                            <i class="bi bi-eye"></i>
                        </x-ui.button>
                        <x-ui.button variant="ghost" href="{{ route('employees.edit', $employee) }}" class="btn-sm">
                            <i class="bi bi-pencil"></i>
                        </x-ui.button>
                    </x-ui.action-buttons>
                </div>
                <div class="d-flex flex-wrap gap-1 mb-2">
                    @if($locationStatus['state'] === \App\Enums\EmployeeLocationState::IN_TRANSIT)
                        <x-ui.badge variant="warning">🚗 W podróży</x-ui.badge>
                    @elseif($locationStatus['state'] === \App\Enums\EmployeeLocationState::IN_BASE)
                        <x-ui.badge variant="success">🏠 Baza</x-ui.badge>
                    @else
                        <x-ui.badge variant="info">📍 Poza bazą</x-ui.badge>
                    @endif
                    <x-ui.badge :variant="$hasActiveRotation ? 'success' : 'danger'">
                        {{ $hasActiveRotation ? '✓' : '✗' }} Rotacja
                    </x-ui.badge>
                    @if($companyAssignment && $companyAssignment->company)
                        <x-ui.badge variant="secondary">🏢 {{ $companyAssignment->company->name }}</x-ui.badge>
                    @endif
                </div>
                @unless($inBaseOrTransit)
                    <div class="d-flex flex-wrap gap-1 small">
                        @if(!empty($locationStatus['accommodation_names']))
                            @foreach($locationStatus['accommodation_names'] as $accName)
                                <x-ui.badge :variant="($locationStatus['has_assignment_overlap'] ?? false) ? 'warning' : 'info'">🏡 {{ $accName }}</x-ui.badge>
                            @endforeach
                        @else
                            <x-ui.badge variant="danger">❌ Brak domu</x-ui.badge>
                        @endif
                        @if(!empty($locationStatus['vehicle_labels']))
                            @foreach($locationStatus['vehicle_labels'] as $reg)
                                <x-ui.badge :variant="($locationStatus['has_assignment_overlap'] ?? false) ? 'warning' : 'info'">🚗 {{ $reg }}</x-ui.badge>
                            @endforeach
                        @else
                            <x-ui.badge variant="danger">❌ Brak auta</x-ui.badge>
                        @endif
                        @if(!empty($locationStatus['project_names']))
                            @foreach($locationStatus['project_names'] as $pname)
                                <x-ui.badge :variant="($locationStatus['has_assignment_overlap'] ?? false) ? 'warning' : 'info'">🏢 {{ $pname }}</x-ui.badge>
                            @endforeach
                        @else
                            <x-ui.badge variant="danger">❌ Brak projektu</x-ui.badge>
                        @endif
                    </div>
                @endunless
            </x-ui.card>
        @empty
            <x-ui.empty-state icon="people" message="Brak pracowników do wyświetlenia" />
        @endforelse

        @if($employees->hasPages())
            <div class="mt-3">
                {{ $employees->links() }}
            </div>
        @endif
    </div>

    <!-- Tabela (desktop/tablet — na mobile zastąpiona kartami wyżej) -->
    <x-ui.card class="d-none d-md-block">
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
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <x-employee-cell :employee="$employee"  />
                                    @if($employee->isTerminated())
                                        <x-ui.badge variant="danger">Zwolniony</x-ui.badge>
                                    @endif
                                </div>
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
