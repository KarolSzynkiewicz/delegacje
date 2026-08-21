<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Nowy sprint">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('sprints.index') }}" action="back">
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Nowy sprint">
                <form method="POST" action="{{ route('sprints.store') }}" enctype="multipart/form-data">
                    @csrf
                    @include('sprints._form')
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <x-ui.button variant="ghost" href="{{ route('sprints.index') }}" action="cancel">
                            Anuluj
                        </x-ui.button>
                        <x-ui.button variant="primary" type="submit" action="save">
                            Zapisz sprint
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
