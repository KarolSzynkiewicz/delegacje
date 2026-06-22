<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Spółkę: {{ $company->name }}">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('companies.index') }}" action="back">
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Spółkę">
                <x-ui.errors />

                <form action="{{ route('companies.update', $company) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('companies._form', ['company' => $company])
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <x-ui.button variant="primary" type="submit" action="save">Zapisz</x-ui.button>
                        <x-ui.button variant="ghost" href="{{ route('companies.show', $company) }}" action="cancel">Anuluj</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
