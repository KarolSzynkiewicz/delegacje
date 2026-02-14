<div>
    <!-- Statystyki i Filtry -->
    <x-ui.card class="mb-4">
        <!-- Statystyki -->
        <div class="mb-4 pb-3 border-top border-bottom">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h3 class="fs-5 fw-semibold mb-1">Pracownicy</h3>
                    <p class="small text-muted mb-0">
                        @if($search || $roleFilter || $locationFilter || $rotationFilter)
                            Znaleziono: <span class="fw-semibold">{{ $employees->total() }}</span> pracowników
                        @else
                            Łącznie: <span class="fw-semibold">{{ $employees->total() }}</span> pracowników
                        @endif
                    </p>
                </div>
                @if($search || $roleFilter || $locationFilter || $rotationFilter)
                    <x-ui.button variant="ghost" wire:click="clearFilters" class="btn-sm">
                        <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                    </x-ui.button>
                @endif
            </div>
        </div>

        <!-- Filtry -->
        <div class="row g-3">
            <!-- Wyszukiwanie -->
            <div class="col-md-3">
                <label class="form-label small">
                    <i class="bi bi-search me-1"></i> Szukaj
                </label>
                <input type="text" wire:model.live.debounce.300ms="search" 
                    placeholder="Imię, nazwisko lub email..."
                    class="form-control">
            </div>

            <!-- Rola -->
            <div class="col-md-3">
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
            <div class="col-md-3">
                <label class="form-label small">
                    <i class="bi bi-geo-alt me-1"></i> Lokalizacja
                </label>
                <select wire:model.live="locationFilter" class="form-control">
                    <option value="">Wszystkie lokalizacje</option>
                    <option value="base">Baza</option>
                    <option value="field">W terenie</option>
                    <option value="transit">W podróży</option>
                </select>
            </div>

            <!-- Rotacja -->
            <div class="col-md-3">
                <label class="form-label small">
                    <i class="bi bi-arrow-repeat me-1"></i> Rotacja
                </label>
                <select wire:model.live="rotationFilter" class="form-control">
                    <option value="">Wszystkie</option>
                    <option value="active">Aktywna</option>
                    <option value="inactive">Nieaktywna</option>
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
                        <x-livewire.sortable-header field="name" :sortField="$sortField" :sortDirection="$sortDirection">
                            Pracownik
                        </x-livewire.sortable-header>
                        <x-livewire.sortable-header field="email" :sortField="$sortField" :sortDirection="$sortDirection" class="d-none d-md-table-cell">
                            Email
                        </x-livewire.sortable-header>
                        <th class="text-start">Rola</th>
                        <th class="text-start">Lokalizacja</th>
                        <th class="text-start">Rotacja</th>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr>
                            <td>
                                <x-employee-cell :employee="$employee"  />
                                <div class="d-md-none small text-muted mt-1">{{ $employee->email }}</div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-envelope me-2 text-muted"></i>
                                    {{ $employee->email }}
                                </div>
                            </td>
                            <td>
                                @if($employee->roles->count() > 0)
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($employee->roles as $role)
                                            <x-ui.badge variant="accent">{{ $role->name }}</x-ui.badge>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted small">Brak ról</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    // Jeśli jesteśmy w kontekście /mine/*, pokaż status przypisania do projektów kierownika
                                    if (isset($filterProjectIds) && is_array($filterProjectIds) && !empty($filterProjectIds)) {
                                        // Pobierz wszystkie przypisania pracownika do projektów kierownika
                                        $managerAssignments = $employee->assignments()
                                            ->whereIn('project_id', $filterProjectIds)
                                            ->get();
                                        
                                        $hasActive = false;
                                        $hasScheduled = false;
                                        $hasCompleted = false;
                                        $hasCancelled = false;
                                        
                                        foreach ($managerAssignments as $assignment) {
                                            if ($assignment->is_cancelled) {
                                                $hasCancelled = true;
                                            } elseif ($assignment->isCurrentlyActive()) {
                                                $hasActive = true;
                                            } elseif ($assignment->isScheduled()) {
                                                $hasScheduled = true;
                                            } elseif ($assignment->isPast()) {
                                                $hasCompleted = true;
                                            }
                                        }
                                        
                                        // Priorytet: cancelled > active > scheduled > completed
                                        if ($hasCancelled) {
                                            $status = 'cancelled';
                                            $label = 'Anulowany';
                                            $variant = 'danger';
                                        } elseif ($hasActive) {
                                            $status = 'active';
                                            $label = 'Aktywny';
                                            $variant = 'success';
                                        } elseif ($hasScheduled) {
                                            $status = 'scheduled';
                                            $label = 'Zaplanowany';
                                            $variant = 'info';
                                        } elseif ($hasCompleted) {
                                            $status = 'completed';
                                            $label = 'Historic';
                                            $variant = 'accent';
                                        } else {
                                            $status = null;
                                        }
                                        
                                        if ($status) {
                                            $projectsList = $managerAssignments->pluck('project.name')->filter()->unique()->join(', ');
                                            $tooltipText = 'Projekty: ' . $projectsList;
                                        }
                                    } else {
                                        // Domyślna logika - pokaż lokalizację pracownika
                                        $locationTracker = app(\App\Services\LocationTrackingService::class);
                                        $currentLocation = $locationTracker->forEmployee($employee);
                                        
                                        $currentProjects = $employee->current_projects;
                                        $projectsList = $currentProjects->pluck('name')->join(', ');
                                        
                                        // Check if in transit (use model method)
                                        $inTransit = \App\Models\LogisticsEvent::isEmployeeInTransit($employee, now());
                                        
                                        if ($inTransit) {
                                            $status = 'transit';
                                            $label = '✈️ W podróży';
                                            $variant = 'warning';
                                            $tooltipText = 'Pracownik jest w trakcie wyjazdu/powrotu';
                                        } elseif (!$currentLocation) {
                                            // Brak lokalizacji - nieprzypisany
                                            $status = 'no-location';
                                            $label = '❓ Brak lokalizacji';
                                            $variant = 'accent';
                                            $tooltipText = 'Pracownik nie ma przypisanej lokalizacji';
                                        } elseif ($currentLocation->is_base) {
                                            // W bazie
                                            $status = 'base';
                                            $label = '🏠 Baza';
                                            $variant = 'success';
                                            $tooltipText = 'Pracownik jest w bazie: ' . $currentLocation->name;
                                        } else {
                                            // W terenie - na projekcie
                                            $status = 'field';
                                            $label = '🏢 ' . $currentLocation->name;
                                            $variant = 'info';
                                            $tooltipText = $projectsList 
                                                ? 'Przypisany do: ' . $projectsList . ' w lokalizacji ' . $currentLocation->name
                                                : 'W lokalizacji: ' . $currentLocation->name;
                                        }
                                    }
                                @endphp
                                
                                @if(isset($status))
                                    <x-tooltip title="{{ $tooltipText ?? '' }}">
                                        <x-ui.badge variant="{{ $variant }}">{{ $label }}</x-ui.badge>
                                    </x-tooltip>
                                @endif
                            </td>
                            <td>
                                @php
                                    // Check if employee has active rotation using eager-loaded collection
                                    $today = now();
                                    $activeRotation = $employee->rotations->filter(function($rotation) use ($today) {
                                        $startDate = $rotation->start_date ? \Carbon\Carbon::parse($rotation->start_date) : null;
                                        $endDate = $rotation->end_date ? \Carbon\Carbon::parse($rotation->end_date) : null;
                                        
                                        if (!$startDate) {
                                            return false;
                                        }
                                        
                                        return $startDate->lte($today) 
                                            && ($endDate === null || $endDate->gte($today));
                                    })->first();
                                    
                                    $hasActiveRotation = $activeRotation !== null;
                                @endphp
                                
                                @if($hasActiveRotation && $activeRotation)
                                    <x-tooltip title="Rotacja od {{ $activeRotation->start_date->format('d.m.Y') }} do {{ $activeRotation->end_date ? $activeRotation->end_date->format('d.m.Y') : 'brak daty końca' }}">
                                        <x-ui.badge variant="success">
                                            <i class="bi bi-check-circle me-1"></i>Aktywna
                                        </x-ui.badge>
                                    </x-tooltip>
                                @else
                                    <x-tooltip title="Pracownik nie ma aktywnej rotacji">
                                        <x-ui.badge variant="danger">
                                            <i class="bi bi-x-circle me-1"></i>Nieaktywna
                                        </x-ui.badge>
                                    </x-tooltip>
                                @endif
                            </td>
                            <td class="text-end">
                                <x-action-buttons
                                viewRoute="{{ route('employees.show', $employee) }}"
                                editRoute="{{ route('employees.edit', $employee) }}"
                                deleteRoute="{{ route('employees.destroy', $employee) }}"
                                deleteMessage="⚠️ UWAGA: Usunięcie pracownika spowoduje kaskadowe usunięcie wszystkich powiązanych danych:
• Wszystkie przypisania do projektów
• Wszystkie wpisy czasu pracy
• Wszystkie przypisania do aut i domów
• Wszystkie rekordy płac
• Wszystkie zaliczki
• Wszystkie kary i nagrody
• Wszystkie oceny
• Wszystkie rotacje
• Wszystkie dokumenty

Czy na pewno chcesz usunąć tego pracownika?"
                            />
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-state 
                            icon="people"
                            :message="$search || $roleFilter || $locationFilter || $rotationFilter ? 'Brak pracowników spełniających kryteria wyszukiwania' : 'Brak pracowników'"
                            :has-filters="$search || $roleFilter || $locationFilter || $rotationFilter"
                            clear-filters-action="wire:clearFilters"
                            :in-table="true"
                            colspan="7"
                        />
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

