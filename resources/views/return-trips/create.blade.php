<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Utwórz Zjazd">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('return-trips.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @livewire('return-trip-planner')
</x-app-layout>
