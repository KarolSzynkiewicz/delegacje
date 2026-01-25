<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Szablon Kosztu Stałego">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('fixed-costs.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('fixed-costs.edit', $fixedCost) }}"
                    routeName="fixed-costs.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card>
        <x-ui.detail-list>
            <x-ui.detail-item label="Nazwa:">{{ $fixedCost->name }}</x-ui.detail-item>
            <x-ui.detail-item label="Kwota:">
                <strong>{{ number_format($fixedCost->amount, 2) }} {{ $fixedCost->currency }}</strong>
            </x-ui.detail-item>
            <x-ui.detail-item label="Typ interwału:">
                @if($fixedCost->interval_type === 'monthly')
                    Miesięczny
                @elseif($fixedCost->interval_type === 'weekly')
                    Tygodniowy
                @else
                    Roczny
                @endif
            </x-ui.detail-item>
            <x-ui.detail-item label="Dzień interwału:">{{ $fixedCost->interval_day }}</x-ui.detail-item>
            <x-ui.detail-item label="Data rozpoczęcia obowiązywania:">
                {{ $fixedCost->start_date ? $fixedCost->start_date->format('Y-m-d') : 'Od zawsze' }}
            </x-ui.detail-item>
            <x-ui.detail-item label="Data zakończenia obowiązywania:">
                {{ $fixedCost->end_date ? $fixedCost->end_date->format('Y-m-d') : 'Bieżące' }}
            </x-ui.detail-item>
            <x-ui.detail-item label="Status:">
                @if($fixedCost->is_active)
                    <x-ui.badge variant="success">Aktywny</x-ui.badge>
                @else
                    <x-ui.badge variant="secondary">Nieaktywny</x-ui.badge>
                @endif
            </x-ui.detail-item>
            @if($fixedCost->notes)
                <x-ui.detail-item label="Notatki:" :full-width="true">{{ $fixedCost->notes }}</x-ui.detail-item>
            @endif
        </x-ui.detail-list>
    </x-ui.card>

    <x-ui.card label="Wygenerowane Koszty" class="mt-4">
        @if($entries->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Okres</th>
                            <th>Data księgowania</th>
                            <th>Kwota</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr>
                                <td>
                                    {{ $entry->period_start->format('Y-m-d') }} - {{ $entry->period_end->format('Y-m-d') }}
                                </td>
                                <td>{{ $entry->accounting_date->format('Y-m-d') }}</td>
                                <td class="fw-semibold">{{ number_format($entry->amount, 2) }} {{ $entry->currency }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($entries->hasPages())
                <div class="mt-3">
                    {{ $entries->links() }}
                </div>
            @endif
        @else
            <x-ui.empty-state 
                icon="folder-x"
                message="Brak wygenerowanych kosztów dla tego szablonu"
            />
        @endif
    </x-ui.card>
</x-app-layout>
