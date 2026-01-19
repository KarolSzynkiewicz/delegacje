<div>
    <!-- Statystyki i Filtry -->
    <x-ui.card class="mb-4">
        <!-- Statystyki -->
        <div class="mb-4 pb-3 border-top border-bottom">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h3 class="fs-5 fw-semibold mb-1">Pracownicy</h3>
                    <p class="small text-muted mb-0">
                        @if($search || $roleFilter)
                            Znaleziono: <span class="fw-semibold">{{ $employees->total() }}</span> pracowników
                        @else
                            Łącznie: <span class="fw-semibold">{{ $employees->total() }}</span> pracowników
                        @endif
                    </p>
                </div>
                @if($search || $roleFilter)
                    <x-ui.button variant="ghost" wire:click="clearFilters" class="btn-sm">
                        <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                    </x-ui.button>
                @endif
            </div>
        </div>

        <!-- Filtry -->
        <div class="row g-3">
            <!-- Wyszukiwanie -->
            <div class="col-md-6">
                <label class="form-label small">
                    <i class="bi bi-search me-1"></i> Szukaj
                </label>
                <div class="position-relative">
                    <input type="text" wire:model.live.debounce.300ms="search" 
                        placeholder="Imię, nazwisko lub email..."
                        class="form-control ps-5">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                </div>
            </div>

            <!-- Rola -->
            <div class="col-md-6">
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
        </div>
    </x-ui.card>

    <!-- Tabela -->
    <x-ui.card>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th class="text-start">Zdjęcie</th>
                        <x-livewire.sortable-header field="name" :sortField="$sortField" :sortDirection="$sortDirection">
                            Imię i Nazwisko
                        </x-livewire.sortable-header>
                        <x-livewire.sortable-header field="email" :sortField="$sortField" :sortDirection="$sortDirection" class="d-none d-md-table-cell">
                            Email
                        </x-livewire.sortable-header>
                        <th class="text-start">Rola</th>
                        <th class="text-start d-none d-lg-table-cell">Zasoby</th>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr>
                            <td>
                                <x-ui.avatar 
                                    :image-url="$employee->image_path ? $employee->image_url : null"
                                    :alt="$employee->full_name"
                                    :initials="substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)"
                                    size="50px"
                                    shape="circle"
                                />
                            </td>
                            <td>
                                <div class="fw-medium">{{ $employee->full_name }}</div>
                                @if($employee->phone)
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-telephone"></i> {{ $employee->phone }}
                                    </div>
                                @endif
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
                            <td class="d-none d-lg-table-cell">
                                <div class="d-flex gap-2">
                                    <x-ui.button variant="ghost" href="{{ route('employees.vehicles.index', $employee) }}" class="btn-sm">
                                        <i class="bi bi-car-front"></i> Pojazdy
                                    </x-ui.button>
                                    <x-ui.button variant="ghost" href="{{ route('employees.accommodations.index', $employee) }}" class="btn-sm">
                                        <i class="bi bi-house"></i> Mieszkania
                                    </x-ui.button>
                                </div>
                            </td>
                            <td class="text-end">
                                <x-action-buttons
                                viewRoute="{{ route('employees.show', $employee) }}"
                                editRoute="{{ route('employees.edit', $employee) }}"
                                deleteRoute="{{ route('employees.destroy', $employee) }}"
                            />
                                <!-- Mobile: Zasoby -->
                                <div class="d-lg-none mt-2">
                                    <x-ui.button variant="ghost" href="{{ route('employees.vehicles.index', $employee) }}" class="btn-sm me-1">
                                        <i class="bi bi-car-front"></i>
                                    </x-ui.button>
                                    <x-ui.button variant="ghost" href="{{ route('employees.accommodations.index', $employee) }}" class="btn-sm">
                                        <i class="bi bi-house"></i>
                                    </x-ui.button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-state 
                            icon="people"
                            :message="$search || $roleFilter ? 'Brak pracowników spełniających kryteria wyszukiwania' : 'Brak pracowników'"
                            :has-filters="$search || $roleFilter"
                            clear-filters-action="wire:clearFilters"
                            :in-table="true"
                            colspan="6"
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
