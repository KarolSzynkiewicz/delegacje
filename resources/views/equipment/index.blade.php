<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Sprzęt</h2>
            <a href="{{ route('equipment.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Dodaj Sprzęt
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    @if($equipment->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start">Nazwa</th>
                                        <th class="text-start">Kategoria</th>
                                        <th class="text-start">W magazynie</th>
                                        <th class="text-start">Dostępne</th>
                                        <th class="text-start">Min. ilość</th>
                                        <th class="text-start">Status</th>
                                        <th class="text-start">Akcje</th>
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
                                                    <span class="badge bg-danger">Niski stan</span>
                                                @else
                                                    <span class="badge bg-success">OK</span>
                                                @endif
                                            </td>
                                            <td>
                                                @can('delete', $item)
                                                    <x-action-buttons
                                                        viewRoute="{{ route('equipment.show', $item) }}"
                                                        editRoute="{{ route('equipment.edit', $item) }}"
                                                        deleteRoute="{{ route('equipment.destroy', $item) }}"
                                                        deleteMessage="Czy na pewno chcesz usunąć ten sprzęt?"
                                                    />
                                                @else
                                                    <x-action-buttons
                                                        viewRoute="{{ route('equipment.show', $item) }}"
                                                        editRoute="{{ route('equipment.edit', $item) }}"
                                                    />
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($equipment->hasPages())
                            <div class="mt-3">
                                {{ $equipment->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                            <p class="text-muted mb-3">Brak sprzętu w systemie.</p>
                            <a href="{{ route('equipment.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Dodaj pierwszy sprzęt
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
