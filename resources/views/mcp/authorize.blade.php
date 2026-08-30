<x-guest-layout>
    <x-landing.auth-shell cta-label="">
        <x-slot:eyebrow>Połączenie AI</x-slot>
        <x-slot:title>
            Zezwól asystentowi<br>
            <em>na dostęp do ChronoLogic.</em>
        </x-slot>
        <x-slot:subtitle>
            {{ $client->name }} prosi o dostęp do zadań i sprintów na Twoim koncie.
            Zgoda działa dopóki jej nie cofniesz.
        </x-slot>

        <x-ui.card>
            <p class="text-secondary small mb-3 mb-md-4">
                Zalogowano jako <strong>{{ $user->email }}</strong>
            </p>

            @if (count($scopes) > 0)
                <p class="fw-semibold mb-2">Ta aplikacja będzie mogła:</p>
                <ul class="mb-4">
                    @foreach ($scopes as $scope)
                        <li>{{ $scope->description }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="d-flex flex-column flex-sm-row gap-2">
                <form method="POST" action="{{ route('passport.authorizations.deny') }}" class="flex-fill">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="state" value="{{ $request->state }}">
                    <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                    <input type="hidden" name="auth_token" value="{{ $authToken }}">
                    <x-ui.button variant="ghost" type="submit" class="w-100">
                        Odrzuć
                    </x-ui.button>
                </form>

                <form method="POST" action="{{ route('passport.authorizations.approve') }}" class="flex-fill">
                    @csrf
                    <input type="hidden" name="state" value="{{ $request->state }}">
                    <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                    <input type="hidden" name="auth_token" value="{{ $authToken }}">
                    <x-ui.button variant="primary" type="submit" class="w-100">
                        Zezwól
                    </x-ui.button>
                </form>
            </div>
        </x-ui.card>
    </x-landing.auth-shell>
</x-guest-layout>
