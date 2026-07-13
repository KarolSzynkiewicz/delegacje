<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Kursy walut">
            <x-slot name="right">
                <x-ui.button
                    variant="primary"
                    href="{{ route('exchange-rates.create') }}"
                    routeName="exchange-rates.create"
                    action="create"
                >
                    Dodaj kurs
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card>
        <p class="small text-muted">
            Kursy używane są WYŁĄCZNIE do prezentacji — do orientacyjnego przeliczenia sum w raportach kontrolingowych
            (np. na stronie <a href="{{ route('profitability.index') }}" class="text-decoration-none">Rentowność</a>),
            gdy w grę wchodzi więcej niż jedna waluta. Nigdy nie zmieniają kwot źródłowych zapisanych w systemie.
            Konwencja: 1 jednostka waluty bazowej = <em>kurs</em> jednostek waluty docelowej (np. EUR → PLN, kurs 4.30 = 1 EUR to 4.30 PLN).
        </p>

        @if($rates->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle table-sm">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Z waluty</th>
                            <th>Na walutę</th>
                            <th class="text-end">Kurs</th>
                            <th>Źródło</th>
                            <th>Notatki</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rates as $rate)
                            <tr>
                                <td>{{ $rate->rate_date?->format('d.m.Y') }}</td>
                                <td><span class="badge text-bg-secondary">{{ $rate->base_currency }}</span></td>
                                <td><span class="badge text-bg-secondary">{{ $rate->quote_currency }}</span></td>
                                <td class="text-end fw-semibold">{{ number_format((float) $rate->rate, 6, ',', ' ') }}</td>
                                <td>{{ $rate->source ?? '—' }}</td>
                                <td class="small text-muted">{{ $rate->notes ?? '—' }}</td>
                                <td>
                                    <x-action-buttons
                                        editRoute="{{ route('exchange-rates.edit', $rate) }}"
                                        deleteRoute="{{ route('exchange-rates.destroy', $rate) }}"
                                        deleteMessage="Czy na pewno chcesz usunąć ten kurs?"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($rates->hasPages())
                <div class="mt-3">
                    <x-ui.pagination :paginator="$rates" />
                </div>
            @endif
        @else
            <x-ui.empty-state
                icon="currency-exchange"
                message="Brak zdefiniowanych kursów walut"
            >
                <x-ui.button
                    variant="primary"
                    href="{{ route('exchange-rates.create') }}"
                    routeName="exchange-rates.create"
                    action="create"
                >
                    Dodaj pierwszy kurs
                </x-ui.button>
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</x-app-layout>
