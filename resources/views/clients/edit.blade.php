<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj kontrahenta: {{ $client->name }}">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('clients.show', $client) }}" action="back">
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edycja kontrahenta">
                <form action="{{ route('clients.update', $client) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('clients._form', ['client' => $client])
                    <div class="d-flex justify-content-end gap-2">
                        <x-ui.button variant="ghost" href="{{ route('clients.show', $client) }}" action="cancel">Anuluj</x-ui.button>
                        <x-ui.button variant="primary" type="submit" action="save">Zapisz</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
