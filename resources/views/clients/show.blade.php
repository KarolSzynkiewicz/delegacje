<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ $client->type?->shortLabel() ?? 'Kontrahent' }}: {{ $client->name }}">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('clients.index') }}" action="back">
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button variant="ghost" href="{{ route('clients.edit', $client) }}" routeName="clients.edit" action="edit">
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-ui.alert variant="success" dismissible class="mb-3">{{ session('success') }}</x-ui.alert>
    @endif
    @if(session('error'))
        <x-ui.alert variant="danger" dismissible class="mb-3">{{ session('error') }}</x-ui.alert>
    @endif

    <livewire:client-tabs :client="$client" />
</x-app-layout>
