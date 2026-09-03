<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Role Użytkowników">
            <x-slot name="right">
                <x-ui.button
                    variant="primary"
                    href="{{ route('user-roles.create') }}"
                    routeName="user-roles.create"
                    action="create"
                >
                    Dodaj Rolę
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

    <livewire:user-roles-table />
</x-app-layout>
