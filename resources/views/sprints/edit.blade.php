<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj sprint: {{ $sprint->name }}">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('sprints.show', $sprint) }}" action="back">
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj sprint">
                <form method="POST" action="{{ route('sprints.update', $sprint) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @include('sprints._form', ['sprint' => $sprint])
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <x-ui.button variant="ghost" href="{{ route('sprints.show', $sprint) }}" action="cancel">
                            Anuluj
                        </x-ui.button>
                        <x-ui.button variant="primary" type="submit" action="save">
                            Zapisz zmiany
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
