<div>
    @php $filterBadgeCount = count($filterChips); @endphp
    <x-ui.card class="mb-2">
        <div class="emp-toolbar">
            <div class="input-group emp-toolbar__search">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Szukaj…" class="form-control" aria-label="Szukaj">
            </div>

            <label class="emp-toolbar__date">
                <span>Stan na dzień</span>
                <input type="date" wire:model.live="statusDate" class="form-control" aria-label="Stan na dzień">
            </label>

            <div x-data="{ open: false, top: 0, left: 0 }">
                <button type="button"
                        @click.stop="if (open) { open = false; return } const r = $el.getBoundingClientRect(); const pw = Math.min(420, window.innerWidth - 24); top = r.bottom + 4; left = Math.max(4, Math.min(r.left, window.innerWidth - pw - 4)); open = true"
                        class="btn btn-sm btn-outline-secondary emp-quiet-btn {{ $filterBadgeCount > 0 ? 'is-on' : '' }}">
                    <i class="bi bi-sliders me-1"></i>Filtry
                    @if($filterBadgeCount > 0)
                        <span class="emp-quiet-count">{{ $filterBadgeCount }}</span>
                    @endif
                </button>
                <template x-teleport="body">
                    <div x-show="open" x-cloak @click.outside="open = false"
                         :style="`position:fixed;top:${top}px;left:${left}px;z-index:999990;width:min(420px, calc(100vw - 24px))`"
                         class="rp-filter-panel">
                        @include('livewire.partials.emp-filter-panel')
                    </div>
                </template>
            </div>

            <div x-data="{ open: false, top: 0, left: 0 }">
                <button type="button"
                        @click.stop="if (open) { open = false; return } const r = $el.getBoundingClientRect(); const pw = Math.min(320, window.innerWidth - 24); top = r.bottom + 4; left = Math.max(4, Math.min(r.left, window.innerWidth - pw - 4)); open = true"
                        class="btn btn-sm btn-outline-secondary emp-quiet-btn">
                    <i class="bi bi-layout-three-columns me-1"></i>Kolumny
                    <span class="emp-quiet-count">{{ $this->visibleColumnCount() }}</span>
                </button>
                <template x-teleport="body">
                    <div x-show="open" x-cloak @click.outside="open = false"
                         :style="`position:fixed;top:${top}px;left:${left}px;z-index:999990;width:min(320px, calc(100vw - 24px))`"
                         class="rp-filter-panel">
                        @include('livewire.partials.emp-columns-panel')
                    </div>
                </template>
            </div>

            <div class="emp-toolbar__views">
                @foreach($savedViews as $savedView)
                    <button type="button"
                            wire:click="loadSavedView({{ $savedView->id }})"
                            class="btn btn-sm btn-outline-secondary emp-quiet-btn {{ $activeViewId === $savedView->id ? 'is-on' : '' }}"
                            title="{{ $savedView->is_global ? 'Widok globalny' : 'Twój widok' }}">
                        <i class="bi bi-{{ $savedView->is_global ? 'globe' : 'bookmark' }} me-1"></i>{{ $savedView->name }}
                    </button>
                @endforeach
            </div>

            <div class="ms-auto d-flex align-items-center gap-2">
                <div x-data="{ open: false, top: 0, left: 0 }">
                    <button type="button"
                            @click.stop="if (open) { open = false; return } const r = $el.getBoundingClientRect(); const pw = Math.min(300, window.innerWidth - 24); top = r.bottom + 4; left = Math.max(4, Math.min(r.right - pw, window.innerWidth - pw - 4)); open = true"
                            class="btn btn-sm btn-outline-secondary emp-quiet-btn {{ $activeViewId ? 'is-on' : '' }}"
                            title="Zapisz i zarządzaj widokami">
                        <i class="bi bi-bookmark{{ $activeViewId ? '-fill' : '' }}"></i>
                    </button>
                    <template x-teleport="body">
                        <div x-show="open" x-cloak @click.outside="open = false"
                             :style="`position:fixed;top:${top}px;left:${left}px;z-index:999990;width:min(300px, calc(100vw - 24px))`"
                             class="dropdown-menu show emp-view-menu shadow-lg">
                            @if($activeViewId && $activeViewName)
                                <div class="small fw-semibold mb-2">{{ $activeViewName }}</div>
                                <button type="button" wire:click="clearView" @click="open = false" class="btn btn-sm btn-outline-secondary w-100 mb-2">Widok domyślny</button>
                            @endif
                            @foreach($savedViews as $savedView)
                                <div class="d-flex align-items-center gap-1 mb-1">
                                    <button type="button" wire:click="loadSavedView({{ $savedView->id }})" @click="open = false"
                                            class="btn btn-sm btn-link text-start flex-grow-1 p-1 text-decoration-none {{ $activeViewId === $savedView->id ? 'fw-bold' : '' }}">
                                        <i class="bi bi-{{ $savedView->is_global ? 'globe' : 'bookmark' }} me-1"></i>{{ $savedView->name }}
                                    </button>
                                    @if($savedView->canBeManagedBy(auth()->user()))
                                        <button type="button" wire:click="overwriteView({{ $savedView->id }})" @click="open = false" class="btn btn-sm btn-link p-1" title="Nadpisz bieżącymi filtrami">
                                            <i class="bi bi-floppy"></i>
                                        </button>
                                        <button type="button" wire:click="deleteView({{ $savedView->id }})" @click="open = false" class="btn btn-sm btn-link p-1 text-danger" title="Usuń">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                            <div class="small text-muted text-uppercase mt-2 mb-1" style="letter-spacing:.04em;font-size:.68rem">Zapisz jako nowy widok</div>
                            <div class="d-flex gap-2">
                                <input wire:model="saveViewName" type="text" class="form-control form-control-sm" placeholder="Nazwa widoku…" wire:keydown.enter="saveView" @click.stop>
                                <button type="button" wire:click="saveView" @click="open = false" class="btn btn-sm btn-primary"><i class="bi bi-floppy"></i></button>
                            </div>
                            <label class="form-check form-check-compact mt-2 mb-0" @click.stop>
                                <input type="checkbox" class="form-check-input" wire:model="saveViewAsGlobal">
                                <span>Widok globalny</span>
                            </label>
                        </div>
                    </template>
                </div>
                <span class="emp-toolbar__count">Rekordów: <strong>{{ $employees->total() }}</strong></span>
            </div>
        </div>
        @if($notice !== '')
            <div class="small text-muted mt-2">{{ $notice }}</div>
        @endif
    </x-ui.card>

    @if(count($filterChips) > 0)
        <div class="rp-active-filters mb-2 px-1">
            <span class="rp-active-filters__label">Filtry:</span>
            @foreach($filterChips as $chip)
                <span class="rp-active-filters__chip" wire:key="emp-filter-{{ $chip['key'] }}">
                    <span>{{ $chip['label'] }}</span>
                    <button type="button" wire:click="clearFilter('{{ $chip['key'] }}')" class="rp-active-filters__chip-remove" title="Usuń filtr">
                        <i class="bi bi-x"></i>
                    </button>
                </span>
            @endforeach
            <button type="button" wire:click="clearFilters" class="rp-active-filters__clear">Wyczyść</button>
        </div>
    @endif

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
                @if($this->showsColumn('qualifications'))
                    <div class="mb-2">
                        @include('livewire.partials.employee-qualifications')
                    </div>
                @endif
                <div class="d-flex flex-wrap gap-1 mb-2">
                    @if($this->showsColumn('rotation'))
                        <x-ui.badge :variant="$hasActiveRotation ? 'success' : 'danger'">
                            {{ $hasActiveRotation ? '✓' : '✗' }} Rotacja
                        </x-ui.badge>
                    @endif
                    @if($this->showsColumn('company') && $companyAssignment && $companyAssignment->company)
                        <x-ui.badge variant="secondary">🏢 {{ $companyAssignment->company->name }}</x-ui.badge>
                    @endif
                </div>
                @if($this->showsColumn('where'))
                    @include('livewire.partials.employee-where')
                @endif
                @if($this->showsColumn('hired') && $employee->hired_at)
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
                        @if($this->showsColumn('id'))
                            <x-livewire.sortable-header field="id" :sortField="$sortField" :sortDirection="$sortDirection" class="text-start" style="width: 70px;">
                                ID
                            </x-livewire.sortable-header>
                        @endif
                        <x-livewire.sortable-header field="name" :sortField="$sortField" :sortDirection="$sortDirection">
                            Pracownik
                        </x-livewire.sortable-header>
                        @if($this->showsColumn('qualifications'))
                            <th data-emp-col="qualifications" style="min-width: 200px;">Kwalifikacje</th>
                        @endif
                        @if($this->showsColumn('where'))
                            <th style="min-width: 220px;">Gdzie jest</th>
                        @endif
                        @if($this->showsColumn('hired'))
                            <x-livewire.sortable-header field="hired_at" :sortField="$sortField" :sortDirection="$sortDirection" class="text-nowrap">
                                Zatrudniony
                            </x-livewire.sortable-header>
                        @endif
                        @if($this->showsColumn('rotation'))
                            <th class="text-center" style="min-width: 100px;">Rotacja</th>
                        @endif
                        @if($this->showsColumn('company'))
                            <th class="text-center" style="min-width: 110px;">Spółka</th>
                        @endif
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
                            @if($this->showsColumn('id'))
                            <td class="text-muted small">
                                {{ $employee->id }}
                            </td>
                            @endif
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
                            @if($this->showsColumn('qualifications'))
                            <td class="employees-table__stack">
                                @include('livewire.partials.employee-qualifications')
                            </td>
                            @endif
                            @if($this->showsColumn('where'))
                            <td class="employees-table__stack">
                                @include('livewire.partials.employee-where')
                            </td>
                            @endif
                            @if($this->showsColumn('hired'))
                            <td class="text-nowrap small" @if($employee->hired_at) title="{{ $employee->hired_at->format('d.m.Y') }}" @endif>
                                {{ $employee->hired_at?->locale('pl')->diffForHumans() ?? '—' }}
                            </td>
                            @endif
                            
                            @if($this->showsColumn('rotation'))
                            <td class="text-center">
                                @if($hasActiveRotation)
                                    <x-ui.badge variant="success">✓ Tak</x-ui.badge>
                                @else
                                    <x-ui.badge variant="danger">✗ Nie</x-ui.badge>
                                @endif
                            </td>
                            @endif

                            @if($this->showsColumn('company'))
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
                            @endif

                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $this->visibleColumnCount() }}" class="text-center py-4">
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
