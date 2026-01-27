<div>
    <!-- Statystyki i Filtry -->
    <x-ui.card class="mb-4">
        <!-- Statystyki -->
        <div class="mb-4 pb-3 border-top border-bottom">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h3 class="fs-5 fw-semibold mb-1">Projekty</h3>
                    <p class="small text-muted mb-0">
                        @php
                            $totalProjects = $projects->total();
                        @endphp
                        @if($search || $statusFilter || $locationFilter)
                            Znaleziono: <span class="fw-semibold">{{ $totalProjects }}</span> projektów
                        @else
                            Łącznie: <span class="fw-semibold">{{ $totalProjects }}</span> projektów
                        @endif
                    </p>
                </div>
                @if($search || $statusFilter || $locationFilter)
                    <x-ui.button variant="ghost" wire:click="clearFilters" class="btn-sm">
                        <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                    </x-ui.button>
                @endif
            </div>
        </div>

        <!-- Filtry -->
        <div class="row g-3">
            <!-- Wyszukiwanie -->
            <div class="col-md-4">
                <label class="form-label small">
                    <i class="bi bi-search me-1"></i> Szukaj
                </label>
                <div class="position-relative">
                    <input type="text" wire:model.live.debounce.500ms="search" 
                        placeholder="Nazwa projektu lub klient..."
                        class="form-control ps-5">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                </div>
            </div>

            <!-- Status -->
            <div class="col-md-4">
                <label class="form-label small">
                    <i class="bi bi-check-circle me-1"></i> Status
                </label>
                <select wire:model.live.debounce.300ms="statusFilter" class="form-control">
                    <option value="">Wszystkie statusy</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Lokalizacja -->
            <div class="col-md-4">
                <label class="form-label small">
                    <i class="bi bi-geo-alt me-1"></i> Lokalizacja
                </label>
                <select wire:model.live.debounce.300ms="locationFilter" class="form-control">
                    <option value="">Wszystkie lokalizacje</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
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
                        <x-livewire.sortable-header field="name" :sortField="$sortField" :sortDirection="$sortDirection">
                            Nazwa
                        </x-livewire.sortable-header>
                        <th class="text-start d-none d-md-table-cell">Klient</th>
                        <x-livewire.sortable-header field="status" :sortField="$sortField" :sortDirection="$sortDirection">
                            Status
                        </x-livewire.sortable-header>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($projects as $project)
                        <tr wire:key="project-{{ $project->id }}">
                            <td>
                                <div class="fw-medium">{{ $project->name }}</div>
                                @if($project->location)
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-geo-alt"></i> {{ $project->location->name }}
                                    </div>
                                @endif
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div>{{ $project->client_name ?? '-' }}</div>
                            </td>
                            <td>
                                @php
                                    $status = $project->status ?? \App\Enums\ProjectStatus::ACTIVE;
                                    $statusValue = $status instanceof \App\Enums\ProjectStatus ? $status->value : $status;
                                    $statusLabel = $status instanceof \App\Enums\ProjectStatus ? $status->label() : ucfirst($status);
                                    
                                    $badgeVariant = match(\App\Services\StatusColorService::getProjectStatusColor($status)) {
                                        'success' => 'success',
                                        'danger' => 'danger',
                                        'warning' => 'warning',
                                        'info' => 'info',
                                        'primary' => 'info',
                                        'secondary' => 'info',
                                        default => 'info'
                                    };
                                @endphp
                                <x-ui.badge variant="{{ $badgeVariant }}">
                                    {{ $statusLabel }}
                                </x-ui.badge>
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-2 justify-content-end">
                                    <x-ui.button variant="ghost" href="{{ route('projects.show', $project) }}" class="btn-sm">
                                        <i class="bi bi-eye"></i>
                                        <span class="d-none d-sm-inline ms-1">Zobacz</span>
                                    </x-ui.button>
                                    <x-ui.button variant="ghost" href="{{ route('projects.show', $project) }}" class="btn-sm">
                                        <i class="bi bi-people"></i>
                                        <span class="d-none d-sm-inline ms-1">Pracownicy</span>
                                    </x-ui.button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-state 
                            icon="folder-x"
                            :message="$search || $statusFilter || $locationFilter ? 'Brak projektów spełniających kryteria wyszukiwania' : 'Brak projektów'"
                            :has-filters="$search || $statusFilter || $locationFilter"
                            clear-filters-action="wire:clearFilters"
                            :in-table="true"
                            colspan="4"
                        />
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginacja -->
        @if($projects->hasPages())
            <div class="mt-3 pt-3 border-top">
                {{ $projects->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
