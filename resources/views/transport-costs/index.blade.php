<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Koszty Transportu">
            <x-slot name="right">
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('transport-costs.create') }}"
                    routeName="transport-costs.create"
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
                            <th>Data</th>
                            <th>Typ</th>
                            <th>Kwota</th>
                            <th>Zdarzenie</th>
                            <th>Opis</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($costs as $cost)
                            <tr>
                                <td>{{ $cost->cost_date->format('Y-m-d') }}</td>
                                <td>{{ ucfirst($cost->cost_type) }}</td>
                                <td class="fw-semibold">{{ number_format($cost->amount, 2) }} {{ $cost->currency }}</td>
                                <td>
                                    @if($cost->logisticsEvent)
                                        <a href="{{ route('return-trips.show', $cost->logisticsEvent) }}" class="text-decoration-none">
                                            Zdarzenie #{{ $cost->logisticsEvent->id }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $cost->description ?? '-' }}</td>
                                <td>
                                    <x-action-buttons
                                        viewRoute="{{ route('transport-costs.show', $cost) }}"
                                        editRoute="{{ route('transport-costs.edit', $cost) }}"
                                        deleteRoute="{{ route('transport-costs.destroy', $cost) }}"
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
                    <x-ui.pagination :paginator="$costs" />
                </div>
            @endif
        @else
            <x-ui.empty-state 
                icon="inbox" 
                message="Brak kosztów w systemie."
            >
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('transport-costs.create') }}"
                    routeName="transport-costs.create"
                    action="create"
                >
                    Dodaj pierwszy koszt
                </x-ui.button>
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</x-app-layout>
