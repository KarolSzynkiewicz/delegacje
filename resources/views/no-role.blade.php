<x-app-layout>
    
    <x-slot name="header">
        <x-ui.page-header title="Brak przypisanej roli">

        </x-ui.page-header>
    </x-slot>
  
        <x-ui.card variant="elevated" label="Dostęp do systemu">
            <div class="space-y-4">
                
                <x-ui.alert
                    variant="warning"
                    title="Brak uprawnień"
                    icon
                >
                    Twoje konto nie ma obecnie przypisanej roli w systemie, dlatego dostęp do funkcji aplikacji jest zablokowany.
                </x-ui.alert>

                <div class="text-muted">
                    Aby kontynuować pracę:
                    <ul class="list-disc list-inside mt-2">
                        <li>skontaktuj się z administratorem systemu</li>
                        <li>poczekaj na przypisanie odpowiedniej roli</li>
                        <li>po nadaniu roli odśwież stronę lub zaloguj się ponownie</li>
                    </ul>
                </div>

                <div class="flex gap-2 pt-2">
                    <x-ui.button
                        variant="primary"
                        action="view"
                        :href="route('profile.edit')"
                    >
                        Przejdź do profilu
                    </x-ui.button>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.button
                            variant="ghost"
                            action="back"
                            type="submit"
                        >
                            Wyloguj się
                        </x-ui.button>
                    </form>
                </div>

            </div>
        </x-ui.card>

</x-app-layout>