<x-app-layout>
    
    <x-slot name="header">
        <x-ui.page-header title="Sprzęt">

            <x-slot name="right">
                <x-ui.button 
                    variant="primary"
                    href="{{ route('equipment.create') }}"
                    routeName="equipment.create"
                    action="create"
                >
                    Dodaj Sprzęt
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <!-- Filtry -->
    <x-ui.card class="mb-4">
        <form method="GET" action="{{ route('equipment.index') }}" id="filter-form">
            <div class="row g-3">
                <!-- Wyszukiwanie -->
                <div class="col-md-4">
                    <label class="form-label small">
                        <i class="bi bi-search me-1"></i> Szukaj
                    </label>
                    <div class="position-relative">
                        <input type="text" name="search" value="{{ request('search') }}" 
                            placeholder="Nazwa sprzętu..."
                            class="form-control ps-5"
                            onchange="this.form.submit()">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    </div>
                </div>

                <!-- Kategoria -->
                <div class="col-md-4">
                    <label class="form-label small">
                        <i class="bi bi-tags me-1"></i> Kategoria
                    </label>
                    <select name="category" class="form-control" onchange="this.form.submit()">
                        <option value="">Wszystkie kategorie</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>
                                {{ $category }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status -->
                <div class="col-md-4">
                    <label class="form-label small">
                        <i class="bi bi-check-circle me-1"></i> Status
                    </label>
                    <select name="status" class="form-control" onchange="this.form.submit()">
                        <option value="">Wszystkie</option>
                        <option value="low_stock" {{ request('status') === 'low_stock' ? 'selected' : '' }}>Niski stan</option>
                        <option value="ok" {{ request('status') === 'ok' ? 'selected' : '' }}>OK</option>
                    </select>
                </div>
            </div>
        </form>

        @if(request('search') || request('category') || request('status'))
            <div class="mt-3 pt-3 border-top">
                <a href="{{ route('equipment.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                </a>
            </div>
        @endif
    </x-ui.card>

    <x-ui.card>
                @if($equipment->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Nazwa</th>
                                    <th>Kategoria</th>
                                    <th>W magazynie</th>
                                    <th>Dostępne</th>
                                    <th>Min. ilość</th>
                                    <th>Status</th>
                                    <th>Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($equipment as $item)
                                    <tr>
                                        <td class="fw-medium">{{ $item->name }}</td>
                                        <td>{{ $item->category ?? '-' }}</td>
                                        <td>{{ $item->quantity_in_stock }} {{ $item->unit }}</td>
                                        <td>{{ $item->available_quantity }} {{ $item->unit }}</td>
                                        <td>{{ $item->min_quantity }} {{ $item->unit }}</td>
                                        <td>
                                            @if($item->isLowStock())
                                                <x-ui.badge variant="danger">Niski stan</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="success">OK</x-ui.badge>
                                            @endif
                                        </td>
                                        <td>
                                            <x-action-buttons
                                                viewRoute="{{ route('equipment.show', $item) }}"
                                                editRoute="{{ route('equipment.edit', $item) }}"
                                                deleteRoute="{{ route('equipment.destroy', $item) }}"
                                                deleteMessage="Czy na pewno chcesz usunąć ten sprzęt?"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($equipment->hasPages())
                        <div class="mt-3">
                            <x-ui.pagination :paginator="$equipment" />
                        </div>
                    @endif
                @else
                    <x-ui.empty-state 
                        icon="inbox" 
                        :message="request('search') || request('category') || request('status') ? 'Brak sprzętu spełniającego kryteria wyszukiwania' : 'Brak sprzętu w systemie.'"
                    >
                        @if(!request('search') && !request('category') && !request('status'))
                            <x-ui.button 
                                variant="primary" 
                                href="{{ route('equipment.create') }}"
                                routeName="equipment.create"
                                action="create"
                            >
                                Dodaj pierwszy sprzęt
                            </x-ui.button>
                        @else
                            <a href="{{ route('equipment.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                            </a>
                        @endif
                    </x-ui.empty-state>
                @endif
    </x-ui.card>
</x-app-layout>
