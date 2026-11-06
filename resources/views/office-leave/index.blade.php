<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Urlopy biurowe">
            <x-slot name="right">
                <div class="d-flex gap-2 flex-wrap">
                    <x-ui.button variant="ghost" href="{{ route('office-hours.index') }}" class="btn-sm">
                        Godziny biurowe
                    </x-ui.button>
                    <x-ui.button variant="ghost" href="{{ route('office-profiles.index') }}" class="btn-sm">
                        Profile biurowe
                    </x-ui.button>
                </div>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card>
        <livewire:office-leave-manager />
    </x-ui.card>
</x-app-layout>
