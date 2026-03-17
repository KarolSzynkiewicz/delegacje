<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Lokalizację">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('locations.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Lokalizację">
                <form method="POST" action="{{ route('locations.update', $location) }}">
                    @csrf
                    @method('PUT')

                    @livewire('location-form', ['location' => $location])

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Zapisz
                        </x-ui.button>
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('locations.index') }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
