<div>
    <!-- Statystyki i Filtry -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <!-- Statystyki -->
            <div class="mb-4 pb-3 border-bottom">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h3 class="fs-5 fw-semibold text-dark mb-1">Pracownicy</h3>
                        <p class="small text-muted mb-0">
                            @if($search || $roleFilter)
                                Znaleziono: <span class="fw-semibold text-dark">{{ $employees->total() }}</span> pracowników
                            @else
                                Łącznie: <span class="fw-semibold text-dark">{{ $employees->total() }}</span> pracowników
                            @endif
                        </p>
                    </div>
                    @if($search || $roleFilter)
                        <button wire:click="clearFilters" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                        </button>
                    @endif
                </div>
            </div>

            <!-- Filtry -->
            <div class="row g-3">
                <!-- Wyszukiwanie -->
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">
                        <i class="bi bi-search me-1"></i> Szukaj
                    </label>
                    <div class="position-relative">
                        <input type="text" wire:model.live.debounce.300ms="search" 
                            placeholder="Imię, nazwisko lub email..."
                            class="form-control form-control-sm ps-5">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                        @if($search)
                            <div wire:loading class="position-absolute top-50 end-0 translate-middle-y me-3">
                                <span class="spinner-border spinner-border-sm text-muted" role="status"></span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Rola -->
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">
                        <i class="bi bi-person-badge me-1"></i> Rola
                    </label>
                    <select wire:model.live="roleFilter" class="form-select form-select-sm">
                        <option value="">Wszystkie role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela -->
    <div class="card shadow-sm border-0 position-relative">
        <!-- Wskaźnik ładowania -->
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
                            <button wire:click="sortBy('name')" class="btn btn-link text-decoration-none p-0 fw-semibold text-dark d-flex align-items-center gap-1">
                                <span>Imię i Nazwisko</span>
                                @if($sortField === 'name')
                                    @if($sortDirection === 'asc')
                                        <i class="bi bi-chevron-up"></i>
                                    @else
                                        <i class="bi bi-chevron-down"></i>
                                    @endif
                                @else
                                    <i class="bi bi-chevron-expand text-muted"></i>
                                @endif
                            </button>
                        </th>
                        <th class="text-start d-none d-md-table-cell">
                            <button wire:click="sortBy('email')" class="btn btn-link text-decoration-none p-0 fw-semibold text-dark d-flex align-items-center gap-1">
                                <span>Email</span>
                                @if($sortField === 'email')
                                    @if($sortDirection === 'asc')
                                        <i class="bi bi-chevron-up"></i>
                                    @else
                                        <i class="bi bi-chevron-down"></i>
                                    @endif
                                @else
                                    <i class="bi bi-chevron-expand text-muted"></i>
                                @endif
                            </button>
                        </th>
                        <th class="text-start">Rola</th>
                        <th class="text-start d-none d-lg-table-cell">Zasoby</th>
                        <th class="text-start">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr>
                            <td>
                                @if($employee->image_path)
                                    <img src="{{ $employee->image_url }}" alt="{{ $employee->full_name }}" 
                                        class="rounded-circle border border-2" 
                                        style="width: 50px; height: 50px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-primary bg-opacity-75 d-flex align-items-center justify-content-center border border-2" 
                                        style="width: 50px; height: 50px;">
                                        <span class="text-white small fw-semibold">{{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}</span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-medium text-dark">{{ $employee->full_name }}</div>
                                @if($employee->phone)
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-telephone"></i> {{ $employee->phone }}
                                    </div>
                                @endif
                                <div class="d-md-none small text-muted mt-1">{{ $employee->email }}</div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div class="text-dark d-flex align-items-center">
                                    <i class="bi bi-envelope me-2 text-muted"></i>
                                    {{ $employee->email }}
                                </div>
                            </td>
                            <td>
                                @if($employee->roles->count() > 0)
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($employee->roles as $role)
                                            <span class="badge bg-primary">{{ $role->name }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted small">Brak ról</span>
                                @endif
                            </td>
                            <td class="d-none d-lg-table-cell">
                                <div class="d-flex gap-2">
                                    <a href="{{ route('employees.vehicles.index', $employee) }}" 
                                        class="btn btn-sm btn-warning">
                                        <i class="bi bi-car-front"></i> Pojazdy
                                    </a>
                                    <a href="{{ route('employees.accommodations.index', $employee) }}" 
                                        class="btn btn-sm btn-danger">
                                        <i class="bi bi-house"></i> Mieszkania
                                    </a>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                        <span class="d-none d-sm-inline ms-1">Zobacz</span>
                                    </a>
                                    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                        <span class="d-none d-sm-inline ms-1">Edytuj</span>
                                    </a>
                                </div>
                                <!-- Mobile: Zasoby -->
                                <div class="d-lg-none mt-2">
                                    <a href="{{ route('employees.vehicles.index', $employee) }}" 
                                        class="btn btn-sm btn-outline-warning me-1">
                                        <i class="bi bi-car-front"></i>
                                    </a>
                                    <a href="{{ route('employees.accommodations.index', $employee) }}" 
                                        class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-house"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-people"></i>
                                    <p class="text-muted small fw-medium mb-2">
                                        @if($search || $roleFilter)
                                            Brak pracowników spełniających kryteria wyszukiwania
                                        @else
                                            Brak pracowników
                                        @endif
                                    </p>
                                    @if($search || $roleFilter)
                                        <button wire:click="clearFilters" class="btn btn-sm btn-link text-primary">
                                            Wyczyść filtry
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginacja -->
        @if($employees->hasPages())
            <div class="card-footer bg-light">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
</div>
