<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header 
            title="Sprzęt"
            primaryActionLabel="Dodaj Sprzęt"
            primaryActionHref="{{ route('equipment.create') }}"
            primaryActionAction="create"
        />
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <x-ui.card>
                @if($equipment->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
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
                                                <x-ui.badge variant="danger">Niski stan</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="success">OK</x-ui.badge>
                                            @endif
                                        </td>
                                        <td>
                                            @if(auth()->user()->hasPermission('equipment.delete'))
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
                                            @endif
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
                    <x-ui.empty-state 
                        icon="inbox" 
                        message="Brak sprzętu w systemie."
                    >
                        <x-ui.button 
                            variant="primary" 
                            href="{{ route('equipment.create') }}"
                            action="create"
                        >
                            Dodaj pierwszy sprzęt
                        </x-ui.button>
                    </x-ui.empty-state>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
