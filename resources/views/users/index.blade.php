<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Użytkownicy">
            <x-slot name="right">
                <x-ui.button
                    variant="primary"
                    href="{{ route('users.create') }}"
                    routeName="users.create"
                    action="create"
                >
                    Dodaj Użytkownika
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if (session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif

    @if (session('error'))
        <x-alert type="danger" dismissible icon="exclamation-triangle">
            {{ session('error') }}
        </x-alert>
    @endif

    <livewire:users-table />
</x-app-layout>
