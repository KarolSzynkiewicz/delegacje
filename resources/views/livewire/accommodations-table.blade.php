<div>
    <x-ui.card class="mb-4">
        <div class="mb-4 pb-3 border-top border-bottom">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h3 class="fs-5 fw-semibold mb-1">Mieszkania</h3>
                    <p class="small text-muted mb-0">
                        @if($search || $statusFilter)
                            Znaleziono: <span class="fw-semibold">{{ $accommodations->total() }}</span> mieszkań
                        @else
                            Łącznie: <span class="fw-semibold">{{ $accommodations->total() }}</span> mieszkań
                        @endif
                    </p>
                </div>
                @if($search || $statusFilter)
                    <x-ui.button variant="ghost" wire:click="clearFilters" class="btn-sm">
                        <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                    </x-ui.button>
                @endif
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small">
                    <i class="bi bi-search me-1"></i> Szukaj
                </label>
                <div class="position-relative">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Nazwa, adres, miasto..." class="form-control ps-5">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Status</label>
                <select wire:model.live="statusFilter" class="form-control">
                    <option value="">Wszystkie</option>
                    <option value="full">Pełne</option>
                    <option value="available">Wolne miejsca</option>
                </select>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th class="text-start">Zdjęcie</th>
                        <x-livewire.sortable-header field="name" :sortField="$sortField" :sortDirection="$sortDirection">
                            Nazwa
                        </x-livewire.sortable-header>
                        <th class="text-start d-none d-md-table-cell">Adres</th>
                        <th class="text-start">Miasto</th>
                        <th class="text-start">Kraj</th>
                        <th class="text-start">Współrzędne</th>
                        <th class="text-start">Pojemność</th>
                        <th class="text-start">Status</th>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accommodations as $accommodation)
                        @php
                            $currentCount = $accommodation->currentAssignments()->count();
                            $isFull = $currentCount >= $accommodation->capacity;
                            $isOverfilled = $currentCount > $accommodation->capacity;
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center justify-content-center">
                                    <x-ui.avatar 
                                        :image-url="$accommodation->image_path ? $accommodation->image_url : null"
                                        :alt="$accommodation->name"
                                        :initials="substr($accommodation->name, 0, 2)"
                                        size="50px"
                                        shape="rounded"
                                    />
                                </div>
                            </td>
                            <td class="fw-medium">{{ $accommodation->name }}</td>
                            <td class="d-none d-md-table-cell">{{ $accommodation->address }}</td>
                            <td>{{ $accommodation->city ?? '-' }}</td>
                            <td>{{ $accommodation->country ? $accommodation->country->labelWithFlag() : '-' }}</td>
                            <td>
                                @if($accommodation->hasCoordinates())
                                    <div>
                                        <a 
                                            href="https://www.openstreetmap.org/?mlat={{ floatval($accommodation->latitude) }}&mlon={{ floatval($accommodation->longitude) }}&zoom=15" 
                                            target="_blank"
                                            class="text-decoration-none d-inline-flex align-items-center gap-1"
                                            title="Otwórz na mapie"
                                            style="color: var(--success); cursor: pointer;"
                                            onmouseover="this.style.opacity='0.8';"
                                            onmouseout="this.style.opacity='1';"
                                        >
                                            <i class="bi bi-geo-alt-fill"></i>
                                            <small>
                                                {{ number_format((float)$accommodation->latitude, 6) }}, {{ number_format((float)$accommodation->longitude, 6) }}
                                            </small>
                                        </a>
                                    </div>
                                    <div class="mt-1">
                                        <a 
                                            href="https://www.openstreetmap.org/?mlat={{ floatval($accommodation->latitude) }}&mlon={{ floatval($accommodation->longitude) }}&zoom=15" 
                                            target="_blank"
                                            class="btn btn-sm btn-outline-secondary"
                                            style="font-size: 0.75rem; padding: 0.25rem 0.5rem;"
                                            title="Otwórz na mapie"
                                        >
                                            <i class="bi bi-map"></i> Mapa
                                        </a>
                                    </div>
                                @else
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt"></i> Brak
                                    </small>
                                @endif
                            </td>
                            <td>
                                <span class="small {{ $isOverfilled ? 'text-danger fw-bold' : ($isFull ? 'text-success fw-semibold' : 'text-muted') }}">
                                    {{ $currentCount }} / {{ $accommodation->capacity }} osób
                                </span>
                            </td>
                            <td>
                                @if($isOverfilled)
                                    <x-ui.badge variant="danger">Przepełnione</x-ui.badge>
                                @elseif($isFull)
                                    <x-ui.badge variant="warning">Pełne</x-ui.badge>
                                @else
                                    <x-ui.badge variant="success">Wolne miejsca</x-ui.badge>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-2 justify-content-end">
                                    <x-action-buttons    
                                        viewRoute="{{ route('accommodations.show', $accommodation) }}"
                                        editRoute="{{ route('accommodations.edit', $accommodation) }}"
                                        deleteRoute="{{ route('accommodations.destroy', $accommodation) }}"
                                    />                                   
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-state 
                            icon="house-x"
                            :message="$search || $statusFilter ? 'Brak mieszkań spełniających kryteria' : 'Brak mieszkań'"
                            :has-filters="$search || $statusFilter"
                            clear-filters-action="wire:clearFilters"
                            :in-table="true"
                            colspan="9"
                        />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($accommodations->hasPages())
            <div class="mt-3 pt-3 border-top">
                {{ $accommodations->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
