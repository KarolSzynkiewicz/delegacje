<x-guest-layout>
    <x-landing.auth-shell>
        <x-slot:eyebrow>Nowe hasło</x-slot>
        <x-slot:title>
            Ustaw hasło<br>
            <em>i wróć do systemu.</em>
        </x-slot>
        <x-slot:subtitle>
            Wpisz adres e-mail konta oraz dwukrotnie nowe hasło.
        </x-slot>

        <x-ui.card>
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('password.store') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="mb-3">
                    <x-ui.input
                        type="email"
                        name="email"
                        id="email"
                        label="E-mail"
                        value="{{ old('email', $request->email) }}"
                        required="true"
                        autofocus
                        autocomplete="username"
                    />
                </div>

                <div class="mb-3">
                    <x-ui.input
                        type="password"
                        name="password"
                        id="password"
                        label="Nowe hasło"
                        required="true"
                        autocomplete="new-password"
                    />
                </div>

                <div class="mb-4">
                    <x-ui.input
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        label="Powtórz hasło"
                        required="true"
                        autocomplete="new-password"
                    />
                </div>

                <x-ui.button variant="primary" type="submit" class="w-100">
                    Zapisz hasło
                </x-ui.button>
            </form>
        </x-ui.card>
    </x-landing.auth-shell>
</x-guest-layout>
