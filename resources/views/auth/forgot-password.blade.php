<x-guest-layout>
    <x-landing.auth-shell>
        <x-slot:eyebrow>Odzyskiwanie dostępu</x-slot>
        <x-slot:title>
            Nie pamiętasz hasła?<br>
            <em>Wyślemy link.</em>
        </x-slot>
        <x-slot:subtitle>
            Podaj adres e-mail konta. Jeśli istnieje w systemie, wyślemy na niego link do ustawienia nowego hasła.
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
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="mb-4">
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

                <x-ui.button variant="primary" type="submit" class="w-100 mb-4">
                    Wyślij link resetujący
                </x-ui.button>

                <p class="mb-0 text-center cl-landing-auth__meta">
                    <a href="{{ route('login') }}" class="cl-landing-auth__link">Wróć do logowania</a>
                </p>
            </form>
        </x-ui.card>
    </x-landing.auth-shell>
</x-guest-layout>
