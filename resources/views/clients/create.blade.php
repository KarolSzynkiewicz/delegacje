<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dodaj kontrahenta">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('clients.index') }}" action="back">
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Nowy kontrahent CRM">
                <form action="{{ route('clients.store') }}" method="POST">
                    @csrf
                    @include('clients._form')
                    <div class="d-flex justify-content-end gap-2">
                        <x-ui.button variant="ghost" href="{{ route('clients.index') }}" action="cancel">Anuluj</x-ui.button>
                        <x-ui.button variant="primary" type="submit" action="save">Zapisz</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
