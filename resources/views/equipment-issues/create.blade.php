<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Zlecenie wydania">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('equipment.tab.issues') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div style="background:var(--bg-card);border:1px solid var(--glass-border);border-radius:20px;padding:1.25rem;">
        <livewire:warehouse-issue-form :warehouse="$warehouse" />
    </div>
</x-app-layout>
