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
                            routeName="equipment.create"
                            action="create"
                        >
                            Dodaj pierwszy sprzęt
                        </x-ui.button>
                    </x-ui.empty-state>
                @endif
    </x-ui.card>
</x-app-layout>
