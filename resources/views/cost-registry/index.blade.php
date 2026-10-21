<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Rejestr kosztów">
            <x-slot name="right">
                <div class="d-flex flex-wrap gap-2">
                    <x-ui.button
                        variant="primary"
                        href="{{ route('cost-registry.create-many') }}"
                        routeName="cost-registry.create-many"
                        action="create"
                    >
                        Dodaj wiele
                    </x-ui.button>
                    <x-ui.button
                        variant="ghost"
                        href="{{ route('fixed-cost-entries.create') }}"
                        routeName="fixed-cost-entries.create"
                        action="create"
                    >
                        Koszt ogólnofirmowy
                    </x-ui.button>
                    <x-ui.button
                        variant="ghost"
                        href="{{ route('project-variable-costs.create') }}"
                        routeName="project-variable-costs.create"
                        action="create"
                    >
                        Koszt projektowy
                    </x-ui.button>
                    <x-ui.button
                        variant="ghost"
                        href="{{ route('transport-costs.create') }}"
                        routeName="transport-costs.create"
                        action="create"
                    >
                        Koszt transportu
                    </x-ui.button>
                </div>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if (session('success'))
        <x-ui.alert variant="success" dismissible class="mb-3">{{ session('success') }}</x-ui.alert>
    @endif

    <livewire:cost-registry-table />
</x-app-layout>
