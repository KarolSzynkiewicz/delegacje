<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dodaj kurs walut">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('exchange-rates.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Nowy kurs wymiany">
                <form method="POST" action="{{ route('exchange-rates.store') }}">
                    @csrf

                    @include('exchange-rates._form')

                    <div class="d-flex justify-content-end align-items-center gap-2 mt-4">
                        <x-ui.button
                            variant="ghost"
                            href="{{ route('exchange-rates.index') }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                        <x-ui.button
                            variant="primary"
                            type="submit"
                            action="save"
                        >
                            Zapisz
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
