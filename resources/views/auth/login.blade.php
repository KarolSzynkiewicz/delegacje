<x-guest-layout>
    <x-landing.auth-shell cta-label="">
        <x-slot:eyebrow>Wejście do systemu</x-slot>
        <x-slot:title>
            Rotacje pod kontrolą,<br>
            <em>nie w Excelu.</em>
        </x-slot>
        <x-slot:subtitle>
            Zaloguj się, żeby prowadzić pracowników od wyjazdu przez kwaterę i projekt aż po wypłatę — w jednym miejscu.
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
                    <strong><i class="bi bi-exclamation-triangle me-2"></i>Błąd logowania:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <x-ui.input
                        type="email"
                        name="email"
                        id="email"
                        label="E-mail"
                        value="{{ old('email') }}"
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
                        label="Hasło"
                        required="true"
                        autocomplete="current-password"
                    />
                </div>

                <div class="form-check d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <x-ui.input
                            type="checkbox"
                            name="remember"
                            id="remember_me"
                            label="Zapamiętaj mnie"
                        />
                    </div>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="cl-landing-auth__link">
                            Nie pamiętasz hasła?
                        </a>
                    @endif
                </div>

                <x-ui.button variant="primary" type="submit" class="w-100 mb-4">
                    Zaloguj się
                </x-ui.button>

                @if (Route::has('register'))
                    <p class="mb-0 text-center cl-landing-auth__meta">
                        Nie masz konta?
                        <a href="{{ route('register') }}" class="cl-landing-auth__link">Zarejestruj się</a>
                    </p>
                @endif
            </form>
        </x-ui.card>
    </x-landing.auth-shell>
</x-guest-layout>
