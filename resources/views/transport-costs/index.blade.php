<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Koszty Transportu</h2>
            <x-ui.button variant="primary" href="{{ route('transport-costs.create') }}">
                <i class="bi bi-plus-circle"></i> Dodaj Koszt
            </x-ui.button>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    @if($costs->count() > 0)
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th class="text-start">Data</th>
                                        <th class="text-start">Typ</th>
                                        <th class="text-start">Kwota</th>
                                        <th class="text-start">Zdarzenie</th>
                                        <th class="text-start">Opis</th>
                                        <th class="text-start">Akcje</th>
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
                                                @if(auth()->user()->hasPermission('transport-costs.delete'))
                                                    <x-action-buttons
                                                        viewRoute="{{ route('transport-costs.show', $cost) }}"
                                                        editRoute="{{ route('transport-costs.edit', $cost) }}"
                                                        deleteRoute="{{ route('transport-costs.destroy', $cost) }}"
                                                        deleteMessage="Czy na pewno chcesz usunąć ten koszt?"
                                                    />
                                                @else
                                                    <x-action-buttons
                                                        viewRoute="{{ route('transport-costs.show', $cost) }}"
                                                        editRoute="{{ route('transport-costs.edit', $cost) }}"
                                                    />
                                                @endif
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
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                            <p class="text-muted mb-3">Brak kosztów w systemie.</p>
                            <x-ui.button variant="primary" href="{{ route('transport-costs.create') }}">
                                <i class="bi bi-plus-circle"></i> Dodaj pierwszy koszt
                            </x-ui.button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
