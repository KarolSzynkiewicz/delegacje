<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Biuro — profile">
            <x-slot name="right">
                <x-ui.button variant="ghost" href="{{ route('office-hours.index') }}" class="btn-sm">
                    Godziny biurowe
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <livewire:office-profiles-table />
</x-app-layout>
