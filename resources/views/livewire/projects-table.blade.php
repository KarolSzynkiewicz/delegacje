<div>
    <!-- Statystyki i Filtry -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <!-- Statystyki -->
            <div class="mb-4 pb-3 border-bottom">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h3 class="fs-5 fw-semibold text-dark mb-1">Projekty</h3>
                        <p class="small text-muted mb-0">
                            @php
                                $totalProjects = $projects->total();
                            @endphp
                            @if($search || $statusFilter || $locationFilter)
                                Znaleziono: <span class="fw-semibold text-dark">{{ $totalProjects }}</span> projektów
                            @else
                                Łącznie: <span class="fw-semibold text-dark">{{ $totalProjects }}</span> projektów
                            @endif
                        </p>
                    </div>
                    @if($search || $statusFilter || $locationFilter)
                        <button wire:click="clearFilters" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                        </button>
                    @endif
                </div>
            </div>

            <!-- Filtry -->
            <div class="row g-3">
                <!-- Wyszukiwanie -->
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">
                        <i class="bi bi-search me-1"></i> Szukaj
                    </label>
                    <div class="position-relative">
                        <input type="text" wire:model.live.debounce.500ms="search" 
                            placeholder="Nazwa projektu lub klient..."
                            class="form-control form-control-sm ps-5">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                        @if($search)
                            <div wire:loading class="position-absolute top-50 end-0 translate-middle-y me-3">
                                <span class="spinner-border spinner-border-sm text-muted" role="status"></span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Status -->
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">
                        <i class="bi bi-check-circle me-1"></i> Status
                    </label>
                    <select wire:model.live="statusFilter" class="form-select form-select-sm">
                        <option value="">Wszystkie statusy</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Lokalizacja -->
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">
                        <i class="bi bi-geo-alt me-1"></i> Lokalizacja
                    </label>
                    <select wire:model.live="locationFilter" class="form-select form-select-sm">
                        <option value="">Wszystkie lokalizacje</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela -->
    <div class="card shadow-sm border-0 position-relative">
        <!-- Wskaźnik ładowania -->
        <div wire:loading class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-90 d-flex align-items-center justify-content-center rounded z-3">
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
                        <th class="text-start">
                            <button wire:click="sortBy('name')" class="btn btn-link text-decoration-none p-0 fw-semibold text-dark d-flex align-items-center gap-1">
                                <span>Nazwa</span>
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
                        <th class="text-start d-none d-md-table-cell">Klient</th>
                        <th class="text-start">
                            <button wire:click="sortBy('status')" class="btn btn-link text-decoration-none p-0 fw-semibold text-dark d-flex align-items-center gap-1">
                                <span>Status</span>
                                @if($sortField === 'status')
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
                        <th class="text-start">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($projects as $project)
                        <tr>
                            <td>
                                <div class="fw-medium text-dark">{{ $project->name }}</div>
                                @if($project->location)
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-geo-alt"></i> {{ $project->location->name }}
                                    </div>
                                @endif
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div class="text-dark">{{ $project->client_name ?? '-' }}</div>
                            </td>
                            <td>
                                <x-badge type="{{ \App\Services\StatusColorService::getProjectStatusColor($project->status) }}">
                                    {{ ucfirst($project->status) }}
                                </x-badge>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('projects.show', $project) }}" 
                                        class="btn btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                        <span class="d-none d-sm-inline ms-1">Zobacz</span>
                                    </a>
                                    <a href="{{ route('projects.assignments.index', $project) }}" 
                                        class="btn btn-outline-success">
                                        <i class="bi bi-people"></i>
                                        <span class="d-none d-sm-inline ms-1">Pracownicy</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-folder-x"></i>
                                    <p class="text-muted small fw-medium mb-2">
                                        @if($search || $statusFilter || $locationFilter)
                                            Brak projektów spełniających kryteria wyszukiwania
                                        @else
                                            Brak projektów
                                        @endif
                                    </p>
                                    @if($search || $statusFilter || $locationFilter)
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
        @if($projects->hasPages())
            <div class="card-footer bg-light">
                {{ $projects->links() }}
            </div>
        @endif
    </div>
</div>
