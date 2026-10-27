<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Nowy profil biurowy">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('office-profiles.index') }}" action="back">
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Profil biurowy">
                <x-ui.errors />

                @if($users->isEmpty())
                    <x-ui.alert variant="warning">
                        Wszyscy użytkownicy mają już profil biurowy albo nie ma użytkowników do przypisania.
                    </x-ui.alert>
                @else
                    <form method="POST" action="{{ route('office-profiles.store') }}">
                        @csrf
                        @include('office-profiles._form', ['profile' => null, 'users' => $users])
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <x-ui.button variant="ghost" href="{{ route('office-profiles.index') }}">Anuluj</x-ui.button>
                            <x-ui.button variant="primary" type="submit">Zapisz</x-ui.button>
                        </div>
                    </form>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
