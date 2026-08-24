<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Kalendarz">
            <x-slot name="right">
                <x-ui.button href="{{ route('weekly-overview.index') }}" variant="ghost">
                    <i class="bi bi-calendar-week"></i> Przegląd tygodniowy
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <livewire:resource-calendar />
</x-app-layout>
