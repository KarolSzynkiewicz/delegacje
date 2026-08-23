<x-guest-layout>
    <x-landing.auth-shell cta-label="">
        <x-slot:eyebrow>Weryfikacja</x-slot>
        <x-slot:title>
            Potwierdź adres e-mail,<br>
            <em>żeby wejść do systemu.</em>
        </x-slot>
        <x-slot:subtitle>
            Wysłaliśmy link na adres podany przy rejestracji. Jeśli wiadomość nie doszła, wyślemy ją jeszcze raz.
        </x-slot>

        <x-ui.card>
            @if (session('status') == 'verification-link-sent')
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Nowy link weryfikacyjny został wysłany na Twój adres e-mail.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}" class="mb-3">
                @csrf
                <x-ui.button variant="primary" type="submit" class="w-100">
                    Wyślij link ponownie
                </x-ui.button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary w-100">Wyloguj się</button>
            </form>
        </x-ui.card>
    </x-landing.auth-shell>
</x-guest-layout>
