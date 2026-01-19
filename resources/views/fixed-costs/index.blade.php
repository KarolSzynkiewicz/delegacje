<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Koszty Stałe">
            <x-slot name="right">
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('fixed-costs.create') }}"
                    routeName="fixed-costs.create"
                    action="create"
                >
                    Dodaj Koszt
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card>
        @if($costs->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Nazwa</th>
                            <th>Kwota</th>
                            <th>Data kosztu</th>
                            <th>Okres</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                            <tbody>
                                @foreach ($costs as $cost)
                                    <tr>
                                        <td>{{ $cost->name }}</td>
                                        <td class="fw-semibold">{{ number_format($cost->amount, 2) }} {{ $cost->currency }}</td>
                                        <td>{{ $cost->cost_date->format('Y-m-d') }}</td>
                                        <td>
                                            {{ $cost->start_date->format('Y-m-d') }}
                                            @if($cost->end_date)
                                                - {{ $cost->end_date->format('Y-m-d') }}
                                            @else
                                                - bieżące
                                            @endif
                                        </td>
                                        <td>
                                            <x-action-buttons
                                                viewRoute="{{ route('fixed-costs.show', $cost) }}"
                                                editRoute="{{ route('fixed-costs.edit', $cost) }}"
                                                deleteRoute="{{ route('fixed-costs.destroy', $cost) }}"
                                                deleteMessage="Czy na pewno chcesz usunąć ten koszt?"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($costs->hasPages())
                        <div class="mt-3">
                            {{ $costs->links() }}
                        </div>
                    @endif
        @else
            <x-ui.empty-state 
                icon="folder-x"
                message="Brak kosztów stałych w systemie"
            >
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('fixed-costs.create') }}"
                    routeName="fixed-costs.create"
                    action="create"
                >
                    Dodaj pierwszy koszt
                </x-ui.button>
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</x-app-layout>
