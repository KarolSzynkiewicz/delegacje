<div>
    <x-data-table-filters
        :count="$employees->total()"
        :has-filters="(bool) ($search || $roleFilter || $locationFilter || $rotationFilter || $statusDate || $companyFilter || $showTerminated || $hirePeriod || $komornikFilter)"
        item-label="pracowników"
    >
        @if($statusDate || $showTerminated || $hirePeriod)
            <x-slot:note>
                @if($hirePeriod === '7d')zatrudnieni w ostatnich 7 dniach @endif
                @if($hirePeriod === '30d')zatrudnieni w ostatnich 30 dniach @endif
                @if($hirePeriod && ($statusDate || $showTerminated)), @endif
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
            <label class="form-label small"><i class="bi bi-person-plus me-1"></i> Zatrudniony</label>
            <select wire:model.live="hirePeriod" class="form-select">
                <option value="">Wszyscy</option>
                <option value="7d">Ostatnie 7 dni</option>
                <option value="30d">Ostatnie 30 dni</option>
            </select>
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
            <label class="form-label small"><i class="bi bi-exclamation-octagon me-1"></i> Komornik</label>
            <select wire:model.live="komornikFilter" class="form-select">
                <option value="">Wszyscy</option>
                <option value="yes">Z komornikiem</option>
                <option value="no">Bez komornika</option>
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

    <!-- Karty na mobile: kwalifikacje i „gdzie jest” nie mieszczą się obok siebie
         na wąskim ekranie — ten sam układ wierszy, jeden pracownik na kartę. -->
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
            @endphp
            <x-ui.card class="mb-2 py-3 emp-row-card" data-emp-href="{{ route('employees.show', $employee) }}">
                <div class="mb-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <x-employee-cell :employee="$employee" />
                        @if($employee->isTerminated())
                            <x-ui.badge variant="danger">Zwolniony</x-ui.badge>
                        @endif
                        @if($employee->has_komornik)
                            <x-ui.badge variant="warning">Komornik</x-ui.badge>
                        @endif
                    </div>
                    @include('livewire.partials.employee-list-meta')
                </div>
                <div class="mb-2">
                    @include('livewire.partials.employee-qualifications')
                </div>
                <div class="d-flex flex-wrap gap-1 mb-2">
                    <x-ui.badge :variant="$hasActiveRotation ? 'success' : 'danger'">
                        {{ $hasActiveRotation ? '✓' : '✗' }} Rotacja
                    </x-ui.badge>
                    @if($companyAssignment && $companyAssignment->company)
                        <x-ui.badge variant="secondary">🏢 {{ $companyAssignment->company->name }}</x-ui.badge>
                    @endif
                </div>
                @include('livewire.partials.employee-where')
                @if($employee->hired_at)
                    <div class="small text-muted mt-2" title="{{ $employee->hired_at->format('d.m.Y') }}">
                        Zatrudniony {{ $employee->hired_at->locale('pl')->diffForHumans() }}
                    </div>
                @endif
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
            <table class="table employees-table">
                <thead>
                    <tr>
                        <x-livewire.sortable-header field="id" :sortField="$sortField" :sortDirection="$sortDirection" class="text-start" style="width: 70px;">
                            ID
                        </x-livewire.sortable-header>
                        <x-livewire.sortable-header field="name" :sortField="$sortField" :sortDirection="$sortDirection">
                            Pracownik
                        </x-livewire.sortable-header>
                        <th style="min-width: 200px;">Kwalifikacje</th>
                        <th style="min-width: 220px;">Gdzie jest</th>
                        <x-livewire.sortable-header field="hired_at" :sortField="$sortField" :sortDirection="$sortDirection" class="text-nowrap">
                            Zatrudniony
                        </x-livewire.sortable-header>
                        <th class="text-center" style="min-width: 100px;">Rotacja</th>
                        <th class="text-center" style="min-width: 110px;">Spółka</th>
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
                        <tr data-emp-href="{{ route('employees.show', $employee) }}">
                            <td class="text-muted small">
                                {{ $employee->id }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <x-employee-cell :employee="$employee"  />
                                    @if($employee->isTerminated())
                                        <x-ui.badge variant="danger">Zwolniony</x-ui.badge>
                                    @endif
                                    @if($employee->has_komornik)
                                        <x-ui.badge variant="warning">Komornik</x-ui.badge>
                                    @endif
                                </div>
                                @include('livewire.partials.employee-list-meta')
                            </td>
                            <td class="employees-table__stack">
                                @include('livewire.partials.employee-qualifications')
                            </td>
                            <td class="employees-table__stack">
                                @include('livewire.partials.employee-where')
                            </td>
                            <td class="text-nowrap small" @if($employee->hired_at) title="{{ $employee->hired_at->format('d.m.Y') }}" @endif>
                                {{ $employee->hired_at?->locale('pl')->diffForHumans() ?? '—' }}
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

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
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

<script>
    if (!window.__empRowNav) {
        window.__empRowNav = true;
        document.addEventListener('click', (event) => {
            const row = event.target.closest('[data-emp-href]');
            if (!row || event.target.closest('a, button, input, select, label')) {
                return;
            }
            window.location.assign(row.getAttribute('data-emp-href'));
        });
    }
</script>
