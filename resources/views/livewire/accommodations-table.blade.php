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
            <table class="table">
                <thead>
                    <tr>
                        <th class="text-start">Zdjęcie</th>
                        <th class="text-start">
                            <button wire:click="sortBy('name')" class="btn-link text-decoration-none p-0 fw-semibold d-flex align-items-center gap-1" style="background: none; border: none; color: var(--text-main);">
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
                                    @if($accommodation->image_path)
                                        <img src="{{ $accommodation->image_url }}" alt="{{ $accommodation->name }}" class="rounded border border-2 accommodation-image">
                                    @else
                                        <div class="avatar-ui accommodation-image">
                                            <span>{{ substr($accommodation->name, 0, 2) }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium">{{ $accommodation->name }}</div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div>{{ $accommodation->address }}{{ $accommodation->city ? ', ' . $accommodation->city : '' }}</div>
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
                                    <x-ui.button variant="ghost" href="{{ route('accommodations.show', $accommodation) }}" class="btn-sm">
                                        <i class="bi bi-eye"></i>
                                        <span class="d-none d-sm-inline ms-1">Zobacz</span>
                                    </x-ui.button>
                                    <x-ui.button variant="ghost" href="{{ route('accommodations.edit', $accommodation) }}" class="btn-sm">
                                        <i class="bi bi-pencil"></i>
                                        <span class="d-none d-sm-inline ms-1">Edytuj</span>
                                    </x-ui.button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-house-x text-muted fs-1 d-block mb-2"></i>
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
            <div class="mt-3 pt-3 border-top">
                {{ $accommodations->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
