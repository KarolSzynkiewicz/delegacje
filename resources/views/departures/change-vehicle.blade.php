<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Zmień auto wyjazdu">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('departures.show', $departure) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="container-fluid" style="max-width: 880px;">
        <livewire:departure-vehicle-swap
            :departure-id="$departure->id"
            :key="'vehicle-swap-'.$departure->id"
        />
    </div>
</x-app-layout>
