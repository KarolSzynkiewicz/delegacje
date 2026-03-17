<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dodaj Nową Akomodację">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('accommodations.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Dodaj Nową Akomodację">
                <x-ui.errors />

                <form action="{{ route('accommodations.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    @livewire('accommodation-form')

                    <x-ui.image-preview />

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Dodaj Akomodację
                        </x-ui.button>
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('accommodations.index') }}"
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
