<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Utwórz Wyjazd V2 (Nowy Formularz)">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('departures.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('departures.create') }}"
                    class="ms-2"
                >
                    Stary formularz
                </x-ui.button>
            </x-slot>
            
            @if(session('departure_draft_v2'))
                <x-slot name="right">
                    <div class="alert alert-info d-flex align-items-center gap-2 mb-0 py-2 px-3" style="font-size: 0.875rem;">
                        <i class="bi bi-info-circle-fill text-primary"></i>
                        <span>Zapisana wersja robocza z poprzedniej sesji</span>
                        <form method="POST" action="{{ route('departures.clear-draft-v2') }}" class="d-inline ms-2">
                            @csrf
                            <button 
                                type="submit" 
                                class="btn btn-sm btn-outline-danger"
                            >
                                <i class="bi bi-x-circle"></i> Wyczyść
                            </button>
                        </form>
                    </div>
                </x-slot>
            @endif
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

        <livewire:departure-planner-v2 
            :departureDate="old('departure_date', date('Y-m-d'))"
            :endDate="old('end_date')"
            :vehicleId="old('vehicle_id')"
        />
        
        <!-- Modal: Employee Assignment Calendar (outside Livewire component to avoid nesting issues) -->
        <livewire:employee-assignment-modal 
            wire:key="employee-assignment-modal"
        />
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    @endpush
</x-app-layout>
