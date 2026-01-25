<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Koszt Księgowy">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('fixed-costs.tab.entries') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card>
        <x-ui.detail-list>
            <x-ui.detail-item label="Nazwa:">{{ $entry->name }}</x-ui.detail-item>
            <x-ui.detail-item label="Kwota:">
                <strong>{{ number_format($entry->amount, 2) }} {{ $entry->currency }}</strong>
            </x-ui.detail-item>
            <x-ui.detail-item label="Okres:">
                {{ $entry->period_start->format('Y-m-d') }} - {{ $entry->period_end->format('Y-m-d') }}
            </x-ui.detail-item>
            <x-ui.detail-item label="Data księgowania:">{{ $entry->accounting_date->format('Y-m-d') }}</x-ui.detail-item>
            <x-ui.detail-item label="Szablon:">
                @if($entry->template)
                    <a href="{{ route('fixed-costs.show', $entry->template) }}" class="text-decoration-none">
                        {{ $entry->template->name }}
                    </a>
                @else
                    <span class="text-muted">Brak szablonu</span>
                @endif
            </x-ui.detail-item>
            @if($entry->notes)
                <x-ui.detail-item label="Notatki:" :full-width="true">{{ $entry->notes }}</x-ui.detail-item>
            @endif
        </x-ui.detail-list>
    </x-ui.card>
</x-app-layout>
