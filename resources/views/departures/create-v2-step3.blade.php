<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Utwórz Wyjazd V2 - Krok 3: Pojazdy">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('departures.create-v2-step2') }}"
                    action="back"
                >
                    ← Wróć do kroku 2
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="container-fluid">
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

        <livewire:departure-vehicle-planner />
    </div>
</x-app-layout>
