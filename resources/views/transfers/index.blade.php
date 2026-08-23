<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Transfery">
            <x-slot name="right">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('transfers.create') }}"
                    routeName="transfers.create"
                    action="create"
                >
                    <i class="bi bi-plus-lg me-1"></i> Utwórz transfer
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-alert type="success" dismissible icon="check-circle">{{ session('success') }}</x-alert>
    @endif

    <livewire:transfers-table />
</x-app-layout>
