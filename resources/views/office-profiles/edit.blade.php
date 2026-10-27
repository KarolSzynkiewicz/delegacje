<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edycja profilu: {{ $profile->user?->name }}">
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

                <form method="POST" action="{{ route('office-profiles.update', $profile) }}">
                    @csrf
                    @method('PUT')
                    @include('office-profiles._form', ['profile' => $profile, 'users' => collect()])
                    <div class="d-flex justify-content-between gap-2 mt-4 flex-wrap">
                        <x-ui.button
                            variant="danger"
                            type="submit"
                            form="office-profile-delete"
                            onclick="return confirm('Usunąć profil biurowy? Godziny też znikną.')"
                        >Usuń</x-ui.button>
                        <div class="d-flex gap-2">
                            <x-ui.button variant="ghost" href="{{ route('office-profiles.index') }}">Anuluj</x-ui.button>
                            <x-ui.button variant="primary" type="submit">Zapisz</x-ui.button>
                        </div>
                    </div>
                </form>

                <form id="office-profile-delete" method="POST" action="{{ route('office-profiles.destroy', $profile) }}" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
