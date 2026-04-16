<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Utwórz wyjazd">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('departures.index') }}"
                    action="back"
                >
                    Powrót
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

        @if($errors->any())
            <x-ui.alert variant="danger" title="Błąd walidacji" dismissible class="mb-3">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <livewire:departure-planner-v2 
            :departureDate="old('departure_date', request('departure_date'))"
            :endDate="old('end_date', request('end_date'))"
            :vehicleId="old('vehicle_id', request('vehicle_id'))"
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
