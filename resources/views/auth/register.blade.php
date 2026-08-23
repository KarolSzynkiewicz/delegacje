<x-guest-layout>
    <x-landing.auth-shell>
        <x-slot:eyebrow>Nowe konto</x-slot>
        <x-slot:title>
            Dołącz do systemu.<br>
            <em>Jeden rekord, cały cykl.</em>
        </x-slot>
        <x-slot:subtitle>
            Konto w ChronoLogic łączy rekrutację, wyjazd, kwaterę, projekt i płace — bez przepisywania danych między arkuszami.
        </x-slot>

        <x-ui.card>
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong><i class="bi bi-exclamation-triangle me-2"></i>Błąd rejestracji:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="mb-3">
                    <x-ui.input
                        type="text"
                        name="name"
                        id="name"
                        label="Imię i nazwisko"
                        value="{{ old('name') }}"
                        required="true"
                        autofocus
                        autocomplete="name"
                    />
                </div>

                <div class="mb-3">
                    <x-ui.input
                        type="email"
                        name="email"
                        id="email"
                        label="E-mail"
                        value="{{ old('email') }}"
                        required="true"
                        autocomplete="username"
                    />
                </div>

                <div class="mb-3">
                    <x-ui.input
                        type="password"
                        name="password"
                        id="password"
                        label="Hasło"
                        required="true"
                        autocomplete="new-password"
                    />
                </div>

                <div class="mb-3">
                    <x-ui.input
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        label="Powtórz hasło"
                        required="true"
                        autocomplete="new-password"
                    />
                </div>

                <x-ui.button variant="primary" type="submit" class="w-100 mb-4">
                    Utwórz konto
                </x-ui.button>

                <p class="mb-0 text-center cl-landing-auth__meta">
                    Masz już konto?
                    <a href="{{ route('login') }}" class="cl-landing-auth__link">Zaloguj się</a>
                </p>
            </form>
        </x-ui.card>
    </x-landing.auth-shell>
</x-guest-layout>
