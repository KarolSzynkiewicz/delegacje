<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">Pracownik</h2>
            <x-ui.button variant="ghost" href="{{ route('employees.index') }}">Wróć do listy</x-ui.button>
        </div>
    </x-slot>

    <div class="container-xxl">
        @if(session('success'))
            <x-ui.alert variant="success" title="Sukces" dismissible class="mb-3">
                {{ session('success') }}
            </x-ui.alert>
        @endif

        @if(session('error'))
            <x-ui.alert variant="danger" title="Błąd" dismissible class="mb-3">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        <div class="row">
            <div class="col-md-12">
                <livewire:employee-tabs :employee="$employee" />
            </div>
        </div>
    </div>
</x-app-layout>
