<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dodaj Dokument">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('documents.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Dodaj Nowy Dokument">
                <x-ui.errors />

                <form action="{{ route('documents.store') }}" method="POST">
                    @csrf

                    @include('documents.partials.fields')

                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('documents.index') }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Dodaj Dokument
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
