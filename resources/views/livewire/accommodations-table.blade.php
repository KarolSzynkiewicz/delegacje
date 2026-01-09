<div>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="mb-4 pb-3 border-bottom">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h3 class="fs-5 fw-semibold text-dark mb-1">Mieszkania</h3>
                        <p class="small text-muted mb-0">
                            @if($search || $statusFilter)
                                Znaleziono: <span class="fw-semibold text-dark">{{ $accommodations->total() }}</span> mieszkań
                            @else
                                Łącznie: <span class="fw-semibold text-dark">{{ $accommodations->total() }}</span> mieszkań
                            @endif
                        </p>
                    </div>
                    @if($search || $statusFilter)
                        <button wire:click="clearFilters" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                        </button>
                    @endif
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">
                        <i class="bi bi-search me-1"></i> Szukaj
                    </label>
                    <div class="position-relative">
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Nazwa, adres, miasto..." class="form-control form-control-sm ps-5">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Status</label>
                    <select wire:model.live="statusFilter" class="form-select form-select-sm">
                        <option value="">Wszystkie</option>
                        <option value="full">Pełne</option>
                        <option value="available">Wolne miejsca</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 position-relative">
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
                                <span>Nazwa</span>
                                @if($sortField === 'name')
                                    @if($sortDirection === 'asc')
                                        <i class="bi bi-chevron-up"></i>
                                    @else
                                        <i class="bi bi-chevron-down"></i>
                                    @endif
                                @endif
                            </button>
                        </th>
                        <th class="text-start d-none d-md-table-cell">Adres</th>
                        <th class="text-start">Pojemność</th>
                        <th class="text-start">Status</th>
                        <th class="text-start">Akcje</th>
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
                                @if($accommodation->image_path)
                                    <img src="{{ $accommodation->image_url }}" alt="{{ $accommodation->name }}" class="rounded border border-2" style="width: 50px; height: 50px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-danger bg-opacity-75 d-flex align-items-center justify-content-center border border-2" style="width: 50px; height: 50px;">
                                        <span class="text-white small fw-semibold">{{ substr($accommodation->name, 0, 2) }}</span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-medium text-dark">{{ $accommodation->name }}</div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div class="text-dark">{{ $accommodation->address }}{{ $accommodation->city ? ', ' . $accommodation->city : '' }}</div>
                            </td>
                            <td>
                                <span class="small {{ $isOverfilled ? 'text-danger fw-bold' : ($isFull ? 'text-success fw-semibold' : 'text-muted') }}">
                                    {{ $currentCount }} / {{ $accommodation->capacity }} osób
                                </span>
                            </td>
                            <td>
                                @if($isOverfilled)
                                    <span class="badge bg-danger">Przepełnione</span>
                                @elseif($isFull)
                                    <span class="badge bg-warning">Pełne</span>
                                @else
                                    <span class="badge bg-success">Wolne miejsca</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('accommodations.show', $accommodation) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                        <span class="d-none d-sm-inline ms-1">Zobacz</span>
                                    </a>
                                    <a href="{{ route('accommodations.edit', $accommodation) }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                        <span class="d-none d-sm-inline ms-1">Edytuj</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-house-x"></i>
                                    <p class="text-muted small fw-medium mb-0">
                                        @if($search || $statusFilter)
                                            Brak mieszkań spełniających kryteria
                                        @else
                                            Brak mieszkań
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($accommodations->hasPages())
            <div class="card-footer bg-light">
                {{ $accommodations->links() }}
            </div>
        @endif
    </div>
</div>
