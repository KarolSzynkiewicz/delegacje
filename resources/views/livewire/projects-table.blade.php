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
                <input type="text" wire:model.live.debounce.500ms="search" 
                    placeholder="Nazwa projektu lub klient..."
                    class="form-control">
            </div>

            <!-- Status -->
            <div class="col-md-4">
                <label class="form-label small">
                    <i class="bi bi-check-circle me-1"></i> Status
                </label>
                <select wire:model.live.debounce.300ms="statusFilter" class="form-control">
                    <option value="">Wszystkie statusy</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
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
                        <th class="text-start">Lokalizacja</th>
                        <x-livewire.sortable-header field="start_date" :sortField="$sortField" :sortDirection="$sortDirection" class="text-start">
                            Data od
                        </x-livewire.sortable-header>
                        <x-livewire.sortable-header field="end_date" :sortField="$sortField" :sortDirection="$sortDirection" class="text-start">
                            Data do
                        </x-livewire.sortable-header>
                        <th class="text-start">Stan</th>
                        <x-livewire.sortable-header field="status" :sortField="$sortField" :sortDirection="$sortDirection">
                            Status
                        </x-livewire.sortable-header>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($projects as $project)
                        @php
                            // Status enum (ręcznie ustawiony)
                            $status = $project->status ?? \App\Enums\ProjectStatus::ACTIVE;
                            $statusLabel = $status instanceof \App\Enums\ProjectStatus ? $status->label() : ucfirst($status);
                            $badgeVariant = match(\App\Services\StatusColorService::getProjectStatusColor($status)) {
                                'success' => 'success',
                                'danger'  => 'danger',
                                'warning' => 'warning',
                                default   => 'info',
                            };

                            // Stan wyliczany z dat (HasDateRange)
                            if ($project->isScheduled()) {
                                $stateLabel   = 'Zaplanowany';
                                $stateVariant = 'warning';
                                $stateIcon    = 'clock';
                            } elseif ($project->isCurrentlyActive()) {
                                $stateLabel   = 'Aktywny';
                                $stateVariant = 'success';
                                $stateIcon    = 'play-circle';
                            } elseif ($project->isPast()) {
                                $stateLabel   = 'Zakończony';
                                $stateVariant = 'info';
                                $stateIcon    = 'check-circle';
                            } else {
                                $stateLabel   = 'Brak dat';
                                $stateVariant = 'accent';
                                $stateIcon    = 'dash-circle';
                            }
                        @endphp
                        <tr wire:key="project-{{ $project->id }}">
                            <td>
                                <div class="fw-medium">{{ $project->name }}</div>
                                @php
                                    $type = $project->type ?? \App\Enums\ProjectType::CONTRACT;
                                    $typeValue = $type instanceof \App\Enums\ProjectType ? $type->value : $type;
                                    $typeInfo = $typeValue === 'hourly'
                                        ? ($project->hourly_rate ? number_format($project->hourly_rate, 2, ',', ' ') . ' ' . ($project->currency ?? 'EUR') . '/h' : '')
                                        : ($project->contract_amount ? number_format($project->contract_amount, 2, ',', ' ') . ' ' . ($project->currency ?? 'PLN') : '');
                                @endphp
                                @if($typeInfo)
                                    <div class="small text-muted mt-1">{{ $typeInfo }}</div>
                                @endif
                            </td>
                            <td class="d-none d-md-table-cell">
                                {{ $project->client_name ?? '-' }}
                            </td>
                            <td>
                                @if($project->location)
                                    <div><i class="bi bi-geo-alt text-muted me-1"></i>{{ $project->location->name }}</div>
                                    @if($project->location->city)
                                        <div class="small text-muted">{{ $project->location->city }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if($project->start_date)
                                    {{ $project->start_date->format('d.m.Y') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if($project->end_date)
                                    {{ $project->end_date->format('d.m.Y') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <x-ui.badge variant="{{ $stateVariant }}">
                                    <i class="bi bi-{{ $stateIcon }} me-1"></i>{{ $stateLabel }}
                                </x-ui.badge>
                            </td>
                            <td>
                                <x-ui.badge variant="{{ $badgeVariant }}">
                                    {{ $statusLabel }}
                                </x-ui.badge>
                            </td>
                            <td class="text-end">
                                @php $showRoute = $isMineView ? 'mine.projects.show' : 'projects.show'; @endphp
                                <x-ui.button variant="ghost" href="{{ route($showRoute, $project) }}" class="btn-sm">
                                    <i class="bi bi-eye"></i>
                                    <span class="d-none d-sm-inline ms-1">Zobacz</span>
                                </x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-state 
                            icon="folder-x"
                            :message="$search || $statusFilter || $locationFilter ? 'Brak projektów spełniających kryteria wyszukiwania' : 'Brak projektów'"
                            :has-filters="$search || $statusFilter || $locationFilter"
                            clear-filters-action="wire:clearFilters"
                            :in-table="true"
                            colspan="8"
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
