<x-guest-layout>
    <x-landing.auth-shell cta-label="">
        <x-slot:eyebrow>Strefa chroniona</x-slot>
        <x-slot:title>
            Potwierdź hasło,<br>
            <em>zanim pójdziesz dalej.</em>
        </x-slot>
        <x-slot:subtitle>
            Ta część systemu wymaga ponownego potwierdzenia tożsamości.
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

            <form method="POST" action="{{ route('password.confirm') }}">
                @csrf

                <div class="mb-4">
                    <x-ui.input
                        type="password"
                        name="password"
                        id="password"
                        label="Hasło"
                        required="true"
                        autofocus
                        autocomplete="current-password"
                    />
                </div>

                <x-ui.button variant="primary" type="submit" class="w-100">
                    Potwierdź
                </x-ui.button>
            </form>
        </x-ui.card>
    </x-landing.auth-shell>
</x-guest-layout>
